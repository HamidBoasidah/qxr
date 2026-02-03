<template>
  <div class="relative" ref="dropdownRef">
    <button
      class="flex items-center text-gray-700 dark:text-gray-400"
      @click.prevent="toggleDropdown"
    >
      <span class="overflow-hidden rounded-full h-11 w-11 ltr:mr-3 rtl:ml-3">
        <template v-if="user?.avatar">
          <img :src="`/storage/${user.avatar}`" alt="User" class="h-11 w-11 object-cover" />
        </template>
        <template v-else>
          <UserCircleIcon class="h-11 w-11 text-gray-400" />
        </template>
      </span>

      <span class="block font-medium text-theme-sm ltr:mr-1 rtl:ml-1">{{ user?.name || t('user.name', { default: 'Code Brains' }) }}</span>

      <ChevronDownIcon :class="{ 'rotate-180': dropdownOpen }" />
    </button>

    <!-- Dropdown Start -->
    <div
      v-if="dropdownOpen"
      class="absolute mt-[17px] flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark ltr:right-0 rtl:left-0"
    >
          <div>
            <span class="block font-medium text-gray-700 text-theme-sm dark:text-gray-400">
              {{ user?.name || t('user.name', { default: 'Code Brains' }) }}
            </span>
            <span class="mt-0.5 block text-theme-xs text-gray-500 dark:text-gray-400">
              {{ user?.email || t('user.email', { default: 'randomuser@pimjo.com' }) }}
            </span>
          </div>

      <ul class="flex flex-col gap-1 pt-4 pb-3 border-b border-gray-200 dark:border-gray-800">
        <li v-for="item in menuItems" :key="item.href">
          <Link
            :href="item.href"
            class="flex items-center gap-3 px-3 py-2 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
          >
            <component
              :is="item.icon"
              class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300"
            />
            {{ t(item.text) }}
          </Link>
        </li>
      </ul>
      <Link
        @click.prevent="signOut"
        class="flex items-center gap-3 px-3 py-2 mt-3 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
      >
        <LogoutIcon
          class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300"
        />
        {{ t('user.signOut') }}
      </Link>
    </div>
    <!-- Dropdown End -->
  </div>
</template>

<script setup>
// Commented out unused icons (support & account settings) per request
import { UserCircleIcon, ChevronDownIcon, LogoutIcon /*, SettingsIcon, InfoCircleIcon */ } from '@/icons'
import { Link, usePage , router} from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { route } from 'ziggy-js'
import { useGlobalLoading } from '@/composables/useGlobalLoading'

const { t } = useI18n()
const page = usePage()
const { showGlobalLoading } = useGlobalLoading()

// استخدام computed لجعل user reactive
const user = computed(() => {
  const authUser = page.props.auth?.user
  if (!authUser) return null
  
  return {
    ...authUser,
    // دعم عرض الاسم من first_name + last_name إذا لم يكن name موجود
    name: authUser.name || [authUser.first_name, authUser.last_name].filter(Boolean).join(' ') || null
  }
})

const dropdownOpen = ref(false)
const dropdownRef = ref(null)

const menuItems = [
  { href: '/profile', icon: UserCircleIcon, text: 'user.editProfile' },
  // { href: '/chat', icon: SettingsIcon, text: 'user.accountSettings' }, // commented out
  // { href: '/profile', icon: InfoCircleIcon, text: 'user.support' }, // commented out
]

const toggleDropdown = () => {
  dropdownOpen.value = !dropdownOpen.value
}

const closeDropdown = () => {
  dropdownOpen.value = false
}

const signOut = () => {
  showGlobalLoading()
  closeDropdown()
  router.post(route('admin.logout'))
}

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    closeDropdown()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>
