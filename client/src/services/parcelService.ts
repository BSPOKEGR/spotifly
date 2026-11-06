import { apiClient } from './api'

export interface ApiParcel {
  id: string
  name: string
  crop: string
  area: number
  location: { lat: number; lng: number }
  polygon?: [number, number][]
}

export interface ApiParcelsResponse {
  data: ApiParcel[]
}

export async function fetchParcels(): Promise<ApiParcel[]> {
  const response = await apiClient.get<ApiParcelsResponse>('/api/parcels')
  return response.data
}
