<template>
  <v-container fluid class="pa-6">
    <v-row>
      <v-col 
        v-for="metric in metrics" 
        :key="metric.title"
        cols="12" 
        sm="6" 
        lg="3"
      >
        <v-card 
          class="metric-card pa-6 text-center" 
          :class="`metric-card--${metric.type}`"
        >
          <!-- Value with Trend -->
          <div class="d-flex align-center justify-center mb-4">
            <div class="text-h3 font-weight-bold metric-value">{{ metric.value }}</div>
            <v-chip 
              :color="metric.trendColor" 
              size="small" 
              variant="flat"
              class="ml-2 trend-chip"
            >
              {{ metric.trend }}
            </v-chip>
          </div>
          
          <!-- Title and Subtitle -->
          <div class="text-h6 font-weight-medium mb-1">{{ metric.title }}</div>
          <div class="text-body-2 text-medium-emphasis">{{ metric.subtitle }}</div>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref } from 'vue'

import type { Parcel } from '../../stores/parcels'

defineProps<{
  parcel: Parcel
}>()

const metrics = ref([
  {
    title: 'Detection Accuracy',
    value: '94.2%',
    subtitle: 'AI Model Performance',
    trend: '+2.1%',
    trendColor: 'success',
    type: 'success'
  },
  {
    title: 'Pesticide Reduction',
    value: '32%',
    subtitle: 'vs Traditional Methods',
    trend: '+5%',
    trendColor: 'info',
    type: 'info'
  },
  {
    title: 'Cost Savings',
    value: '€2,340',
    subtitle: 'This Season',
    trend: '+€340',
    trendColor: 'warning',
    type: 'warning'
  },
  {
    title: 'Edge Latency',
    value: '12ms',
    subtitle: 'Detection Speed',
    trend: '-3ms',
    trendColor: 'primary',
    type: 'primary'
  }
])
</script>

<style scoped>
.metric-card {
  height: 200px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 16px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 250, 252, 0.9) 100%);
  border: 1px solid rgba(0, 0, 0, 0.05);
  position: relative;
  overflow: hidden;
  box-shadow: 0 10px 13px -6px rgba(0, 0, 0, 0.08), 0 20px 31px 3px rgba(0, 0, 0, 0.06), 0 8px 38px 7px rgba(0, 0, 0, 0.04) !important;
}

.metric-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: transparent;
  transition: all 0.3s ease;
}

.metric-card--success::before {
  background: linear-gradient(90deg, #4CAF50, #66BB6A);
}

.metric-card--info::before {
  background: linear-gradient(90deg, #2196F3, #42A5F5);
}

.metric-card--warning::before {
  background: linear-gradient(90deg, #FF9800, #FFB74D);
}

.metric-card--primary::before {
  background: linear-gradient(90deg, #333333, #555555);
}

.metric-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
}

.metric-card:hover::before {
  height: 6px;
}

.metric-value {
  color: rgb(var(--v-theme-primary));
  transition: all 0.3s ease;
}

.trend-chip {
  font-size: 0.75rem;
  font-weight: 600;
  min-width: 50px;
}

/* Mobile Optimizations */
@media (max-width: 600px) {
  .metric-card {
    height: 180px;
  }

  .metric-value {
    font-size: 1.8rem !important;
  }
}
</style>