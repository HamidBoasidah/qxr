<template>
  <CompanyLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard :title="currentPageTitle">
        <ShowUsers :users="users" />
      </ComponentCard>
    </div>
  </CompanyLayout>
</template>

<script setup>
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import CompanyLayout from '@/components/layout/CompanyLayout.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import ShowUsers from '@/components/admin/user/ShowUsers.vue'
import { usePage } from '@inertiajs/vue3' // أضف هذا السطر
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const currentPageTitle = computed(() => t('menu.users'))

const users = computed(() => usePage().props.users)

</script>
