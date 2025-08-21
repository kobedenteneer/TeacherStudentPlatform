// App.vue
<template>
  <div id="app">
    <nav v-if="authStore.token" class="navbar">
      <div class="nav-content">
        <h1>Student Evaluatie Systeem</h1>
        <div class="nav-actions">
          <span class="user-info">
            {{ authStore.user?.username }} 
            ({{ authStore.user?.roles.includes('ROLE_TEACHER') ? 'Lesgever' : 
              authStore.user?.roles.includes('ROLE_ADMIN') ? 'Admin' : 'Student' }})
               </span>
          <button @click="handleLogout" class="logout-btn">Uitloggen</button>
        </div>
      </div>
    </nav>
    <main class="main-content">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { useAuthStore } from './stores/authStore'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

function handleLogout() {
  authStore.logout()
  router.push('/login')
}
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background-color: #f5f5f5;
}

.navbar {
  background: white;
  border-bottom: 1px solid #e5e5e5;
  padding: 1rem 0;
}

.nav-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.user-info {
  font-size: 0.9rem;
  color: #666;
}

.logout-btn {
  padding: 0.5rem 1rem;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.logout-btn:hover {
  background: #b91c1c;
}

.main-content {
  min-height: calc(100vh - 80px);
  padding: 2rem;
}

@media (max-width: 768px) {
  .nav-content {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .main-content {
    padding: 1rem;
  }
}
</style>