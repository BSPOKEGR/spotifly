import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { fetchParcels } from '../services/parcelService'

export interface Parcel {
  id: string
  name: string
  cropType: string
  area: number
  coordinates: [number, number]
  polygon?: [number, number][]
}

export const useParcelsStore = defineStore('parcels', () => {
  const loading = ref(false)
  const error = ref<string | null>(null)
  const parcels = ref<Parcel[]>([])

  const filters = ref({
    cropType: ''
  })

  const filteredParcels = computed(() => {
    if (!filters.value.cropType) return parcels.value
    return parcels.value.filter(p =>
      p.cropType.toLowerCase().includes(filters.value.cropType.toLowerCase())
    )
  })

  const updateFilters = (newFilters: Partial<typeof filters.value>) => {
    filters.value = { ...filters.value, ...newFilters }
  }

  const getParcelById = (id: string) => {
    return parcels.value.find(p => p.id === id)
  }

  let _loadPromise: Promise<void> | null = null

  const loadParcels = async (force = false) => {
    if (!force && parcels.value.length > 0) return
    if (_loadPromise) return _loadPromise
    loading.value = true
    error.value = null
    _loadPromise = (async () => {
      try {
        const apiParcels = await fetchParcels()
        parcels.value = apiParcels.map(p => ({
          id: p.id,
          name: p.name,
          cropType: p.crop,
          area: p.area,
          coordinates: [p.location.lat, p.location.lng] as [number, number],
          polygon: p.polygon
        }))
      } catch (err: any) {
        error.value = err?.message ?? 'Failed to load parcels'
      } finally {
        loading.value = false
        _loadPromise = null
      }
    })()
    return _loadPromise
  }

  return {
    parcels,
    loading,
    error,
    filters,
    filteredParcels,
    updateFilters,
    getParcelById,
    loadParcels
  }
})
