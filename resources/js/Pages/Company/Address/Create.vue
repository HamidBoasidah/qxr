<template>
  <CompanyLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />

    <CreateAddress
      :governorates="governorates"
      :districts="districts"
      :areas="areas"
    />
  </CompanyLayout>
</template>

<script setup>
import CompanyLayout from '@/components/layout/CompanyLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import CreateAddress from '@/components/company/address/CreateAddress.vue'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { usePage } from '@inertiajs/vue3'

const { t } = useI18n()
const currentPageTitle = computed(() => t('addresses.addAddress'))

const governorates = computed(() => usePage().props.governorates)
const districts = computed(() => usePage().props.districts)
const areas = computed(() => usePage().props.areas)
</script>
