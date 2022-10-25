<template>
  <div class="subscription">
    <el-form :model="subscription" @submit.native.prevent="submit" ref="form">
      <div class="subscription-switch">
        <el-switch
          class="subscription-switch__switch"
          v-model="subscription.planType"
          :width="50"
          active-text="Billed Annually"
          inactive-text="Billed Monthly"
          active-color="rgb(155,89,182)"
          inactive-color="rgb(155,89,182)"
          active-value="yearly"
          inactive-value="monthly"
        >
        </el-switch>
      </div>
      <div v-if="subscription.planType === 'monthly'">
        <h3 class="tw-my-2">AdPuzl - Monthly Plan</h3>
        <span class="text-plan-price discounted-price"
          >${{ planRates["1"].price }}/month</span
        >
      </div>
      <div v-if="subscription.planType === 'yearly'">
        <h3 class="tw-my-2">AdPuzl - Annual Plan (25%&nbsp;Off)</h3>
        <span class="text-plan-price compare-at-price"
          >${{ planRates["1"].price }}/{{ planRates["1"].per }}</span
        >
        <span class="text-plan-price discounted-price"
          >${{ planRates["2"].price }}/{{ planRates["2"].per }}</span
        >
        <!-- <p>
          Use code
          <em class="highlight"
            ><span style="font-weight: 500">WELCOME25</span></em
          >
          at checkout
        </p> -->
      </div>
      <a
        href="#"
        class="activation-code-link tw-my-4"
        @click.prevent="isRedemptionVisible = !isRedemptionVisible"
        >I have a promo code</a
      >
      <div class="tw-mx-auto" style="max-width: 20em">
        <el-form-item
          prop="code"
          :error="serverMessage && serverMessage.message"
          v-if="isRedemptionVisible"
        >
          <el-input
            placeholder="Enter Code"
            v-model.trim="subscription.code"
            :disabled="isSubmitting"
          >
          </el-input
        ></el-form-item>
        <el-button
          class="tw-mx-auto tw-mb-4"
          native-type="submit"
          type="info"
          style="width: 100%"
        >
          {{ isTrialEligible ? "ACTIVATE ACCOUNT" : "SUBSCRIBE" }}
        </el-button>
      </div>
    </el-form>
  </div>
</template>

<script>
import { mapActions, mapMutations, mapState, mapGetters } from "vuex";
export default {
  data: () => ({
    isRedemptionVisible: false,
    isSubmitting: false,
    subscription: {
      planType: "yearly",
      code: null,
    },
  }),
  computed: {
    ...mapState("auth", {
      planRates() {
        return this.$store.state.adpuzl.planRates;
      },
      serverMessage: (state) => state.serverMessage,
    }),
    ...mapGetters("auth", ["isTrialEligible"]),
  },
  methods: {
    ...mapActions("auth", ["redeemCode", "getUser"]),
    ...mapMutations("auth", ["SET_SERVER_MESSAGE"]),
    changeHandler(data) {
      this.$emit("update:plan-type", data);
    },
    async submit() {
      this.SET_SERVER_MESSAGE(null);
      if (!this.subscription.code || !this.isRedemptionVisible) {
        this.handleCheckout();
        return;
      }
      this.isSubmitting = true;
      const res = await this.redeemCode({
        code: this.subscription.code,
        interval: this.subscription.planType,
      });
      this.isSubmitting = false;
      if (res.data.type === "stripe") {
        this.handleCheckout(res.data.checkout_session_id);
      }
    },
    async handleCheckout(session_id) {
      this.$emit("payment-window-open");
      await this.$store.dispatch("openStripeCheckoutWindow", {
        planType: this.subscription.planType,
        session_id,
      });

      await this.getUser();
      this.$emit("payment-window-close");
    },
  },
};
</script>

<style scoped>
.subscription {
  font-family: var(--ff-heading);
}
.subscription-switch {
  display: flex;
  justify-content: center;
}
.subscription-switch__switch * {
  font-family: var(--ff-body);
  font-size: 1.2rem;
}
.subscription-switch__switch >>> .el-switch__label.is-active {
  font-weight: 600;
  color: black;
}

.text-plan-price {
  font-weight: 500;
}

.compare-at-price {
  text-decoration: line-through;
}

.text-plan-price.discounted-price {
  color: #9b59b6;
  font-size: 1.8rem;
}
.activation-code-link {
  display: inline-block;
  margin: 0.5rem 0;
  font-size: 1.125rem;
  text-decoration-line: underline;
  text-decoration-thickness: 1px;
  text-decoration-style: dashed;
  text-underline-offset: 2px;
}
</style>
