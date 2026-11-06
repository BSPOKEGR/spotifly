<template>
  <v-app>
    <v-main>
      <v-container fluid class="fill-height pa-0">
        <v-row no-gutters class="fill-height">
          <!-- Login Form -->
          <v-col cols="12">
            <div class="auth-form d-flex flex-column justify-center align-center pa-8">
              <div class="form-container">
                <div class="text-center mb-8">
                  <div class="mb-8 d-flex align-center justify-center">
                    <CustomSvgIcon :size="80" color="primary" style="width: 180px; height: 50px;" />
                  </div>
                  <h2 class="text-h4 font-weight-bold text-primary mb-2">Welcome Back</h2>
                  <p class="text-subtitle-1 text-medium-emphasis">
                    Sign in to access your olive monitoring dashboard
                  </p>
                </div>

                <v-form @submit.prevent="handleLogin" ref="form">
                  <v-text-field
                    v-model="email"
                    :rules="emailRules"
                    label="Email Address"
                    type="email"
                    variant="outlined"
                    class="mb-4"
                    required
                  >
                    <template v-slot:prepend-inner>
                      <v-icon icon="mdi-email-outline" />
                    </template>
                  </v-text-field>

                  <v-text-field
                    v-model="password"
                    :rules="passwordRules"
                    label="Password"
                    :type="showPassword ? 'text' : 'password'"
                    variant="outlined"
                    class="mb-6"
                    required
                  >
                    <template v-slot:prepend-inner>
                      <v-icon icon="mdi-lock-outline" />
                    </template>
                    <template v-slot:append-inner>
                      <div @click="showPassword = !showPassword" style="cursor: pointer;">
                        <v-icon :icon="showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'" />
                      </div>
                    </template>
                  </v-text-field>

                  <v-btn
                    type="submit"
                    color="primary"
                    size="large"
                    block
                    :loading="loading"
                  >
                    Sign In
                  </v-btn>
                </v-form>
              </div>
            </div>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <v-snackbar v-model="showError" color="error" timeout="5000">
      {{ errorMessage }}
      <template v-slot:actions>
        <v-btn variant="text" @click="showError = false">Close</v-btn>
      </template>
    </v-snackbar>
  </v-app>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import CustomSvgIcon from '../components/CustomSvgIcon.vue'

const router = useRouter()
const authStore = useAuthStore()

const form = ref()
const email = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)
const showError = ref(false)
const errorMessage = ref('')

const emailRules = [
  (v: string) => !!v || 'Email is required',
  (v: string) => /.+@.+\..+/.test(v) || 'Email must be valid'
]

const passwordRules = [
  (v: string) => !!v || 'Password is required',
  (v: string) => v.length >= 6 || 'Password must be at least 6 characters'
]

const handleLogin = async () => {
  const { valid } = await form.value.validate()
  if (!valid) return

  loading.value = true
  try {
    await authStore.login(email.value, password.value)
    router.push('/dashboard')
  } catch (error) {
    showError.value = true
    errorMessage.value = 'Invalid email or password'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-form {
  background: white;
  min-height: 100vh;
}

.form-container {
  width: 100%;
  max-width: 400px;
}

@media (max-width: 960px) {
  .form-container {
    max-width: 500px;
  }
}
</style>