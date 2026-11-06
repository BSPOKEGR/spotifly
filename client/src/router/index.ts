import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/Login.vue'
import Dashboard from '../views/Dashboard.vue'
import ParcelDetail from '../views/ParcelDetail.vue'
import Actions from '../views/Actions.vue'

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: Login
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true }
  },
  {
    path: '/parcel/:id',
    name: 'ParcelDetail',
    component: ParcelDetail,
    meta: { requiresAuth: true }
  },
  {
    path: '/actions',
    name: 'Actions',
    component: Actions,
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

function isTokenValid(): boolean {
  const token = localStorage.getItem('authToken')
  if (!token) return false
  try {
    const payload = JSON.parse(atob(token.split('.')[1]))
    return payload.exp * 1000 > Date.now()
  } catch {
    return false
  }
}

router.beforeEach((to, _from, next) => {
  const authenticated = !!localStorage.getItem('user') && isTokenValid()

  if (!authenticated) {
    localStorage.removeItem('authToken')
    localStorage.removeItem('user')
  }

  if (to.meta.requiresAuth && !authenticated) {
    next('/login')
  } else if (to.name === 'Login' && authenticated) {
    next('/dashboard')
  } else {
    next()
  }
})

export default router