import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { actionsService, mapApiActionToLocal } from '../services/actionsService'
import { useParcelsStore } from './parcels'

export interface Action {
  id: string
  type: 'plant-protection' | 'observation'
  parcelId: string
  parcelName: string
  date: string
  title?: string
  details: any
  createdAt: string
}

export const useActionsStore = defineStore('actions', () => {
  const actions = ref<Action[]>([])
  const parcelNameMap = ref<Record<string, string>>({})
  const loading = ref(false)
  const error = ref('')

  const getByParcel = computed(() => (parcelId: string) =>
    actions.value.filter(a => a.parcelId === parcelId)
  )

  const load = async (parcelId?: string, parcelName?: string) => {
    const parcelsStore = useParcelsStore()
    loading.value = true
    error.value = ''
    try {
      await parcelsStore.loadParcels()
      const actionsRes = await actionsService.getActions(parcelId)
      const nameMap: Record<string, string> = {}
      parcelsStore.parcels.forEach(p => { nameMap[p.id] = p.name })
      parcelNameMap.value = nameMap
      const mapped = (actionsRes.data || []).map((a: any) =>
        mapApiActionToLocal(a, nameMap[a.parcelId] || a.parcel?.name || parcelName || '')
      )
      if (parcelId) {
        actions.value = [
          ...actions.value.filter(a => a.parcelId !== parcelId),
          ...mapped
        ]
      } else {
        actions.value = mapped
      }
    } catch (e: any) {
      error.value = e?.message || 'Failed to load actions'
    } finally {
      loading.value = false
    }
  }

  const remove = async (actionId: string) => {
    await actionsService.deleteAction(actionId)
    actions.value = actions.value.filter(a => a.id !== actionId)
  }

  const removeMany = async (actionIds: string[]) => {
    await Promise.all(actionIds.map(id => actionsService.deleteAction(id)))
    actions.value = actions.value.filter(a => !actionIds.includes(a.id))
  }

  const refresh = (parcelId?: string, parcelName?: string) => load(parcelId, parcelName)

  return {
    actions,
    parcelNameMap,
    loading,
    error,
    getByParcel,
    load,
    remove,
    removeMany,
    refresh
  }
})
