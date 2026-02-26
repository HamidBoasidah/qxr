<template>
  <div>
    <div class="p-5 mb-6 border border-gray-200 rounded-2xl dark:border-gray-800 lg:p-6">
      <div class="flex items-center justify-between mb-6">
        <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">
          {{ t('profile.personalInformation') }}
        </h4>
        <div class="flex items-center gap-2">
          <button v-if="!isEditing" class="edit-button" @click="startEditing">
            <svg class="fill-current" width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M15.0911 2.78206C14.2125 1.90338 12.7878 1.90338 11.9092 2.78206L4.57524 10.116C4.26682 10.4244 4.0547 10.8158 3.96468 11.2426L3.31231 14.3352C3.25997 14.5833 3.33653 14.841 3.51583 15.0203C3.69512 15.1996 3.95286 15.2761 4.20096 15.2238L7.29355 14.5714C7.72031 14.4814 8.11172 14.2693 8.42013 13.9609L15.7541 6.62695C16.6327 5.74827 16.6327 4.32365 15.7541 3.44497L15.0911 2.78206ZM12.9698 3.84272C13.2627 3.54982 13.7376 3.54982 14.0305 3.84272L14.6934 4.50563C14.9863 4.79852 14.9863 5.2734 14.6934 5.56629L14.044 6.21573L12.3204 4.49215L12.9698 3.84272ZM11.2597 5.55281L5.6359 11.1766C5.53309 11.2794 5.46238 11.4099 5.43238 11.5522L5.01758 13.5185L6.98394 13.1037C7.1262 13.0737 7.25666 13.003 7.35947 12.9002L12.9833 7.27639L11.2597 5.55281Z" fill="" />
            </svg>
            {{ t('profile.edit') }}
          </button>
          <template v-else>
            <button @click="cancelEditing" type="button" class="flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
              {{ t('buttons.cancel') }}
            </button>
            <button @click="saveProfile" type="button" class="flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
              {{ t('buttons.saveChanges') }}
            </button>
          </template>
        </div>
      </div>

      <!-- Company Info (only for company panel) -->
      <div v-if="isCompany" class="mb-6">
        <h5 class="mb-4 text-base font-medium text-gray-800 dark:text-white/90">{{ t('profile.companyInformation') }}</h5>
        <div class="grid grid-cols-1 gap-x-6 gap-y-4 lg:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs text-gray-500 dark:text-gray-400">{{ t('profile.labels.companyName') }}</label>
            <template v-if="isEditing">
              <input type="text" v-model="form.company_name" class="input-field" :placeholder="t('profile.labels.companyName')" :class="{ 'border-red-500': form.errors.company_name }" />
              <p v-if="form.errors.company_name" class="mt-1 text-sm text-red-600">{{ form.errors.company_name }}</p>
            </template>
            <p v-else class="text-sm font-medium text-gray-800 dark:text-white/90">{{ user?.company_profile?.company_name || '-' }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-xs text-gray-500 dark:text-gray-400">{{ t('profile.labels.category') }}</label>
            <template v-if="isEditing">
              <input type="text" v-model="form.category_id" class="input-field" :placeholder="t('profile.labels.category')" :class="{ 'border-red-500': form.errors.category_id }" />
              <p v-if="form.errors.category_id" class="mt-1 text-sm text-red-600">{{ form.errors.category_id }}</p>
            </template>
            <p v-else class="text-sm font-medium text-gray-800 dark:text-white/90">{{ user?.company_profile?.category?.name || '-' }}</p>
          </div>
        </div>
      </div>

      <!-- Personal Info -->
      <div class="mb-6">
        <h5 v-if="isCompany" class="mb-4 text-base font-medium text-gray-800 dark:text-white/90">{{ t('profile.personalInformation') }}</h5>
        <div class="grid grid-cols-1 gap-x-6 gap-y-4 lg:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs text-gray-500 dark:text-gray-400">{{ t('profile.labels.firstName') }}</label>
            <template v-if="isEditing">
              <input type="text" v-model="form.first_name" class="input-field" :placeholder="t('profile.labels.firstName')" :class="{ 'border-red-500': form.errors.first_name }" />
              <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-600">{{ form.errors.first_name }}</p>
            </template>
            <p v-else class="text-sm font-medium text-gray-800 dark:text-white/90">{{ user?.first_name || '-' }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-xs text-gray-500 dark:text-gray-400">{{ t('profile.labels.lastName') }}</label>
            <template v-if="isEditing">
              <input type="text" v-model="form.last_name" class="input-field" :placeholder="t('profile.labels.lastName')" :class="{ 'border-red-500': form.errors.last_name }" />
              <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-600">{{ form.errors.last_name }}</p>
            </template>
            <p v-else class="text-sm font-medium text-gray-800 dark:text-white/90">{{ user?.last_name || '-' }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-xs text-gray-500 dark:text-gray-400">{{ t('profile.labels.emailAddress') }}</label>
            <template v-if="isEditing">
              <input type="email" v-model="form.email" class="input-field" :placeholder="t('profile.labels.emailAddress')" :class="{ 'border-red-500': form.errors.email }" />
              <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
            </template>
            <p v-else class="text-sm font-medium text-gray-800 dark:text-white/90">{{ user?.email || '-' }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-xs text-gray-500 dark:text-gray-400">{{ t('profile.labels.phone') }}</label>
            <template v-if="isEditing">
              <input type="tel" v-model="form.phone_number" class="input-field" :placeholder="t('profile.labels.phone')" :class="{ 'border-red-500': form.errors.phone_number }" />
              <p v-if="form.errors.phone_number" class="mt-1 text-sm text-red-600">{{ form.errors.phone_number }}</p>
            </template>
            <p v-else class="text-sm font-medium text-gray-800 dark:text-white/90">{{ user?.phone_number || '-' }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-xs text-gray-500 dark:text-gray-400">{{ t('profile.labels.whatsapp') }}</label>
            <template v-if="isEditing">
              <input type="tel" v-model="form.whatsapp_number" class="input-field" :placeholder="t('profile.labels.whatsapp')" :class="{ 'border-red-500': form.errors.whatsapp_number }" />
              <p v-if="form.errors.whatsapp_number" class="mt-1 text-sm text-red-600">{{ form.errors.whatsapp_number }}</p>
            </template>
            <p v-else class="text-sm font-medium text-gray-800 dark:text-white/90">{{ user?.whatsapp_number || '-' }}</p>
          </div>
        </div>
      </div>

      <!-- Social Links -->
      <div>
        <h5 class="mb-4 text-base font-medium text-gray-800 dark:text-white/90">{{ t('profile.socialLinks') }}</h5>
        <div class="grid grid-cols-1 gap-x-6 gap-y-4 lg:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs text-gray-500 dark:text-gray-400">{{ t('profile.labels.facebook') }}</label>
            <template v-if="isEditing">
              <input type="url" v-model="form.facebook" class="input-field" :placeholder="t('profile.labels.facebook')" :class="{ 'border-red-500': form.errors.facebook }" />
              <p v-if="form.errors.facebook" class="mt-1 text-sm text-red-600">{{ form.errors.facebook }}</p>
            </template>
            <p v-else class="text-sm font-medium text-gray-800 dark:text-white/90 truncate">{{ user?.facebook || '-' }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-xs text-gray-500 dark:text-gray-400">{{ t('profile.labels.x') }}</label>
            <template v-if="isEditing">
              <input type="url" v-model="form.x_url" class="input-field" :placeholder="t('profile.labels.x')" :class="{ 'border-red-500': form.errors.x_url }" />
              <p v-if="form.errors.x_url" class="mt-1 text-sm text-red-600">{{ form.errors.x_url }}</p>
            </template>
            <p v-else class="text-sm font-medium text-gray-800 dark:text-white/90 truncate">{{ user?.x_url || '-' }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-xs text-gray-500 dark:text-gray-400">{{ t('profile.labels.linkedin') }}</label>
            <template v-if="isEditing">
              <input type="url" v-model="form.linkedin" class="input-field" :placeholder="t('profile.labels.linkedin')" :class="{ 'border-red-500': form.errors.linkedin }" />
              <p v-if="form.errors.linkedin" class="mt-1 text-sm text-red-600">{{ form.errors.linkedin }}</p>
            </template>
            <p v-else class="text-sm font-medium text-gray-800 dark:text-white/90 truncate">{{ user?.linkedin || '-' }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-xs text-gray-500 dark:text-gray-400">{{ t('profile.labels.instagram') }}</label>
            <template v-if="isEditing">
              <input type="url" v-model="form.instagram" class="input-field" :placeholder="t('profile.labels.instagram')" :class="{ 'border-red-500': form.errors.instagram }" />
              <p v-if="form.errors.instagram" class="mt-1 text-sm text-red-600">{{ form.errors.instagram }}</p>
            </template>
            <p v-else class="text-sm font-medium text-gray-800 dark:text-white/90 truncate">{{ user?.instagram || '-' }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useForm, usePage } from '@inertiajs/vue3'
import { useNotifications } from '@/composables/useNotifications'

const { t } = useI18n()
const { success, error } = useNotifications()

const props = defineProps({
  user: Object
})

const isEditing = ref(false)

const isCompany = computed(() => usePage().url.startsWith('/company'))

const updateRoute = computed(() => {
  if (usePage().url.startsWith('/admin')) return route('admin.profile.update')
  return route('company.profile.update')
})

const form = useForm({
  company_name: props.user?.company_profile?.company_name || '',
  category_id: props.user?.company_profile?.category_id || '',
  first_name: props.user?.first_name || '',
  last_name: props.user?.last_name || '',
  email: props.user?.email || '',
  phone_number: props.user?.phone_number || '',
  whatsapp_number: props.user?.whatsapp_number || '',
  facebook: props.user?.facebook || '',
  x_url: props.user?.x_url || '',
  linkedin: props.user?.linkedin || '',
  instagram: props.user?.instagram || ''
})

const startEditing = () => {
  form.company_name = props.user?.company_profile?.company_name || ''
  form.category_id = props.user?.company_profile?.category_id || ''
  form.first_name = props.user?.first_name || ''
  form.last_name = props.user?.last_name || ''
  form.email = props.user?.email || ''
  form.phone_number = props.user?.phone_number || ''
  form.whatsapp_number = props.user?.whatsapp_number || ''
  form.facebook = props.user?.facebook || ''
  form.x_url = props.user?.x_url || ''
  form.linkedin = props.user?.linkedin || ''
  form.instagram = props.user?.instagram || ''
  isEditing.value = true
}

const cancelEditing = () => {
  form.clearErrors()
  isEditing.value = false
}

const saveProfile = () => {
  form.patch(updateRoute.value, {
    onSuccess: () => {
      isEditing.value = false
      success(t('users.userUpdatedSuccessfully'))
    },
    onError: () => {
      error(t('users.userUpdateFailed'))
    },
    preserveScroll: true,
  })
}
</script>

<style scoped>
@reference "../../assets/main.css";

.input-field {
  @apply h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800;
}
</style>
