<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <ShowReturnInvoice :returnInvoice="returnInvoice" />
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ShowReturnInvoice from '@/components/admin/returnInvoice/ShowReturnInvoice.vue'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const currentPageTitle = computed(() => t('returnInvoice.showReturnInvoice') || 'عرض فاتورة الاسترجاع')
const returnInvoice = computed(() => usePage().props.returnInvoice)
</script>
