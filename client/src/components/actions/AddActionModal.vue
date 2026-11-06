<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="800px"
    persistent
    scrollable
  >
    <v-card class="add-action-modal">
      <!-- Header -->
      <v-card-title class="modal-header d-flex justify-space-between align-center">
        <span class="text-h5 font-weight-bold">
          {{ action ? 'Edit Action' : 'Add Action' }}
        </span>
        <v-btn
          icon
          variant="text"
          @click="$emit('cancel')"
        >
          <v-icon icon="mdi-close" />
        </v-btn>
      </v-card-title>

      <!-- Body -->
      <v-card-text class="modal-body">
        <v-form ref="form" @submit.prevent="handleSave">
          <!-- Always Visible Fields -->
          <div class="always-visible-fields">
            <v-row>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.type"
                  :items="actionTypeOptions"
                  label="Action Type"
                  variant="outlined"
                  required
                  :rules="[v => !!v || 'Action type is required']"
                  hide-details
                  :readonly="!!action"
                  :class="{ 'locked-field': !!action }"
                >
                  <template v-if="action" v-slot:append-inner>
                    <v-icon icon="mdi-lock-outline" color="grey" />
                  </template>
                </v-select>
              </v-col>

              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.cultivationPeriod"
                  :items="cultivationPeriodOptions"
                  label="Cultivation Period"
                  variant="outlined"
                  required
                  :rules="[v => !!v || 'Cultivation period is required']"
                  hide-details
                  :readonly="!!action"
                  :class="{ 'locked-field': !!action }"
                >
                  <template v-if="action" v-slot:append-inner>
                    <v-icon icon="mdi-lock-outline" color="grey" />
                  </template>
                </v-select>
              </v-col>
            </v-row>

            <v-row>
              <v-col cols="12" md="6">
                <v-date-input
                  v-model="formData.date"
                  label="Date"
                  variant="outlined"
                  required
                  :rules="[v => !!v || 'Date is required']"
                  clearable
                  prepend-icon=""
                  persistent-placeholder
                />
              </v-col>

              <v-col cols="12" md="6">
                <!-- Locked parcel display (predefined or editing) -->
                <div v-if="predefinedParcel || action">
                  <v-select
                    class="predefined-parcel-select locked-field"
                    :model-value="lockedParcelValue"
                    :items="parcelOptions"
                    label="Parcel"
                    variant="outlined"
                    readonly
                    hide-details
                    :menu-icon="undefined"
                  >
                    <template v-slot:selection="{ item }">
                      <span class="text-body-1">{{ item.title }}</span>
                    </template>
                    <template v-slot:append-inner>
                      <v-icon icon="mdi-lock-outline" color="grey" />
                    </template>
                  </v-select>
                </div>

                <!-- Multiple Parcel Selection -->
                <div v-else>
                  <v-select
                    v-model="formData.parcels"
                    :items="parcelOptions"
                    label="Select Parcels"
                    variant="outlined"
                    multiple
                    required
                    persistent-hint
                    persistent-placeholder
                    :loading="loadingParcels"
                    :rules="[v => v.length > 0 || 'At least one parcel is required']"
                  >
                    <template v-slot:selection="{ item, index }">
                      <div v-if="index === 0" class="d-flex align-center" style="max-width: 100%; overflow: hidden;">
                        <v-chip :text="item.title" size="small" class="mr-2 flex-shrink-0"></v-chip>
                        <span
                          v-if="formData.parcels.length > 1"
                          class="text-grey text-caption flex-shrink-0"
                        >
                          (+{{ formData.parcels.length - 1 }} others)
                        </span>
                      </div>
                    </template>
                  </v-select>
                </div>
              </v-col>
            </v-row>

            <v-divider class="my-6" />
          </div>

          <!-- Plant Protection Fields -->
          <div v-if="formData.type === 'plant-protection'" class="plant-protection-fields">
            <div class="fixed-title-section mb-6">
              <h3 class="text-h6 font-weight-bold pb-3" style="border-bottom: 1px solid #e0e0e0;">
                Insecticides
              </h3>
            </div>

            <v-tabs v-model="activeTab" class="mb-4">
              <v-tab value="basic">Basic Information</v-tab>
              <v-tab value="costs">Costs</v-tab>
            </v-tabs>

            <v-tabs-window v-model="activeTab">
              <v-tabs-window-item value="basic" class="pt-4">
                <v-row>
                  <v-col cols="12">
                    <v-select
                      v-model="formData.time"
                      :items="timeOptions"
                      label="Time"
                      variant="outlined"
                      required
                      :rules="[v => !!v || 'Time is required']"
                      style="max-width: 200px;"
                    />
                  </v-col>
                </v-row>

                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="formData.targetInsect"
                      label="Target Insect"
                      variant="outlined"
                      required
                      :rules="[v => !!v || 'Target insect is required']"
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="formData.activeIngredient"
                      label="Active Ingredient"
                      variant="outlined"
                      required
                      :rules="[v => !!v || 'Active ingredient is required']"
                    />
                  </v-col>
                </v-row>

                <v-row>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="formData.dosage"
                      label="Dosage"
                      variant="outlined"
                      type="number"
                      required
                      :rules="[v => !!v || 'Dosage is required']"
                    />
                  </v-col>

                  <v-col cols="12" md="4">
                    <v-select
                      v-model="formData.dosageUnit"
                      :items="dosageUnitOptions"
                      label="Unit"
                      variant="outlined"
                      required
                      :rules="[v => !!v || 'Unit is required']"
                    />
                  </v-col>

                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="formData.sprayVolume"
                      label="Spray Solution Volume (L)"
                      variant="outlined"
                      type="number"
                      required
                      :rules="[v => !!v || 'Spray volume is required']"
                    />
                  </v-col>
                </v-row>
              </v-tabs-window-item>

              <!-- Costs Tab -->
              <v-tabs-window-item value="costs" class="pt-4">
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model.number="formData.costs.fuel"
                      label="Fuel (€)"
                      variant="outlined"
                      type="number"
                      min="0"
                      step="0.01"
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model.number="formData.costs.materials"
                      label="Materials (€)"
                      variant="outlined"
                      type="number"
                      min="0"
                      step="0.01"
                    />
                  </v-col>
                </v-row>

                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model.number="formData.costs.labor"
                      label="Labor (€)"
                      variant="outlined"
                      type="number"
                      min="0"
                      step="0.01"
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model.number="formData.costs.other"
                      label="Other (€)"
                      variant="outlined"
                      type="number"
                      min="0"
                      step="0.01"
                    />
                  </v-col>
                </v-row>

                <v-row>
                  <v-col cols="12">
                    <v-card variant="outlined" class="pa-4">
                      <div class="d-flex justify-space-between align-center">
                        <span class="text-h6 font-weight-bold">Total Cost:</span>
                        <span class="text-h6 font-weight-bold text-primary">
                          €{{ totalCost.toFixed(2) }}
                        </span>
                      </div>
                    </v-card>
                  </v-col>
                </v-row>
              </v-tabs-window-item>
            </v-tabs-window>
          </div>

          <!-- Observation Fields -->
          <div v-else-if="formData.type === 'observation'" class="observation-fields pt-4">
            <v-row>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.time"
                  :items="timeOptions"
                  label="Time"
                  variant="outlined"
                  required
                  :rules="[v => !!v || 'Time is required']"
                />
              </v-col>
            </v-row>

            <!-- Olive Fruit Fly Section -->
            <div class="pest-section mt-6">
              <h4 class="text-h6 font-weight-bold mb-3 pb-2" style="border-bottom: 1px solid #e0e0e0;">
                Olive Fruit Fly
              </h4>

              <v-row>
                <v-col cols="12" md="4">
                  <v-switch
                    v-model="formData.oliveFruitFly"
                    label="Infestation Present"
                    color="primary"
                    inset
                  />
                </v-col>

                <v-col cols="12" md="4">
                  <v-text-field
                    v-model.number="formData.oliveFruitFlyLarvaeObservations"
                    label="Larvae Observations"
                    variant="outlined"
                    type="number"
                    min="0"
                    :disabled="!formData.oliveFruitFly"
                  />
                </v-col>

                <v-col cols="12" md="4">
                  <v-text-field
                    v-model.number="formData.oliveFruitFlyAdultCaptures"
                    label="Adult Captures"
                    variant="outlined"
                    type="number"
                    min="0"
                    :disabled="!formData.oliveFruitFly"
                  />
                </v-col>
              </v-row>
            </div>

            <!-- Olive Moth Section -->
            <div class="pest-section mt-6">
              <h4 class="text-h6 font-weight-bold mb-3 pb-2" style="border-bottom: 1px solid #e0e0e0;">
                Olive Moth
              </h4>

              <v-row>
                <v-col cols="12" md="4">
                  <v-switch
                    v-model="formData.oliveMoth"
                    label="Infestation Present"
                    color="primary"
                    inset
                  />
                </v-col>

                <v-col cols="12" md="4">
                  <v-text-field
                    v-model.number="formData.oliveMothLarvaeObservations"
                    label="Larvae Observations"
                    variant="outlined"
                    type="number"
                    min="0"
                    :disabled="!formData.oliveMoth"
                  />
                </v-col>

                <v-col cols="12" md="4">
                  <v-text-field
                    v-model.number="formData.oliveMothAdultCaptures"
                    label="Adult Captures"
                    variant="outlined"
                    type="number"
                    min="0"
                    :disabled="!formData.oliveMoth"
                  />
                </v-col>
              </v-row>
            </div>
          </div>

          <!-- Save Error -->
          <v-alert v-if="saveError" type="error" :text="saveError" class="mt-4" />
        </v-form>
      </v-card-text>

      <!-- Footer -->
      <v-card-actions class="modal-footer justify-end pa-4">
        <v-btn
          variant="outlined"
          @click="$emit('cancel')"
        >
          Cancel
        </v-btn>
        <v-btn
          color="primary"
          @click="handleSave"
          :loading="saving"
        >
          {{ action ? 'Update' : 'Add' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { actionsService } from '../../services/actionsService'
import { useParcelsStore } from '../../stores/parcels'

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
  modelValue: boolean
  action?: Action | null
  predefinedParcel?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'save': [data: any]
  'cancel': []
}>()

const form = ref()
const activeTab = ref('basic')
const saving = ref(false)
const saveError = ref('')
const parcelsStore = useParcelsStore()

const loadingParcels = computed(() => parcelsStore.loading)

const parcelOptions = computed(() =>
  parcelsStore.parcels.map(p => ({ title: p.name, value: p.id }))
)

const lockedParcelValue = computed(() => {
  if (props.predefinedParcel) return props.predefinedParcel
  if (props.action) return props.action.parcelId
  return ''
})

const formData = ref({
  type: 'plant-protection',
  cultivationPeriod: '2025',
  date: null as Date | null,
  parcels: [] as string[],
  title: '',
  time: '',
  targetInsect: '',
  activeIngredient: '',
  dosage: '',
  dosageUnit: '',
  sprayVolume: '',
  costs: {
    fuel: 0,
    materials: 0,
    labor: 0,
    other: 0
  },
  oliveFruitFly: false,
  oliveFruitFlyLarvaeObservations: 0,
  oliveFruitFlyAdultCaptures: 0,
  oliveMoth: false,
  oliveMothLarvaeObservations: 0,
  oliveMothAdultCaptures: 0
})

const actionTypeOptions = [
  { title: 'Plant Protection', value: 'plant-protection' },
  { title: 'Observation', value: 'observation' }
]

const cultivationPeriodOptions = [
  { title: '2026', value: '2026' },
  { title: '2025', value: '2025' },
  { title: '2024', value: '2024' },
  { title: '2023', value: '2023' }
]

const timeOptions = [
  '06:00','06:30','07:00','07:30','08:00','08:30','09:00','09:30',
  '10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30',
  '14:00','14:30','15:00','15:30','16:00','16:30','17:00','17:30',
  '18:00','18:30','19:00','19:30','20:00'
].map(t => ({ title: t, value: t }))

const dosageUnitOptions = [
  { title: 'ml/ha', value: 'ml/ha' },
  { title: 'kg/ha', value: 'kg/ha' },
  { title: 'g/ha', value: 'g/ha' },
  { title: 'L/ha', value: 'L/ha' }
]

const totalCost = computed(() => {
  return formData.value.costs.fuel +
         formData.value.costs.materials +
         formData.value.costs.labor +
         formData.value.costs.other
})

const formatDateForApi = (date: Date | string | null): string => {
  if (!date) return ''
  if (typeof date === 'string') return date
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

const loadParcels = () => parcelsStore.loadParcels()

const resetForm = () => {
  formData.value = {
    type: 'plant-protection',
    cultivationPeriod: '2025',
    date: null,
    parcels: props.predefinedParcel ? [props.predefinedParcel] : [],
    title: '',
    time: '',
    targetInsect: '',
    activeIngredient: '',
    dosage: '',
    dosageUnit: '',
    sprayVolume: '',
    costs: { fuel: 0, materials: 0, labor: 0, other: 0 },
    oliveFruitFly: false,
    oliveFruitFlyLarvaeObservations: 0,
    oliveFruitFlyAdultCaptures: 0,
    oliveMoth: false,
    oliveMothLarvaeObservations: 0,
    oliveMothAdultCaptures: 0
  }
  activeTab.value = 'basic'
  saveError.value = ''
}

const loadActionData = (action: Action) => {
  formData.value.type = action.type
  formData.value.date = action.date ? new Date(action.date) : null
  formData.value.parcels = [action.parcelId]
  formData.value.cultivationPeriod = action.details.cultivationPeriod || '2025'

  if (action.type === 'plant-protection') {
    formData.value.title = action.title || ''
    formData.value.time = action.details.time || '08:00'
    formData.value.targetInsect = action.details.targetInsect || ''
    formData.value.activeIngredient = action.details.activeIngredient || ''
    formData.value.dosage = action.details.dosage || ''
    formData.value.dosageUnit = action.details.dosageUnit || ''
    formData.value.sprayVolume = action.details.sprayVolume || ''
    formData.value.costs = { ...action.details.costs }
  } else {
    formData.value.title = action.title || ''
    formData.value.time = action.details.time || '08:00'
    formData.value.oliveFruitFly = action.details.oliveFruitFly?.active ?? false
    formData.value.oliveFruitFlyLarvaeObservations = Number(action.details.oliveFruitFly?.larvaeObservations) || 0
    formData.value.oliveFruitFlyAdultCaptures = Number(action.details.oliveFruitFly?.adultCaptures) || 0
    formData.value.oliveMoth = action.details.oliveMoth?.active ?? false
    formData.value.oliveMothLarvaeObservations = Number(action.details.oliveMoth?.larvaeObservations) || 0
    formData.value.oliveMothAdultCaptures = Number(action.details.oliveMoth?.adultCaptures) || 0
  }
  saveError.value = ''
}

const handleSave = async () => {
  const { valid } = await form.value.validate()
  if (!valid) return

  saving.value = true
  saveError.value = ''

  try {
    const scheduledDate = formatDateForApi(formData.value.date)

    if (props.action) {
      const basePayload = {
        scheduledDate,
        responsible: 'Farmer',
        cultivation_period: formData.value.cultivationPeriod,
        time: formData.value.time
      }

      const fullPayload = formData.value.type === 'plant-protection'
        ? {
            ...basePayload,
            title: formData.value.targetInsect || 'Plant Protection',
            description: `Active ingredient: ${formData.value.activeIngredient}`,
            target_insect: formData.value.targetInsect,
            active_ingredient: formData.value.activeIngredient,
            dosage: formData.value.dosage,
            unit: formData.value.dosageUnit,
            spray_solution_volume: formData.value.sprayVolume,
            fuel: String(formData.value.costs.fuel),
            materials: String(formData.value.costs.materials),
            labor: String(formData.value.costs.labor),
            other: String(formData.value.costs.other)
          }
        : {
            ...basePayload,
            title: 'Observation',
            description: 'Observation',
            olive_fruit_fly: formData.value.oliveFruitFly,
            olive_fruit_fly_larvae_observations: String(formData.value.oliveFruitFlyLarvaeObservations),
            olive_fruit_fly_adult_captures: String(formData.value.oliveFruitFlyAdultCaptures),
            olive_molth: formData.value.oliveMoth,
            olive_molth_larvae_observations: String(formData.value.oliveMothLarvaeObservations),
            olive_molth_adult_captures: String(formData.value.oliveMothAdultCaptures)
          }

      await actionsService.updateAction(props.action.id, fullPayload)
      emit('save', [])
    } else {
      // Create action for each selected parcel
      const parcelIds = props.predefinedParcel
        ? [props.predefinedParcel]
        : formData.value.parcels

      const results = await Promise.all(parcelIds.map(parcelId => {
        if (formData.value.type === 'plant-protection') {
          return actionsService.createAction(parcelId, {
            parcelId,
            type: 'plant_protection',
            title: formData.value.targetInsect || 'Plant Protection',
            scheduledDate,
            description: `Active ingredient: ${formData.value.activeIngredient}`,
            responsible: 'Farmer',
            cultivation_period: formData.value.cultivationPeriod,
            time: formData.value.time,
            target_insect: formData.value.targetInsect,
            active_ingredient: formData.value.activeIngredient,
            dosage: formData.value.dosage,
            unit: formData.value.dosageUnit,
            spray_solution_volume: formData.value.sprayVolume,
            fuel: String(formData.value.costs.fuel),
            materials: String(formData.value.costs.materials),
            labor: String(formData.value.costs.labor),
            other: String(formData.value.costs.other)
          })
        } else {
          return actionsService.createAction(parcelId, {
            parcelId,
            type: 'observation',
            title: 'Observation',
            scheduledDate,
            responsible: 'Farmer',
            cultivation_period: formData.value.cultivationPeriod,
            time: formData.value.time,
            olive_fruit_fly: formData.value.oliveFruitFly,
            olive_fruit_fly_larvae_observations: String(formData.value.oliveFruitFlyLarvaeObservations),
            olive_fruit_fly_adult_captures: String(formData.value.oliveFruitFlyAdultCaptures),
            olive_molth: formData.value.oliveMoth,
            olive_molth_larvae_observations: String(formData.value.oliveMothLarvaeObservations),
            olive_molth_adult_captures: String(formData.value.oliveMothAdultCaptures)
          })
        }
      }))

      emit('save', results)
    }
  } catch (e: any) {
    saveError.value = e?.message || 'Failed to save action'
  } finally {
    saving.value = false
  }
}

watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    if (!parcelsStore.parcels.length) loadParcels()
    if (props.action) {
      loadActionData(props.action)
    } else {
      resetForm()
    }
  }
})

