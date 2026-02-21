<template>
  <CompanyLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <ShowOrderForm :order="order"/>
  </CompanyLayout>
</template>

<script setup>
import CompanyLayout from '@/components/layout/CompanyLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ShowOrderForm from '@/components/company/order/ShowOrder.vue'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { usePage } from '@inertiajs/vue3'


const { t } = useI18n()
const currentPageTitle = computed(() => t('order.showOrder'))

// استلام البيانات من Inertia

const order = computed(() => usePage().props.order)

</script>
