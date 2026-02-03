<template>
  <div class="space-y-6">
    <!-- Role Information Section -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">
          {{ t('roles.roleInformation') }}
        </h2>
      </div>

      <div class="p-4 sm:p-6">
        <form @submit.prevent>
          <!-- الحقل التقني (الـ slug) -->
          <div>
            <label for="role-key" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('roles.roleKey') }}
            </label>
            <input
              v-model="form.name"
              type="text"
              id="role-key"
              class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
              :placeholder="t('roles.roleKeyPlaceholder')"
            />
          </div>

          <!-- الاسم الظاهر متعدد اللغات -->
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mt-4">
            <div>
              <label for="role-name-en" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                {{ t('roles.roleNameEn') }}
              </label>
              <input
                v-model="form.display_name.en"
                type="text"
                id="role-name-en"
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                placeholder="Super Admin"
              />
            </div>

            <div>
              <label for="role-name-ar" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                {{ t('roles.roleNameAr') }}
              </label>
              <input
                v-model="form.display_name.ar"
                type="text"
                id="role-name-ar"
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                placeholder="مدير النظام"
              />
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Permissions Section -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex flex-col gap-4 border-b border-gray-200 px-6 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">
          {{ t('roles.rolePermissions') }}
        </h2>

        <div>
          <label for="selectAll" class="flex cursor-pointer select-none items-center text-sm font-medium text-gray-700 dark:text-gray-400">
            <div class="relative">
              <input
                type="checkbox"
                id="selectAll"
                v-model="selectAllGlobal"
                class="sr-only"
                @change="toggleSelectAllGlobal"
              />
              <div
                :class="selectAllGlobal ? 'border-brand-500 bg-brand-500' : 'bg-transparent border-gray-300 dark:border-gray-700'"
                class="mr-3 flex h-5 w-5 items-center justify-center rounded-md border-[1.25px] hover:border-brand-500 dark:hover:border-brand-500"
              >
                <span :class="selectAllGlobal ? '' : 'opacity-0'">
                  <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7" stroke="white" stroke-width="1.94437" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </span>
              </div>
            </div>
            {{ t('roles.selectAll') }}
          </label>
        </div>
      </div>

      <div class="max-w-full overflow-x-auto p-4 sm:p-6">
        <table class="w-full min-w-full">
          <thead>
            <tr>
              <th class="border border-gray-100 px-4 py-3 text-start text-sm font-medium text-gray-700 dark:border-gray-800 dark:text-gray-400">
                {{ t('roles.resource') }}
              </th>
              <th
                v-for="action in actionsHeader"
                :key="action"
                class="border border-gray-100 px-4 py-3 text-start text-sm font-medium text-gray-700 dark:border-gray-800 dark:text-gray-400"
              >
                {{ actionLabel(action) }}
              </th>
              <th class="border border-gray-100 px-4 py-3 text-start text-sm font-medium text-gray-700 dark:border-gray-800 dark:text-gray-400">
                {{ t('roles.all') }}
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

              <td
                v-for="action in actionsHeader"
                :key="action"
                class="border border-gray-100 px-4 py-3 dark:border-gray-800"
              >
                <div v-if="permissions[action]">
                  <label
                    :for="`checkbox-${resource}-${action}`"
                    class="flex cursor-pointer select-none items-center text-sm font-medium text-gray-700 dark:text-gray-400"
                  >
                    <div class="relative">
                      <input
                        type="checkbox"
                        :id="`checkbox-${resource}-${action}`"
                        v-model="permissions[action].selected"
                        class="sr-only"
                        @change="handlePermissionToggle(resource)"
                      />
                      <div
                        :class="permissions[action].selected ? 'border-brand-500 bg-brand-500' : 'bg-transparent border-gray-300 dark:border-gray-700'"
                        class="flex h-5 w-5 items-center justify-center rounded-md border-[1.25px] hover:border-brand-500 dark:hover:border-brand-500"
                      >
                        <span :class="permissions[action].selected ? '' : 'opacity-0'">
                          <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7" stroke="white" stroke-width="1.94437" stroke-linecap="round" stroke-linejoin="round" />
                          </svg>
                        </span>
                      </div>
                    </div>
                  </label>
                </div>
              </td>

              <td class="border border-gray-100 px-4 py-3 dark:border-gray-800">
                <label
                  :for="`checkbox-${resource}-all`"
                  class="flex cursor-pointer select-none items-center text-sm font-medium text-gray-700 dark:text-gray-400"
                >
                  <div class="relative">
                    <input
                      type="checkbox"
                      :id="`checkbox-${resource}-all`"
                      v-model="selectAllRow[resource]"
                      class="sr-only"
                      @change="handleRowSelectAll(resource)"
                    />
                    <div
                      :class="selectAllRow[resource] ? 'border-brand-500 bg-brand-500' : 'bg-transparent border-gray-300 dark:border-gray-700'"
                      class="flex h-5 w-5 items-center justify-center rounded-md border-[1.25px] hover:border-brand-500 dark:hover:border-brand-500"
                    >
                      <span :class="selectAllRow[resource] ? '' : 'opacity-0'">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7" stroke="white" stroke-width="1.94437" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                      </span>
                    </div>
                  </div>
                </label>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Buttons -->
    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
      <button
        @click="cancel"
        class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]"
      >
        {{ t('buttons.cancel') }}
      </button>
      <button
        @click="createRole"
        class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white transition"
        :disabled="form.processing"
      >
        {{ t('buttons.create') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { useNotifications } from '@/composables/useNotifications'

const { t, locale } = useI18n()
const { success, error } = useNotifications()

/**
 * نتوقع أن يرسل السيرفر:
 * props.acl = {
 *   resources: { 'users': ['view','create','update','delete'], 'dashboard': ['view'], ... },
 *   resource_labels: { users: {en:'Users', ar:'المستخدمون'}, ... },
 *   action_labels:   { view: {en:'View', ar:'عرض'}, create: {...}, ... }
 * }
 * (اختياري) props.preselected: مصفوفة أذونات مُختارة مسبقًا (عند التعديل)
 */
const props = defineProps({
  acl: Object,
  preselected: { type: Array, default: () => [] },
})

// خريطة "resource" -> "display_name" من config
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
  const dn  = resourceLabelMap.value?.[resource]
  return dn?.[loc] ?? dn?.en ?? resource
}

// لافتات الأفعال في الترويسات
const actionLabel = (action) => {
  const loc = locale.value
  const al  = props.acl?.action_labels?.[action]
  return al?.[loc] ?? al?.en ?? action
}

// ترتيب ترويسة الأفعال: view ثم CRUD
const ACTIONS_ORDER = ['view','create','update','delete']
// اتحاد كل الأفعال الموجودة في ACL بترتيب منطقي
const actionsHeader = computed(() => {
  const set = new Set()
  Object.values(props.acl?.resources || {}).forEach((arr) => {
    (arr || []).forEach((a) => set.add(a))
  })
  return ACTIONS_ORDER.filter(a => set.has(a))
})

/**
 * نموذج الدور الجديد
 * - display_name مترجم للـ Role فقط (الدور ديناميكي)
 * - permissions: أسماء Spatie مثل users.view
 */
const form = useForm({
  name: '',
  display_name: { en: '', ar: '' },
  permissions: [],
})

const structuredPermissions = ref({})
const selectAllRow = ref({})
const selectAllGlobal = ref(false)

const availableActions = (resource) =>
  actionsHeader.value.filter((action) => structuredPermissions.value[resource]?.[action])

// بناء جدول الصلاحيات من ACL
const processPermissions = () => {
  const grouped = {}
  buildResourceLabelMap()

  const resources = props.acl?.resources || {}
  const selectedSet = new Set(props.preselected || [])

  Object.keys(resources).forEach((resource) => {
    const allowedActions = resources[resource] || []
    grouped[resource] = {}
    allowedActions.forEach((action) => {
      const name = `${resource}.${action}`
      grouped[resource][action] = { name, selected: selectedSet.has(name) }
    })
    // تهيئة selectAllRow حسب الحالة المبدئية
    selectAllRow.value[resource] = allowedActions.length
      ? allowedActions.every((a) => grouped[resource][a]?.selected)
      : false
  })

  structuredPermissions.value = grouped
  syncFormPermissions()
  syncGlobalState()
}

const syncFormPermissions = () => {
  // نضيف الأفعال الفعلية فقط (لا نستخدم wildcard عند الحفظ)
  const finalPermissions = new Set()
  Object.keys(structuredPermissions.value).forEach((resource) => {
    availableActions(resource).forEach((action) => {
      if (structuredPermissions.value[resource][action]?.selected) {
        finalPermissions.add(structuredPermissions.value[resource][action].name)
      }
    })
  })
  form.permissions = Array.from(finalPermissions)
}

const syncRowState = (resource) => {
  const actionsForResource = availableActions(resource)
  if (!actionsForResource.length) {
    selectAllRow.value[resource] = false
    return
  }
  selectAllRow.value[resource] = actionsForResource.every(
    (action) => structuredPermissions.value[resource][action]?.selected,
  )
}

const syncGlobalState = () => {
  const resources = Object.keys(structuredPermissions.value)
  if (!resources.length) {
    selectAllGlobal.value = false
    return
  }
  selectAllGlobal.value = resources.every((resource) => selectAllRow.value[resource])
}

const handleRowSelectAll = (resource) => {
  const isSelected = selectAllRow.value[resource]
  availableActions(resource).forEach((action) => {
    structuredPermissions.value[resource][action].selected = isSelected
  })
  syncFormPermissions()
  syncGlobalState()
}

const handlePermissionToggle = (resource) => {
  syncRowState(resource)
  syncFormPermissions()
  syncGlobalState()
}

const toggleSelectAllGlobal = () => {
  const isSelected = selectAllGlobal.value
  Object.keys(structuredPermissions.value).forEach((resource) => {
    selectAllRow.value[resource] = isSelected
    availableActions(resource).forEach((action) => {
      structuredPermissions.value[resource][action].selected = isSelected
    })
  })
  syncFormPermissions()
  syncGlobalState()
}

function createRole() {
  form.post(route('admin.roles.store'), {
    onSuccess: () => {
      success(t('roles.roleCreatedSuccessfully'))
      // router.get(route('admin.roles.index'))
    },
    onError: (errors) => {
      let errorMessage = t('roles.roleCreationFailed')
      if (errors?.name) errorMessage += `: ${errors.name}`
      if (errors?.['display_name.en']) errorMessage += ` / ${errors['display_name.en']}`
      if (errors?.['display_name.ar']) errorMessage += ` / ${errors['display_name.ar']}`
      error(errorMessage)
    },
  })
}

function cancel() {
  router.get(route('admin.roles.index'))
}

// ابدأ البناء
processPermissions()
</script>

<style scoped>
</style>
