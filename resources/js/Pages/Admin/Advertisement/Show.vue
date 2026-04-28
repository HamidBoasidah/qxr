<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="t('advertisements.show')" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard :title="t('advertisements.show')">
        <ShowAdvertisement :advertisement="advertisement" />
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import ShowAdvertisement from '@/components/admin/advertisement/ShowAdvertisement.vue'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const advertisement = computed(() => usePage().props.advertisement)
</script>
