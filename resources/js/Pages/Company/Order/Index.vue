<template>
  <CompanyLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard :title="currentPageTitle">
        <ShowOrders :orders="orders" />
      </ComponentCard>
    </div>
  </CompanyLayout>
</template>

<script setup>
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import CompanyLayout from '@/components/layout/CompanyLayout.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import ShowOrders from '@/components/company/order/ShowOrders.vue'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const currentPageTitle = computed(() => t('menu.orders'))

const orders = computed(() => usePage().props.orders)
</script>
