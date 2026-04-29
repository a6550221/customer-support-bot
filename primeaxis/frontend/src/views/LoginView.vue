<template>
  <div class="login-page">
    <div class="login-card">
      <div class="logo-area">
        <div class="logo-icon">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
            <line x1="12" y1="2" x2="12" y2="22" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
            <line x1="2" y1="12" x2="22" y2="12" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
            <line x1="19.07" y1="4.93" x2="4.93" y2="19.07" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
          </svg>
        </div>
        <div>
          <div class="logo-name">PrimeAxis</div>
          <div class="logo-sub">LOGISTICS CRM</div>
        </div>
      </div>

      <h2 class="login-title">歡迎回來 Welcome back</h2>
      <p class="login-sub">登入您的客服管理帳戶</p>

      <el-form :model="form" :rules="rules" ref="formRef" @submit.prevent="handleLogin">
        <el-form-item prop="email">
          <el-input v-model="form.email" placeholder="電子郵件 Email" size="large" :prefix-icon="User" />
        </el-form-item>
        <el-form-item prop="password">
          <el-input v-model="form.password" type="password" placeholder="密碼 Password" size="large" :prefix-icon="Lock" show-password />
        </el-form-item>
        <el-button type="primary" size="large" class="login-btn" :loading="loading" @click="handleLogin" native-type="submit">
          登入 Login
        </el-button>
      </el-form>

      <div class="login-hint">
        <span>測試帳號：admin@primeaxis.com / password123</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { User, Lock } from '@element-plus/icons-vue'

const router  = useRouter()
const auth    = useAuthStore()
const formRef = ref()
const loading = ref(false)
const form    = ref({ email: 'admin@primeaxis.com', password: 'password123' })
const rules   = {
  email:    [{ required: true, type: 'email', message: '請輸入有效的電子郵件', trigger: 'blur' }],
  password: [{ required: true, min: 6, message: '密碼至少 6 位', trigger: 'blur' }],
}

async function handleLogin() {
  await formRef.value?.validate()
  loading.value = true
  try {
    await auth.login(form.value)
    router.push('/dashboard')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f7f5f0 0%, #fdf1e3 100%);
}

.login-card {
  width: 400px;
  background: #fff;
  border-radius: 20px;
  padding: 40px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.08);
  border: 1px solid #f0ede8;
}

.logo-area {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 32px;
}

.logo-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: #0d4a38;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.logo-name {
  font-size: 20px;
  font-weight: 800;
  color: #2c2520;
  letter-spacing: -0.3px;
  line-height: 1.15;
}

.logo-sub {
  font-size: 9px;
  color: #0d4a38;
  font-weight: 700;
  letter-spacing: 1.5px;
}

.login-title {
  font-size: 22px;
  font-weight: 700;
  color: #2c2520;
  margin-bottom: 6px;
}

.login-sub {
  font-size: 13px;
  color: #9e9890;
  margin-bottom: 28px;
}

.login-btn {
  width: 100%;
  height: 44px;
  font-size: 15px;
  font-weight: 700;
  border-radius: 10px;
  background: #e8851a;
  border-color: #e8851a;
  margin-top: 4px;
}
.login-btn:hover { background: #d4760f; border-color: #d4760f; }

.login-hint {
  margin-top: 20px;
  text-align: center;
  font-size: 11px;
  color: #b8b2aa;
}

:deep(.el-input__wrapper) {
  border-radius: 10px;
  padding: 4px 12px;
}
</style>
