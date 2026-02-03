<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />

    <CreateAddress
      :governorates="governorates"
      :districts="districts"
      :areas="areas"
    />
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/Components/common/PageBreadcrumb.vue'
import CreateAddress from '@/Components/admin/address/CreateAddress.vue'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { usePage } from '@inertiajs/vue3'

const { t } = useI18n()
const currentPageTitle = computed(() => t('addresses.addAddress'))

const governorates = computed(() => usePage().props.governorates)
const districts = computed(() => usePage().props.districts)
const areas = computed(() => usePage().props.areas)
</script>
