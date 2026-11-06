<template>
  <v-card
    class="parcel-card mb-4"
    elevation="6"
    @click="$emit('parcel-click', parcel.id)"
    hover
  >
    <div class="card-content d-flex pa-4">
      <div class="parcel-data flex-grow-1">
        <!-- Header Row -->
        <div class="d-flex justify-space-between align-start mb-3">
          <div>
            <h3 class="text-h6 font-weight-bold text-primary mb-1">
              {{ parcel.name }}
            </h3>
            <div class="d-flex align-center gap-2">
              <v-chip size="x-small" variant="tonal" color="primary">
                {{ parcel.cropType }}
              </v-chip>
              <span class="text-body-2 text-medium-emphasis">{{ parcel.area }} ha</span>
            </div>
          </div>

          <!-- Weather Badge -->
          <div v-if="weather" class="weather-badge d-flex align-center">
            <v-icon
              :icon="weatherIcon"
              :color="weatherColor"
              size="22"
              class="mr-1"
            />
            <span class="weather-temp">{{ Math.round(weather.temperature.mean) }}°</span>
          </div>
        </div>

        <!-- Pest Mini Badges -->
        <div v-if="latestPrediction" class="pest-row d-flex align-center gap-2 mb-2">
          <div class="pest-mini-item d-flex align-center gap-1">
            <v-icon icon="mdi-bug-outline" size="13" class="text-medium-emphasis" />
            <span class="pest-mini-label">Fly</span>
            <span class="pest-mini-count" :class="`pest-mini--${riskColor(latestPrediction['bactrocera-oleae'])}`">
              {{ latestPrediction['bactrocera-oleae'] }}
            </span>
            <span class="pest-mini-risk" :class="`pest-mini-risk--${riskColor(latestPrediction['bactrocera-oleae'])}`">
              {{ riskLabel(latestPrediction['bactrocera-oleae']) }}
            </span>
          </div>
          <div class="pest-mini-divider" />
          <div class="pest-mini-item d-flex align-center gap-1">
            <v-icon icon="mdi-butterfly-outline" size="13" class="text-medium-emphasis" />
            <span class="pest-mini-label">Moth</span>
            <span class="pest-mini-count" :class="`pest-mini--${riskColor(latestPrediction['prays-olae'])}`">
              {{ latestPrediction['prays-olae'] }}
            </span>
            <span class="pest-mini-risk" :class="`pest-mini-risk--${riskColor(latestPrediction['prays-olae'])}`">
              {{ riskLabel(latestPrediction['prays-olae']) }}
            </span>
          </div>
        </div>

        <!-- Location Info -->
        <div class="location-row d-flex align-center mt-2" style="border-top: 1px solid #e0e0e0; padding-top: 8px;">
          <v-icon icon="mdi-map-marker-outline" size="16" color="grey" class="mr-1" />
          <span class="text-body-2 text-medium-emphasis">
            {{ parcel.coordinates[0].toFixed(5) }}, {{ parcel.coordinates[1].toFixed(5) }}
          </span>
        </div>
      </div>
    </div>
  </v-card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Parcel } from '../stores/parcels'
import type { DailyWeather } from '../services/weatherService'
import { getWeatherIcon, getWeatherIconColor } from '../utils/weatherIcons'

const props = defineProps<{
  parcel: Parcel
  weather?: DailyWeather | null
  latestPrediction?: { 'bactrocera-oleae': number; 'prays-olae': number } | null
}>()

defineEmits<{
  'parcel-click': [id: string]
  'image-click': [id: string]
}>()

const weatherIcon = computed(() => props.weather ? getWeatherIcon(props.weather.icon) : '')
const weatherColor = computed(() => props.weather ? getWeatherIconColor(props.weather.icon) : '')

function riskColor(value: number) {
  if (value <= 5) return 'success'
  if (value <= 15) return 'warning'
  if (value <= 30) return 'high'
  return 'critical'
}

function riskLabel(value: number) {
  if (value <= 5) return 'Low'
  if (value <= 15) return 'Moderate'
  if (value <= 30) return 'High'
  return 'Critical'
}
</script>

<style scoped>
.parcel-card {
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  border-left: 4px solid rgb(var(--v-theme-primary));
  overflow: hidden;
}

.parcel-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
}

.parcel-card {
  box-shadow: 0 10px 13px -6px rgba(0, 0, 0, 0.08), 0 20px 31px 3px rgba(0, 0, 0, 0.06), 0 8px 38px 7px rgba(0, 0, 0, 0.04) !important;
}

.parcel-data {
  min-width: 0;
  flex: 1;
}

.gap-1 { gap: 4px; }
.gap-2 { gap: 8px; }

.weather-badge {
  flex-shrink: 0;
  align-items: center;
}

.weather-temp {
  font-size: 13px;
  font-weight: 600;
  color: #455a64;
  line-height: 1;
  white-space: nowrap;
}

.pest-row {
  flex-wrap: wrap;
}

.pest-mini-item {
  display: flex;
  align-items: center;
}

.pest-mini-label {
  font-size: 11px;
  color: #78909c;
  font-weight: 500;
}

.pest-mini-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 22px;
  height: 18px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
  padding: 0 5px;
}

.pest-mini-risk {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 16px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 400;
  padding: 0 5px;
}

.pest-mini-divider {
  width: 1px;
  height: 16px;
  background: #e0e0e0;
}

.pest-mini--success  { background: #e8f5e9; color: #2e7d32; }
.pest-mini--warning  { background: #fff3e0; color: #e65100; }
.pest-mini--high     { background: #ffebee; color: #e53935; }
.pest-mini--critical { background: #fce4ec; color: #7f0000; }

.pest-mini-risk--success  { background: #43a047; color: #fff; }
.pest-mini-risk--warning  { background: #fb8c00; color: #fff; }
.pest-mini-risk--high     { background: #e53935; color: #fff; }
.pest-mini-risk--critical { background: #7f0000; color: #fff; }
</style>
