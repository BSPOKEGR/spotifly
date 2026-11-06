<template>
  <v-container fluid class="pa-4">
    <!-- Loading -->
    <div v-if="loading" class="d-flex justify-center align-center pa-12">
      <v-progress-circular indeterminate size="48" />
    </div>

    <!-- Error -->
    <v-alert v-else-if="error" type="error" variant="tonal" class="mb-4">
      {{ error }}
    </v-alert>

    <v-row v-else>
      <!-- 5-Day Forecast -->
      <v-col cols="12">
        <v-card class="pa-4 parcel-detail-card">
          <v-card-title class="text-h5 font-weight-bold mb-4">5-Day Weather Forecast</v-card-title>
          <v-card-text>
            <div v-if="forecast.length > 0" class="weather-forecast-grid">
              <div
                v-for="(day, index) in forecast"
                :key="day.date"
                class="weather-day-card"
                :class="{ 'weather-day-card--today': index === 0 }"
              >
                <div class="weather-day-header">
                  <div class="text-body-1 font-weight-bold mb-1">{{ formatDayLabel(day.date, index) }}</div>
                  <div class="text-caption text-medium-emphasis">{{ formatDate(day.date) }}</div>
                </div>

                <div class="weather-icon-container mb-3">
                  <v-icon :icon="resolveIcon(day.icon)" :color="resolveIconColor(day.icon)" size="40" />
                </div>

                <div class="weather-condition mb-1">
                  <div class="text-caption font-weight-medium">{{ day.condition }}</div>
                </div>

                <div class="weather-temp mb-2">
                  <div class="text-h6 font-weight-bold">{{ Math.round(day.temperature.mean) }}°C</div>
                  <div class="text-caption text-medium-emphasis">
                    {{ Math.round(day.temperature.min) }}° / {{ Math.round(day.temperature.max) }}°
                  </div>
                </div>

                <div class="weather-details">
                  <div class="weather-detail-item">
                    <v-icon icon="mdi-water-percent" size="16" color="blue" class="mr-1" />
                    <span class="text-caption">{{ Math.round(day.humidity.mean) }}%</span>
                  </div>
                  <div class="weather-detail-item" v-if="day.precipitation.total > 0">
                    <v-icon icon="mdi-weather-rainy" size="16" color="blue-darken-1" class="mr-1" />
                    <span class="text-caption">{{ day.precipitation.total.toFixed(1) }}mm</span>
                  </div>
                  <div class="weather-detail-item">
                    <v-icon icon="mdi-weather-windy" size="16" color="blue-grey" class="mr-1" />
                    <span class="text-caption">{{ Math.round(day.wind_speed.mean) }} m/s</span>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center pa-8 text-medium-emphasis">
              No forecast data available
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Summary Cards -->
      <v-col cols="12" md="4" v-if="forecast.length > 0">
        <v-card class="pa-4 parcel-detail-card summary-card">
          <v-card-title class="text-h6 font-weight-bold">Temperature Range</v-card-title>
          <v-card-text>
            <div class="d-flex flex-column summary-gap mt-2">
              <div v-for="day in forecast" :key="'temp-' + day.date" class="d-flex justify-space-between align-center">
                <span class="text-body-2 text-medium-emphasis">{{ formatDate(day.date) }}</span>
                <div class="d-flex align-center gap-sm">
                  <v-chip size="x-small" color="blue-darken-1" variant="tonal">
                    {{ Math.round(day.temperature.min) }}°
                  </v-chip>
                  <span class="text-caption text-medium-emphasis">—</span>
                  <v-chip size="x-small" color="error" variant="tonal">
                    {{ Math.round(day.temperature.max) }}°
                  </v-chip>
                </div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="4" v-if="forecast.length > 0">
        <v-card class="pa-4 parcel-detail-card summary-card">
          <v-card-title class="text-h6 font-weight-bold">Precipitation</v-card-title>
          <v-card-text>
            <div class="d-flex flex-column summary-gap mt-2">
              <div v-for="day in forecast" :key="'rain-' + day.date" class="d-flex justify-space-between align-center">
                <span class="text-body-2 text-medium-emphasis">{{ formatDate(day.date) }}</span>
                <div class="d-flex align-center gap-xs">
                  <v-icon
                    :icon="day.precipitation.total > 0 ? 'mdi-weather-rainy' : 'mdi-weather-sunny'"
                    :color="day.precipitation.total > 0 ? 'blue-darken-1' : 'orange'"
                    size="16"
                  />
                  <span class="text-body-2 font-weight-medium">
                    {{ day.precipitation.total > 0 ? day.precipitation.total.toFixed(1) + ' mm' : 'None' }}
                  </span>
                </div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="4" v-if="forecast.length > 0">
        <v-card class="pa-4 parcel-detail-card summary-card">
          <v-card-title class="text-h6 font-weight-bold">Humidity & Wind</v-card-title>
          <v-card-text>
            <div class="d-flex flex-column summary-gap mt-2">
              <div v-for="day in forecast" :key="'hw-' + day.date" class="d-flex justify-space-between align-center">
                <span class="text-body-2 text-medium-emphasis">{{ formatDate(day.date) }}</span>
                <div class="d-flex align-center gap-md">
                  <div class="d-flex align-center">
                    <v-icon icon="mdi-water-percent" size="14" color="blue" class="mr-1" />
                    <span class="text-caption">{{ Math.round(day.humidity.mean) }}%</span>
                  </div>
                  <div class="d-flex align-center">
                    <v-icon icon="mdi-weather-windy" size="14" color="blue-grey" class="mr-1" />
                    <span class="text-caption">{{ Math.round(day.wind_speed.mean) }}m/s</span>
                  </div>
                </div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import type { Parcel } from '../../stores/parcels'
