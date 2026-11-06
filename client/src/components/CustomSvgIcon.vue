<template>
  <img 
    :src="iconUrl" 
    width="26" 
    height="26" 
    :alt="alt"
    style="display: block"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import spotiflyLogo from '../assets/icons/spotifly-logo.svg?url'

interface Props {
  size?: string | number
  color?: string
  alt?: string
}

withDefaults(defineProps<Props>(), {
  size: 26,
  color: 'currentColor',
  alt: 'Olive icon'
})

const iconUrl = computed(() => spotiflyLogo)
</script>