<script setup>
import { onMounted, ref } from 'vue'
import { Button } from '@/components/ui/button'
import { ArrowRight } from 'lucide-vue-next'

const props = defineProps({
  checkout: {
    type: Object,
    required: true
  }
})

const isLoading = ref(false)

onMounted(() => {
  if (!window.Paddle) {
    console.error('Paddle.js not loaded. Make sure @paddleJS directive is included in layout.')
  } else {
    console.log('Paddle.js loaded successfully')
  }
})

const openCheckout = () => {
  if (!window.Paddle) {
    console.error('Paddle.js not loaded')
    alert('Payment system is not available. Please try again later.')
    return
  }

  isLoading.value = true

  try {
    console.log('Opening Paddle checkout with:', props.checkout)
    
    // Override displayMode to overlay to avoid frameTarget requirement
    const checkoutSettings = {
      ...props.checkout,
      settings: {
        ...props.checkout.settings,
        displayMode: 'overlay',
      }
    }
    
    window.Paddle.Checkout.open({
      settings: checkoutSettings.settings || {},
      items: checkoutSettings.items || [],
      customer: checkoutSettings.customer || {},
      customData: checkoutSettings.customData || {}
    })
  } catch (error) {
    console.error('Paddle checkout error:', error)
    alert('Failed to open checkout. Please try again.')
    isLoading.value = false
  }
}
</script>

<template>
  <Button class="w-full" size="lg" @click="openCheckout" :disabled="isLoading">
    <ArrowRight class="w-5 h-5 mr-2" />
    <slot>Subscribe</slot>
  </Button>
</template>
