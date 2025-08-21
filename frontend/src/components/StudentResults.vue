<template>
  <div class="results-container">
    <header class="results-header">
      <h2>Mijn Resultaten</h2>
      <h4 v-if="studentName">van {{ studentName }}</h4>

    </header>
    
    <main class="results-content">
      <div v-if="loading" class="loading">Laden...</div>
      <div v-else-if="error" class="error">{{ error }}</div>
      <div v-else-if="results.length === 0" class="no-data">
        Geen resultaten gevonden
      </div>
      <div v-else class="results-list">
        <div 
          v-for="courseResult in results" 
          :key="courseResult.course.id"
          class="course-results"
        >
          <h2>{{ courseResult.course.name }}</h2>
          <div class="course-summary">
            <span class="overall-score">
              Totaalscore: {{ courseResult.course.overall_score }}%
            </span>
            <span class="teacher-name">
              Lesgever: {{ courseResult.course.teacher_name }}
            </span>
          </div>
          
          <div class="evaluations-table">
            <table>
              <thead>
                <tr>
                  <th>Datum</th>
                  <th>Resultaat</th>
                  <th>Gewicht</th>
                  <th>Boodschap</th>
                </tr>
              </thead>
              <tbody>
                <tr 
                  v-for="evaluation in courseResult.evaluations" 
                  :key="evaluation.id"
                  :class="{ 'absent': !evaluation.participated }"
                >
                  <td>{{ formatDate(evaluation.created_at) }}</td>
                  <td>
                    <span v-if="!evaluation.participated" class="absent-mark">
                      Afwezig
                    </span>
                    <span v-else class="score">
                      {{ evaluation.result }}/10
                    </span>
                  </td>
                  <td>{{ evaluation.weight }}</td>
                  <td>{{ evaluation.message || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/authStore'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const results = ref([])
const studentName = ref('')
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  await fetchResults()
})

async function fetchResults() {
  try {
    loading.value = true
    error.value = ''
    console.log('Fetching results...')
    const response = await fetch('http://localhost:8000/student/results', {
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    })
    console.log('Response status:', response.status)
    if (!response.ok) {
      throw new Error('Fout bij ophalen van resultaten')
    }
    const data = await response.json()
    console.log('Fetched results:', data)
    results.value = data.results_by_course
    studentName.value = data.student_name
  } catch (err) {
    console.error('Error fetching results:', err)
    error.value = err.message
  } finally {
    loading.value = false
  }
}

function formatDate(dateString) {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('nl-BE')
}

function logout() {
  authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.results-container {
  min-height: 100vh;
  background-color: #f5f5f5;
}

.results-header {
  background: white;
  padding: 1rem 2rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.results-header h2,
.results-header h3 {
  margin: 0;
  color: #333;
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

.results-content {
  padding: 2rem;
}

.loading, .error, .no-data {
  text-align: center;
  padding: 2rem;
  color: #666;
}

.error {
  color: #dc2626;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 4px;
}

.results-list {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.course-results {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  overflow: hidden;
}

.course-results h2 {
  margin: 0;
  padding: 1rem 1.5rem;
  background: #2563eb;
  color: white;
}

.course-summary {
  padding: 1rem 1.5rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.overall-score, .teacher-name {
  font-weight: 600;
  font-size: 1.1rem;
  color: #2563eb;
}

.teacher-name {
  color: #374151;
}

.evaluations-table {
  overflow-x: auto;
}

.evaluations-table table {
  width: 100%;
  border-collapse: collapse;
}

.evaluations-table th,
.evaluations-table td {
  padding: 0.75rem 1rem;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.evaluations-table th {
  background: #f8fafc;
  font-weight: 600;
  color: #374151;
}

.evaluations-table tr.absent {
  background: #fef2f2;
}

.absent-mark {
  color: #dc2626;
  font-weight: 600;
}

.score {
  font-weight: 600;
  color: #059669;
}

@media (max-width: 768px) {
  .results-header {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .results-content {
    padding: 1rem;
  }
  
  .evaluations-table {
    font-size: 0.875rem;
  }
  
  .evaluations-table th,
  .evaluations-table td {
    padding: 0.5rem;
  }
}
h4{
  margin: 0;
  color: #666;
  font-size: 1rem;
  font-weight: normal;
  margin-top: 0.25rem;
  
}
</style>
