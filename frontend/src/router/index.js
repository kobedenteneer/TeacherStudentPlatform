// router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import Login from '../components/Login.vue'
import TeacherDashboard from '../components/TeacherDashboard.vue'
import StudentResults from '../components/StudentResults.vue'
import Adminpanal from '../components/Adminpanal.vue'
import { useAuthStore } from '../stores/authStore'


const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresGuest: true }
  },
  {
    path: '/teacher/dashboard',
    name: 'TeacherDashboard',
    component: TeacherDashboard,
    meta: { requiresAuth: true, role: 'ROLE_TEACHER' }
  },
  {
    path: '/student/results',
    name: 'StudentResults',
    component: StudentResults,
    meta: { requiresAuth: true, role: 'ROLE_STUDENT' }
  },
  {
    path: '/admin/dashboard',
    name: 'Adminpanal', 
    component: Adminpanal,
    meta: { /*requiresAuth: true,*/ role: 'ROLE_ADMIN' }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  
  // Check if route requires authentication
  if (to.meta.requiresAuth) {
    if (!authStore.isAuthenticated) {
      next('/login')
      return
    }
    
    // Check role if specified
    if (to.meta.role && !authStore.hasRole(to.meta.role)) {
      // Redirect to appropriate dashboard based on user role
      if (authStore.hasRole('ROLE_TEACHER')) {
        next('/teacher/dashboard')
      } else if (authStore.hasRole('ROLE_STUDENT')) {
        next('/student/results')
      } else if (authStore.hasRole('ROLE_ADMIN')) {
        next('/admin/dashboard')
      }
      
      else {
        next('/login')
      }
      return
    }
  }
  
  // Redirect authenticated users away from login
  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    if (authStore.hasRole('ROLE_TEACHER')) {
      next('/teacher/dashboard')
    } else if (authStore.hasRole('ROLE_STUDENT')) {
      next('/student/results')
    }else if (authStore.hasRole('ROLE_ADMIN')) {
      next('/admin/dashboard')
    } 
    else {
      next('/')
    }
    return
  }
  
  next()
})

export default router