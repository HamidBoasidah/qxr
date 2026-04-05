<template>
  <div class="space-y-6">
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('returnPolicy.policyInformation') || 'معلومات السياسة' }}</h2>
      </div>
      <div class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

          <!-- Name -->
          <div class="md:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ t('returnPolicy.name') || 'الاسم' }} <span class="text-error-500">*</span></label>
            <input v-model="form.name" type="text" :placeholder="t('returnPolicy.namePlaceholder') || 'اسم السياسة'" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            <p v-if="form.errors.name" class="mt-1 text-sm text-error-500">{{ form.errors.name }}</p>
          </div>

          <!-- Return Window Days -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ t('returnPolicy.return_window_days') || 'نافذة الاسترجاع (أيام)' }} <span class="text-error-500">*</span></label>
            <input v-model.number="form.return_window_days" type="number" min="1" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            <p v-if="form.errors.return_window_days" class="mt-1 text-sm text-error-500">{{ form.errors.return_window_days }}</p>
          </div>

          <!-- Max Return Ratio -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ t('returnPolicy.max_return_ratio') || 'نسبة الاسترجاع القصوى (0.01 - 1.00)' }} <span class="text-error-500">*</span></label>
            <input v-model.number="form.max_return_ratio" type="number" min="0.01" max="1.00" step="0.01" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            <p v-if="form.errors.max_return_ratio" class="mt-1 text-sm text-error-500">{{ form.errors.max_return_ratio }}</p>
          </div>

          <!-- Min Days Before Expiry -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ t('returnPolicy.min_days_before_expiry') || 'الحد الأدنى للأيام قبل الانتهاء (0 = معطّل)' }}</label>
            <input v-model.number="form.min_days_before_expiry" type="number" min="0" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            <p v-if="form.errors.min_days_before_expiry" class="mt-1 text-sm text-error-500">{{ form.errors.min_days_before_expiry }}</p>
          </div>

          <!-- Discount Deduction -->
          <div class="flex items-center gap-3">
            <input v-model="form.discount_deduction_enabled" type="checkbox" id="discount_deduction" class="w-4 h-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
            <label for="discount_deduction" class="text-sm font-medium text-gray-700 dark:text-gray-400">{{ t('returnPolicy.discount_deduction_enabled') || 'خصم مبلغ الاسترجاع' }}</label>
          </div>

          <!-- Is Default -->
          <div class="flex items-center gap-3">
            <input v-model="form.is_default" type="checkbox" id="is_default" class="w-4 h-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
            <label for="is_default" class="text-sm font-medium text-gray-700 dark:text-gray-400">{{ t('returnPolicy.is_default') || 'سياسة افتراضية' }}</label>
          </div>

          <!-- Is Active -->
          <div class="flex items-center gap-3">
            <input v-model="form.is_active" type="checkbox" id="is_active" class="w-4 h-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
            <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-400">{{ t('returnPolicy.is_active') || 'نشطة' }}</label>
          </div>

          <!-- Bonus Return -->
          <div class="flex items-center gap-3">
            <input v-model="form.bonus_return_enabled" type="checkbox" id="bonus_return" class="w-4 h-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
            <label for="bonus_return" class="text-sm font-medium text-gray-700 dark:text-gray-400">{{ t('returnPolicy.bonus_return_enabled') || 'تفعيل استرجاع البونص' }}</label>
          </div>

          <!-- Bonus Return Ratio (conditional) -->
          <div v-if="form.bonus_return_enabled">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ t('returnPolicy.bonus_return_ratio') || 'نسبة استرجاع البونص (0.00 - 1.00)' }} <span class="text-error-500">*</span></label>
            <input v-model.number="form.bonus_return_ratio" type="number" min="0" max="1.00" step="0.01" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            <p v-if="form.errors.bonus_return_ratio" class="mt-1 text-sm text-error-500">{{ form.errors.bonus_return_ratio }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
      <Link :href="route('company.return-policies.index')" class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
        {{ t('buttons.cancel') || 'إلغاء' }}
      </Link>
      <button @click="submit" :disabled="form.processing" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white shadow-sm transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50">
        <svg v-if="form.processing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
        {{ t('returnPolicy.createPolicy') || 'إنشاء السياسة' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const form = useForm({
  name: '',
  return_window_days: 30,
  max_return_ratio: 1.0,
  bonus_return_enabled: false,
  bonus_return_ratio: null,
  discount_deduction_enabled: true,
  min_days_before_expiry: 0,
  is_default: false,
  is_active: true,
})

function submit() {
  form.post(route('company.return-policies.store'))
}
</script>
