<template>
  <div class="filter-panel pa-4">
    <div class="d-flex flex-wrap gap-4 align-center">
      <!-- Crop Type Filter -->
      <div class="filter-group">
        <v-text-field
          v-model="localFilters.cropType"
          label="Filter by Crop"
          variant="outlined"
          density="compact"
          hide-details
          clearable
          style="min-width: 240px;"
          prepend-inner-icon="mdi-sprout"
          @update:model-value="updateFilters"
        />
      </div>

      <!-- Clear Filters Button -->
      <div class="filter-group">
        <v-btn
          variant="text"
          size="small"
          color="primary"
          :disabled="!hasActiveFilters"
          @click="clearFilters"
        >
          Clear Filters
        </v-btn>
      </div>

      <v-spacer />

      <!-- Results Count -->
      <div class="results-count">
        <v-chip variant="text" color="primary">
          {{ resultCount }} {{ resultCount === 1 ? 'parcel' : 'parcels' }}
        </v-chip>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, watch, computed } from 'vue'

const props = defineProps<{
  filters: {
    cropType: string
  }
  resultCount: number
}>()

const emit = defineEmits<{
  'update:filters': [filters: typeof props.filters]
}>()

const localFilters = reactive({ ...props.filters })

const hasActiveFilters = computed(() => !!localFilters.cropType)

const updateFilters = () => {
  emit('update:filters', { ...localFilters })
}

const clearFilters = () => {
  localFilters.cropType = ''
  updateFilters()
}

watch(() => props.filters, (newFilters) => {
  Object.assign(localFilters, newFilters)
}, { deep: true })
</script>

<style scoped>
.filter-panel {
  background: white;
  border-radius: 12px;
}

.filter-group {
  flex-shrink: 0;
}

.gap-4 {
  gap: 16px;
}
</style>
