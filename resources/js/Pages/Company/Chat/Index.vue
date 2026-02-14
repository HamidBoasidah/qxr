<template>
  <CompanyLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <div class="h-[calc(100vh-186px)] overflow-hidden sm:h-[calc(100vh-174px)]">
      <div class="flex flex-col h-full gap-6 xl:flex-row xl:gap-5">
        <!-- Chat Sidebar with Conversation List -->
        <div
          class="xl:w-1/4 flex-col w-full rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] xl:flex"
        >
          <div
            v-if="isOpen"
            class="fixed inset-0 transition-all duration-300 z-999999 bg-gray-900/50"
            @click="toggleSidebar"
          ></div>

          <!-- Chat Header with Search -->
          <div class="p-5 border-b border-gray-200 dark:border-gray-800">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-semibold text-gray-800 text-theme-xl dark:text-white/90 sm:text-2xl">
                {{ t('menu.chat') }}
              </h3>
              <button
                @click="openNewConversationModal"
                class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-white transition-colors rounded-lg bg-brand-500 hover:bg-brand-600"
              >
                <svg
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 4v16m8-8H4"
                  />
                </svg>
                <span class="hidden sm:inline">{{ t('chat.newConversation') }}</span>
              </button>
            </div>
            
            <!-- Search Input -->
            <div class="relative">
              <input
                v-model="searchQuery"
                type="text"
                :placeholder="t('chat.searchConversations')"
                class="w-full px-4 py-2 pr-10 text-sm border border-gray-300 rounded-lg dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500"
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

          <!-- Conversation List -->
          <div
            class="flex-col overflow-auto transition-all duration-300 no-scrollbar"
            :class="{
              'fixed top-0 left-0 z-999999 h-screen bg-white dark:bg-gray-900': isOpen,
              'hidden xl:flex': !isOpen,
            }"
          >
            <div
              class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-800 xl:hidden"
            >
              <div>
                <h3 class="font-semibold text-gray-800 text-theme-xl dark:text-white/90 sm:text-2xl">
                  {{ t('menu.chat') }}
                </h3>
              </div>
              <div class="flex items-center gap-1">
                <button
                  @click="toggleSidebar"
                  class="flex items-center justify-center w-10 h-10 text-gray-700 transition border border-gray-300 rounded-full dark:border-gray-700 dark:text-gray-400 dark:hover:text-white/90"
                >
                  <svg
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <path
                      fill-rule="evenodd"
                      clip-rule="evenodd"
                      d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z"
                      fill="currentColor"
                    />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Empty State -->
            <div
              v-if="conversations.data.length === 0"
              class="flex flex-col items-center justify-center p-8 text-center"
            >
              <svg
                class="w-16 h-16 mb-4 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                />
              </svg>
              <p class="text-gray-600 dark:text-gray-400">
                {{ searchQuery ? t('chat.noConversationsFound') : t('chat.noConversations') }}
              </p>
            </div>

            <!-- Conversation List Items -->
            <div v-else class="flex flex-col max-h-full px-4 overflow-auto sm:px-5">
              <div class="max-h-full space-y-1 overflow-auto custom-scrollbar">
                <Link
                  v-for="conversation in conversations.data"
                  :key="conversation.id"
                  :href="route('company.chat.conversations.show', conversation.id)"
                  class="flex cursor-pointer items-center gap-3 rounded-lg p-3 hover:bg-gray-100 dark:hover:bg-white/[0.03]"
                >
                  <div class="relative h-12 w-full max-w-[48px]">
                    <img
                      v-if="getParticipantAvatar(conversation)"
                      :src="getParticipantAvatar(conversation)"
                      :alt="getParticipantName(conversation)"
                      class="object-cover object-center w-full h-full overflow-hidden rounded-full"
                    />
                    <UserCircleIcon
                      v-else
                      class="w-12 h-12 text-gray-400"
                    />
                    <span
                      v-if="conversation.unread_count > 0"
                      class="absolute top-0 right-0 flex items-center justify-center w-5 h-5 text-xs font-semibold text-white bg-brand-500 rounded-full"
                    >
                      {{ conversation.unread_count > 9 ? '9+' : conversation.unread_count }}
                    </span>
                  </div>
                  <div class="w-full">
                    <div class="flex items-start justify-between">
                      <div class="flex-1 min-w-0">
                        <h5 class="text-sm font-medium text-gray-800 truncate dark:text-white/90">
                          {{ getParticipantName(conversation) }}
                        </h5>
                        <p
                          class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400 truncate"
                          :class="{ 'font-semibold': conversation.unread_count > 0 }"
                        >
                          {{ getLastMessagePreview(conversation) }}
                        </p>
                      </div>
                      <span class="text-gray-400 text-theme-xs whitespace-nowrap ml-2">
                        {{ formatTimestamp(conversation.last_message?.created_at) }}
                      </span>
                    </div>
                  </div>
                </Link>
              </div>
            </div>

            <!-- Pagination -->
            <div
              v-if="conversations.pagination.last_page > 1"
              class="flex items-center justify-between p-4 border-t border-gray-200 dark:border-gray-800"
            >
              <button
                @click="goToPage(conversations.pagination.current_page - 1)"
                :disabled="conversations.pagination.current_page === 1"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ t('pagination.previous') }}
              </button>
              <span class="text-sm text-gray-600 dark:text-gray-400">
                {{ t('pagination.pageOf', { 
                  current: conversations.pagination.current_page, 
                  total: conversations.pagination.last_page 
                }) }}
              </span>
              <button
                @click="goToPage(conversations.pagination.current_page + 1)"
                :disabled="conversations.pagination.current_page === conversations.pagination.last_page"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ t('pagination.next') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Empty Chat Box Placeholder -->
        <div
          class="flex-1 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] flex items-center justify-center"
        >
          <div class="text-center">
            <svg
              class="w-20 h-20 mx-auto mb-4 text-gray-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
              />
            </svg>
            <p class="text-lg text-gray-600 dark:text-gray-400">
              {{ t('chat.selectConversation') }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- New Conversation Modal -->
    <NewConversationModal
      :isOpen="isNewConversationModalOpen"
      :authUserId="auth.user.id"
      @close="closeNewConversationModal"
    />
  </CompanyLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import CompanyLayout from '@/components/layout/CompanyLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import NewConversationModal from '@/components/company/chat/NewConversationModal.vue'
import { UserCircleIcon } from '@/icons'

const { t } = useI18n()

// Props
const props = defineProps({
  conversations: {
    type: Object,
    required: true,
    default: () => ({
      data: [],
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 0,
      },
    }),
  },
  auth: {
    type: Object,
    required: true,
  },
})

