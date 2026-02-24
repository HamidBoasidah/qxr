<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <ShowProductsReport 
      :products="products" 
      :summary="summary" 
      :filters="filters" 
    />
  </AdminLayout>
</template>

<script setup>
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import ShowProductsReport from '@/components/admin/report/ShowProductsReport.vue'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const currentPageTitle = computed(() => t('reports.products') || 'تقرير المنتجات')

const props = defineProps({
  products: Object,
  summary: Object,
  filters: Object
})

const products = computed(() => props.products)
const summary = computed(() => props.summary)
const filters = computed(() => props.filters)
</script>
