<template>
  <div class="space-y-6">
    <!-- Role Information Section -->
    <div
      class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
    >
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">
          {{ t('roles.roleInformation') }}
        </h2>
      </div>
      <div class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-x-5 gap-y-6 md:grid-cols-2">
          <!-- Role Key -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('roles.roleKey') }}</label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ role.name }}</p>
          </div>

          <!-- Display Name (AR) -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('roles.roleNameAr') }}</label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ role.display_name?.ar || 'N/A' }}</p>
          </div>

          <!-- Display Name (EN) -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('roles.roleNameEn') }}</label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ role.display_name?.en || 'N/A' }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Permissions Section -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">
          {{ t('roles.rolePermissions') }}
        </h2>
      </div>
      <div class="max-w-full overflow-x-auto p-4 sm:p-6">
        <table class="w-full min-w-full">
          <thead>
            <tr>
              <th class="border border-gray-100 px-4 py-3 text-start text-sm font-medium text-gray-700 dark:border-gray-800 dark:text-gray-400">
                {{ t('roles.resource') }}
              </th>
              <th v-for="action in actionsHeader" :key="action" class="border border-gray-100 px-4 py-3 text-center text-sm font-medium text-gray-700 dark:border-gray-800 dark:text-gray-400">
                {{ actionLabel(action) }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(permissions, resource) in structuredPermissions" :key="resource">
              <td class="border border-gray-100 px-4 py-3 dark:border-gray-800">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-400">
                  {{ displayResourceLabel(resource) }}
                </span>
              </td>
              <td v-for="action in actionsHeader" :key="action" class="border border-gray-100 px-4 py-3 text-center dark:border-gray-800">
                <div v-if="permissions[action]" class="flex justify-center">
                  <span v-if="hasPermission(resource, action)" class="text-green-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                  </span>
                  <span v-else class="text-error-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Buttons -->
    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
      <Link
        :href="route('admin.roles.index')"
        class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]"
      >
        {{ t('buttons.backToList') }}
      </Link>
      <Link
        :href="route('admin.roles.edit', role.id)"
        class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white transition"
      >
        {{ t('buttons.edit') }}
      </Link>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  role: Object,
  acl: Object,
})

const rolePermissions = computed(() => new Set(props.role.permissions.map(p => p.name)))

const hasPermission = (resource, action) => {
  return rolePermissions.value.has(`${resource}.${action}`)
}

const resourceLabelMap = ref({})
const buildResourceLabelMap = () => {
  const map = {}
  const labels = props.acl?.resource_labels || {}
  Object.keys(props.acl?.resources || {}).forEach((res) => {
    map[res] = labels[res] || {}
  })
  resourceLabelMap.value = map
}

const displayResourceLabel = (resource) => {
  const loc = locale.value
  const dn = resourceLabelMap.value?.[resource]
  return dn?.[loc] ?? dn?.en ?? resource
}

const actionLabel = (action) => {
  const loc = locale.value
  const al = props.acl?.action_labels?.[action]
  return al?.[loc] ?? al?.en ?? action
}

const ACTIONS_ORDER = ['view', 'create', 'update', 'delete']
const actionsHeader = computed(() => {
  const set = new Set()
  Object.values(props.acl?.resources || {}).forEach((arr) => {
    (arr || []).forEach((a) => set.add(a))
  })
  return ACTIONS_ORDER.filter(a => set.has(a))
})

const structuredPermissions = computed(() => {
  const grouped = {}
  buildResourceLabelMap()
  const resources = props.acl?.resources || {}
  Object.keys(resources).forEach((resource) => {
    const allowedActions = resources[resource] || []
    grouped[resource] = {}
    allowedActions.forEach((action) => {
      grouped[resource][action] = { name: `${resource}.${action}` }
    })
  })
  return grouped
})
</script>
