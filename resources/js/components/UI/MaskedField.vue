<template>
  <div>
    <span v-if="isMasked">{{ maskedValue }}</span>
    <span v-else>{{ value }}</span>
  </div>
</template>

<script>
export default {
  name: "MaskedField",
  props: {
    value: {
      type: String,
      required: true,
    },
    type: {
      type: String,
      required: true,
    },
    isUnmasked: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      isMasked: !this.isUnmasked,
    };
  },
  watch: {
    isUnmasked(newVal) {
      this.isMasked = !newVal;
    },
  },
  computed: {
    maskedValue() {
      if (this.type === "email") {
        const [localPart, domain] = this.value.split("@");
        return `${"*".repeat(Math.min(5, localPart.length))}${localPart.slice(
          Math.min(5, localPart.length)
        )}@${domain}`;
      } else if (this.type === "phone") {
        return `${this.value.slice(0, 5)}*****`;
      }
      return this.value;
    },
  },
  methods: {
    toggleMask() {
      this.$emit("toggle-mask");
    },
  },
};
</script>
