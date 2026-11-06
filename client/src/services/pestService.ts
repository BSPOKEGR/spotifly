import { apiClient } from './api'

export interface Alert {
  id: number
  parcel_id: string
  parcel_name: string
  date: string
  type: string
  severity: 'LOW' | 'MEDIUM' | 'HIGH' | 'CRITICAL'
  message: string
  read: boolean
}

export interface PestPrediction {
  id: number
  parcel_id: string
  response_data: {
    'bactrocera-oleae': number
    'prays-olae': number
    received_id: string
  }
  fetched_at: string
}

export interface AlertsResponse {
  data: Alert[]
}

export interface LatestPredictionResponse {
  data: PestPrediction
}

export interface AllPredictionsResponse {
  data: PestPrediction[]
  count: number
}

export const pestService = {
  getAlerts(): Promise<AlertsResponse> {
    return apiClient.get<AlertsResponse>('/api/alerts')
  },

  acknowledgeAlert(alertId: number): Promise<AlertsResponse> {
    return apiClient.post<AlertsResponse>(`/api/alerts/${alertId}/acknowledge`, {})
  },

  getLatestPrediction(): Promise<LatestPredictionResponse> {
    return apiClient.get<LatestPredictionResponse>('/api/predictions/pest/latest')
  },

  getAllPredictions(): Promise<AllPredictionsResponse> {
    return apiClient.get<AllPredictionsResponse>('/api/predictions/pest')
  }
}
