<template>
  <div class="custom-icon-examples pa-4">
    <h3 class="mb-4">Custom SVG Icon Usage Examples</h3>
    
    <!-- Method 1: Direct import as image -->
    <div class="example-section mb-6">
      <h4 class="mb-2">Method 1: Direct Import</h4>
      <div class="d-flex align-center gap-4">
        <img 
          v-if="customIcon" 
          :src="customIcon" 
          alt="Custom Icon" 
          width="24" 
          height="24" 
          style="filter: brightness(0) saturate(100%) invert(27%) sepia(51%) saturate(2878%) hue-rotate(346deg) brightness(104%) contrast(97%);"
        />
        <span>Custom icon as image</span>
      </div>
    </div>

    <!-- Method 2: Inline SVG -->
    <div class="example-section mb-6">
      <h4 class="mb-2">Method 2: Inline SVG</h4>
      <div class="d-flex align-center gap-4">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="custom-svg-icon">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
        </svg>
        <span>Inline SVG icon</span>
      </div>
    </div>

    <!-- Method 3: In v-icon -->
    <div class="example-section mb-6">
      <h4 class="mb-2">Method 3: Inside v-icon</h4>
      <div class="d-flex align-center gap-4">
        <v-icon color="primary">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
            <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.1 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
          </svg>
        </v-icon>
        <span>SVG inside v-icon component</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

// Example of importing a custom SVG (will be null if file doesn't exist)
const customIcon = ref<string | null>(null)

// You would import like this when you have actual SVG files:
// import customIcon from '../assets/icons/your-custom-icon.svg'
</script>

<style scoped>
.custom-icon-examples {
  max-width: 600px;
}

.example-section {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 16px;
  background: #fafafa;
}

.custom-svg-icon {
  color: #1976d2;
}

.gap-4 {
  gap: 16px;
}
</style>