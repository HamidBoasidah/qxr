<template>
  <CompanyLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <ShowProductForm :product="product"/>
  </CompanyLayout>
</template>

<script setup>
import CompanyLayout from '@/components/layout/CompanyLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ShowProductForm from '@/components/company/product/ShowProduct.vue'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { usePage } from '@inertiajs/vue3'


const { t } = useI18n()
const currentPageTitle = computed(() => t('product.showProduct'))

// استلام البيانات من Inertia

const product = computed(() => usePage().props.product)

</script>
