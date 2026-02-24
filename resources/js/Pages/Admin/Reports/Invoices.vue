<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <ShowInvoicesReport 
      :invoices="invoices" 
      :summary="summary" 
      :filters="filters" 
    />
  </AdminLayout>
</template>

<script setup>
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import ShowInvoicesReport from '@/components/admin/report/ShowInvoicesReport.vue'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const currentPageTitle = computed(() => t('reports.invoices') || 'تقرير الفواتير')

const props = defineProps({
  invoices: Object,
  summary: Object,
  filters: Object
})

const invoices = computed(() => props.invoices)
const summary = computed(() => props.summary)
const filters = computed(() => props.filters)
</script>
