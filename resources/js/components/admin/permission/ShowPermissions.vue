<template>
  <div class="space-y-6">
    <!-- Permissions Section -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
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
            <tr v-for="(actions, resource) in structuredPermissions" :key="resource">
              <td class="border border-gray-100 px-4 py-3 dark:border-gray-800">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-400">
                  {{ displayResourceLabel(resource) }}
                </span>
              </td>
              <td v-for="action in actionsHeader" :key="action" class="border border-gray-100 px-4 py-3 text-center dark:border-gray-800">
                <div v-if="isPermissionDefined(resource, action)" class="flex items-center justify-center">
                  <span class="text-green-600 text-xs">{{ t('permissions.available') }}</span>
                </div>
                <div v-else class="flex items-center justify-center">
                  <span class="text-gray-500 text-xs">{{ t('permissions.unavailable') }}</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  acl: Object,
})

const isPermissionDefined = (resource, action) => {
  return props.acl?.resources?.[resource]?.includes(action) ?? false
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
  if (props.acl?.resources) {
    Object.values(props.acl.resources).forEach((arr) => {
      (arr || []).forEach((a) => set.add(a))
    })
  }
  return ACTIONS_ORDER.filter(a => set.has(a))
})

const structuredPermissions = computed(() => {
  buildResourceLabelMap()
  return props.acl?.resources || {}
})
</script>
