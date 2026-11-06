<template>
  <v-app>
    <AppHeader />

    <v-main>
      <div v-if="parcel" class="parcel-detail-page">
        <div class="parcel-header sticky-header">
          <v-container fluid class="pa-4">
            <v-btn
              variant="text"
              @click="$router.push('/dashboard')"
              class="mb-4"
            >
              <template v-slot:prepend>
                <v-icon icon="mdi-arrow-left" />
              </template>
              Back to Dashboard
            </v-btn>

            <div class="d-flex justify-space-between align-start">
              <div>
                <h1 class="text-h5 font-weight-bold text-primary mb-2">{{ parcel.name }}</h1>
                <div class="d-flex align-center gap-4">
                  <v-chip size="small" variant="tonal" color="primary">
                    {{ parcel.cropType }}
                  </v-chip>
                  <span class="text-body-1 text-medium-emphasis">{{ parcel.area }} ha</span>
                  <span class="text-body-2 text-medium-emphasis">
                    <v-icon icon="mdi-map-marker-outline" size="16" class="mr-1" />
                    {{ parcel.coordinates[0].toFixed(5) }}, {{ parcel.coordinates[1].toFixed(5) }}
                  </span>
                </div>
              </div>

              <v-btn color="primary" size="large" @click="handleAddAction">
                <template v-slot:prepend>
                  <v-icon icon="mdi-plus" />
                </template>
                Add Action
              </v-btn>
            </div>
          </v-container>
        </div>

        <div class="tabs-container">
          <v-container fluid class="pa-0">
            <v-tabs v-model="activeTab" class="tabs-header">
              <v-tab value="weather">Weather & Agro Data</v-tab>
              <v-tab value="actions">Actions & History</v-tab>
              <v-tab value="pests">Pest Monitoring</v-tab>
            </v-tabs>

            <v-tabs-window v-model="activeTab" class="tabs-content">
              <v-tabs-window-item value="weather">
                <WeatherTab :parcel="parcel" />
              </v-tabs-window-item>

              <v-tabs-window-item value="actions">
                <ActionsTab :parcel="parcel" />
              </v-tabs-window-item>

              <v-tabs-window-item value="pests">
                <PestsTab :parcel="parcel" />
              </v-tabs-window-item>
            </v-tabs-window>
          </v-container>
        </div>
      </div>

      <v-container v-else class="d-flex justify-center align-center" style="height: 50vh;">
        <div class="text-center">
          <v-progress-circular indeterminate size="48" class="mb-4" />
          <div class="text-h6">Loading parcel details...</div>
        </div>
      </v-container>

      <AddActionModal
        v-model="showAddModal"
        :action="null"
        :predefined-parcel="parcel?.id"
        @save="handleSaveAction"
        @cancel="handleCancelModal"
      />
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useParcelsStore } from '../stores/parcels'
import { useActionsStore } from '../stores/actions'
import AppHeader from '../components/AppHeader.vue'
import WeatherTab from '../components/tabs/WeatherTab.vue'
import ActionsTab from '../components/tabs/ActionsTab.vue'
import PestsTab from '../components/tabs/PestsTab.vue'
import AddActionModal from '../components/actions/AddActionModal.vue'

const route = useRoute()
const parcelsStore = useParcelsStore()
const actionsStore = useActionsStore()
const activeTab = ref('weather')
const showAddModal = ref(false)

const parcel = computed(() => parcelsStore.getParcelById(route.params.id as string))

const handleAddAction = () => {
  showAddModal.value = true
}

const handleSaveAction = async () => {
  await actionsStore.refresh()
  showAddModal.value = false
}

const handleCancelModal = () => {
  showAddModal.value = false
}
</script>

<style scoped>
.parcel-detail-page {
  background-color: #f5f5f5;
  min-height: calc(100vh - 64px);
}

.sticky-header {
  position: sticky;
  top: 64px;
  z-index: 10;
  background: white;
  border-bottom: 1px solid #e0e0e0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.tabs-container {
  background: white;
}

.tabs-header {
  border-bottom: 1px solid #e0e0e0;
}

.tabs-content {
  min-height: 600px;
}

.gap-4 {
  gap: 16px;
}
</style>