// State
const isOpen = ref(false)
const searchQuery = ref('')
const currentPageTitle = computed(() => t('menu.chat'))
const isNewConversationModalOpen = ref(false)

// Methods
const toggleSidebar = () => {
  isOpen.value = !isOpen.value
}

const getParticipantName = (conversation) => {
  // Check if other_participant exists (from ConversationListDTO)
  if (conversation.other_participant) {
    return conversation.other_participant.full_name || t('chat.unknownUser')
  }
  
  // Fallback to participants array (from ConversationDTO)
  if (!conversation.participants || conversation.participants.length === 0) {
    return t('chat.unknownUser')
  }
  
  const otherParticipant = conversation.participants.find(
    (p) => p.id !== props.auth.user.id
  )
  
  return otherParticipant?.name || conversation.participants[0]?.name || t('chat.unknownUser')
}

const getParticipantAvatar = (conversation) => {
  // Check if other_participant exists (from ConversationListDTO)
  if (conversation.other_participant) {
    return conversation.other_participant.avatar ? `/storage/${conversation.other_participant.avatar}` : null
  }
  
  // Fallback to participants array (from ConversationDTO)
  if (!conversation.participants || conversation.participants.length === 0) {
    return null
  }
  
  const otherParticipant = conversation.participants.find(
    (p) => p.id !== props.auth.user.id
  )
  
  return otherParticipant?.avatar ? `/storage/${otherParticipant.avatar}` : null
}

const getLastMessagePreview = (conversation) => {
  if (!conversation.last_message) {
    return t('chat.noMessages')
  }
  
  const message = conversation.last_message
  
  // If message has body, show it
  if (message.body) {
    return message.body
  }
  
  // If message is attachment only, show indicator
  if (message.type === 'attachment' || message.type === 'mixed') {
    return '📎 ' + t('chat.attachment')
  }
  
  return t('chat.noMessages')
}

const formatTimestamp = (timestamp) => {
  if (!timestamp) return ''
  
  const date = new Date(timestamp)
  const now = new Date()
  const diffInMs = now - date
  const diffInMinutes = Math.floor(diffInMs / 60000)
  const diffInHours = Math.floor(diffInMs / 3600000)
  const diffInDays = Math.floor(diffInMs / 86400000)
  
  if (diffInMinutes < 1) {
    return t('time.justNow')
  } else if (diffInMinutes < 60) {
    return t('time.minutesAgo', { count: diffInMinutes })
  } else if (diffInHours < 24) {
    return t('time.hoursAgo', { count: diffInHours })
  } else if (diffInDays < 7) {
    return t('time.daysAgo', { count: diffInDays })
  } else {
    return date.toLocaleDateString('ar-SA', { 
      year: 'numeric', 
      month: 'short', 
      day: 'numeric' 
    })
  }
}

let searchTimeout = null
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get(
      route('company.chat.conversations.index'),
      { search: searchQuery.value },
      { preserveState: true, preserveScroll: true }
    )
  }, 500)
}

const goToPage = (page) => {
  router.get(
    route('company.chat.conversations.index'),
    { page, search: searchQuery.value },
    { preserveState: true, preserveScroll: true }
  )
}

const openNewConversationModal = () => {
  isNewConversationModalOpen.value = true
}

const closeNewConversationModal = () => {
  isNewConversationModalOpen.value = false
}
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}

.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

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
