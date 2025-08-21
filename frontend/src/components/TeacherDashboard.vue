<template>
  <div class="dashboard-container">
    <header class="dashboard-header">
      <h2>Lesgever Dashboard</h2>
    </header>
    
    <main class="dashboard-content">
      <!-- Mijn Vakken Sectie -->
      <div class="courses-section">
        <h2>Mijn Vakken</h2>
        <div v-if="loading" class="loading">Laden...</div>
        <div v-else-if="error" class="error">{{ error }}</div>
        <div v-else-if="courses.length === 0" class="no-data">
          Geen vakken gevonden
        </div>
        <div v-else class="courses-grid">
          <div 
            v-for="course in courses" 
            :key="course.id" 
            class="course-card"
            @click="viewCourse(course.id)"
          >
            <h3>{{ course.name }}</h3>
            <p class="ptext">{{ course.description || 'Geen beschrijving' }}</p>
            
          </div>
        </div>
      </div>

      <!-- Studenten voor Vak Sectie -->
      <div v-if="selectedCourse" class="students-section">
        <h2>Studenten voor vak: {{ selectedCourse.name }}</h2>
        
        <div class="filter-controls">
          <label>
            Ondergrens:
            <input type="number" v-model="lowerbound" min="0" max="100" />
          </label>
          <label>
            Bovengrens:
            <input type="number" v-model="upperbound" min="0" max="100" />
          </label>
          <button @click="fetchStudents(selectedCourse.id)">Filter</button>
        </div>

        <div v-if="students.length === 0" class="no-data">Geen studenten gevonden</div>
        <table v-else class="students-table">
          <thead>
            <tr>
              <th>Naam</th>
              <th>Score</th>
              <th>Acties</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="student in students" :key="student.id">
              <td>{{ student.fullName || `${student.first_name} ${student.last_name}` }}</td>
              <td>{{ student.score }}%</td>
              <td>
                <button @click="openEvaluationForm(student)">Voeg Evaluatie Toe</button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Evaluatie Formulier -->
        <div v-if="selectedStudent" class="evaluation-form">
          <h3>Evaluatie toevoegen voor {{ selectedStudent.first_name }} {{ selectedStudent.last_name }}</h3>
          <form @submit.prevent="submitEvaluation">
            <label>
              Resultaat:
              <input type="number" v-model="evaluation.result" min="-1" max="10" required />
            </label>
            <label>
              Gewicht:
              <input type="number" v-model="evaluation.weight" min="1" max="20" required />
            </label>
            <label>
              Bericht (optioneel):
              <input type="text" v-model="evaluation.message" />
            </label>
            <button type="submit">Opslaan</button>
            <button type="button" @click="cancelEvaluation">Annuleren</button>
          </form>
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

const courses = ref([])
const students = ref([])
const loading = ref(true)
const error = ref('')
const selectedCourse = ref(null)
const selectedStudent = ref(null)
const lowerbound = ref('')
const upperbound = ref('')
const evaluation = ref({
  result: '',
  weight: '',
  message: '',
})

onMounted(fetchCourses)

async function fetchCourses() {
  try {
    loading.value = true
    error.value = ''
    const response = await fetch('http://localhost:8000/teacher/courses', {
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    })
    if (!response.ok) throw new Error('Fout bij ophalen van vakken')
    courses.value = await response.json()
  } catch (err) {
    error.value = err.message
  } finally {
    loading.value = false
  }
}

async function viewCourse(courseId) {
  selectedCourse.value = courses.value.find(c => c.id === courseId)
  await fetchStudents(courseId)
}

async function fetchStudents(courseId) {
  try {
    loading.value = true
    error.value = ''
    const params = new URLSearchParams()
    if (lowerbound.value) params.append('score_lowerbound', lowerbound.value)
    if (upperbound.value) params.append('score_upperbound', upperbound.value)

    const response = await fetch(`http://localhost:8000/teacher/courses/${courseId}/students?${params.toString()}`, {
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    })
    if (!response.ok) throw new Error('Fout bij ophalen van studenten')
    students.value = await response.json()
  } catch (err) {
    error.value = err.message
  } finally {
    loading.value = false
  }
}

function openEvaluationForm(student) {
  selectedStudent.value = student
  evaluation.value = { result: '', weight: '', message: '' }
}

function cancelEvaluation() {
  selectedStudent.value = null
  evaluation.value = { result: '', weight: '', message: '' }
}

async function submitEvaluation() {
  if (!selectedCourse.value || !selectedStudent.value) return

  try {
    const response = await fetch(
      `http://localhost:8000/teacher/courses/${selectedCourse.value.id}/students/${selectedStudent.value.id}/evaluations`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(evaluation.value),
      }
    )

    if (!response.ok) throw new Error('Fout bij het opslaan van de evaluatie')

    // Refresh de studentenlijst na het toevoegen van een evaluatie
    await fetchStudents(selectedCourse.value.id)
    cancelEvaluation()
  } catch (err) {
    error.value = err.message
  }
}

function logout() {
  authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.dashboard-container {
  min-height: 100vh;
  background-color: #f5f5f5;
}

.dashboard-header {
  background: white;
  padding: 1rem 2rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.dashboard-header h2 {
  margin: 0;
  color: #333;
}

.dashboard-content {
  max-width: 800px;
  margin: 2rem auto;
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.courses-section,
.students-section {
  background: white;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  box-shadow: 0 1px 6px rgb(0 0 0 / 0.1);
}

.courses-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin: 20px
}

.course-card {
  cursor: pointer;
  flex: 1 1 200px;
  background-color: #e0e7ff;
  border-radius: 6px;
  padding: 1rem;
  transition: background-color 0.3s ease;
}

.course-card:hover {
  background-color: #4338ca;
  color: white;
}

.students-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
}

.students-table th,
.students-table td {
  border: 1px solid #ddd;
  padding: 0.6rem;
  text-align: left;
}

.students-table th {
  background-color: #4338ca;
  color: white;
}

/* Styling voor de filter-knop */
.filter-controls button {
  background-color: #2563eb;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  margin-left: 10px;
  font-size: 1rem;
  transition: background-color 0.3s ease;
}

.filter-controls button:hover {
  background-color: #1d4ed8;
}

/* Styling voor de "Voeg Evaluatie Toe"-knop */
.students-table button {
  background-color: #1d4ed8;
  color: white;
  border: none;
  padding: 0.4rem 0.8rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: background-color 0.3s ease;
}

.students-table button:hover {
  background-color: #047857;
}

/* Optionele focus-styling voor toegankelijkheid */
.filter-controls button:focus,
.students-table button:focus {
  outline: 2px solid #2563eb;
  outline-offset: 2px;
}

/* Styling voor de evaluatie-formulier knoppen */
.evaluation-form button[type="submit"] {
  background-color: #4338ca;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
  transition: background-color 0.3s ease;
}

.evaluation-form button[type="submit"]:hover {
  background-color: #3730a3;
}

.evaluation-form button[type="button"] {
  background-color: #dc2626;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
  transition: background-color 0.3s ease;
}

.evaluation-form button[type="button"]:hover {
  background-color: #b91c1c;
}
.ptext{
  margin-bottom: 20px;
}
</style>
