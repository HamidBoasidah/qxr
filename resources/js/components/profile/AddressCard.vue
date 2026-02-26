<template>
  <div>
    <div class="p-5 mb-6 border border-gray-200 rounded-2xl dark:border-gray-800 lg:p-6">
      <div class="flex items-center justify-between mb-6">
        <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ t('profile.address.title') }}</h4>
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
            <button @click="saveAddress" type="button" class="flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
              {{ t('buttons.saveChanges') }}
            </button>
          </template>
        </div>
      </div>

      <div>
        <label class="mb-1.5 block text-xs text-gray-500 dark:text-gray-400">{{ t('profile.labels.address') }}</label>
        <template v-if="isEditing">
          <textarea v-model="form.address" rows="3"
            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
            :placeholder="t('profile.labels.address')"
            :class="{ 'border-red-500': form.errors.address }"
          ></textarea>
          <p v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</p>
        </template>
        <p v-else class="text-sm font-medium text-gray-800 dark:text-white/90">{{ user?.address || '-' }}</p>
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

const props = defineProps({ user: Object })

const isEditing = ref(false)

const updateRoute = computed(() => {
  if (usePage().url.startsWith('/admin')) return route('admin.profile.update')
  return route('company.profile.update')
})

const form = useForm({ address: props.user?.address || '' })

const startEditing = () => {
  form.address = props.user?.address || ''
  isEditing.value = true
}
const cancelEditing = () => { form.clearErrors(); isEditing.value = false }

const saveAddress = () => {
  form.patch(updateRoute.value, {
    onSuccess: () => { isEditing.value = false; success(t('users.userUpdatedSuccessfully')) },
    onError: () => { error(t('users.userUpdateFailed')) },
    preserveScroll: true,
  })
}
</script>
