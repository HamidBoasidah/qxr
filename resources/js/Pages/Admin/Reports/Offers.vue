<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <ShowOffersReport 
      :offers="offers" 
      :summary="summary" 
      :filters="filters" 
    />
  </AdminLayout>
</template>

<script setup>
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import ShowOffersReport from '@/components/admin/report/ShowOffersReport.vue'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const currentPageTitle = computed(() => t('reports.offers') || 'تقرير العروض')

const props = defineProps({
  offers: Object,
  summary: Object,
  filters: Object
})

const offers = computed(() => props.offers)
const summary = computed(() => props.summary)
const filters = computed(() => props.filters)
</script>
