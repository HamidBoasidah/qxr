<template>
  <CompanyLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />

    <CreateOffer
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
import CreateOffer from '@/components/company/offer/CreateOffer.vue'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const currentPageTitle = computed(() => t('offers.createOffer') || 'Create Offer')

const products = computed(() => usePage().props.products)
const customerCategories = computed(() => usePage().props.customerCategories)
const customerTags = computed(() => usePage().props.customerTags)
const customers = computed(() => usePage().props.customers)
</script>
