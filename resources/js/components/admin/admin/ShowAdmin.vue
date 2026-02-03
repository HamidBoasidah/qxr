<template>
	<div class="space-y-6">
		<!-- Admin Information Section -->
				<!-- Admin Information Section -->
				<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
					<div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
						<h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('admins.userInformation') }}</h2>
					</div>

					<div class="p-4 sm:p-6">
						<div class="grid grid-cols-1 gap-x-5 gap-y-6 md:grid-cols-2">
							<!-- First Name -->
							<div>
								<label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('profile.labels.firstName') }}</label>
								<p class="text-base text-gray-800 dark:text-white/90">{{ admin.first_name || 'N/A' }}</p>
							</div>

							<!-- Last Name -->
							<div>
								<label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('profile.labels.lastName') }}</label>
								<p class="text-base text-gray-800 dark:text-white/90">{{ admin.last_name || 'N/A' }}</p>
							</div>

							<!-- Email -->
							<div>
								<label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('common.email') }}</label>
								<p class="text-base text-gray-800 dark:text-white/90">{{ admin.email || 'N/A' }}</p>
							</div>

							<!-- Phone Number -->
							<div>
								<label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('common.phoneNumber') }}</label>
								<p class="text-base text-gray-800 dark:text-white/90">{{ admin.phone_number || 'N/A' }}</p>
							</div>

							<!-- WhatsApp Number -->
							<div>
								<label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('common.whatsappNumber') }}</label>
								<p class="text-base text-gray-800 dark:text-white/90">{{ admin.whatsapp_number || 'N/A' }}</p>
							</div>

							<!-- Role -->
							<div>
								<label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('roles.roleInformation') }}</label>
								<p class="text-base capitalize text-gray-800 dark:text-white/90">{{ displayName(admin.role) || 'N/A' }}</p>
							</div>

							<!-- Status -->
							<div>
								<label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('common.status') }}</label>
								<p class="text-base text-gray-800 dark:text-white/90">
									<span
										class="inline-flex items-center justify-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
										:class="{
											'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-500': admin.is_active,
											'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500': !admin.is_active,
										}"
									>
										{{ admin.is_active ? t('common.active') : t('common.inactive') }}
									</span>
								</p>
							</div>

							<!-- Address -->
							<div class="md:col-span-2">
								<label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('common.address') }}</label>
								<p class="text-base text-gray-800 dark:text-white/90">{{ admin.address || 'N/A' }}</p>
							</div>
						</div>
					</div>
				</div>

				<!-- Social Links Section -->
				<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
					<div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
						<h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('profile.socialLinks') }}</h2>
					</div>
					<div class="p-4 sm:p-6">
						<div v-if="hasSocialLinks" class="flex flex-col gap-3">
							<a v-if="admin.facebook" :href="admin.facebook" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm text-brand-500 hover:underline">
								<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M22 12a10 10 0 10-11.5 9.9v-7h-2.2V12h2.2V9.8c0-2.2 1.3-3.5 3.3-3.5.95 0 1.95.17 1.95.17v2.1h-1.08c-1.06 0-1.39.66-1.39 1.34V12h2.36l-.38 2.9h-1.98v7A10 10 0 0022 12z"/></svg>
								{{ t('profile.labels.facebook') }}
							</a>

							<a v-if="admin.x_url" :href="admin.x_url" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm text-brand-500 hover:underline">
								<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M22 5.92c-.77.34-1.6.57-2.47.67a4.2 4.2 0 001.84-2.32 8.3 8.3 0 01-2.64 1 4.15 4.15 0 00-7.08 3.78A11.8 11.8 0 013 4.9a4.15 4.15 0 001.28 5.53c-.66-.02-1.28-.2-1.82-.5v.05a4.15 4.15 0 003.33 4.07c-.43.12-.88.18-1.34.18-.33 0-.65-.03-.96-.09a4.16 4.16 0 003.88 2.88A8.34 8.34 0 012 19.54 11.76 11.76 0 008.29 21c7.55 0 11.68-6.26 11.68-11.68v-.53A8.36 8.36 0 0022 5.92z"/></svg>
								{{ t('profile.labels.x') }}
							</a>

							<a v-if="admin.linkedin" :href="admin.linkedin" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm text-brand-500 hover:underline">
								<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3A2 2 0 0121 5v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h14zm-9.5 7.5H7V18h2.5V10.5zM8.25 9a1.25 1.25 0 110-2.5 1.25 1.25 0 010 2.5zM17 18h-2.5v-3.25c0-.78-.02-1.78-1.08-1.78-1.09 0-1.25.84-1.25 1.71V18H9V10.5h2.4v1c.33-.62 1.17-1.27 2.4-1.27 2.57 0 3.05 1.69 3.05 3.89V18z"/></svg>
								{{ t('profile.labels.linkedin') }}
							</a>

							<a v-if="admin.instagram" :href="admin.instagram" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm text-brand-500 hover:underline">
								<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M7 2h10a5 5 0 015 5v10a5 5 0 01-5 5H7a5 5 0 01-5-5V7a5 5 0 015-5zm5 6.5A4.5 4.5 0 1016.5 13 4.5 4.5 0 0012 8.5zm6.5-2a1 1 0 11-1 1 1 1 0 011-1z"/></svg>
								{{ t('profile.labels.instagram') }}
							</a>
						</div>
						<div v-else class="flex justify-center p-4">
							<p class="text-center text-sm text-gray-500 dark:text-gray-400">No social links</p>
						</div>
					</div>
				</div>

				<!-- Admin Image Section -->
				<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
					<div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
						<h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('admins.userImage') }}</h2>
					</div>
					<div class="p-4 sm:p-6">
						<div v-if="admin.avatar" class="relative flex justify-center p-4">
							<img :src="storagePath" alt="Admin Avatar" class="max-h-64 rounded-lg border border-gray-200 object-contain dark:border-gray-800" />
						</div>
						<div v-else class="flex justify-center p-10">
							<p class="text-center text-sm text-gray-500 dark:text-gray-400">
								{{ t('users.noImage') }}
							</p>
						</div>
					</div>
				</div>

				<!-- Buttons -->
				<div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
					<Link :href="route('admin.admins.index')" class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
						{{ t('buttons.backToList') }}
					</Link>
					<Link :href="route('admin.admins.edit', admin.id)" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white transition">
						{{ t('buttons.edit') }}
					</Link>
				</div>
	</div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
	admin: {
		type: Object,
		required: true,
	},
})

const storagePath = computed(() => (props.admin?.avatar ? `/storage/${props.admin.avatar}` : ''))

const fullName = computed(() => {
	const first = props.admin?.first_name ?? ''
	const last = props.admin?.last_name ?? ''
	return `${first} ${last}`.trim()
})

const socialLinks = computed(() => [
	{ key: 'facebook', label: t('profile.labels.facebook'), value: props.admin?.facebook },
	{ key: 'x', label: t('profile.labels.x'), value: props.admin?.x_url },
	{ key: 'linkedin', label: t('profile.labels.linkedin'), value: props.admin?.linkedin },
	{ key: 'instagram', label: t('profile.labels.instagram'), value: props.admin?.instagram },
])

const hasSocialLinks = computed(() => socialLinks.value.some((link) => !!link.value))

const localeLabel = computed(() => {
	const loc = props.admin?.locale
	return loc ? loc.toUpperCase() : 'N/A'
})

function displayName(role) {
	if (!role) return ''
	const loc = locale.value
	return role.display_name?.[loc] ?? role.display_name?.en ?? role.display_name?.ar ?? role.name ?? ''
}
</script>
