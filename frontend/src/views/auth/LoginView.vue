<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-logo">
        <el-icon size="40" color="#4F46E5"><Headset /></el-icon>
        <h1>HelpDesk</h1>
        <p>{{ t('auth.subtitle') }}</p>
      </div>

      <el-form :model="form" :rules="rules" ref="formRef" label-position="top" @submit.prevent="handleLogin">
        <el-form-item :label="t('auth.email')" prop="email">
          <el-input v-model="form.email" type="email" :placeholder="t('auth.email')" size="large" prefix-icon="Message" />
        </el-form-item>
        <el-form-item :label="t('auth.password')" prop="password">
          <el-input v-model="form.password" type="password" :placeholder="t('auth.password')" size="large" prefix-icon="Lock" show-password @keyup.enter="handleLogin" />
        </el-form-item>
        <el-button type="primary" size="large" class="login-btn" :loading="loading" @click="handleLogin">
          {{ t('auth.loginBtn') }}
        </el-button>
      </el-form>

      <div class="login-hint">
        <small>預設帳號：admin@helpdesk.com｜密碼：password123</small>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ElMessage } from 'element-plus'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const router = useRouter()
const auth   = useAuthStore()

const formRef = ref()
const loading = ref(false)
const form    = reactive({ email: '', password: '' })

const rules = {
  email:    [{ required: true, type: 'email', message: '請輸入有效的電子郵件', trigger: 'blur' }],
  password: [{ required: true, min: 6, message: '密碼至少 6 個字元', trigger: 'blur' }],
}

async function handleLogin() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  loading.value = true
  try {
    await auth.login(form.email, form.password)
    router.push('/')
  } catch (err) {
    ElMessage.error(err?.message || '登入失敗')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.login-card {
  background: #fff;
  border-radius: 16px;
  padding: 48px 40px;
  width: 400px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}

.login-logo {
  text-align: center;
  margin-bottom: 32px;
}

.login-logo h1 {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  margin: 8px 0 4px;
}

.login-logo p {
  color: #64748b;
  font-size: 14px;
}

.login-btn {
  width: 100%;
  margin-top: 8px;
  height: 44px;
  font-size: 16px;
  background: #4F46E5;
  border-color: #4F46E5;
}

.login-hint {
  margin-top: 24px;
  text-align: center;
  color: #94a3b8;
}
</style>
