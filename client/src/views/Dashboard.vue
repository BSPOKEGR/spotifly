<template>
  <v-app>
    <AppHeader />
    
    <v-main>
      <div class="dashboard-container">
        <div class="dashboard-layout">
          <!-- Left Panel - Parcels List -->
          <div class="left-panel">
            <!-- Parcels List -->
            <div class="parcels-list py-4">
              <div class="px-4">
                <ParcelCard
                  v-for="parcel in parcelsStore.filteredParcels"
                  :key="parcel.id"
                  :parcel="parcel"
                  :weather="todayWeather[parcel.id] ?? null"
                  :latest-prediction="latestPrediction"
                  @parcel-click="handleParcelClick"
                  @image-click="handleImageClick"
                />
              </div>

              <!-- Empty State -->
              <div v-if="parcelsStore.filteredParcels.length === 0" class="empty-state pa-8 text-center">
               <v-icon icon="mdi-magnify" size="64" color="grey-lighten-2" class="mb-4" />
                <h3 class="text-h6 text-medium-emphasis mb-2">No parcels found</h3>
                <p class="text-body-2 text-medium-emphasis">
                  Try adjusting your filters to see more results
                </p>
              </div>
            </div>
          </div>

          <!-- Right Panel - Map -->
          <div class="right-panel">
            <div class="map-container">
              <ParcelMap
                :parcels="parcelsStore.filteredParcels"
                :selected-parcel-id="selectedParcelId"
                @parcel-click="handleParcelClick"
                ref="mapRef"
              />
            </div>
          </div>
        </div>
      </div>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useParcelsStore } from '../stores/parcels'
import AppHeader from '../components/AppHeader.vue'
import ParcelCard from '../components/ParcelCard.vue'
import ParcelMap from '../components/ParcelMap.vue'
import { fetchDailyWeather } from '../services/weatherService'
import type { DailyWeather } from '../services/weatherService'
import { pestService } from '../services/pestService'

const router = useRouter()
const authStore = useAuthStore()
const parcelsStore = useParcelsStore()

const selectedParcelId = ref<string>()
const mapRef = ref()
const todayWeather = ref<Record<string, DailyWeather>>({})
const latestPrediction = ref<{ 'bactrocera-oleae': number; 'prays-olae': number } | null>(null)

const loadWeatherForParcels = async (parcelIds: string[]) => {
  const results = await Promise.allSettled(
    parcelIds.map(id => fetchDailyWeather(id).then(days => ({ id, day: days[0] })))
  )
  results.forEach(r => {
    if (r.status === 'fulfilled' && r.value.day) {
      todayWeather.value[r.value.id] = r.value.day
    }
  })
}

onMounted(async () => {
  authStore.initAuth()
  await parcelsStore.loadParcels()
  const ids = parcelsStore.parcels.map(p => p.id)
  if (ids.length) loadWeatherForParcels(ids)
  try {
    const res = await pestService.getLatestPrediction()
    if (res.data) latestPrediction.value = res.data.response_data
  } catch {}
})


const handleParcelClick = (parcelId: string) => {
  router.push(`/parcel/${parcelId}`)
}

const handleImageClick = (parcelId: string) => {
  console.log('Dashboard received image click for parcel:', parcelId)
  selectedParcelId.value = parcelId
  if (mapRef.value && mapRef.value.zoomToParcel) {
    console.log('Calling zoomToParcel with:', parcelId)
    // Add a small delay to ensure map is ready
    setTimeout(() => {
      mapRef.value.zoomToParcel(parcelId)
    }, 100)
  }
}
</script>

<style scoped>
.dashboard-container {
  height: calc(100vh - 64px);
  background-color: #f5f5f5;
  display: flex;
  flex-direction: column;
}

.filters-section {
  width: 100%;
  background: white;
  border-bottom: 1px solid #e0e0e0;
  z-index: 10;
}

.dashboard-layout {
  display: flex;
  flex: 1;
  height: calc(100% - 80px);
}

.left-panel {
  width: 50%;
  background: white;
  border-right: 1px solid #e0e0e0;
  display: flex;
  flex-direction: column;
}

.right-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: white;
  overflow: hidden;
}

.panel-header {
  border-bottom: 1px solid #f0f0f0;
  background: linear-gradient(135deg, rgba(46, 125, 50, 0.03) 0%, rgba(76, 175, 80, 0.03) 100%);
}

.map-header {
  border-bottom: 1px solid #f0f0f0;
  background: linear-gradient(135deg, rgba(46, 125, 50, 0.03) 0%, rgba(76, 175, 80, 0.03) 100%);
}

.parcels-list {
  flex: 1;
  overflow-y: auto;
}

.map-container {
  flex: 1;
}

.empty-state {
  margin-top: 40px;
}

/* Scrollbar Styling */
.parcels-list::-webkit-scrollbar {
  width: 4px;
}

.parcels-list::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.parcels-list::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 2px;
}

.parcels-list::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .left-panel {
    width: 45%;
  }
}

@media (max-width: 960px) {
  .dashboard-layout {
    flex-direction: column;
  }
  
  .left-panel {
    width: 100%;
    height: 50vh;
    border-right: none;
    border-bottom: 1px solid #e0e0e0;
  }
  
  .right-panel {
    margin: 0;
    border-radius: 0;
    height: 50vh;
  }
}
</style>