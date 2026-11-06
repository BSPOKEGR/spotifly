import { apiClient } from './api'

export interface DailyWeather {
  date: string
  temperature: { min: number; max: number; mean: number }
  humidity: { min: number; max: number; mean: number }
  wind_speed: { min: number; max: number; mean: number }
  precipitation: { total: number; max: number }
  icon: string
  condition: string
}

interface WeatherResponse {
  data: DailyWeather[]
  source: string
  parcel_id: string
}

const _cache = new Map<string, Promise<DailyWeather[]>>()

export function fetchDailyWeather(parcelId: string): Promise<DailyWeather[]> {
  if (!_cache.has(parcelId)) {
    const promise = apiClient.get<WeatherResponse>(
      `/api/weather/daily?parcel_id=${parcelId}`
    ).then(r => r.data)
    _cache.set(parcelId, promise)
  }
  return _cache.get(parcelId)!
}
