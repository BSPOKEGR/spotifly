<template>
  <div class="filters-header">
    <v-container fluid class="pa-4">
      <div class="d-flex justify-space-between align-center">
        <!-- Left side - Filters -->
        <div class="d-flex align-center gap-4">
          <!-- Parcel Filter -->
          <v-select
            :model-value="filters.parcelId"
            @update:model-value="updateFilter('parcelId', $event)"
            :items="parcelOptions"
            label="Parcel"
            variant="outlined"
            density="compact"
            hide-details
            clearable
            style="min-width: 200px;"
          />

          <!-- Action Type Filter -->
          <v-select
            :model-value="filters.actionType"
            @update:model-value="updateFilter('actionType', $event)"
            :items="actionTypeOptions"
            label="Action Type"
            variant="outlined"
            density="compact"
            hide-details
            clearable
            style="min-width: 200px;"
          />

          <!-- Date Range Picker -->
          <div class="d-flex align-center gap-2">
            <v-date-input
              :model-value="filters.dateRange.start"
              @update:model-value="updateDateRange('start', $event)"
              label="Start Date"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              prepend-icon=""
              persistent-placeholder
              style="min-width: 160px;"
            />
            
            <span class="text-medium-emphasis">to</span>
            
            <v-date-input
              :model-value="filters.dateRange.end"
              @update:model-value="updateDateRange('end', $event)"
              label="End Date"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              prepend-icon=""
              persistent-placeholder
              style="min-width: 160px;"
            />
          </div>

          <!-- Clear Filters -->
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

        <!-- Right side - Add Action Button -->
        <v-btn
          color="primary"
          size="large"
          @click="$emit('add-action')"
        >
          <template v-slot:prepend>
            <v-icon icon="mdi-plus" />
          </template>
          Add Action
        </v-btn>
      </div>
    </v-container>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Filters {
  actionType: string
  parcelId: string
  dateRange: { start: string; end: string }
}

interface ParcelOption {
  id: string
  name: string
}

const props = defineProps<{
  filters: Filters
  parcels: ParcelOption[]
}>()

const emit = defineEmits<{
  'update:filters': [filters: Partial<Filters>]
  'add-action': []
}>()

const actionTypeOptions = [
  { title: 'Plant Protection', value: 'plant-protection' },
  { title: 'Observation', value: 'observation' }
]

const parcelOptions = computed(() =>
  props.parcels.map(p => ({ title: p.name, value: p.id }))
)

const hasActiveFilters = computed(() => {
  return props.filters.actionType ||
         props.filters.parcelId ||
         props.filters.dateRange.start ||
         props.filters.dateRange.end
})

const updateFilter = (key: keyof Filters, value: any) => {
  emit('update:filters', { [key]: value })
}

const updateDateRange = (key: 'start' | 'end', value: string) => {
  const newDateRange = { ...props.filters.dateRange, [key]: value }
  emit('update:filters', { dateRange: newDateRange })
}

const clearFilters = () => {
  emit('update:filters', {
    actionType: '',
    parcelId: '',
    dateRange: { start: '', end: '' }
  })
}
</script>

<style scoped>
.filters-header {
  background: white;
  border-bottom: 1px solid #e0e0e0;
  position: sticky;
  top: 64px;
  z-index: 10;
}

.gap-4 {
  gap: 16px;
}

.gap-2 {
  gap: 8px;
}
</style>