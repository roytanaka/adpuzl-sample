<template>
  <div class="my-account">
    <section class="page-header container">
      <h1>Account & Settings</h1>
    </section>
    <div class="account-and-settings container !tw-mb-12">
      <ApFieldsetCard class="current-plan" title="Subscription Plan">
        <!-- Free trial never started -->
        <div
          class="tw-my-4 tw-mx-auto"
          v-if="!isPremiumUser && !isTrialActive"
          style="max-width: 18em"
        >
          <ApSubscriptionSwitch
            @payment-window-open="isPaymentWindowOpen = true"
            @payment-window-close="isPaymentWindowOpen = false"
          />
        </div>
        <!-- Trial Started or Premium User -->
        <div v-else>
          <font-awesome-icon
            class="section-icon"
            :icon="['fal', 'file-invoice-dollar']"
          />
          <h2 class="h3">
            <span v-if="isPremiumUser">{{
              discountPlanName || currentPlanName
            }}</span>
            <span v-else>Free trial</span>
          </h2>
          <div v-if="isPremiumUser">
            <p>Thanks for supporting AdPuzl!</p>
            <el-button
              v-if="cardDetails"
              class="tw-mb-4"
              type="info"
              @click="handleUpdatePaymentUpdateButton"
              >MANAGE SUBSCRIPTION</el-button
            >
          </div>
          <div v-else>
            <p>
              Your trial ends
              <strong> {{ displayDateMMMDYYYY(trialEndDateDayJs) }}. </strong>
            </p>
            <div v-if="isPlanActive">
              <!-- <div class="h1 plan-price">${{ planRates[user.plan].price }}</div>
              <div class="h4">
                per {{ planRates[user.plan].per
                }}<span v-if="user.plan === 2">, billed annually</span>
              </div> -->
              <p>
                Your first payment will start on
                <strong>
                  {{ displayDateMMMDYYYY(paymentStartDateDisplay) }}.
                </strong>
              </p>
              <el-button
                class="tw-mb-4"
                type="info"
                @click="handleUpdatePaymentUpdateButton"
                >MANAGE SUBSCRIPTION</el-button
              >
            </div>
          </div>
        </div>
        <div class="tw-mb-8" v-if="cardDetails">
          <hr />
          <font-awesome-icon
            class="section-icon"
            :icon="['far', 'credit-card']"
          />
          <h2 class="h3">Payment Information</h2>
          <ApCreditCard class="tw-mx-auto tw-my-8" v-bind="cardDetails" />
          <el-button type="info" @click="handleUpdatePaymentUpdateButton"
            >UPDATE PAYMENT METHOD</el-button
          >
        </div>
      </ApFieldsetCard>
      <div class="settings">
        <section class="container setting">
          <div class="setting__header">
            <h2 class="h3">AdPuzl Login Settings</h2>
            <p>Edit your AdPuzl login email and password.</p>
          </div>
          <ApFieldsetCard
            class="setting__content"
            :icon="['far', 'envelope']"
            title="Email and Password"
          >
            <h3 class="h4 tw-mb-4">
              Login email: <span class="hyperlink">{{ user.email }}</span>
            </h3>

            <div>
              <el-button
                type="info"
                style="width: 100%"
                @click="emailDialogVisible = true"
              >
                CHANGE EMAIL
              </el-button>
            </div>
            <div>
              <el-button
                type="info"
                style="width: 100%"
                @click="passwordDialogVisible = true"
              >
                CHANGE PASSWORD
              </el-button>
            </div>
          </ApFieldsetCard>
        </section>

        <section class="container setting facebook-account">
          <div class="setting__header">
            <h2 class="h3">Facebook Settings</h2>
            <p>Connect to Facebook and view your Ad Account and Pages.</p>
          </div>
          <ApFieldsetCard
            class="setting__content"
            :icon="['fab', 'facebook']"
            title="Facebook Account"
          >
            <h3 class="h4 facebook-header tw-mb-4" v-if="isFbConnected">
              <el-image :src="user_profile_picture || placeholderImg" />
              <span class="hyperlink">{{ user_full_name }}</span>
            </h3>

            <div>
              <el-button
                v-if="isFbConnected"
                type="info"
                style="width: 100%"
                @click="handleFbDisconnect"
              >
                DISCONNECT FACEBOOK
              </el-button>
              <el-button
                v-else
                @click="$store.state.fb.scope.login"
                class="facebook-login-btn"
                style="width: 100%"
                :loading="$store.state.fb.fbStatus.working"
              >
                <font-awesome-icon :icon="['fab', 'facebook-square']" />
                Connect to Facebook
              </el-button>
            </div>
            <div v-if="isFbConnected">
              <div class="facebook-account__title" v-if="fbPages.length">
                <font-awesome-icon :icon="['fab', 'facebook']" />
                <h3>My Facebook Pages</h3>
              </div>
              <ul class="facebook-account__list custom-scroll">
                <li v-for="page in fbPages" :key="page.id">
                  {{ page.name }}
                </li>
              </ul>
              <div class="facebook-account__title" v-if="fbAdaccounts.length">
                <font-awesome-icon :icon="['fab', 'facebook']" />
                <h3>My Facebook Ad Accounts</h3>
              </div>
              <ul class="facebook-account__list custom-scroll">
                <li v-for="account in fbAdaccounts" :key="account.id">
                  {{ account.name }}
                </li>
              </ul>
            </div>
          </ApFieldsetCard>
        </section>
      </div>
    </div>
    <footer class="footer-links">
      <FooterLinks />
    </footer>

    <ApDialog :visible.sync="emailDialogVisible" @closed="handleDialogClose">
      <template v-slot:title>
        <h2 class="tw-mb-1">Change Email</h2>
        <h3 v-if="!isSubmitted">Enter new email address:</h3>
      </template>
      <template v-slot:default>
        <div v-if="isSubmitted" style="text-align: left">
          <p>
            Almost there! We’ve sent an email to confirm your new address:
            <strong>{{ changeEmailForm.email }}</strong
            >.
          </p>
          <p>
            Be sure to click the link in the email! We will continue to use your
            old address (<strong>{{ user.email }}</strong
            >) until you confirm your change.
          </p>
        </div>
        <el-form
          v-else
          class="welcome-form"
          id="changeEmailForm"
          :model="changeEmailForm"
          ref="changeEmailForm"
          :rules="formRules"
          show-message
        >
          <el-collapse-transition>
            <el-alert
              v-if="serverMessage"
              :title="serverMessage.message"
              :type="serverMessage.type"
              @close="SET_SERVER_MESSAGE(null)"
              show-icon
            />
          </el-collapse-transition>
          <el-form-item label="New Email" prop="email">
            <el-input
              ref="email"
              v-model="changeEmailForm.email"
              placeholder="New Email Address"
              type="email"
            >
              <template v-slot:prefix
                ><font-awesome-icon :icon="['far', 'envelope']"
              /></template>
            </el-input>
          </el-form-item>
        </el-form>
      </template>
      <template v-slot:footer>
        <el-button
          type="info"
          v-if="isSubmitted"
          native-type="submit"
          @click="emailDialogVisible = false"
          >OK</el-button
        >
        <div class="button-group" v-else>
          <el-button native-type="submit" @click="emailDialogVisible = false"
            >CANCEL</el-button
          >
          <el-button
            type="info"
            native-type="submit"
            @click.prevent="handleEmailChange"
            :loading="isSubmitting"
            form="changeEmailForm"
            >CHANGE EMAIL</el-button
          >
        </div>
      </template>
    </ApDialog>

    <ApDialog :visible.sync="passwordDialogVisible" @closed="handleDialogClose">
      <template v-slot:title>
        <h2 class="tw-mb-1">Change Password</h2>
        <h3 v-if="!isSubmitted">Enter new password:</h3>
      </template>
      <template v-slot:default>
        <div v-if="isSubmitted">
          <h3>🎉 Your password has been updated!</h3>
        </div>
        <el-form
          v-else
          class="welcome-form"
          id="changePasswordForm"
          :model="changePasswordForm"
          ref="changePasswordForm"
          :rules="formRules"
          show-message
        >
          <el-collapse-transition>
            <el-alert
              v-if="serverMessage"
              :title="serverMessage.message"
              :type="serverMessage.type"
              @close="SET_SERVER_MESSAGE(null)"
              show-icon
            />
          </el-collapse-transition>
          <el-form-item label="Current Password" prop="current_password">
            <el-input
              v-model="changePasswordForm.current_password"
              placeholder="Current Password"
              type="password"
            >
              <template v-slot:prefix
                ><font-awesome-icon icon="lock-alt"
              /></template>
            </el-input>
          </el-form-item>
          <el-form-item label="New Password" prop="password">
            <el-input
              v-model="changePasswordForm.password"
              placeholder="New Password"
              type="password"
            >
              <template v-slot:prefix>
                <font-awesome-layers>
                  <font-awesome-icon
                    icon="lock-alt"
                    transform="shrink-2 left-4 up-4"
                  />
                  <font-awesome-icon
                    icon="plus"
                    transform="shrink-6 right-4 down-5"
                  />
                </font-awesome-layers>
              </template>
            </el-input>
          </el-form-item>
          <el-form-item label="Confirm Password" prop="passwordCheck">
            <el-input
              v-model="changePasswordForm.passwordCheck"
              placeholder="Confirm Password"
              type="password"
            >
              <template v-slot:prefix>
                <font-awesome-layers>
                  <font-awesome-icon
                    icon="lock-alt"
                    transform="shrink-2 left-4 up-4"
                  />
                  <font-awesome-icon
                    icon="check"
                    transform="shrink-6 right-4 down-5"
                  />
                </font-awesome-layers>
              </template>
            </el-input>
          </el-form-item>
        </el-form>
      </template>
      <template v-slot:footer>
        <el-button
          type="info"
          v-if="isSubmitted"
          native-type="submit"
          @click="passwordDialogVisible = false"
          >OK</el-button
        >
        <div class="button-group" v-else>
          <el-button native-type="submit" @click="passwordDialogVisible = false"
            >CANCEL</el-button
          >
          <el-button
            type="info"
            native-type="submit"
            @click.prevent="handlePasswordChange"
            :loading="isSubmitting"
            form="changePasswordForm"
            >CHANGE PASSWORD</el-button
          >
        </div>
      </template>
    </ApDialog>
    <ApDialog
      :close-on-press-escape="false"
      :close-on-click-modal="false"
      :visible.sync="isPaymentWindowOpen"
    >
      <template v-slot:title>
        <h2 class="tw-mb-1">Activating Your Trial</h2>
      </template>
      <template v-slot:default>
        <p class="tw-mb-1">Hang on while we activate your trial</p>
        <ApSpinner />
        <el-button type="info" @click="focusPaymentWindow"
          >PAYMENT WINDOW</el-button
        >
      </template>
    </ApDialog>
  </div>
