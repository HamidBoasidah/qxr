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

          <!-- Conversation List (Sidebar) -->
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
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
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

            <!-- Conversation List Items (Placeholder - would be loaded from backend) -->
            <div class="flex flex-col max-h-full px-4 overflow-auto sm:px-5">
              <div class="max-h-full space-y-1 overflow-auto custom-scrollbar">
                <Link
                  v-for="conv in conversations.data"
                  :key="conv.id"
                  :href="route('company.chat.conversations.show', conv.id)"
                  class="flex cursor-pointer items-center gap-3 rounded-lg p-3 hover:bg-gray-100 dark:hover:bg-white/[0.03]"
                  :class="{ 'bg-gray-100 dark:bg-white/[0.03]': conv.id === conversation.id }"
                >
                  <div class="relative h-10 w-full max-w-[40px]">
                    <img
                      v-if="getConversationAvatar(conv)"
                      :src="getConversationAvatar(conv)"
                      :alt="getConversationName(conv)"
                      class="object-cover object-center w-full h-full overflow-hidden rounded-full"
                    />
                    <UserCircleIcon
                      v-else
                      class="w-10 h-10 text-gray-400"
                    />
                    <span
                      v-if="conv.unread_count > 0"
                      class="absolute top-0 right-0 flex items-center justify-center w-4 h-4 text-xs font-semibold text-white bg-brand-500 rounded-full"
                    >
                      {{ conv.unread_count > 9 ? '9+' : conv.unread_count }}
                    </span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <h5 class="text-sm font-medium text-gray-800 truncate dark:text-white/90">
                      {{ getConversationName(conv) }}
                    </h5>
                    <p
                      class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400 truncate"
                      :class="{ 'font-semibold': conv.unread_count > 0 }"
                    >
                      {{ getConversationLastMessage(conv) }}
                    </p>
                  </div>
                </Link>
              </div>
            </div>
          </div>
        </div>

        <!-- Chat Box -->
        <div
          class="flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] xl:w-3/4"
        >
          <!-- Chat Header -->
          <div
            class="sticky flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800 xl:px-6"
          >
            <div class="flex items-center gap-3">
              <button
                @click="toggleSidebar"
                class="flex items-center justify-center w-10 h-10 text-gray-700 border border-gray-300 rounded-lg dark:border-gray-700 dark:text-gray-400 xl:hidden"
              >
                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none">
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M3.25 6C3.25 5.58579 3.58579 5.25 4 5.25H20C20.4142 5.25 20.75 5.58579 20.75 6C20.75 6.41421 20.4142 6.75 20 6.75L4 6.75C3.58579 6.75 3.25 6.41422 3.25 6ZM3.25 18C3.25 17.5858 3.58579 17.25 4 17.25L20 17.25C20.4142 17.25 20.75 17.5858 20.75 18C20.75 18.4142 20.4142 18.75 20 18.75L4 18.75C3.58579 18.75 3.25 18.4142 3.25 18ZM4 11.25C3.58579 11.25 3.25 11.5858 3.25 12C3.25 12.4142 3.58579 12.75 4 12.75L20 12.75C20.4142 12.75 20.75 12.4142 20.75 12C20.75 11.5858 20.4142 11.25 20 11.25L4 11.25Z"
                    fill=""
                  />
                </svg>
              </button>
              
              <div class="relative h-12 w-full max-w-[48px]">
                <img
                  v-if="getParticipantAvatar()"
                  :src="getParticipantAvatar()"
                  :alt="getParticipantName()"
                  class="object-cover object-center w-full h-full overflow-hidden rounded-full"
                />
                <UserCircleIcon
                  v-else
                  class="w-12 h-12 text-gray-400"
                />
                <span
                  class="absolute bottom-0 right-0 block h-3 w-3 rounded-full border-[1.5px] border-white bg-success-500 dark:border-gray-900"
                ></span>
              </div>

              <h5 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ getParticipantName() }}
              </h5>
            </div>
          </div>

          <!-- Messages Container -->
          <div
            ref="messagesContainer"
            class="flex-1 max-h-full p-5 space-y-6 overflow-auto custom-scrollbar xl:space-y-8 xl:p-6"
            @scroll="handleScroll"
          >
            <!-- Loading indicator for infinite scroll -->
            <div v-if="loadingMore" class="flex justify-center py-4">
              <div class="w-6 h-6 border-2 border-gray-300 rounded-full animate-spin border-t-brand-500"></div>
            </div>

            <!-- Messages -->
            <div
              v-for="message in displayedMessages"
              :key="message.id"
              :class="[
                message.sender.id === auth.user.id ? 'ml-auto max-w-[350px] text-right' : 'max-w-[350px]'
              ]"
            >
              <!-- Received Message -->
              <div v-if="message.sender.id !== auth.user.id" class="flex items-start gap-4">
                <div class="w-10 h-10">
                  <img
                    v-if="message.sender.avatar"
                    :src="`/storage/${message.sender.avatar}`"
                    :alt="message.sender.name"
                    class="object-cover object-center w-full h-full overflow-hidden rounded-full"
                  />
                  <UserCircleIcon
                    v-else
                    class="w-10 h-10 text-gray-400"
                  />
                </div>

                <div>
                  <!-- Attachments -->
                  <div
                    v-if="message.attachments && message.attachments.length > 0"
                    class="mb-2 space-y-2"
                  >
                    <div
                      v-for="attachment in message.attachments"
                      :key="attachment.id"
                      class="w-full max-w-[270px] overflow-hidden rounded-lg"
                    >
                      <img
                        v-if="isImage(attachment.mime_type)"
                        :src="attachment.download_url"
                        :alt="attachment.original_name"
                        class="w-full"
                      />
                      <a
                        v-else
                        :href="attachment.download_url"
                        :download="attachment.original_name"
                        class="flex items-center gap-2 p-3 bg-gray-100 rounded-lg dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10"
                      >
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M8 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0017.414 6L14 2.586A2 2 0 0012.586 2H8z" />
                        </svg>
                        <span class="text-sm text-gray-800 truncate dark:text-white/90">
                          {{ attachment.original_name }}
                        </span>
                      </a>
                    </div>
                  </div>

                  <!-- Message Body -->
                  <div
                    v-if="message.body"
                    class="px-3 py-2 bg-gray-100 rounded-lg rounded-tl-sm dark:bg-white/5"
                  >
                    <p class="text-sm text-gray-800 dark:text-white/90 whitespace-pre-wrap">
                      {{ message.body }}
                    </p>
                  </div>
                  
                  <p class="mt-2 text-gray-500 text-theme-xs dark:text-gray-400">
                    {{ message.sender.name }}, {{ formatTimestamp(message.created_at) }}
                  </p>
                </div>
              </div>

              <!-- Sent Message -->
              <div v-else>
                <!-- Attachments -->
                <div
                  v-if="message.attachments && message.attachments.length > 0"
                  class="mb-2 ml-auto space-y-2 max-w-max"
                >
                  <div
                    v-for="attachment in message.attachments"
                    :key="attachment.id"
                    class="w-full max-w-[270px] overflow-hidden rounded-lg"
                  >
                    <img
                      v-if="isImage(attachment.mime_type)"
                      :src="attachment.download_url"
                      :alt="attachment.original_name"
                      class="w-full"
                    />
                    <a
                      v-else
                      :href="attachment.download_url"
                      :download="attachment.original_name"
                      class="flex items-center gap-2 p-3 rounded-lg bg-brand-500 hover:bg-brand-600"
                    >
                      <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0017.414 6L14 2.586A2 2 0 0012.586 2H8z" />
                      </svg>
                      <span class="text-sm text-white truncate">
                        {{ attachment.original_name }}
                      </span>
                    </a>
                  </div>
                </div>

                <!-- Message Body -->
                <div
                  v-if="message.body"
                  class="px-3 py-2 ml-auto rounded-lg rounded-tr-sm max-w-max bg-brand-500 dark:bg-brand-500"
                >
                  <p class="text-sm text-white dark:text-white/90 whitespace-pre-wrap">
                    {{ message.body }}
                  </p>
                </div>
                
                <p class="mt-2 text-gray-500 text-theme-xs dark:text-gray-400">
                  {{ formatTimestamp(message.created_at) }}
                </p>
              </div>
            </div>

            <!-- Empty state -->
            <div
              v-if="displayedMessages.length === 0 && !loadingMore"
              class="flex flex-col items-center justify-center h-full text-center"
            >
              <svg class="w-16 h-16 mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                />
              </svg>
              <p class="text-gray-600 dark:text-gray-400">
                {{ t('chat.noMessages') }}
              </p>
            </div>
          </div>

          <!-- Message Input Form -->
          <div class="sticky bottom-0 p-3 border-t border-gray-200 dark:border-gray-800">
            <!-- Error Message -->
            <div
              v-if="errorMessage"
              class="px-4 py-2 mb-3 text-sm text-red-700 bg-red-100 border border-red-200 rounded-lg dark:bg-red-900/20 dark:border-red-800 dark:text-red-400"
            >
              {{ errorMessage }}
            </div>

            <!-- File Preview -->
            <div v-if="selectedFiles.length > 0" class="flex flex-wrap gap-2 mb-3">
              <div
                v-for="(file, index) in selectedFiles"
                :key="index"
                class="relative flex items-center gap-2 px-3 py-2 bg-gray-100 rounded-lg dark:bg-white/5"
              >
                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M8 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0017.414 6L14 2.586A2 2 0 0012.586 2H8z" />
                </svg>
                <span class="text-xs text-gray-800 dark:text-white/90">{{ file.name }}</span>
                <button
                  @click="removeFile(index)"
                  type="button"
                  class="text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400"
                >
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path
                      fill-rule="evenodd"
                      d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </button>
              </div>
            </div>

            <form @submit.prevent="sendMessage" class="flex items-center justify-between">
              <div class="relative w-full">
                <input
                  v-model="messageBody"
                  type="text"
                  :placeholder="t('chat.typeMessage')"
                  :disabled="sending"
                  class="w-full pl-12 pr-5 text-sm text-gray-800 bg-transparent border-none outline-hidden h-9 placeholder:text-gray-400 focus:border-0 focus:ring-0 dark:text-white/90 disabled:opacity-50"
                  @keydown.enter.exact.prevent="sendMessage"
                />
              </div>

              <div class="flex items-center">
                <!-- File Upload Button -->
                <label
                  class="mr-2 text-gray-500 cursor-pointer hover:text-gray-800 dark:text-gray-400 dark:hover:text-white/90"
                  :class="{ 'opacity-50 cursor-not-allowed': sending }"
                >
                  <input
                    ref="fileInput"
                    type="file"
                    multiple
                    :disabled="sending"
                    class="hidden"
                    @change="handleFileSelect"
                    accept="image/*,.pdf,.doc,.docx"
                  />
                  <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path
                      fill-rule="evenodd"
                      clip-rule="evenodd"
                      d="M12.9522 14.4422C12.9522 14.452 12.9524 14.4618 12.9527 14.4714V16.1442C12.9527 16.6699 12.5265 17.0961 12.0008 17.0961C11.475 17.0961 11.0488 16.6699 11.0488 16.1442V6.15388C11.0488 5.73966 10.7131 5.40388 10.2988 5.40388C9.88463 5.40388 9.54885 5.73966 9.54885 6.15388V16.1442C9.54885 17.4984 10.6466 18.5961 12.0008 18.5961C13.355 18.5961 14.4527 17.4983 14.4527 16.1442V6.15388C14.4527 6.14308 14.4525 6.13235 14.452 6.12166C14.4347 3.84237 12.5817 2 10.2983 2C8.00416 2 6.14441 3.85976 6.14441 6.15388V14.4422C6.14441 14.4492 6.1445 14.4561 6.14469 14.463V16.1442C6.14469 19.3783 8.76643 22 12.0005 22C15.2346 22 17.8563 19.3783 17.8563 16.1442V9.55775C17.8563 9.14354 17.5205 8.80775 17.1063 8.80775C16.6921 8.80775 16.3563 9.14354 16.3563 9.55775V16.1442C16.3563 18.5498 14.4062 20.5 12.0005 20.5C9.59485 20.5 7.64469 18.5498 7.64469 16.1442V9.55775C7.64469 9.55083 7.6446 9.54393 7.64441 9.53706L7.64441 6.15388C7.64441 4.68818 8.83259 3.5 10.2983 3.5C11.764 3.5 12.9522 4.68818 12.9522 6.15388L12.9522 14.4422Z"
                      fill=""
                    />
                  </svg>
                </label>

                <!-- Send Button -->
                <button
                  type="submit"
                  :disabled="sending || (!messageBody.trim() && selectedFiles.length === 0)"
                  class="flex items-center justify-center ml-3 text-white rounded-lg h-9 w-9 bg-brand-500 hover:bg-brand-600 xl:ml-5 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <svg
                    v-if="!sending"
                    width="20"
                    height="20"
                    viewBox="0 0 20 20"
                    fill="none"
                  >
                    <path
                      fill-rule="evenodd"
                      clip-rule="evenodd"
                      d="M4.98481 2.44399C3.11333 1.57147 1.15325 3.46979 1.96543 5.36824L3.82086 9.70527C3.90146 9.89367 3.90146 10.1069 3.82086 10.2953L1.96543 14.6323C1.15326 16.5307 3.11332 18.4291 4.98481 17.5565L16.8184 12.0395C18.5508 11.2319 18.5508 8.76865 16.8184 7.961L4.98481 2.44399ZM3.34453 4.77824C3.0738 4.14543 3.72716 3.51266 4.35099 3.80349L16.1846 9.32051C16.762 9.58973 16.762 10.4108 16.1846 10.68L4.35098 16.197C3.72716 16.4879 3.0738 15.8551 3.34453 15.2223L5.19996 10.8853C5.21944 10.8397 5.23735 10.7937 5.2537 10.7473L9.11784 10.7473C9.53206 10.7473 9.86784 10.4115 9.86784 9.99726C9.86784 9.58304 9.53206 9.24726 9.11784 9.24726L5.25157 9.24726C5.2358 9.20287 5.2186 9.15885 5.19996 9.11528L3.34453 4.77824Z"
                      fill="white"
                    />
                  </svg>
                  <div v-else class="w-5 h-5 border-2 border-white rounded-full animate-spin border-t-transparent"></div>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </CompanyLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import CompanyLayout from '@/components/layout/CompanyLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import { UserCircleIcon } from '@/icons'

