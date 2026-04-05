<template>
  <CompanyLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <ShowReturnInvoice :returnInvoice="returnInvoice" />
  </CompanyLayout>
</template>

<script setup>
import CompanyLayout from '@/components/layout/CompanyLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ShowReturnInvoice from '@/components/company/returnInvoice/ShowReturnInvoice.vue'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const currentPageTitle = computed(() => t('returnInvoice.showReturnInvoice') || 'عرض فاتورة الاسترجاع')
const returnInvoice = computed(() => usePage().props.returnInvoice)
</script>
