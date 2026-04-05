<template>
  <div class="space-y-6">
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('returnPolicy.policyDetails') || 'تفاصيل السياسة' }}</h2>
      </div>
      <div class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-x-5 gap-y-6 md:grid-cols-2">
          <InfoItem :label="t('returnPolicy.name') || 'الاسم'" :value="policy.name" />
          <InfoItem :label="t('common.status')">
            <Badge :color="policy.is_active ? 'success' : 'dark'" variant="light" size="sm">
              {{ policy.is_active ? (t('common.active') || 'نشطة') : (t('common.inactive') || 'غير نشطة') }}
            </Badge>
          </InfoItem>
          <InfoItem :label="t('returnPolicy.is_default') || 'افتراضية'">
            <Badge :color="policy.is_default ? 'success' : 'light'" variant="light" size="sm">
              {{ policy.is_default ? (t('common.yes') || 'نعم') : (t('common.no') || 'لا') }}
            </Badge>
          </InfoItem>
          <InfoItem :label="t('returnPolicy.return_window_days') || 'نافذة الاسترجاع'" :value="`${policy.return_window_days} ${t('returnPolicy.days') || 'يوم'}`" />
          <InfoItem :label="t('returnPolicy.max_return_ratio') || 'نسبة الاسترجاع القصوى'" :value="`${(policy.max_return_ratio * 100).toFixed(0)}%`" />
          <InfoItem :label="t('returnPolicy.discount_deduction_enabled') || 'خصم الاسترجاع'">
            <Badge :color="policy.discount_deduction_enabled ? 'info' : 'light'" variant="light" size="sm">
              {{ policy.discount_deduction_enabled ? (t('common.yes') || 'نعم') : (t('common.no') || 'لا') }}
            </Badge>
          </InfoItem>
          <InfoItem :label="t('returnPolicy.min_days_before_expiry') || 'الحد الأدنى للأيام قبل الانتهاء'" :value="policy.min_days_before_expiry === 0 ? (t('returnPolicy.disabled') || 'معطّل') : `${policy.min_days_before_expiry} ${t('returnPolicy.days') || 'يوم'}`" />
          <InfoItem :label="t('returnPolicy.bonus_return_enabled') || 'استرجاع البونص'">
            <Badge :color="policy.bonus_return_enabled ? 'info' : 'light'" variant="light" size="sm">
              {{ policy.bonus_return_enabled ? (t('common.yes') || 'نعم') : (t('common.no') || 'لا') }}
            </Badge>
          </InfoItem>
          <InfoItem v-if="policy.bonus_return_enabled" :label="t('returnPolicy.bonus_return_ratio') || 'نسبة استرجاع البونص'" :value="`${(policy.bonus_return_ratio * 100).toFixed(0)}%`" />
        </div>
      </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
      <Link :href="route('company.return-policies.edit', policy.id)" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white hover:bg-brand-600 transition">
        {{ t('common.edit') || 'تعديل' }}
      </Link>
      <Link :href="route('company.return-policies.index')" class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
        {{ t('buttons.backToList') || 'العودة للقائمة' }}
      </Link>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import Badge from '@/components/ui/Badge.vue'
import InfoItem from '@/components/common/InfoItem.vue'

const { t } = useI18n()
defineProps({ policy: { type: Object, required: true } })
</script>
