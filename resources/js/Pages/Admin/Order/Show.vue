<template>
  <admin-layout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <ShowOrderForm :order="order"/>
  </admin-layout>
</template>

<script setup>
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ShowOrderForm from '@/components/admin/order/ShowOrder.vue'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { usePage } from '@inertiajs/vue3'


const { t } = useI18n()
const currentPageTitle = computed(() => t('order.showOrder'))

// استلام البيانات من Inertia

const order = computed(() => usePage().props.order)

</script>
