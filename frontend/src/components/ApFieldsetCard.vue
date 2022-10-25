<template>
  <fieldset class="fieldset-card">
    <div class="fieldset-card__title">
      <h2>
        <font-awesome-icon
          class="fieldset-card__icon"
          :icon="icon"
          v-if="icon"
        />
        {{ title }}
      </h2>
      <el-tooltip v-if="!!$slots.tooltip" placement="top-end" effect="light">
        <template v-slot:content>
          <slot name="tooltip"></slot>
        </template>
        <el-button class="fieldset-card__info" type="text">
          <font-awesome-icon
            style="font-size: 1.2rem"
            :icon="['far', 'info-circle']"
          />
        </el-button>
      </el-tooltip>
    </div>
    <div class="fieldset-card__body">
      <slot></slot>
    </div>
  </fieldset>
</template>

<script>
export default {
  props: {
    icon: Array,
    title: {
      type: String,
      required: true,
    },
  },
};
</script>

<style scoped>
.fieldset-card {
  border: none;
  border-radius: 4px;
  overflow: hidden;
  background: white;
  transition: 0.2s;
  margin-bottom: 1.5em;
  box-shadow: 0.05em 0.05em 0.5em 0.05em rgba(0, 0, 0, 0.2);
  padding: 0;
}

.fieldset-card >>> hr {
  color: var(--clouds);
  border: 2px solid;
}

.fieldset-card:hover > .fieldset-card__title,
.fieldset-card:focus-within > .fieldset-card__title {
  background-color: #9b59b6;
  color: white;
}

.fieldset-card__icon {
  margin: 0 0.5em;
}

.fieldset-card__title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 18px;
  font-weight: bold;
  background-color: #1abc9c;
  padding: 7px;
  color: rgba(0, 0, 0, 0.65);
  transition: all 250ms ease;
}

.fieldset-card__title h2 {
  margin: 0;
  flex-grow: 1;
}

.fieldset-card__body {
  color: var(--midnight-blue);
  padding: 1em 2em;
}

.fieldset-card__body .el-form-item {
  margin-bottom: 0;
}

.fieldset-card__info {
  color: inherit;
  padding: 0 0.5em 0;
}
.el-button {
  white-space: normal;
  line-height: 1.25;
}
</style>
