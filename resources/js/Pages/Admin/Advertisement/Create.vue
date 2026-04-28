<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="t('advertisements.create')" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard :title="t('advertisements.create')">
        <CreateAdvertisement :companies="companies" />
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import CreateAdvertisement from '@/components/admin/advertisement/CreateAdvertisement.vue'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const companies = computed(() => usePage().props.companies)
</script>
