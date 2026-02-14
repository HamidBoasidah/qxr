<template>
  <CompanyLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />

    <EditOffer
      :offer="offer"
      :products="products"
      :customer-categories="customerCategories"
      :customer-tags="customerTags"
      :customers="customers"
    />
  </CompanyLayout>
</template>

<script setup>
import CompanyLayout from '@/components/layout/CompanyLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import EditOffer from '@/components/company/offer/EditOffer.vue'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const currentPageTitle = computed(() => t('offers.editOffer') || 'Edit Offer')

const offer = computed(() => usePage().props.offer)
const products = computed(() => usePage().props.products)
const customerCategories = computed(() => usePage().props.customerCategories)
const customerTags = computed(() => usePage().props.customerTags)
const customers = computed(() => usePage().props.customers)
</script>