const { t } = useI18n()

// Props
const props = defineProps({
  conversation: {
    type: Object,
    required: true,
  },
  messages: {
    type: Object,
    required: true,
    default: () => ({
      data: [],
      meta: {
        next_cursor: null,
        prev_cursor: null,
        per_page: 50,
        unread_count: 0,
      },
    }),
  },
  conversations: {
    type: Object,
    required: false,
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
const messageBody = ref('')
const selectedFiles = ref([])
const sending = ref(false)
const loadingMore = ref(false)
const errorMessage = ref('')
const displayedMessages = ref([...props.messages.data])
const nextCursor = ref(props.messages.meta.next_cursor)
const prevCursor = ref(props.messages.meta.prev_cursor)
const messagesContainer = ref(null)
const fileInput = ref(null)
const hasScrolledToBottom = ref(false)

// Computed
const currentPageTitle = computed(() => getParticipantName())

// Methods
const toggleSidebar = () => {
  isOpen.value = !isOpen.value
}

const getParticipantName = () => {
  if (!props.conversation.participants || props.conversation.participants.length === 0) {
    return t('chat.unknownUser')
  }
  
  const otherParticipant = props.conversation.participants.find(
    (p) => p.id !== props.auth.user.id
  )
  
  if (!otherParticipant) {
    return t('chat.unknownUser')
  }
  
  // Build name from first_name and last_name
  if (otherParticipant.first_name || otherParticipant.last_name) {
    const fullName = ((otherParticipant.first_name || '') + ' ' + (otherParticipant.last_name || '')).trim()
    return fullName || t('chat.unknownUser')
  }
  
  return t('chat.unknownUser')
}

const getParticipantAvatar = () => {
  if (!props.conversation.participants || props.conversation.participants.length === 0) {
    return null
  }
  
  const otherParticipant = props.conversation.participants.find(
    (p) => p.id !== props.auth.user.id
  )
  
  return otherParticipant?.avatar ? `/storage/${otherParticipant.avatar}` : null
}

const getConversationName = (conv) => {
  if (conv.other_participant) {
    return conv.other_participant.full_name || t('chat.unknownUser')
  }
  return t('chat.unknownUser')
}

const getConversationAvatar = (conv) => {
  if (conv.other_participant?.avatar) {
    return `/storage/${conv.other_participant.avatar}`
  }
  return null
}

const getConversationLastMessage = (conv) => {
  if (!conv.last_message) {
    return t('chat.noMessages')
  }
  
  const message = conv.last_message
  
  if (message.body) {
    return message.body
  }
  
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
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }
}

const isImage = (mimeType) => {
  return mimeType && mimeType.startsWith('image/')
}

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files)
  
  // Validate file count (max 5)
  if (selectedFiles.value.length + files.length > 5) {
    errorMessage.value = t('chat.maxFilesExceeded')
    setTimeout(() => {
      errorMessage.value = ''
    }, 3000)
    return
  }
  
  // Validate file sizes (max 10MB each)
  const invalidFiles = files.filter(file => file.size > 10 * 1024 * 1024)
  if (invalidFiles.length > 0) {
    errorMessage.value = t('chat.fileTooLarge')
    setTimeout(() => {
      errorMessage.value = ''
    }, 3000)
    return
  }
  
  selectedFiles.value.push(...files)
  
  // Reset file input
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const removeFile = (index) => {
  selectedFiles.value.splice(index, 1)
}

const sendMessage = async () => {
  // Validate that we have either a message body or files
  if (!messageBody.value.trim() && selectedFiles.value.length === 0) {
    return
  }
  
  sending.value = true
  errorMessage.value = ''
  
  try {
    const formData = new FormData()
    
    if (messageBody.value.trim()) {
      formData.append('body', messageBody.value.trim())
    }
    
    selectedFiles.value.forEach((file, index) => {
      formData.append(`files[${index}]`, file)
    })
    
    const response = await axios.post(
      route('company.chat.messages.store', props.conversation.id),
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    )
    
    if (response.data.success && response.data.data) {
      // Add the new message to the displayed messages
      displayedMessages.value.push(response.data.data)
      
      // Clear form
      messageBody.value = ''
      selectedFiles.value = []
      
      // Scroll to bottom
      await nextTick()
      scrollToBottom()
    }
  } catch (error) {
    console.error('Error sending message:', error)
    
    if (error.response?.data?.message) {
      errorMessage.value = error.response.data.message
    } else if (error.response?.data?.errors) {
      const errors = Object.values(error.response.data.errors).flat()
      errorMessage.value = errors[0] || t('chat.sendError')
    } else {
      errorMessage.value = t('chat.sendError')
    }
    
    setTimeout(() => {
      errorMessage.value = ''
    }, 5000)
  } finally {
    sending.value = false
  }
}

const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const handleScroll = async () => {
  if (!messagesContainer.value || loadingMore.value || !prevCursor.value) {
    return
  }
  
  // Check if scrolled to top (with 50px threshold)
  if (messagesContainer.value.scrollTop < 50) {
    await loadPreviousMessages()
  }
}

const loadPreviousMessages = async () => {
  if (!prevCursor.value || loadingMore.value) {
    return
  }
  
  loadingMore.value = true
  
  try {
    // Save current scroll position
    const scrollHeight = messagesContainer.value.scrollHeight
    
    const response = await axios.get(
      route('company.chat.conversations.show', props.conversation.id),
      {
        params: {
          cursor: prevCursor.value,
        },
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
      }
    )
    
    if (response.data.messages?.data) {
      // Prepend older messages
      displayedMessages.value = [...response.data.messages.data, ...displayedMessages.value]
      
      // Update cursors
      prevCursor.value = response.data.messages.meta.prev_cursor
      nextCursor.value = response.data.messages.meta.next_cursor
      
      // Restore scroll position
      await nextTick()
      const newScrollHeight = messagesContainer.value.scrollHeight
      messagesContainer.value.scrollTop = newScrollHeight - scrollHeight
    }
  } catch (error) {
    console.error('Error loading previous messages:', error)
  } finally {
    loadingMore.value = false
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

// Lifecycle hooks
onMounted(() => {
  // Scroll to bottom on initial load
  nextTick(() => {
    scrollToBottom()
    hasScrolledToBottom.value = true
  })
})

// Watch for new messages from props (e.g., real-time updates)
watch(
  () => props.messages.data,
  (newMessages) => {
    if (newMessages.length > displayedMessages.value.length) {
      displayedMessages.value = [...newMessages]
      nextTick(() => {
        scrollToBottom()
      })
    }
  }
)
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

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
