<template>
  <div class="info-field">
    <div class="info-label">{{ label }}</div>
    <div class="info-value" :class="[mono && 'mono', status && 'status-' + status]">{{ value }}</div>
  </div>
</template>
<script setup>
defineProps({ label: String, value: String, mono: Boolean, status: String })
</script>
<style scoped>
.info-field { margin-bottom: 10px; }
.info-label { font-size: 10px; color: #9e9890; margin-bottom: 2px; }
.info-value { font-size: 13px; color: #2c2520; font-weight: 500; }
.mono { font-family: monospace; color: var(--pa-orange); font-weight: 700; }
</style>
