<template>
  <CompanyLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <ShowReturnPolicy :policy="policy" />
  </CompanyLayout>
</template>

<script setup>
import CompanyLayout from '@/components/layout/CompanyLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ShowReturnPolicy from '@/components/company/returnPolicy/ShowReturnPolicy.vue'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const currentPageTitle = computed(() => t('returnPolicy.showPolicy') || 'عرض سياسة الاسترجاع')
const policy = computed(() => usePage().props.policy)
</script>
