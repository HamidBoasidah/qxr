<template>
  <CompanyLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <ShowOffersReport 
      :offers="offers" 
      :summary="summary" 
      :filters="filters" 
    />
  </CompanyLayout>
</template>

<script setup>
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import CompanyLayout from '@/components/layout/CompanyLayout.vue'
import ShowOffersReport from '@/components/company/report/ShowOffersReport.vue'
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
