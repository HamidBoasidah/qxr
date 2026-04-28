<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="t('advertisements.title')" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard :title="t('advertisements.title')">
        <ShowAdvertisements :advertisements="advertisements" />
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import ShowAdvertisements from '@/components/admin/advertisement/ShowAdvertisements.vue'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const advertisements = computed(() => usePage().props.advertisements)
</script>
