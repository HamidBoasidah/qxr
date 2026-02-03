<template>
  <admin-layout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />

    <AddUserForm 
      :roles="roles" />
  </admin-layout>
</template>

<script setup>
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AddUserForm from '@/components/admin/user/CreateUser.vue'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { usePage } from '@inertiajs/vue3'


const { t } = useI18n()
const currentPageTitle = computed(() => t('users.addUser'))

// استلام البيانات من Inertia

const roles = computed(() => usePage().props.roles)

</script>