</template>

<script>
import dayjs from "dayjs";
import relativeTime from "dayjs/plugin/relativeTime";
dayjs.extend(relativeTime);
import { mapState, mapGetters, mapActions, mapMutations } from "vuex";
import ApDialog from "../components/ApDialog.vue";
import ApCreditCard from "../components/ApCreditCard.vue";
import ApFieldsetCard from "../components/ApFieldsetCard.vue";
import ApSubscriptionSwitch from "../components/ApSubscriptionSwitch.vue";
import FooterLinks from "../components/FooterLinks.vue";
import Axios from "axios";

export default {
  components: {
    ApDialog,
    ApCreditCard,
    ApFieldsetCard,
    FooterLinks,
    ApSubscriptionSwitch,
  },
  beforeRouteEnter(to, from, next) {
    if ("payment_failed" in to.query || "payment_success" in to.query) {
      return window.close();
    }
    next();
  },
  // mounted: function () {
  //   this.$nextTick(function () {
  //     let recaptchaScript = document.createElement("script");
  //     recaptchaScript.setAttribute("src", "https://js.stripe.com/v3/");
  //     document.head.appendChild(recaptchaScript);
  //   });
  // },
  data: function () {
    const passwordCheckValidator = (rule, value, callback) => {
      if (value !== this.changePasswordForm.password) {
        callback(new Error("Passwords do not match"));
      } else {
        callback();
      }
    };
    return {
      isPaymentWindowOpen: false,
      form: null,
      selectedPlan: null,
      emailDialogVisible: false,
      changeEmailForm: { email: null },
      isSubmitting: false,
      isSubmitted: false,
      passwordDialogVisible: false,
      changePasswordForm: {
        current_password: null,
        password: null,
        passwordCheck: null,
      },
      formRules: {
        email: [
          { required: true, message: "Email is required", trigger: "blur" },
          {
            type: "email",
            message: "Enter a valid email",
            trigger: "blur",
          },
        ],
        current_password: [
          {
            required: true,
            message: "Current password is required",
            trigger: "blur",
          },
        ],
        password: [
          {
            required: true,
            message: "New password is required",
            trigger: "blur",
          },
          {
            min: 8,
            message: "Password must be at least 8 characters",
            trigger: "blur",
          },
        ],
        passwordCheck: [
          {
            required: true,
            message: "Please enter the password again",
            trigger: "blur",
          },
          {
            validator: passwordCheckValidator,
            trigger: "blur",
          },
        ],
      },
      placeholderImg: require("../assets/user.svg"),
    };
  },
  computed: {
    ...mapState("auth", {
      user: (state) => state.user,
      paymentInfo: (state) => state.paymentInfo,
      serverMessage: (state) => state.serverMessage,
    }),
    ...mapState({
      fbLogout: (state) => state.fb.scope.logout,
      fbLogin: (state) => state.fb.scope.login,
      fbPages: (state) => state.ad.myfb.pages,
      fbAdaccounts: (state) => state.ad.myfb.adaccounts,
      planRates: (state) => state.adpuzl.planRates,
    }),
    ...mapGetters(["isFbConnected", "user_profile_picture", "user_full_name"]),
    ...mapGetters("auth", [
      "cardDetails",
      "isPremiumUser",
      "isTrialActive",
      "trialEndDateDayJs",
      "currentPlanName",
      "discountPlanName",
      "isTrialEligible",
      "isPlanActive",
    ]),
    trialEndDateDisplay() {
      if (!this.trialEndDateDayJs) return null;
      return this.trialEndDateDayJs.format("MMM D YYYY");
    },
    paymentStartDateDisplay() {
      if (!this.trialEndDateDayJs) return null;
      return this.trialEndDateDayJs.add(1, "day");
    },
  },
  methods: {
    ...mapActions("auth", ["changeEmail", "updatePasswordAuth"]),
    ...mapMutations("auth", ["SET_SERVER_MESSAGE"]),
    displayDateMMMDYYYY(dayjsDate) {
      if (!dayjsDate) return null;
      return dayjsDate.format("MMM D YYYY");
    },
    handleDialogClose() {
      this.isSubmitting = false;
      this.isSubmitted = false;
      this.SET_SERVER_MESSAGE(null);
      if (this.$refs.changePasswordForm)
        this.$refs.changePasswordForm.resetFields();
      if (this.$refs.changeEmailForm) this.$refs.changeEmailForm.resetFields();
    },
    handleEmailChange() {
      this.SET_SERVER_MESSAGE(null);
      this.$refs.changeEmailForm.validate(async (valid) => {
        if (valid) {
          this.isSubmitting = true;
          const res = await this.changeEmail(this.changeEmailForm);
          if (res) {
            this.isSubmitted = true;
          }
        } else {
          return false;
        }
        this.isSubmitting = false;
      });
    },
    handlePasswordChange() {
      this.SET_SERVER_MESSAGE(null);
      this.$refs.changePasswordForm.validate(async (valid) => {
        if (valid) {
          this.isSubmitting = true;
          const res = await this.updatePasswordAuth(this.changePasswordForm);
          if (res) {
            this.isSubmitted = true;
          }
        } else {
          return false;
        }
        this.isSubmitting = false;
      });
    },

    handleUpdatePaymentUpdateButton() {
      Axios.post("remote/create-customer-portal-session", {
        customer: this.paymentInfo.customer,
      }).then((result) => {
        window.location = result.data.customer_portal_url;
      });
    },
    handleFbDisconnect() {
      this.$confirm(
        "Are you sure you want to disconnect your Facebook account?",
        {
          confirmButtonText: "Disconnect",
          type: "warning",
        }
      ).then(() => {
        this.fbLogout();
      });
    },
    focusPaymentWindow() {
      this.$store.state.adpuzl.stripeWindow.focus();
    },
  },
};
</script>

