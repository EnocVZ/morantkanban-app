<template>
  <div v-if="visible" :class="['toast', type]" @click="closeOnClick">
    <slot></slot>
  </div>
</template>

<script>
export default {
  name: 'Toast',
  props: {
    type: {
      type: String,
      default: 'success',
    },
    duration: {
      type: Number,
      default: 7000,
    },
    onClose: {
      type: Function,
      default: () => {},
    },
  },
  data() {
    return {
      visible: false,
    };
  },
  methods: {
    showToast() {
      this.visible = true;
      setTimeout(() => {
        this.hideToast();
      }, this.duration);
    },
    hideToast() {
      this.visible = false;
    },
    closeOnClick() {
      this.hideToast();
      this.onClose();
    },
  },
};
</script>

<style scoped>
.toast {
  position: fixed;
  top: 20px; /* Mueve el toast a la parte superior */
  right: 20px; /* Alinea el toast a la derecha */
  padding: 16px;
  border-radius: 8px;
  color: white;
  opacity: 0.9;
  cursor: pointer;
  transition: opacity 0.3s ease;
  z-index: 1000;
}
.toast.success {
  background-color: #38a169;
}
.toast.error {
  background-color: #e53e3e;
}
.toast.info {
  background-color: #3182ce;
}
.toast.warning {
  background-color: #dd6b20;
}
.toast:hover {
  opacity: 1;
}
</style>