import { fetchDailyWeather } from '../../services/weatherService'
import type { DailyWeather } from '../../services/weatherService'
import { getWeatherIcon, getWeatherIconColor } from '../../utils/weatherIcons'

const props = defineProps<{
  parcel: Parcel
}>()

const loading = ref(false)
const error = ref<string | null>(null)
const forecast = ref<DailyWeather[]>([])

onMounted(async () => {
  loading.value = true
  error.value = null
  try {
    forecast.value = await fetchDailyWeather(props.parcel.id)
  } catch (err: any) {
    error.value = err?.message ?? 'Failed to load weather data'
  } finally {
    loading.value = false
  }
})

const formatDayLabel = (date: string, index: number): string => {
  if (index === 0) return 'Today'
  if (index === 1) return 'Tomorrow'
  return new Date(date).toLocaleDateString('en-US', { weekday: 'short' })
}

const formatDate = (date: string): string => {
  const d = new Date(date)
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  return `${day}/${month}`
}

const resolveIcon = getWeatherIcon
const resolveIconColor = getWeatherIconColor
</script>

<style scoped>
.parcel-detail-card {
  box-shadow: 0 10px 13px -6px rgba(0, 0, 0, 0.08), 0 20px 31px 3px rgba(0, 0, 0, 0.06), 0 8px 38px 7px rgba(0, 0, 0, 0.04) !important;
}

.summary-card {
  height: 100%;
}

.weather-forecast-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 16px;
}

.weather-day-card {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 250, 252, 0.9) 100%);
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 12px;
  padding: 16px 12px;
  text-align: center;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.weather-day-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
  border-color: rgba(var(--v-theme-primary), 0.2);
}

.weather-day-card--today {
  background: linear-gradient(135deg, rgba(46, 125, 50, 0.05) 0%, rgba(76, 175, 80, 0.05) 100%);
  border-color: rgba(76, 175, 80, 0.3);
}

.weather-day-card--today::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #4CAF50, #66BB6A);
}

.weather-day-header {
  margin-bottom: 12px;
}

.weather-icon-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 50px;
}

.weather-condition {
  min-height: 20px;
}

.weather-temp {
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  padding-bottom: 8px;
}

.weather-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-top: 8px;
}

.weather-detail-item {
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0.85;
}

.summary-gap {
  gap: 10px;
}

.gap-xs {
  gap: 4px;
}

.gap-sm {
  gap: 6px;
}

.gap-md {
  gap: 12px;
}

@media (max-width: 768px) {
  .weather-forecast-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
  }

  .weather-day-card {
    padding: 12px 8px;
  }

  .weather-icon-container {
    height: 40px;
  }
}

@media (max-width: 480px) {
  .weather-forecast-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
