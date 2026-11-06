<template>
  <div class="bulk-controls-row">
    <v-container fluid class="pa-4">
      <div class="d-flex justify-space-between align-center">
        <!-- Left side - Selection Controls -->
        <div class="d-flex align-center gap-4">
          <!-- Select All Checkbox -->
          <v-checkbox
            :model-value="allSelected"
            @update:model-value="$emit('select-all', !!$event)"
            label="Select All"
            density="compact"
            hide-details
          />

          <!-- Expand/Collapse All or Delete Selected -->
          <v-btn
            v-if="selectedCount === 0"
            variant="outlined"
            size="small"
            @click="$emit('expand-collapse-all')"
          >
            {{ allExpanded ? 'Collapse All' : 'Expand All' }}
          </v-btn>

          <v-btn
            v-else
            variant="outlined"
            size="small"
            color="error"
            @click="$emit('delete-selected')"
          >
            <template v-slot:prepend>
              <v-icon icon="mdi-delete" />
            </template>
            Delete Selected ({{ selectedCount }})
          </v-btn>
        </div>

        <!-- Right side - Pagination -->
        <div class="d-flex align-center gap-4">
          <!-- Items per page -->
          <div class="d-flex align-center gap-2">
            <span class="text-body-2">Items per page:</span>
            <v-select
              :model-value="itemsPerPage"
              @update:model-value="$emit('update:items-per-page', $event)"
              :items="[5, 10, 25, 50]"
              variant="outlined"
              density="compact"
              hide-details
              style="min-width: 80px;"
            />
          </div>

          <!-- Page navigation -->
          <div class="d-flex align-center gap-2">
            <v-btn
              icon
              size="small"
              variant="outlined"
              :disabled="currentPage === 1"
              @click="$emit('update:page', currentPage - 1)"
            >
              <v-icon icon="mdi-chevron-left" />
            </v-btn>
            
            <span class="text-body-2 px-2">
              {{ currentPage }} of {{ totalPages }}
            </span>
            
            <v-btn
              icon
              size="small"
              variant="outlined"
              :disabled="currentPage === totalPages"
              @click="$emit('update:page', currentPage + 1)"
            >
              <v-icon icon="mdi-chevron-right" />
            </v-btn>
          </div>
        </div>
      </div>
    </v-container>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  selectedCount: number
  totalCount: number
  allExpanded: boolean
  itemsPerPage: number
  currentPage: number
  totalPages: number
}>()

defineEmits<{
  'select-all': [selected: boolean]
  'expand-collapse-all': []
  'delete-selected': []
  'update:items-per-page': [value: number]
  'update:page': [page: number]
}>()

const allSelected = computed(() => {
  return props.selectedCount > 0 && props.selectedCount === props.totalCount
})
</script>

<style scoped>
.bulk-controls-row {
  background: white;
  border-bottom: 1px solid #e0e0e0;
  position: sticky;
  top: 128px;
  z-index: 9;
}

.gap-4 {
  gap: 16px;
}

.gap-2 {
  gap: 8px;
}
</style>