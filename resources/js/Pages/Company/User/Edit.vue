<template>
  <CompanyLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />

    <EditUserForm
      :user="user"
      :categories="categories"
    />
  </CompanyLayout>
</template>

<script setup>
import CompanyLayout from '@/components/layout/CompanyLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import EditUserForm from '@/components/admin/user/EditUser.vue'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'


const { t } = useI18n()
const currentPageTitle = computed(() => t('users.editUser'))

// استلام البيانات من Inertia
const user = computed(() => usePage().props.user)
const categories = computed(() => usePage().props.categories)


</script>
