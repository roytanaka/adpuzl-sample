<template>
  <div class="credit-card">
    <h3 class="credit-card__name">{{ name }}</h3>
    <div class="credit-card__info">
      <font-awesome-icon class="credit-card__logo" :icon="['fab', cc]" />
      <div class="credit-card__numbers">
        <div class="credit-card__number">
          <span class="stars">&#65290;&#65290;&#65290;&#65290;</span>
          <span class="stars">&#65290;&#65290;&#65290;&#65290;</span>
          <span class="stars">&#65290;&#65290;&#65290;&#65290;</span>
          {{ last4 }}
        </div>
        <div class="credit-card__exp">
          <span class="exp">Exp:</span> {{ exp_month }}/{{ exp_year }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    brand: String,
    name: String,
    exp_month: Number,
    exp_year: Number,
    last4: String,
  },
  computed: {
    cc() {
      switch (this.brand) {
        case "visa":
          return "cc-visa";
        case "mastercard":
          return "cc-mastercard";
        case "amex":
          return "cc-amex";
        default:
          return "other";
      }
    },
  },
};
</script>

<style scoped>
.credit-card {
  background: var(--clouds);
  padding: 2rem;
  border-radius: 6px;
  letter-spacing: 0.2ex;
  max-width: 20em;
  min-width: 15em;
  text-align: left;
}
.stars {
  letter-spacing: -0.25rem;
  font-size: 0.85rem;
}
.credit-card__info {
  display: flex;
  align-items: flex-end;
}

.credit-card__logo {
  font-size: 2.5rem;
  margin-right: 1rem;
  color: var(--asbestos);
}

.exp {
  color: var(--asbestos);
}
</style>
