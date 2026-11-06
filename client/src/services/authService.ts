import { apiClient } from './api'

export interface LoginResponse {
  access_token: string
  token_type: string
  expires_in: number
}

export async function loginWithCredentials(
  username: string,
  password: string
): Promise<LoginResponse> {
  return apiClient.post<LoginResponse>('/api/auth/login', { username, password })
}
