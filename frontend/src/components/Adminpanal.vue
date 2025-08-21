<template>
  <div class="password-reset-container">
    <div class="card">
      <h2>Wachtwoord Resetten</h2>
      <p class="subtitle">Reset het wachtwoord van een gebruiker</p>
      
      <form @submit.prevent="resetPassword" class="reset-form">
        <div class="form-group">
          <label for="userId">Gebruiker ID:</label>
          <input
            id="userId"
            v-model="formData.userId"
            type="text"
            placeholder="Voer gebruiker ID in"
            required
            :disabled="loading"
          />
        </div>

        <div class="form-group">
          <label for="newPassword">Nieuw Wachtwoord:</label>
          <div class="password-input-container">
            <input
              id="newPassword"
              v-model="formData.newPassword"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Voer nieuw wachtwoord in"
              required
              minlength="8"
              :disabled="loading"
            />
            
          </div>
          <small class="password-hint">Minimaal 8 karakters vereist</small>
        </div>

        <div class="form-actions">
          <button
            type="submit"
            class="btn-primary"
            :disabled="loading || !isFormValid"
          >
            <span v-if="loading">Resetten...</span>
            <span v-else>Wachtwoord Resetten</span>
          </button>
          
          <button
            type="button"
            @click="clearForm"
            class="btn-secondary"
            :disabled="loading"
          >
            Wissen
          </button>
        </div>
      </form>

      <!-- Status berichten -->
      <div v-if="message" :class="['message', messageType]">
        {{ message }}
      </div>

      <!-- Bevestiging modal -->
      <div v-if="showConfirmation" class="modal-overlay" @click="closeConfirmation">
        <div class="modal" @click.stop>
          <h3>Bevestiging</h3>
          <p>Weet je zeker dat je het wachtwoord wilt resetten voor gebruiker ID: <strong>{{ formData.userId }}</strong>?</p>
          <div class="modal-actions">
            <button @click="confirmReset" class="btn-danger">Ja, Reset</button>
            <button @click="closeConfirmation" class="btn-secondary">Annuleren</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AdminPasswordReset',
  data() {
    return {
      formData: {
        userId: '',
        newPassword: ''
      },
      loading: false,
      message: '',
      messageType: '',
      showPassword: false,
      showConfirmation: false
    }
  },
  computed: {
    isFormValid() {
      return this.formData.userId.trim() !== '' && 
             this.formData.newPassword.length >= 8;
    }
  },
  methods: {
    resetPassword() {
      this.showConfirmation = true;
    },
    
    async confirmReset() {
      this.showConfirmation = false;
      this.loading = true;
      this.message = '';
      
      try {
        const response = await fetch(`/admin/users/${this.formData.userId}/password`, {
          method: 'PATCH',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${this.getAuthToken()}`
          },
          body: JSON.stringify({
            newPassword: this.formData.newPassword
          })
        });

        if (response.ok) {
          this.showMessage('Wachtwoord succesvol gereset!', 'success');
          this.clearForm();
        } else {
          const errorData = await response.json();
          this.showMessage(
            errorData.message || 'Er is een fout opgetreden bij het resetten van het wachtwoord.',
            'error'
          );
        }
      } catch (error) {
        this.showMessage('Netwerkfout: Kon geen verbinding maken met de server.', 'error');
        console.error('Password reset error:', error);
      } finally {
        this.loading = false;
      }
    },
    
    closeConfirmation() {
      this.showConfirmation = false;
    },
    
    clearForm() {
      this.formData.userId = '';
      this.formData.newPassword = '';
      this.message = '';
      this.showPassword = false;
    },
    
    togglePasswordVisibility() {
      this.showPassword = !this.showPassword;
    },
    
    showMessage(text, type) {
      this.message = text;
      this.messageType = type;
      
      // Auto-hide success messages after 5 seconds
      if (type === 'success') {
        setTimeout(() => {
          this.message = '';
        }, 5000);
      }
    },
    
    getAuthToken() {
      // Implementeer je authenticatie logica hier
      // Bijvoorbeeld: return localStorage.getItem('authToken');
      return 'your-auth-token';
    }
  }
}
</script>

<style scoped>
.password-reset-container {
  max-width: 500px;
  margin: 0 auto;
  padding: 20px;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  padding: 30px;
  border: 1px solid #e1e5e9;
}

h2 {
  color: #2c3e50;
  margin-bottom: 8px;
  font-size: 1.8rem;
  font-weight: 600;
}

.subtitle {
  color: #6c757d;
  margin-bottom: 25px;
  font-size: 0.95rem;
}

.reset-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

label {
  margin-bottom: 6px;
  font-weight: 500;
  color: #374151;
  font-size: 0.9rem;
}

input {
  padding: 12px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s ease;
}

input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

input:disabled {
  background-color: #f9fafb;
  color: #6b7280;
  cursor: not-allowed;
}

.password-input-container {
  position: relative;
  display: flex;
  align-items: center;
}

.password-input-container input {
  flex: 1;
  padding-right: 50px;
}

.password-toggle {
  position: absolute;
  right: 12px;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.2rem;
  padding: 4px;
  color: #6b7280;
  transition: color 0.2s ease;
}

.password-toggle:hover:not(:disabled) {
  color: #374151;
}

.password-toggle:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.password-hint {
  color: #6b7280;
  font-size: 0.8rem;
  margin-top: 4px;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 10px;
}

.btn-primary, .btn-secondary, .btn-danger {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  flex: 1;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2563eb;
  transform: translateY(-1px);
}

.btn-secondary {
  background-color: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover:not(:disabled) {
  background-color: #e5e7eb;
}

.btn-danger {
  background-color: #dc2626;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background-color: #b91c1c;
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

.message {
  margin-top: 20px;
  padding: 12px 16px;
  border-radius: 8px;
  font-weight: 500;
}

.message.success {
  background-color: #dcfce7;
  color: #166534;
  border: 1px solid #bbf7d0;
}

.message.error {
  background-color: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 12px;
  padding: 24px;
  max-width: 400px;
  width: 90%;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.modal h3 {
  margin-top: 0;
  margin-bottom: 16px;
  color: #374151;
}

.modal p {
  margin-bottom: 20px;
  color: #6b7280;
  line-height: 1.5;
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.modal-actions button {
  flex: none;
  min-width: 100px;
}

@media (max-width: 600px) {
  .password-reset-container {
    padding: 15px;
  }
  
  .card {
    padding: 20px;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .modal-actions {
    flex-direction: column-reverse;
  }
}
</style>