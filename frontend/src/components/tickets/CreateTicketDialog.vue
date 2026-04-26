<template>
  <el-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" title="建立工單" width="540px">
    <el-form :model="form" :rules="rules" ref="formRef" label-position="top">
      <el-form-item label="主題" prop="subject">
        <el-input v-model="form.subject" placeholder="請輸入工單主題" />
      </el-form-item>
      <el-row :gutter="12">
        <el-col :span="12">
          <el-form-item label="優先級" prop="priority">
            <el-select v-model="form.priority" style="width: 100%">
              <el-option label="緊急" value="urgent" />
              <el-option label="高" value="high" />
              <el-option label="中" value="medium" />
              <el-option label="低" value="low" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="分類">
            <el-select v-model="form.category_id" clearable placeholder="選擇分類" style="width: 100%">
              <el-option v-for="c in categories" :key="c.id" :label="c.name" :value="c.id" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>
      <el-form-item label="客戶">
        <el-select v-model="form.customer_id" filterable clearable remote :remote-method="searchCustomer" placeholder="搜尋客戶" style="width: 100%">
          <el-option v-for="c in customerOptions" :key="c.id" :label="`${c.name} (${c.email || '無 Email'})`" :value="c.id" />
        </el-select>
      </el-form-item>
      <el-form-item label="問題描述" prop="content">
        <el-input v-model="form.content" type="textarea" :rows="5" placeholder="請描述客戶的問題..." />
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="$emit('update:modelValue', false)">取消</el-button>
      <el-button type="primary" :loading="saving" @click="submit">建立工單</el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { ticketApi, categoryApi, customerApi } from '@/api'

const props = defineProps({ modelValue: Boolean })
const emit  = defineEmits(['update:modelValue', 'created'])

const formRef         = ref()
const saving          = ref(false)
const categories      = ref([])
const customerOptions = ref([])

const form = reactive({
  subject: '', priority: 'medium', category_id: null, customer_id: null, content: '',
})

const rules = {
  subject:  [{ required: true, message: '請輸入主題', trigger: 'blur' }],
  priority: [{ required: true, message: '請選擇優先級', trigger: 'change' }],
  content:  [{ required: true, message: '請輸入問題描述', trigger: 'blur' }],
}

async function searchCustomer(query) {
  if (!query) return
  const res = await customerApi.list({ search: query })
  customerOptions.value = res.data.data
}

async function submit() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  saving.value = true
  try {
    await ticketApi.create(form)
    ElMessage.success('工單已建立')
    emit('created')
    emit('update:modelValue', false)
    Object.assign(form, { subject: '', priority: 'medium', category_id: null, customer_id: null, content: '' })
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  const res = await categoryApi.list()
  categories.value = res.data
})
</script>
