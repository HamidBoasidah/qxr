<template>
  <admin-layout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />

    <EditUserForm
      :user="user"
      :categories="categories"
    />
  </admin-layout>
</template>

<script setup>
import AdminLayout from '@/components/layout/AdminLayout.vue'
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
