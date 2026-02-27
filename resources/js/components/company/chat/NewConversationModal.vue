<template>
  <Modal v-if="isOpen" :fullScreenBackdrop="true" @close="closeModal">
    <template #body>
      <div
        class="relative w-full max-w-[584px] rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10"
      >
        <!-- Modal Header -->
        <div class="mb-6 flex items-center justify-between">
          <h4 class="text-lg font-medium text-gray-800 dark:text-white/90">
            {{ t('chat.newConversation') }}
          </h4>
          
          <!-- Close Button -->
          <button
            @click="closeModal"
            class="group flex h-9 w-9 items-center justify-center rounded-full bg-gray-200 text-gray-500 transition-colors hover:bg-gray-300 hover:text-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
          >
            <svg
              class="h-5 w-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        <!-- Modal Content -->
        <div>

          <!-- Search Input -->
          <div class="mb-4">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('chat.searchUsers') }}
            </label>
            <div class="relative">
              <input
                v-model="searchQuery"
                type="text"
                :placeholder="t('chat.searchUsersPlaceholder')"
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pr-10 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                @input="handleSearch"
              />
              <svg
                class="absolute w-5 h-5 text-gray-400 transform -translate-y-1/2 right-3 top-1/2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                />
              </svg>
            </div>
          </div>

          <!-- Users List -->
          <div class="mb-6">
            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-8">
              <svg
                class="w-8 h-8 text-brand-500 animate-spin"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
            </div>

            <!-- Empty State -->
            <div
              v-else-if="users.length === 0"
              class="flex flex-col items-center justify-center py-8 text-center"
            >
              <svg
                class="w-12 h-12 mb-3 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                />
              </svg>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ searchQuery ? t('chat.noUsersFound') : t('chat.noUsers') }}
              </p>
            </div>

            <!-- Users List -->
            <div
              v-else
              class="max-h-[400px] overflow-y-auto space-y-2 custom-scrollbar"
            >
              <button
                v-for="user in users"
                :key="user.id"
                @click="selectUser(user)"
                :disabled="creatingConversation"
                class="flex items-center w-full gap-3 p-3 transition-colors border border-gray-200 rounded-lg hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-white/[0.03] disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <div class="h-10 w-10 flex-shrink-0">
                  <img
                    v-if="user.avatar"
                    :src="`/storage/${user.avatar}`"
                    :alt="user.full_name"
                    class="h-10 w-10 rounded-full object-cover"
                  />
                  <UserCircleIcon
                    v-else
                    class="h-10 w-10 text-gray-400"
                  />
                </div>
                <div class="flex-1 text-left">
                  <h5 class="text-sm font-medium text-gray-800 dark:text-white/90">
                    {{ user.first_name }} {{ user.last_name }}
                  </h5>
                </div>
              </button>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end gap-3">
            <button
              @click="closeModal"
              type="button"
              class="flex justify-center px-4 py-3 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
            >
              {{ t('common.cancel') }}
            </button>
          </div>
        </div>
      </div>
    </template>
  </Modal>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import Modal from '@/components/ui/Modal.vue'
import { UserCircleIcon } from '@/icons'

const { t } = useI18n()

// Props
const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true,
  },
  authUserId: {
    type: Number,
    required: true,
  },
})

// Emits
const emit = defineEmits(['close'])

// State
const searchQuery = ref('')
const users = ref([])
const loading = ref(false)
const creatingConversation = ref(false)
let searchTimeout = null

// Methods
const closeModal = () => {
  emit('close')
  searchQuery.value = ''
  users.value = []
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchUsers()
  }, 500)
}

const fetchUsers = async () => {
  loading.value = true
  try {
    const url = route('company.chat.users.index')
    const params = new URLSearchParams({
      search: searchQuery.value || '',
      per_page: '50'
    })
    
    const response = await fetch(`${url}?${params}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin'
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()
    
    // Handle paginated response
    if (data.data && Array.isArray(data.data)) {
      users.value = data.data
    } else if (Array.isArray(data)) {
      users.value = data
    } else {
      console.error('Unexpected response format:', data)
      users.value = []
    }
  } catch (error) {
    console.error('Error fetching users:', error)
    users.value = []
  } finally {
    loading.value = false
  }
}

const selectUser = (user) => {
  creatingConversation.value = true
  
  router.post(route('company.chat.conversations.store'), {
    user_id: user.id,
  }, {
    preserveScroll: false,
    onSuccess: () => {
      closeModal()
    },
    onError: (errors) => {
      console.error('Error creating conversation:', errors)
      const errorMessage = errors?.error || t('chat.errorCreatingConversation')
      alert(errorMessage)
    },
    onFinish: () => {
      creatingConversation.value = false
    }
  })
}

// Watch for modal open
watch(() => props.isOpen, (newValue) => {
  if (newValue) {
    fetchUsers()
  }
})
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #a0aec0;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background: #4a5568;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #718096;
}
</style>
