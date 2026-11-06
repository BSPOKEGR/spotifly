import { apiClient } from './api'

export interface ApiActionMetadata {
  cultivation_period?: string
  time?: string
  action_type?: string
  target_insect?: string
  active_ingredient?: string
  dosage?: string
  unit?: string
  spray_solution_volume?: string
  fuel?: string
  materials?: string
  labor?: string
  other?: string
  olive_fruit_fly?: boolean
  olive_fruit_fly_larvae_observations?: string
  olive_fruit_fly_adult_captures?: string
  olive_molth?: boolean
  olive_molth_larvae_observations?: string
  olive_molth_adult_captures?: string
}

export interface ApiAction {
  id: string
  parcelId: string
  parcel: any
  type: string
  title: string
  description: string
  scheduledDate: string
  completedDate: string | null
  responsible: string
  linkedAlerts: any[]
  metadata?: ApiActionMetadata
}

export interface ApiActionResponse {
  data: ApiAction
  message?: string
}

export interface ActionsListResponse {
  data: ApiAction[]
}

export interface CreatePlantProtectionPayload {
  parcelId: string
  type: 'plant_protection'
  title: string
  scheduledDate: string
  description: string
  responsible: string
  cultivation_period: string
  time: string
  target_insect: string
  active_ingredient: string
  dosage: string
  unit: string
  spray_solution_volume: string
  fuel: string
  materials: string
  labor: string
  other: string
}

export interface CreateObservationPayload {
  parcelId: string
  type: 'observation'
  title: string
  scheduledDate: string
  responsible: string
  cultivation_period: string
  time: string
  olive_fruit_fly: boolean
  olive_fruit_fly_larvae_observations: string
  olive_fruit_fly_adult_captures: string
  olive_molth: boolean
  olive_molth_larvae_observations: string
  olive_molth_adult_captures: string
}

export interface UpdateActionPayload {
  title?: string
  description?: string
  scheduledDate?: string
  responsible?: string
  completedDate?: string
  time?: string
  cultivation_period?: string
  target_insect?: string
  active_ingredient?: string
  dosage?: string
  unit?: string
  spray_solution_volume?: string
  fuel?: string
  materials?: string
  labor?: string
  other?: string
  olive_fruit_fly?: boolean
  olive_fruit_fly_larvae_observations?: string
  olive_fruit_fly_adult_captures?: string
  olive_molth?: boolean
  olive_molth_larvae_observations?: string
  olive_molth_adult_captures?: string
}

export const actionsService = {
  async getActions(parcelId?: string): Promise<ActionsListResponse> {
    const query = parcelId ? `?parcel_id=${parcelId}` : ''
    return apiClient.get<ActionsListResponse>(`/api/actions${query}`)
  },

  async createAction(parcelId: string, payload: CreatePlantProtectionPayload | CreateObservationPayload): Promise<ApiAction> {
    const res = await apiClient.post<ApiActionResponse>(`/api/actions?parcel_id=${parcelId}`, payload)
    return res.data
  },

  async updateAction(actionId: string, payload: UpdateActionPayload): Promise<ApiAction> {
    const res = await apiClient.put<ApiActionResponse>(`/api/actions/${actionId}`, payload)
    return res.data
  },

  deleteAction(actionId: string): Promise<void> {
    return apiClient.delete<void>(`/api/actions/${actionId}`)
  }
}

export function mapApiActionToLocal(apiAction: ApiAction, parcelName: string) {
  const meta = apiAction.metadata || {}
  const actionType = (meta.action_type || apiAction.type || '').toLowerCase().replace(/[-_\s]/g, '')
  const isPlantProtection = actionType === 'plantprotection' || actionType === 'spraying'
  const localType: 'plant-protection' | 'observation' = isPlantProtection ? 'plant-protection' : 'observation'

  return {
    id: apiAction.id,
    type: localType,
    parcelId: apiAction.parcelId,
    parcelName: parcelName,
    date: apiAction.scheduledDate ? apiAction.scheduledDate.split('T')[0] : '',
    title: apiAction.title,
    details: isPlantProtection ? {
      time: meta.time || '',
      targetInsect: meta.target_insect || '',
      activeIngredient: meta.active_ingredient || '',
      dosage: meta.dosage || '',
      dosageUnit: meta.unit || '',
      sprayVolume: meta.spray_solution_volume || '',
      cultivationPeriod: meta.cultivation_period || '',
      costs: {
        fuel: Number(meta.fuel) || 0,
        materials: Number(meta.materials) || 0,
        labor: Number(meta.labor) || 0,
        other: Number(meta.other) || 0
      }
    } : {
      time: meta.time || '',
      cultivationPeriod: meta.cultivation_period || '',
      oliveFruitFly: {
        active: meta.olive_fruit_fly === true || meta.olive_fruit_fly === ('true' as any),
        larvaeObservations: Number(meta.olive_fruit_fly_larvae_observations) || 0,
        adultCaptures: Number(meta.olive_fruit_fly_adult_captures) || 0
      },
      oliveMoth: {
        active: meta.olive_molth === true || meta.olive_molth === ('true' as any),
        larvaeObservations: Number(meta.olive_molth_larvae_observations) || 0,
        adultCaptures: Number(meta.olive_molth_adult_captures) || 0
      }
    },
    createdAt: (() => {
      const base = apiAction.scheduledDate || new Date().toISOString()
      const time = meta?.time
      if (time && base) {
        const datePart = base.split('T')[0]
        return new Date(`${datePart}T${time}:00`).toISOString()
      }
      return base
    })()
  }
}
