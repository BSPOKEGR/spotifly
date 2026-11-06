<template>
  <div class="map-container">
    <div ref="mapContainer" class="map" />

    <div class="map-controls">
      <v-btn
        icon
        size="small"
        color="white"
        elevation="2"
        class="mb-2"
        @click="resetView"
      >
        <v-icon icon="mdi-crosshairs-gps" />
      </v-btn>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, nextTick } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import type { Parcel } from '../stores/parcels'

delete (L.Icon.Default.prototype as any)._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
})

const props = defineProps<{
  parcels: Parcel[]
  selectedParcelId?: string
}>()

const emit = defineEmits<{
  'parcel-click': [id: string]
}>()

const mapContainer = ref<HTMLElement>()
let map: L.Map
let parcelLayers: (L.Marker | L.Polygon)[] = []

onMounted(async () => {
  await nextTick()
  initializeMap()
  updateLayers()
})

const initializeMap = () => {
  if (!mapContainer.value) return

  map = L.map(mapContainer.value, {
    center: [39.823, 22.038],
    zoom: 14,
    zoomControl: false
  })

  L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    attribution: '© Esri, Maxar, Earthstar Geographics, and the GIS User Community'
  }).addTo(map)

  L.control.zoom({ position: 'bottomright' }).addTo(map)
}

const updateLayers = () => {
  parcelLayers.forEach(layer => map.removeLayer(layer))
  parcelLayers = []

  props.parcels.forEach(parcel => {
    const popupContent = `
      <div style="font-family: sans-serif; min-width: 160px;">
        <strong style="font-size: 14px;">${parcel.name}</strong>
        <div style="margin-top: 6px; font-size: 13px; color: #555;">
          <div><b>Crop:</b> ${parcel.cropType}</div>
          <div><b>Area:</b> ${parcel.area} ha</div>
        </div>
      </div>
    `

    if (parcel.polygon && parcel.polygon.length >= 3) {
      const poly = L.polygon(parcel.polygon, {
        color: '#1976D2',
        weight: 2,
        opacity: 0.9,
        fillColor: '#1976D2',
        fillOpacity: 0.25
      })
        .bindPopup(popupContent)
        .on('click', () => emit('parcel-click', parcel.id))

      poly.addTo(map)
      parcelLayers.push(poly)
    } else {
      const marker = L.marker(parcel.coordinates)
        .bindPopup(popupContent)
        .on('click', () => emit('parcel-click', parcel.id))

      marker.addTo(map)
      parcelLayers.push(marker)
    }
  })

  if (parcelLayers.length > 0) {
    const group = new L.FeatureGroup(parcelLayers)
    map.fitBounds(group.getBounds().pad(0.15))
  }
}

const resetView = () => {
  if (parcelLayers.length > 0) {
    const group = new L.FeatureGroup(parcelLayers)
    map.fitBounds(group.getBounds().pad(0.15))
  }
}

const zoomToParcel = (parcelId: string) => {
  const parcel = props.parcels.find(p => p.id === parcelId)
  if (!parcel) return

  if (parcel.polygon && parcel.polygon.length >= 3) {
    const poly = L.polygon(parcel.polygon)
    map.fitBounds(poly.getBounds().pad(0.2), { animate: true })
  } else {
    map.setView(parcel.coordinates, 17, { animate: true, duration: 0.5 })
  }
}

watch(() => props.parcels, updateLayers, { deep: true })

watch(() => props.selectedParcelId, (newId) => {
  if (newId) zoomToParcel(newId)
})

defineExpose({ zoomToParcel })
</script>

<style scoped>
.map-container {
  position: relative;
  height: 100%;
  width: 100%;
}

.map {
  height: 100%;
  width: 100%;
}

.map-controls {
  position: absolute;
  top: 16px;
  right: 16px;
  z-index: 1000;
  display: flex;
  flex-direction: column;
}

:deep(.leaflet-popup-content-wrapper) {
  border-radius: 8px;
}
</style>
