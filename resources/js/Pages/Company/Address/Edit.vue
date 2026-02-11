<template>
  <CompanyLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <EditAddress
      :address="address"
      :governorates="governorates"
      :districts="districts"
      :areas="areas"
    />
  </CompanyLayout>
</template>

<script setup>
import CompanyLayout from '@/components/layout/CompanyLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import EditAddress from '@/components/company/address/EditAddress.vue'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { usePage } from '@inertiajs/vue3'

const { t } = useI18n()
const currentPageTitle = computed(() => t('addresses.editAddress'))

const address = computed(() => usePage().props.address)
const governorates = computed(() => usePage().props.governorates)
const districts = computed(() => usePage().props.districts)
const areas = computed(() => usePage().props.areas)
</script>
