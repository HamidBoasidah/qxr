<template>
  <CompanyLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />

    <AddUserForm
      :categories="categories"
    />
  </CompanyLayout>
</template>

<script setup>
import CompanyLayout from '@/components/layout/CompanyLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AddUserForm from '@/components/admin/user/CreateUser.vue'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'


const { t } = useI18n()
const currentPageTitle = computed(() => t('users.addUser'))

// استلام البيانات من Inertia
const categories = computed(() => usePage().props.categories)

</script>