watch(() => props.action, (newAction) => {
  if (newAction && props.modelValue) {
    loadActionData(newAction)
  }
})

onMounted(() => {
  loadParcels()
})
</script>

<style scoped>
.add-action-modal {
  max-height: 90vh;
}

.modal-header {
  position: sticky;
  top: 0;
  z-index: 10;
  background: white;
  border-bottom: 1px solid #e0e0e0;
}

.modal-body {
  max-height: calc(90vh - 140px);
  overflow-y: auto;
}

.modal-footer {
  position: sticky;
  bottom: 0;
  z-index: 10;
  background: white;
  border-top: 1px solid #e0e0e0;
}

.always-visible-fields {
  margin-bottom: 24px;
}

.pest-section {
  background: rgba(var(--v-theme-surface), 0.5);
  border-radius: 8px;
  padding: 16px;
  border: 1px solid rgba(var(--v-theme-primary), 0.1);
}

.predefined-parcel-select :deep(.v-field) {
  pointer-events: none;
}

.locked-field :deep(.v-field) {
  pointer-events: none;
}

.locked-field :deep(.v-field):hover {
  box-shadow: none !important;
  border-color: rgba(var(--v-border-color), var(--v-border-opacity)) !important;
}

.locked-field :deep(.v-field--focused) {
  box-shadow: none !important;
  border-color: rgba(var(--v-border-color), var(--v-border-opacity)) !important;
}

.locked-field :deep(.v-field--readonly) {
  opacity: 1 !important;
}

.modal-body::-webkit-scrollbar {
  width: 4px;
}

.modal-body::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.modal-body::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 2px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}
</style>
