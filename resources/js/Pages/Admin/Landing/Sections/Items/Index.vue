<template>
    <AdminLayout>
        <PageBreadcrumb :pageTitle="currentPageTitle" />
        <div class="space-y-5 sm:space-y-6">
            <ComponentCard :title="currentPageTitle" :desc="getLocalizedTitle(section)">
                <!-- Back Link -->
                <div class="mb-4">
                    <Link
                        :href="route('admin.landing.sections.index', landingPage.id)"
                        class="inline-flex items-center text-sm text-brand-500 hover:text-brand-600 dark:text-brand-400"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        {{ $t('landing.sections') }}
                    </Link>
                </div>

                <div class="overflow-hidden">
                    <!-- Toolbar -->
                    <div class="flex flex-col gap-2 px-4 py-4 border border-b-0 border-gray-200 rounded-b-none rounded-xl dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ items.length }} {{ $t('landing.items') }}</span>
                        </div>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <Link
                                :href="route('admin.landing.sections.items.create', [landingPage.id, section.id])"
                                class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-[11px] text-sm font-medium text-white transition sm:w-auto"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M5 10.0002H15.0006M10.0002 5V15.0006" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                {{ $t('landing.addItem') }}
                            </Link>
                        </div>
                    </div>

                    <div v-if="items.length > 0" class="max-w-full overflow-x-auto">
                        <table class="w-full min-w-full">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
                                        <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ $t('landing.order') }}</p>
                                    </th>
                                    <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
                                        <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ $t('common.title') }}</p>
                                    </th>
                                    <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
                                        <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ $t('common.description') }}</p>
                                    </th>
                                    <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
                                        <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ $t('common.image') }}</p>
                                    </th>
                                    <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
                                        <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ $t('common.status') }}</p>
                                    </th>
                                    <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
                                        <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ $t('common.actions') }}</p>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition-colors">
                                    <td class="px-4 py-3.5 border border-gray-100 dark:border-gray-800">
                                        <p class="text-sm text-gray-800 dark:text-white/90">{{ item.order }}</p>
                                    </td>
                                    <td class="px-4 py-3.5 border border-gray-100 dark:border-gray-800">
                                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ getLocalizedTitle(item) }}</p>
                                    </td>
                                    <td class="px-4 py-3.5 border border-gray-100 dark:border-gray-800">
                                        <div class="text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate">
                                            {{ getLocalizedDescription(item) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 border border-gray-100 dark:border-gray-800">
                                        <img
                                            v-if="item.image_url"
                                            :src="item.image_url"
                                            :alt="getLocalizedTitle(item)"
                                            class="h-10 w-10 rounded-lg object-cover"
                                        />
                                        <span v-else class="text-xs text-gray-400">{{ $t('common.noImage') }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 border border-gray-100 dark:border-gray-800">
                                        <button
                                            @click="toggleVisibility(item)"
                                            :class="[
                                                'inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium',
                                                item.is_active
                                                    ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300'
                                                    : 'bg-gray-50 text-gray-700 dark:bg-gray-900/20 dark:text-gray-300'
                                            ]"
                                        >
                                            {{ item.is_active ? $t('common.active') : $t('common.inactive') }}
                                        </button>
                                    </td>
                                    <td class="px-4 py-3.5 border border-gray-100 dark:border-gray-800">
                                        <div class="flex items-center gap-3">
                                            <Link
                                                :href="route('admin.landing.sections.items.edit', [landingPage.id, section.id, item.id])"
                                                class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium"
                                            >
                                                {{ $t('common.edit') }}
                                            </Link>
                                            <button
                                                @click="deleteItem(item)"
                                                class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium"
                                            >
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Reorder Info -->
                        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-t-0 border-gray-200 dark:border-gray-800 rounded-b-xl">
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                💡 {{ $t('landing.reorderInfo') }}
                            </p>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12 border border-t-0 border-gray-200 dark:border-gray-800 rounded-b-xl">
                        <div class="text-gray-400 dark:text-gray-500 mb-4">
                            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-2">
                            {{ $t('landing.noItems') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                            {{ $t('landing.noItemsDescription') }}
                        </p>
                        <Link
                            :href="route('admin.landing.sections.items.create', [landingPage.id, section.id])"
                            class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-[11px] text-sm font-medium text-white transition"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M5 10.0002H15.0006M10.0002 5V15.0006" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            {{ $t('landing.addFirstItem') }}
                        </Link>
                    </div>
                </div>
            </ComponentCard>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import { useI18n } from 'vue-i18n'

const { locale, t } = useI18n()
const currentPageTitle = computed(() => t('landing.items'))

const props = defineProps({
    landingPage: {
        type: Object,
        required: true
    },
    section: {
        type: Object,
        required: true
    },
    items: {
        type: Array,
        default: () => []
    }
});

const getLocalizedTitle = (item) => {
    if (item.title && typeof item.title === 'object') {
        return item.title[locale.value] || item.title.ar || item.title.en || 'Untitled';
    }
    return item.title || 'Untitled';
};

const getLocalizedDescription = (item) => {
    if (item.description && typeof item.description === 'object') {
        return item.description[locale.value] || item.description.ar || item.description.en || '';
    }
    return item.description || '';
};

const toggleVisibility = (item) => {
    if (confirm('تبديل حالة العنصر؟')) {
        router.patch(
            route('admin.landing.sections.items.toggle', [props.landingPage.id, props.section.id, item.id]),
            {},
            {
                preserveScroll: true
            }
        );
    }
};

const deleteItem = (item) => {
    if (confirm('هل أنت متأكد من حذف هذا العنصر؟')) {
        router.delete(
            route('admin.landing.sections.items.destroy', [props.landingPage.id, props.section.id, item.id]),
            {
                preserveScroll: true
            }
        );
    }
};
</script>
