<template>
  <CompanyLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <EditReturnPolicy :policy="policy" />
  </CompanyLayout>
</template>

<script setup>
import CompanyLayout from '@/components/layout/CompanyLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import EditReturnPolicy from '@/components/company/returnPolicy/EditReturnPolicy.vue'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const currentPageTitle = computed(() => t('returnPolicy.editPolicy') || 'تعديل سياسة الاسترجاع')
const policy = computed(() => usePage().props.policy)
</script>
