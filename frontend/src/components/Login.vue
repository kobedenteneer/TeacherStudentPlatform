// components/Login.vue
<template>
  <div class="login-container">
    <div class="login-card">
      <h2 class="login-title">Inloggen</h2>
      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label class="form-label">Gebruikersnaam</label>
          <input 
            v-model="username" 
            type="text" 
            class="form-input" 
            required 
            :disabled="loading"
          />
        </div>
        <div class="form-group">
          <label class="form-label">Wachtwoord</label>
          <input 
            v-model="password" 
            type="password" 
            class="form-input" 
            required 
            :disabled="loading"
          />
        </div>
        <button type="submit" class="login-button" :disabled="loading">
          {{ loading ? 'Bezig met inloggen...' : 'Inloggen' }}
        </button>
        <div v-if="error" class="error-message">{{ error }}</div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../stores/authStore'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const username = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function handleLogin() {
  if (loading.value) return
  
  loading.value = true
  error.value = ''
  
  try {
    const response = await fetch('http://localhost:8000/api/login_check', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ 
        username: username.value, 
        password: password.value 
      }),
    })

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}))
      throw new Error(errorData.message || 'Inloggen mislukt')
    }

    const data = await response.json()
    
    if (!data.token) {
      throw new Error('Geen token ontvangen')
    }

    // Sla de token en gebruikersinformatie op
    authStore.setToken(data.token)
    authStore.setUser(data.user || { username: username.value, roles: data.roles || [] })

    // Controleer de rol en stuur door naar de juiste route
    const userRoles = data.user?.roles || data.roles || []
    
    if (userRoles.includes('ROLE_TEACHER')) {
      router.push('/teacher/dashboard')
    } else if (userRoles.includes('ROLE_STUDENT')) {
      router.push('/student/results')
    } else if (userRoles.includes('ROLE_ADMIN')) {
      router.push('/admin/dashboard')
    } else {
      throw new Error('Onbekende rol')
    }
  } catch (err) {
    console.error('Login error:', err)
    error.value = err.message || 'Er is een fout opgetreden bij het inloggen'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  padding: 1rem;
}

.login-card {
  width: 100%;
  max-width: 400px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  padding: 2rem;
}

.login-title {
  font-size: 1.5rem;
  text-align: center;
  margin-bottom: 2rem;
  color: #333;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-weight: 600;
  color: #374151;
}

.form-input {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-input:disabled {
  background-color: #f9fafb;
  cursor: not-allowed;
}

.login-button {
  padding: 0.75rem;
  background: #2563eb;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
  margin-top: 0.5rem;
}

.login-button:hover:not(:disabled) {
  background: #1d4ed8;
}

.login-button:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

.error-message {
  color: #dc2626;
  font-size: 0.875rem;
  text-align: center;
  padding: 0.5rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 4px;
}
</style>