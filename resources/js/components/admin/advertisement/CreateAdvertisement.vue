<template>
  <form @submit.prevent="submit" class="space-y-6 p-4 sm:p-6">
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
      <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ t('advertisements.titleField') }} *</label>
        <input v-model="form.title" type="text" class="dark:bg-gray-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white/90" />
        <p v-if="form.errors.title" class="mt-1 text-sm text-red-500">{{ form.errors.title }}</p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ t('advertisements.position') }} *</label>
        <select v-model="form.position" class="dark:bg-gray-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white/90">
          <option value="home_slider">{{ t('advertisements.positions.home_slider') }}</option>
          <option value="home_banner">{{ t('advertisements.positions.home_banner') }}</option>
          <option value="category_banner">{{ t('advertisements.positions.category_banner') }}</option>
          <option value="popup">{{ t('advertisements.positions.popup') }}</option>
        </select>
      </div>

      <div class="sm:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ t('advertisements.description') }}</label>
        <textarea v-model="form.description" rows="3" class="dark:bg-gray-900 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white/90"></textarea>
      </div>

      <div class="sm:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ t('advertisements.image') }} *</label>
        <input type="file" @change="form.image = $event.target.files[0]" accept="image/*" class="w-full text-sm" />
        <p v-if="form.errors.image" class="mt-1 text-sm text-red-500">{{ form.errors.image }}</p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ t('advertisements.company') }}</label>
        <select v-model="form.company_user_id" class="dark:bg-gray-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white/90">
          <option :value="null">{{ t('common.none') }}</option>
          <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.first_name }} {{ c.last_name }}</option>
        </select>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ t('advertisements.linkUrl') }}</label>
        <input v-model="form.link_url" type="url" class="dark:bg-gray-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white/90" />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ t('advertisements.startDate') }}</label>
        <input v-model="form.start_date" type="date" class="dark:bg-gray-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white/90" />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ t('advertisements.endDate') }}</label>
        <input v-model="form.end_date" type="date" class="dark:bg-gray-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white/90" />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ t('advertisements.sortOrder') }}</label>
        <input v-model="form.sort_order" type="number" min="0" class="dark:bg-gray-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white/90" />
      </div>

      <div class="flex items-center gap-2">
        <input v-model="form.is_active" type="checkbox" id="is_active" class="h-4 w-4 rounded border-gray-300" />
        <label for="is_active" class="text-sm text-gray-700 dark:text-gray-400">{{ t('common.active') }}</label>
      </div>
    </div>

    <div class="flex justify-end">
      <button type="submit" :disabled="form.processing" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600 disabled:opacity-50">
        {{ t('common.save') }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { route } from '@/route'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const props = defineProps({ companies: Array })

const form = useForm({
  title: '',
  description: '',
  image: null,
  position: 'home_slider',
  link_url: '',
  company_user_id: null,
  start_date: '',
  end_date: '',
  sort_order: 0,
  is_active: true,
})

const submit = () => {
  form.post(route('admin.advertisements.store'), {
    forceFormData: true,
  })
}
</script>
