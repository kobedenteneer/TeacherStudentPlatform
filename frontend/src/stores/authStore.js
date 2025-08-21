// stores/authStore.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  // Initialiseer token en user uit localStorage, of null als niet aanwezig
  const token = ref(localStorage.getItem('token') || null)
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))

  // Computed property die zegt of gebruiker ingelogd is
  const isAuthenticated = computed(() => {
    return token.value !== null && user.value !== null
  })

  // Sla token op in store en localStorage
  function setToken(newToken) {
    token.value = newToken
    localStorage.setItem('token', newToken)
  }

  // Sla user data op in store en localStorage
  function setUser(userData) {
    user.value = userData
    localStorage.setItem('user', JSON.stringify(userData))
  }

  // Check of user een bepaalde rol heeft
  function hasRole(role) {
    if (!user.value || !user.value.roles) {
      return false
    }
    return user.value.roles.includes(role)
  }

  // Uitloggen: wis store en localStorage
  function logout() {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  return {
    token,
    user,
    isAuthenticated,
    setToken,
    setUser,
    hasRole,
    logout
  }
})