<style>
.my-account {
  color: white;
}

.account-and-settings {
  margin-top: 2em;
  display: flex;
  flex-flow: column;
}

.page-header {
  margin-top: 2em;
  margin-bottom: 1em;
}

.current-plan {
  flex: 1 1 auto;
  text-align: center;
  min-width: 18em;
}
.fieldset-card.current-plan .fieldset-card__body {
  padding: 1em;
}
.section-icon {
  font-size: 2rem;
  color: var(--turquoise);
  margin-bottom: 0.2em;
}

.settings {
  color: var(--midnight-blue);
  background: white;
  flex: 1 1 auto;
  padding: 1rem;
  border-radius: 4px;
}

.setting {
  display: flex;
  flex-flow: row wrap;
  padding: 0;
  gap: 1em;
}

.setting__header {
  flex: 1 1 15em;
}
.setting__content {
  flex: 1 1 20em;
}

.setting__content button {
  margin-bottom: 1em;
}

@media (min-width: 700px) {
  .account-and-settings {
    flex-flow: row;
    align-items: flex-start;
  }
  .current-plan {
    max-width: 25em;
  }
  .settings {
    padding: 2em;
    margin-left: 2em;
  }
}

.facebook-account__title {
  display: flex;
  flex-flow: row nowrap;
  align-items: center;
  margin: 1em 0;
  border-top: var(--clouds) solid 4px;
  padding-top: 1.5em;
}

.facebook-account__title h3 {
  font-size: 1.25rem;
  font-weight: 500;
  margin-bottom: 0.125em;
}

.facebook-account__title svg {
  font-size: 2.125rem;
  margin-right: 0.25em;
  color: #3c57a4;
}

.facebook-account__list {
  list-style: none;
  padding: 0;
  margin: 0;
  max-height: 15.5em;
  overflow-y: auto;
  margin-right: -0.3em;
  padding-right: 0.3em;
  margin-bottom: 1.5em;
}

.facebook-account__list > li {
  background: var(--clouds);
  margin-bottom: 0.75em;
  padding: 0.5em 1em;
  border-radius: 4px;
}

.plan-price {
  font-size: 3.5rem;
  font-weight: 700;
  margin: 0;
}

.hyperlink {
  color: rgb(155, 89, 182);
  font-weight: 500;
}

.facebook-header {
  display: flex;
  align-items: center;
}

.facebook-header .el-image {
  width: 50px;
  height: 50px;
  border-radius: 100%;
  margin-right: 0.5em;
}

.fieldset-body {
  display: flex;
  flex-flow: row wrap;
}

.fieldset-body > * {
  flex: 1 1 100%;
}
</style>
