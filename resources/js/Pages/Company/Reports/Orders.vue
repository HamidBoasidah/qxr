<template>
  <CompanyLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <ShowOrdersReport 
      :orders="orders" 
      :summary="summary" 
      :filters="filters" 
    />
  </CompanyLayout>
</template>

<script setup>
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import CompanyLayout from '@/components/layout/CompanyLayout.vue'
import ShowOrdersReport from '@/components/company/report/ShowOrdersReport.vue'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const currentPageTitle = computed(() => t('reports.orders') || 'تقرير الطلبات')

const props = defineProps({
  orders: Object,
  summary: Object,
  filters: Object
})

const orders = computed(() => props.orders)
const summary = computed(() => props.summary)
const filters = computed(() => props.filters)
</script>
