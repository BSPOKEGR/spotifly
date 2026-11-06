<template>
  <v-container fluid class="pa-6 pests-tab">

    <!-- Loading State -->
    <div v-if="loading" class="d-flex justify-center align-center pa-16">
      <div class="text-center">
        <v-progress-circular indeterminate color="primary" size="48" class="mb-4" />
        <div class="text-body-1 text-medium-emphasis">Loading pest data...</div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="d-flex justify-center align-center pa-16">
      <div class="text-center">
        <v-icon icon="mdi-alert-circle-outline" size="64" color="error" class="mb-4" />
        <div class="text-h6 mb-2">Failed to load pest data</div>
        <div class="text-body-2 text-medium-emphasis mb-4">{{ error }}</div>
        <v-btn color="primary" variant="tonal" @click="loadData">Retry</v-btn>
      </div>
    </div>

    <template v-else>
      <!-- Alerts Section -->
      <div class="section-header d-flex align-center justify-space-between mb-4">
        <div class="d-flex align-center gap-2">
          <div class="section-icon-wrap alert-icon">
            <v-icon icon="mdi-bell-ring" size="18" color="error" />
          </div>
          <span class="text-h6 font-weight-bold">Last Active Alerts</span>
          <v-chip
            v-if="unreadAlerts.length > 0"
            size="x-small"
            color="error"
            variant="flat"
            class="ml-1"
          >
            {{ unreadAlerts.length }} new
          </v-chip>
        </div>
      </div>

      <div v-if="alerts.length === 0" class="empty-card mb-6">
        <v-icon icon="mdi-bell-check-outline" size="32" color="success" class="mb-2" />
        <div class="text-body-1 font-weight-medium">No active alerts</div>
        <div class="text-body-2 text-medium-emphasis">All pest levels are within safe ranges</div>
      </div>

      <div v-else class="alerts-list mb-6">
        <div
          v-for="alert in alerts"
          :key="alert.id"
          class="alert-card"
          :class="[`alert-card--${alert.severity.toLowerCase()}`, { 'alert-card--read': alert.read }]"
        >
          <div class="alert-severity-bar" />
          <div class="alert-content">
            <div class="alert-top">
              <div class="d-flex align-center gap-2">
                <v-icon :icon="pestIcon(alert.type)" size="20" :color="severityColor(alert.severity)" />
                <span class="text-body-1 font-weight-semibold">{{ pestLabel(alert.type) }}</span>
                <v-chip
                  :color="severityColor(alert.severity)"
                  size="x-small"
                  variant="flat"
                  class="font-weight-bold"
                >
                  {{ alert.severity }}
                </v-chip>
                <v-chip v-if="!alert.read" color="error" size="x-small" variant="tonal">
                  Unread
                </v-chip>
              </div>
              <div class="d-flex align-center gap-2">
                <span class="text-caption text-medium-emphasis">{{ formatDate(alert.date) }}</span>
                <v-btn
                  v-if="!alert.read"
                  size="x-small"
                  variant="tonal"
                  color="primary"
                  :loading="acknowledging === alert.id"
                  @click="acknowledge(alert)"
                >
                  Acknowledge
                </v-btn>
              </div>
            </div>
            <div class="text-body-2 mt-1 alert-message">{{ alert.message }}</div>
            <div class="text-caption text-medium-emphasis mt-1">
              <v-icon icon="mdi-map-marker-outline" size="12" class="mr-1" />
              {{ alert.parcel_name }}
            </div>
          </div>
        </div>
      </div>

      <!-- Current Risk Levels -->
      <div class="section-header d-flex align-center gap-2 mb-4">
        <div class="section-icon-wrap prediction-icon">
          <v-icon icon="mdi-chart-line" size="18" color="primary" />
        </div>
        <span class="text-h6 font-weight-bold">Current Risk Levels</span>
        <span v-if="latestPrediction" class="text-caption text-medium-emphasis ml-auto">
          Updated {{ formatDateTime(latestPrediction.fetched_at) }}
        </span>
      </div>

      <div v-if="!latestPrediction" class="empty-card mb-6">
        <v-icon icon="mdi-chart-line-variant" size="32" color="grey-lighten-1" class="mb-2" />
        <div class="text-body-1 font-weight-medium">No prediction data</div>
        <div class="text-body-2 text-medium-emphasis">ML predictions will appear once data is available</div>
      </div>

      <v-row v-else class="mb-6" dense>
        <v-col cols="12" sm="6">
          <div class="pest-risk-card">
            <div class="pest-risk-header">
              <div class="pest-avatar olive-fly">
                <v-icon icon="mdi-bug" size="22" color="white" />
              </div>
              <div>
                <div class="text-body-1 font-weight-bold">Olive Fruit Fly</div>
                <div class="text-caption text-medium-emphasis pest-scientific">Bactrocera oleae</div>
              </div>
              <v-chip
                :style="{ background: riskHex(latestPrediction.response_data['bactrocera-oleae']), color: '#fff' }"
                size="small"
                variant="flat"
                class="ml-auto"
              >
                {{ riskLabel(latestPrediction.response_data['bactrocera-oleae']) }}
              </v-chip>
            </div>

            <div class="pest-risk-meter mt-3">
              <div class="d-flex justify-space-between mb-1">
                <span class="text-caption text-medium-emphasis">Population Index</span>
                <span class="text-caption font-weight-bold">{{ latestPrediction.response_data['bactrocera-oleae'] }}</span>
              </div>
              <v-progress-linear
                :model-value="normalizeRisk(latestPrediction.response_data['bactrocera-oleae'])"
                :color="riskHex(latestPrediction.response_data['bactrocera-oleae'])"
                height="10"
                rounded
                bg-color="grey-lighten-3"
              />
              <div class="risk-scale d-flex justify-space-between mt-1">
                <span class="text-caption text-medium-emphasis">Low</span>
                <span class="text-caption text-medium-emphasis">Critical</span>
              </div>
            </div>
          </div>
        </v-col>

        <v-col cols="12" sm="6">
          <div class="pest-risk-card">
            <div class="pest-risk-header">
              <div class="pest-avatar prays">
                <v-icon icon="mdi-bug-outline" size="22" color="white" />
              </div>
              <div>
                <div class="text-body-1 font-weight-bold">Olive Moth</div>
                <div class="text-caption text-medium-emphasis pest-scientific">Prays oleae</div>
              </div>
              <v-chip
                :style="{ background: riskHex(latestPrediction.response_data['prays-olae']), color: '#fff' }"
                size="small"
                variant="flat"
                class="ml-auto"
              >
                {{ riskLabel(latestPrediction.response_data['prays-olae']) }}
              </v-chip>
            </div>

            <div class="pest-risk-meter mt-3">
              <div class="d-flex justify-space-between mb-1">
                <span class="text-caption text-medium-emphasis">Population Index</span>
                <span class="text-caption font-weight-bold">{{ latestPrediction.response_data['prays-olae'] }}</span>
              </div>
              <v-progress-linear
                :model-value="normalizeRisk(latestPrediction.response_data['prays-olae'])"
                :color="riskHex(latestPrediction.response_data['prays-olae'])"
                height="10"
                rounded
                bg-color="grey-lighten-3"
              />
              <div class="risk-scale d-flex justify-space-between mt-1">
                <span class="text-caption text-medium-emphasis">Low</span>
                <span class="text-caption text-medium-emphasis">Critical</span>
              </div>
            </div>
          </div>
        </v-col>
      </v-row>

      <!-- Historical Trend Chart -->
      <div class="section-header d-flex align-center gap-2 mb-4">
        <div class="section-icon-wrap history-icon">
          <v-icon icon="mdi-chart-bar" size="18" color="teal" />
        </div>
        <span class="text-h6 font-weight-bold">Population Trend</span>
        <div class="d-flex gap-2 ml-auto">
          <v-btn-toggle v-model="chartPest" density="compact" variant="outlined" color="primary" mandatory>
            <v-btn value="bactrocera" size="small">Olive Fly</v-btn>
            <v-btn value="prays" size="small">Olive Moth</v-btn>
          </v-btn-toggle>
        </div>
      </div>

      <div v-if="allPredictions.length === 0" class="empty-card mb-6">
        <v-icon icon="mdi-chart-timeline-variant" size="32" color="grey-lighten-1" class="mb-2" />
        <div class="text-body-1 font-weight-medium">No historical data</div>
        <div class="text-body-2 text-medium-emphasis">Trend data will appear as predictions accumulate</div>
      </div>

      <div v-else class="chart-card mb-6">
        <div class="bar-chart-area">
          <div class="bar-chart-y-axis">
            <span v-for="(val, i) in gridValues" :key="i" class="y-label">{{ val }}</span>
          </div>
          <div class="bar-chart-content">
            <div class="bar-chart-grid">
              <div v-for="n in gridCount + 1" :key="n" class="grid-line" />
            </div>
            <div class="bars-row">
              <div
                v-for="(pt, i) in chartData"
                :key="i"
                class="bar-col"
              >
                <v-tooltip :text="`${pt.label}: ${pt.value}`" location="top">
                  <template #activator="{ props: tooltipProps }">
                    <div
                      v-bind="tooltipProps"
                      class="bar-fill"
                      :style="{
                        height: `${(pt.value / maxVal) * 100}%`,
                        background: riskHex(pt.value)
                      }"
                    />
                  </template>
                </v-tooltip>
                <span class="bar-label">{{ pt.label }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Alerts Timeline (last 10 days) -->
      <div class="section-header d-flex align-center gap-2 mb-4">
        <div class="section-icon-wrap timeline-icon">
          <v-icon icon="mdi-calendar-alert" size="18" color="orange-darken-2" />
        </div>
        <span class="text-h6 font-weight-bold">Alerts Last 10 Days</span>
      </div>

      <div v-if="timelineLoading" class="d-flex justify-center align-center pa-8">
        <v-progress-circular indeterminate color="primary" size="36" />
      </div>

      <div v-else-if="timelineError" class="empty-card mb-6">
        <v-icon icon="mdi-alert-circle-outline" size="32" color="error" class="mb-2" />
        <div class="text-body-1 font-weight-medium">Failed to load timeline</div>
        <div class="text-body-2 text-medium-emphasis">{{ timelineError }}</div>
      </div>

      <div v-else-if="timelineData.length > 0" class="timeline-table-card mb-6">
        <!-- Header row: pest type labels as columns -->
        <div class="timeline-header-row">
          <div class="tl-pest-col tl-header-label">Pest / Disease</div>
          <div
            v-for="day in timelineData"
            :key="day.date"
            class="tl-day-col tl-header-date"
          >
            {{ formatTimelineDate(day.date) }}
          </div>
        </div>

        <!-- One row per pest type -->
        <div
          v-for="pest in timelinePestTypes"
          :key="pest.type"
          class="timeline-data-row"
        >
          <div class="tl-pest-col tl-pest-label">{{ pest.label }}</div>
          <div
            v-for="day in timelineData"
            :key="day.date"
            class="tl-day-col tl-cell"
          >
            <template v-if="getAlertForDay(day, pest.type)">
              <v-tooltip :text="getAlertForDay(day, pest.type)!.message" location="top" max-width="240">
                <template #activator="{ props: tp }">
                  <span
                    v-bind="tp"
                    class="tl-bug-icon"
                    :class="`tl-bug--${getAlertForDay(day, pest.type)!.severity.toLowerCase()}`"
                  >
                    <v-icon icon="mdi-bug" size="18" />
                  </span>
                </template>
              </v-tooltip>
            </template>
            <span v-else class="tl-empty-dash">–</span>
          </div>
        </div>
      </div>

      <div v-else class="empty-card mb-6">
        <v-icon icon="mdi-calendar-check-outline" size="32" color="success" class="mb-2" />
        <div class="text-body-1 font-weight-medium">No alerts in the last 10 days</div>
        <div class="text-body-2 text-medium-emphasis">All pest levels have been within safe ranges</div>
      </div>

    </template>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { Parcel } from '../../stores/parcels'
import { pestService, type Alert, type PestPrediction } from '../../services/pestService'
import { apiClient } from '../../services/api'

defineProps<{
  parcel: Parcel
}>()

const loading = ref(true)
const error = ref<string | null>(null)
const alerts = ref<Alert[]>([])
const latestPrediction = ref<PestPrediction | null>(null)
const allPredictions = ref<PestPrediction[]>([])
const acknowledging = ref<number | null>(null)
const chartPest = ref<'bactrocera' | 'prays'>('bactrocera')

interface TimelineAlert {
  id: number
  parcel_id: string
  parcel_name: string
  type: string
  severity: string
  message: string
  read: boolean
}

interface TimelineDay {
  date: string
  alerts: TimelineAlert[] | null
  message?: string
}

interface TimelineResponse {
  data: TimelineDay[]
}

const timelineLoading = ref(false)
const timelineError = ref<string | null>(null)
const timelineData = ref<TimelineDay[]>([])

const timelinePestTypes = [
  { type: 'olive_fruit_fly', label: 'Olive Fruit Fly' },
  { type: 'olive_moth', label: 'Olive Moth' },
]

function getAlertForDay(day: TimelineDay, pestType: string): TimelineAlert | null {
  if (!day.alerts) return null
  return day.alerts.find(a => a.type === pestType) ?? null
}

function formatTimelineDate(dateStr: string) {
  const d = new Date(dateStr)
  return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}`
}

async function loadTimeline() {
  timelineLoading.value = true
  timelineError.value = null
  try {
    const res = await apiClient.get<TimelineResponse>('/api/alerts/timeline')
    timelineData.value = res.data
  } catch (e: any) {
    timelineError.value = e?.message || 'Failed to load timeline'
  } finally {
    timelineLoading.value = false
  }
}

const unreadAlerts = computed(() => alerts.value.filter(a => !a.read))

async function loadData() {
  loading.value = true
  error.value = null
  try {
    const [alertsRes, latestRes, allRes] = await Promise.all([
      pestService.getAlerts(),
      pestService.getLatestPrediction(),
      pestService.getAllPredictions()
    ])
    alerts.value = alertsRes.data
    latestPrediction.value = latestRes.data
    allPredictions.value = allRes.data
  } catch (e: any) {
    error.value = e?.message || 'Unknown error occurred'
  } finally {
    loading.value = false
  }
}

async function acknowledge(alert: Alert) {
  acknowledging.value = alert.id
  try {
    await pestService.acknowledgeAlert(alert.id)
    alert.read = true
  } catch {}
  acknowledging.value = null
}

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function formatDateTime(dateStr: string) {
  return new Date(dateStr).toLocaleString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
}

function pestLabel(type: string) {
  const map: Record<string, string> = {
    olive_fruit_fly: 'Olive Fruit Fly',
    prays_oleae: 'Olive Moth',
    bactrocera_oleae: 'Olive Fruit Fly'
  }
  return map[type] ?? type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function pestIcon(type: string) {
  return type.includes('fly') || type.includes('bactrocera') ? 'mdi-bug' : 'mdi-bug-outline'
}

function severityColor(severity: string) {
  const map: Record<string, string> = {
    LOW: 'success',
    MEDIUM: 'warning',
    HIGH: '#e53935',
    CRITICAL: '#7f0000'
  }
  return map[severity] ?? 'grey'
}

function riskHex(value: number) {
  if (value <= 5) return '#43a047'
  if (value <= 15) return '#fb8c00'
  if (value <= 30) return '#e53935'
  return '#7f0000'
}


function riskLabel(value: number) {
  if (value <= 5) return 'Low'
  if (value <= 15) return 'Moderate'
  if (value <= 30) return 'High'
  return 'Critical'
}

function normalizeRisk(value: number) {
  return Math.min((value / 40) * 100, 100)
}


const gridCount = 4

const chartData = computed(() => {
  const sorted = [...allPredictions.value]
    .sort((a, b) => new Date(a.fetched_at).getTime() - new Date(b.fetched_at).getTime())
    .slice(-14)
  return sorted.map(p => ({
    value: chartPest.value === 'bactrocera'
      ? p.response_data['bactrocera-oleae']
      : p.response_data['prays-olae'],
    label: new Date(p.fetched_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' })
  }))
})

const maxVal = computed(() => Math.max(...chartData.value.map(d => d.value), 10))

const gridValues = computed(() =>
  Array.from({ length: gridCount + 1 }, (_, i) =>
    Math.round((maxVal.value / gridCount) * (gridCount - i))
  )
)

onMounted(() => {
  loadData()
  loadTimeline()
})
</script>

<style scoped>
.pests-tab {
  background: #f8f9fa;
  min-height: 600px;
}

.section-icon-wrap {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.alert-icon { background: rgba(239, 83, 80, 0.12); }
.prediction-icon { background: rgba(25, 118, 210, 0.12); }
.history-icon { background: rgba(38, 166, 154, 0.12); }
.table-icon { background: rgba(96, 125, 139, 0.12); }

.gap-1 { gap: 4px; }
.gap-2 { gap: 8px; }
.gap-4 { gap: 16px; }

.empty-card {
  background: white;
  border-radius: 16px;
  border: 1px dashed #e0e0e0;
  padding: 40px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.alerts-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.alert-card {
  background: white;
  border-radius: 14px;
  border: 1px solid #d0d0d0;
  display: flex;
  overflow: hidden;
  transition: box-shadow 0.2s;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  opacity: 1 !important;
}

.alert-card:hover {
  box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

.alert-card--read {
  opacity: 0.55;
  background: #fafafa;
}

.alert-severity-bar {
  width: 5px;
  flex-shrink: 0;
}

.alert-card--low .alert-severity-bar { background: #43a047; }
.alert-card--medium .alert-severity-bar { background: #fb8c00; }
.alert-card--high .alert-severity-bar { background: #e53935; }
.alert-card--critical .alert-severity-bar { background: #7f0000; }

.alert-content {
  padding: 14px 16px;
  flex: 1;
}

.alert-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px;
}

.alert-message {
  color: #555;
  line-height: 1.5;
}

.pest-risk-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  border: 1px solid #e8e8e8;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
  height: 100%;
}

.pest-risk-header {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pest-avatar {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.olive-fly { background: linear-gradient(135deg, #ef5350, #c62828); }
.prays { background: linear-gradient(135deg, #26a69a, #00695c); }

.pest-scientific {
  font-style: italic;
}

.pest-risk-meter {
  padding: 0 4px;
}

.risk-scale {
  opacity: 0.6;
}

.chart-card {
  background: white;
  border-radius: 16px;
  padding: 20px 20px 12px;
  border: 1px solid #e8e8e8;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}

.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.bar-chart-area {
  display: flex;
  gap: 8px;
  height: 220px;
  width: 100%;
}

.bar-chart-y-axis {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding-bottom: 28px;
  min-width: 32px;
  text-align: right;
}

.y-label {
  font-size: 0.7rem;
  color: #9e9e9e;
  line-height: 1;
}

.bar-chart-content {
  flex: 1;
  position: relative;
  display: flex;
  flex-direction: column;
}

.bar-chart-grid {
  position: absolute;
  inset: 0;
  bottom: 28px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  pointer-events: none;
}

.grid-line {
  width: 100%;
  height: 1px;
  background: #e0e0e0;
  border-top: 1px dashed #e0e0e0;
}

.bars-row {
  display: flex;
  align-items: flex-end;
  gap: 6px;
  height: calc(100% - 28px);
  padding: 0 4px;
}

.bar-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  height: 100%;
  gap: 4px;
}

.bar-fill {
  width: 100%;
  max-width: 40px;
  border-radius: 4px 4px 0 0;
  transition: height 0.3s ease;
  cursor: pointer;
  opacity: 0.85;
}

.bar-fill:hover {
  opacity: 1;
}

.bar-label {
  font-size: 0.65rem;
  color: #9e9e9e;
  text-align: center;
  white-space: nowrap;
  height: 24px;
  line-height: 24px;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}

.timeline-icon { background: rgba(245, 124, 0, 0.12); }

.timeline-table-card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e0e0e0;
  overflow-x: auto;
  box-shadow: 0 2px 8px rgba(0,0,0,0.07);
}

.timeline-header-row,
.timeline-data-row {
  display: flex;
  align-items: center;
  border-bottom: 1px solid #f2f2f2;
}

.timeline-data-row:last-child {
  border-bottom: none;
}

.timeline-header-row {
  background: #f7f7f7;
  border-bottom: 2px solid #e8e8e8;
}

.tl-pest-col {
  min-width: 160px;
  flex: 0 0 160px;
  padding: 12px 20px;
  font-size: 0.875rem;
}

.tl-day-col {
  flex: 1;
  min-width: 60px;
  text-align: center;
  padding: 10px 4px;
}

.tl-header-label {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #888;
}

.tl-header-date {
  font-size: 0.75rem;
  font-weight: 700;
  color: #555;
  letter-spacing: 0.03em;
}

.tl-pest-label {
  color: #444;
  font-weight: 500;
}

.tl-cell {
  display: flex;
  align-items: center;
  justify-content: center;
}

.tl-empty-dash {
  color: #bdbdbd;
  font-size: 1rem;
  font-weight: 400;
}

.tl-bug-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border-radius: 50%;
  padding: 2px;
  transition: transform 0.15s;
}

.tl-bug-icon:hover {
  transform: scale(1.2);
}

.tl-bug--low    { color: #43a047; }
.tl-bug--medium { color: #fb8c00; }
.tl-bug--high   { color: #e53935; }
.tl-bug--critical { color: #7f0000; }

.predictions-table-card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e0e0e0;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.07);
}

.table-header-row,
.table-data-row {
  display: grid;
  grid-template-columns: 2fr 1.5fr 1.5fr 1.2fr;
  align-items: center;
  padding: 14px 24px;
  gap: 12px;
}

.table-header-row {
  background: #f7f7f7;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #888;
  border-bottom: 2px solid #e8e8e8;
}

.table-data-row {
  border-bottom: 1px solid #f2f2f2;
  font-size: 0.9rem;
  transition: background 0.15s;
}

.table-data-row:last-child {
  border-bottom: none;
}

.table-data-row:hover {
  background: #f9fafb;
}

.col-date {
  color: #444;
  font-size: 0.875rem;
  font-weight: 500;
  display: flex;
  align-items: center;
}

.col-pest { display: flex; align-items: center; }
.col-risk { display: flex; align-items: center; }

.pest-count-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 40px;
  height: 28px;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 700;
  padding: 0 10px;
}

.pest-badge--success  { background: #e8f5e9; color: #2e7d32; }
.pest-badge--warning  { background: #fff3e0; color: #e65100; }
.pest-badge--high     { background: #ffebee; color: #e53935; }
.pest-badge--critical { background: #fce4ec; color: #7f0000; }
.pest-badge--error    { background: #ffebee; color: #e53935; }

.risk-label-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 80px;
  height: 28px;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  padding: 0 12px;
}

.inline-risk-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 20px;
  border-radius: 6px;
  font-size: 0.7rem;
  font-weight: 400;
  padding: 0 7px;
}

.risk-badge--success  { background: #43a047; color: #fff; }
.risk-badge--warning  { background: #fb8c00; color: #fff; }
.risk-badge--high     { background: #e53935; color: #fff; }
.risk-badge--critical { background: #7f0000; color: #fff; }
.risk-badge--error    { background: #e53935; color: #fff; }
</style>
