import { defineStore } from 'pinia'
import { ref } from 'vue'
import { loginWithCredentials } from '../services/authService'

interface User {
  id: string
  email: string
  name: string
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const isAuthenticated = ref(false)

  const login = async (email: string, password: string) => {
    const response = await loginWithCredentials(email, password)

    localStorage.setItem('authToken', response.access_token)

    const loggedInUser: User = {
      id: email,
      email,
      name: email.split('@')[0]
    }

    user.value = loggedInUser
    isAuthenticated.value = true
    localStorage.setItem('user', JSON.stringify(loggedInUser))
    return true
  }

  const logout = () => {
    user.value = null
    isAuthenticated.value = false
    localStorage.removeItem('user')
    localStorage.removeItem('authToken')
  }

  const initAuth = () => {
    const storedUser = localStorage.getItem('user')
    if (storedUser) {
      user.value = JSON.parse(storedUser)
      isAuthenticated.value = true
    }
  }

  return {
    user,
    isAuthenticated,
    login,
    logout,
    initAuth
  }
})
