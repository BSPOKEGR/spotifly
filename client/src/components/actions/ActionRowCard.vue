<template>
  <v-card
    class="action-row-card mb-3"
    :class="{ 'action-row-card--selected': selected, 'action-row-card--expanded': expanded }"
    elevation="2"
  >
    <!-- Main Row -->
    <div class="action-row-main" @click="$emit('toggle-expand')">
      <div class="action-row-grid">
        <!-- Checkbox -->
        <div class="action-checkbox" @click.stop>
          <v-checkbox
            :model-value="selected"
            @update:model-value="$emit('update:selected', !!$event)"
            density="compact"
            hide-details
          />
        </div>

        <!-- Action Type -->
        <div class="action-type">
          <v-chip
            :color="action.type === 'plant-protection' ? 'success' : 'info'"
            variant="flat"
            size="small"
          >
            {{ action.type === 'plant-protection' ? 'Plant Protection' : 'Observation' }}
          </v-chip>
        </div>

        <!-- Parcel Name -->
        <div class="parcel-name">
          <div class="text-body-1 font-weight-medium">{{ action.parcelName }}</div>
        </div>

        <!-- Date -->
        <div class="action-date">
          <div class="text-body-1">{{ formatDate(action.date) }}</div>
          <div class="text-caption text-medium-emphasis">{{ formatTime(action.createdAt) }}</div>
        </div>

        <!-- Actions -->
        <div class="action-buttons" @click.stop>
          <v-btn
            icon
            size="x-small"
            variant="outlined"
            @click="$emit('edit', action)"
          >
            <v-icon icon="mdi-text-box-edit-outline" />
          </v-btn>
          <v-btn
            icon
            size="x-small"
            variant="outlined"
            color="error"
            @click="showDeleteDialog = true"
          >
            <v-icon icon="mdi-trash-can-outline" />
          </v-btn>
          <div class="vertical-separator"></div>
          <v-btn
            icon
            size="small"
            variant="text"
            @click="$emit('toggle-expand')"
          >
            <v-icon :icon="expanded ? 'mdi-chevron-up' : 'mdi-chevron-down'" />
          </v-btn>
        </div>
      </div>
    </div>

    <!-- Expanded Details -->
    <v-expand-transition>
      <div v-if="expanded" class="action-details">
        <v-divider class="mb-4" />
        
        <!-- Plant Protection Details -->
        <div v-if="action.type === 'plant-protection'" class="protection-details">
          <v-row>
            <v-col cols="12" md="6">
              <div class="detail-section">
                <h5 class="text-subtitle-1 font-weight-bold mb-3 pb-2 section-title">Application Details</h5>
                <div class="detail-item">
                  <span class="detail-label">Cultivation Period:</span>
                  <span class="detail-value">{{ action.details.cultivationPeriod || '2025' }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Parcels:</span>
                  <span class="detail-value">{{ action.parcelName }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Date:</span>
                  <span class="detail-value">{{ formatDate(action.date) }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Time:</span>
                  <span class="detail-value">{{ action.details.time || 'Not specified' }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Target Insect:</span>
                  <span class="detail-value">{{ action.details.targetInsect }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Active Ingredient:</span>
                  <span class="detail-value">{{ action.details.activeIngredient }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Dosage:</span>
                  <span class="detail-value">{{ action.details.dosage }} {{ action.details.dosageUnit }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Spray Volume:</span>
                  <span class="detail-value">{{ action.details.sprayVolume }} L</span>
                </div>
              </div>
            </v-col>
            
            <v-col cols="12" md="6">
              <div class="detail-section">
                <h5 class="text-subtitle-1 font-weight-bold mb-3 pb-2 section-title">Costs</h5>
                <div class="detail-item">
                  <span class="detail-label">Fuel:</span>
                  <span class="detail-value">€{{ action.details.costs.fuel }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Materials:</span>
                  <span class="detail-value">€{{ action.details.costs.materials }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Labor:</span>
                  <span class="detail-value">€{{ action.details.costs.labor }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Other:</span>
                  <span class="detail-value">€{{ action.details.costs.other }}</span>
                </div>
                <div class="detail-item total-cost">
                  <span class="detail-label font-weight-bold">Total:</span>
                  <span class="detail-value font-weight-bold">€{{ getTotalCost(action.details.costs) }}</span>
                </div>
              </div>
            </v-col>
          </v-row>
        </div>

        <!-- Observation Details -->
        <div v-else class="observation-details">
          <v-row>
            <v-col cols="12" md="6">
              <div class="detail-section">
                <h5 class="text-subtitle-1 font-weight-bold mb-3 pb-2 section-title">General Observations</h5>
                <div class="detail-item">
                  <span class="detail-label">Cultivation Period:</span>
                  <span class="detail-value">{{ action.details.cultivationPeriod || '2025' }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Parcels:</span>
                  <span class="detail-value">{{ action.parcelName }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Date:</span>
                  <span class="detail-value">{{ formatDate(action.date) }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Time:</span>
                  <span class="detail-value">{{ action.details.time || 'Not specified' }}</span>
                </div>
              </div>
            </v-col>
            
            <v-col cols="12" md="6">
              <div class="detail-section">
                <h5 class="text-subtitle-1 font-weight-bold mb-3 pb-2 section-title">Olive Fruit Fly</h5>
                <div class="detail-item">
                  <span class="detail-label">Infestation Present:</span>
                  <span class="detail-value">{{ action.details.oliveFruitFly?.active ? 'Yes' : 'No' }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Larvae Observations:</span>
                  <span class="detail-value">{{ action.details.oliveFruitFly?.larvaeObservations ?? 0 }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Adult Captures:</span>
                  <span class="detail-value">{{ action.details.oliveFruitFly?.adultCaptures ?? 0 }}</span>
                </div>
              </div>

              <div class="detail-section mt-4">
                <h5 class="text-subtitle-1 font-weight-bold mb-3 pb-2 section-title">Olive Moth</h5>
                <div class="detail-item">
                  <span class="detail-label">Infestation Present:</span>
                  <span class="detail-value">{{ action.details.oliveMoth?.active ? 'Yes' : 'No' }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Larvae Observations:</span>
                  <span class="detail-value">{{ action.details.oliveMoth?.larvaeObservations ?? 0 }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Adult Captures:</span>
                  <span class="detail-value">{{ action.details.oliveMoth?.adultCaptures ?? 0 }}</span>
                </div>
              </div>
            </v-col>
          </v-row>
        </div>
      </div>
    </v-expand-transition>
  </v-card>

  <v-dialog v-model="showDeleteDialog" max-width="440" persistent>
    <v-card class="delete-confirm-card" rounded="xl">
      <div class="delete-icon-wrap">
        <div class="delete-icon-ring">
          <v-icon icon="mdi-trash-can-outline" size="32" color="error" />
        </div>
      </div>

      <v-card-text class="text-center px-8 pb-2 pt-2">
        <div class="text-h6 font-weight-bold mb-2">Delete Action</div>
        <div class="text-body-2 text-medium-emphasis">
          Are you sure you want to delete this
          <span class="font-weight-medium text-high-emphasis">
            {{ action.type === 'plant-protection' ? 'Plant Protection' : 'Observation' }}
          </span>
          action for
          <span class="font-weight-medium text-high-emphasis">{{ action.parcelName }}</span>?
          <br />
          <span class="text-caption">This action cannot be undone.</span>
        </div>
      </v-card-text>

      <v-card-actions class="px-8 pb-6 pt-4 d-flex gap-3">
        <v-btn
          variant="tonal"
          color="grey"
          rounded="lg"
          class="flex-grow-1"
          @click="showDeleteDialog = false"
        >
          Cancel
        </v-btn>
        <v-btn
          variant="flat"
          color="error"
          rounded="lg"
          class="flex-grow-1"
          @click="confirmDelete"
        >
          Delete
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Action {
  id: string
  type: 'plant-protection' | 'observation'
  parcelId: string
  parcelName: string
  date: string
  title?: string
  details: any
  createdAt: string
}

const props = defineProps<{
  action: Action
  selected: boolean
  expanded: boolean
}>()

const emit = defineEmits<{
  'update:selected': [selected: boolean]
  'toggle-expand': []
  'edit': [action: Action]
  'delete': [actionId: string]
}>()

const showDeleteDialog = ref(false)

const confirmDelete = () => {
  showDeleteDialog.value = false
  emit('delete', props.action.id)
}

const formatDate = (dateString: string) => {
  const d = new Date(dateString)
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const year = d.getFullYear()
  return `${day}/${month}/${year}`
}

const formatTime = (timeString: string) => {
  return new Date(timeString).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getTotalCost = (costs: any) => {
  if (!costs) return 0
  return costs.fuel + costs.materials + costs.labor + costs.other
}
</script>

<style scoped>
.action-row-card {
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  border-left: 4px solid transparent;
  box-shadow: 0 10px 13px -6px rgba(0, 0, 0, 0.08), 0 20px 31px 3px rgba(0, 0, 0, 0.06), 0 8px 38px 7px rgba(0, 0, 0, 0.04) !important;
}

.action-row-card:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.action-row-card--selected {
  border-left-color: rgb(var(--v-theme-primary));
  background-color: rgba(var(--v-theme-primary), 0.02);
}

.action-row-card--expanded {
  border-left-color: rgb(var(--v-theme-secondary));
}

.action-row-main {
  padding: 10px;
}

.action-row-grid {
  display: grid;
  grid-template-columns: auto 1fr 1fr 1fr auto;
  gap: 16px;
  align-items: center;
}

.action-checkbox {
  display: flex;
  justify-content: center;
}

.action-type {
  display: flex;
  align-items: center;
}

.parcel-name {
  display: flex;
  align-items: center;
}

.action-date {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.action-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  align-items: center;
}

.action-details {
  padding: 0 16px 16px 16px;
}

.detail-section {
  background: rgba(var(--v-theme-surface), 0.5);
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
  padding: 4px 0;
}

.detail-item:last-child {
  margin-bottom: 0;
}

.detail-label {
  font-weight: 500;
  color: rgba(var(--v-theme-on-surface), 0.7);
  min-width: 120px;
}

.detail-value {
  text-align: right;
  flex: 1;
}

.total-cost {
  border-top: 1px solid rgba(var(--v-theme-on-surface), 0.1);
  padding-top: 8px;
  margin-top: 8px;
}

.section-title {
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.2);
}

.vertical-separator {
  width: 1px;
  height: 34px;
  border: 1px solid #e0e0e0;
  margin: 0 4px;
}

/* Responsive Design */
@media (max-width: 960px) {
  .action-row-grid {
    grid-template-columns: 50px 120px 1fr 100px 80px;
    gap: 8px;
  }
}

@media (max-width: 600px) {
  .action-row-grid {
    grid-template-columns: 1fr;
    gap: 8px;
  }
  
  .action-checkbox,
  .action-type,
  .parcel-name,
  .action-date,
  .action-buttons {
    justify-content: flex-start;
  }
  
  .action-buttons {
    justify-content: flex-end;
  }
}

.delete-confirm-card {
  overflow: visible !important;
  padding-top: 48px;
}

.delete-icon-wrap {
  position: absolute;
  top: -36px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 1;
}

.delete-icon-ring {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: #fff;
  border: 3px solid rgb(var(--v-theme-error));
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 20px rgba(var(--v-theme-error), 0.25);
}

.gap-3 {
  gap: 12px;
}
</style>