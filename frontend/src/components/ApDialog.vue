<template>
  <el-dialog
    class="ap-dialog"
    v-on="$listeners"
    :visible.sync="dialogVisible"
    width="95%"
    v-bind="{ ...$props, ...$attrs }"
    @open="handleOpen"
    @close="handleClose"
  >
    <template v-slot:title>
      <div class="h2">
        <slot name="title"></slot>
      </div>
    </template>
    <template v-slot:default>
      <slot class="tw-my-8"></slot>
    </template>
    <template v-slot:footer>
      <div class="footer">
        <slot name="footer"></slot>
      </div>
    </template>
  </el-dialog>
</template>

<script>
export default {
  props: {
    visible: Boolean,
  },
  computed: {
    dialogVisible: {
      get() {
        return this.visible;
      },
      set(value) {
        this.$emit("visible", value);
      },
    },
  },
  methods: {
    handleOpen() {
      document.querySelectorAll("#app, main, #page-content").forEach((el) => {
        el.style.overflow = "hidden";
      });
    },
    handleClose() {
      document.querySelectorAll("#app, main, #page-content").forEach((el) => {
        el.style.removeProperty("overflow");
      });
    },
  },
};
</script>

<style scoped>
.ap-dialog,
.ap-dialog >>> .el-dialog__body {
  color: var(--midnight-blue);
}

.ap-dialog {
  text-align: center;
}

.ap-dialog >>> .el-dialog {
  border-radius: 6px;
  max-width: 34em;
  border: 3px solid rgb(142, 68, 173);
  background-color: rgb(249, 251, 251);
}

.ap-dialog >>> .el-dialog .el-dialog__header {
  padding: 2em 2em 0;
}

.ap-dialog >>> .el-dialog :where(.el-dialog__body, .el-dialog__footer) {
  text-align: center;
  padding: 1em 2em;
  font-size: 1rem;
}

.ap-dialog >>> .el-dialog .el-dialog__headerbtn {
  top: 10px;
  right: 15px;
}
.footer >>> .button-group {
  display: grid;
  gap: 0.5em;
  grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));
  place-items: center;
}

.footer >>> .button-group button {
  width: 100%;
  max-width: 20em;
}
</style>
