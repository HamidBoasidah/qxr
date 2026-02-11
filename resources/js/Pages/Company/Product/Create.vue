<template>
  <CompanyLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />

    <AddProductForm
      :categories="categories"
      :tags="tags"
    />
  </CompanyLayout>
</template>

<script setup>
import CompanyLayout from '@/components/layout/CompanyLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AddProductForm from '@/components/company/product/CreateProduct.vue'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const currentPageTitle = computed(() => t('product.addProduct'))

// استلام البيانات من Inertia
const categories = computed(() => usePage().props.categories)
const tags = computed(() => usePage().props.tags)

</script>
