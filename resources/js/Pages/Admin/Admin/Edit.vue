<template>
  <admin-layout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />

    <EditAdminForm
    :admin="admin"
    :roles="roles"
    />
  </admin-layout>
</template>

<script setup>
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import EditAdminForm from '@/components/admin/admin/EditAdmin.vue'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { usePage } from '@inertiajs/vue3'


const { t } = useI18n()
const currentPageTitle = computed(() => t('admins.editAdmin'))

// استلام البيانات من Inertia
const admin = computed(() => usePage().props.admin)
const roles = computed(() => usePage().props.roles)


</script>
