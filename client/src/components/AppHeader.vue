<template>
  <v-app-bar color="white" elevation="0" height="64" style="border-bottom: 1px solid #e0e0e0;">
    <v-container fluid class="d-flex align-center pa-0 px-4">
      <!-- Logo and Brand -->
      <div class="d-flex">
        <CustomSvgIcon :size="32" color="primary" style="width: 160px; height: 50px;" />
      </div>

      <v-spacer />

      <!-- Navigation Menu -->
      <v-tabs v-model="currentTab" class="mx-8" height="64" align-tabs="center">
        <v-tab value="parcels" @click="$router.push('/dashboard')">
          Parcels
        </v-tab>
        <v-tab value="actions" @click="$router.push('/actions')">
          Actions
        </v-tab>
      </v-tabs>

      <v-spacer />

      <!-- User Menu -->
      <div class="d-flex align-center">
        <v-menu>
          <template v-slot:activator="{ props }">
            <v-btn
              v-bind="props"
              icon
              variant="text"
              class="ml-4"
            >
              <v-avatar size="36" color="primary">
                <span class="text-white font-weight-medium">
                  {{ userInitials }}
                </span>
              </v-avatar>
            </v-btn>
          </template>

          <v-list>
            <v-list-item>
              <v-list-item-title>{{ authStore.user?.email }}</v-list-item-title>
              <v-list-item-subtitle>Farmer</v-list-item-subtitle>
            </v-list-item>
            <v-divider />
            <v-list-item @click="handleLogout">
              <template v-slot:prepend>
                <v-icon icon="mdi-logout" />
              </template>
              <v-list-item-title>Logout</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>
      </div>
    </v-container>
  </v-app-bar>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import CustomSvgIcon from './CustomSvgIcon.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const currentTab = computed(() => {
  if (route.path.startsWith('/actions')) return 'actions'
  return 'parcels'
})

const userInitials = computed(() => {
  if (!authStore.user?.email) return 'U'
  return authStore.user.email.charAt(0).toUpperCase()
})

const handleLogout = () => {
  authStore.logout()
  router.push('/login')
}
</script>