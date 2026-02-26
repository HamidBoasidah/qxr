<template>
    <AdminLayout>
        <PageBreadcrumb :pageTitle="currentPageTitle" />
        <div class="space-y-5 sm:space-y-6">
            <ComponentCard :title="currentPageTitle" :desc="landingPage.title">
                <!-- Back Link -->
                <div class="mb-4">
                    <Link
                        :href="route('admin.landing.index')"
                        class="inline-flex items-center text-sm text-brand-500 hover:text-brand-600 dark:text-brand-400"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        {{ $t('landing.pages') }}
                    </Link>
                </div>
                <div class="overflow-hidden">
                    <!-- Toolbar -->
                    <div class="flex flex-col gap-2 px-4 py-4 border border-b-0 border-gray-200 rounded-b-none rounded-xl dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ sections.length }} {{ $t('landing.sections') }}</span>
                        </div>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <Link
                                :href="route('admin.landing.sections.create', landingPage.id)"
                                class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-[11px] text-sm font-medium text-white transition sm:w-auto"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M5 10.0002H15.0006M10.0002 5V15.0006" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                {{ $t('landing.addSection') }}
                            </Link>
                        </div>
                    </div>

                    <div v-if="sections.length > 0" class="max-w-full overflow-x-auto">
                        <table class="w-full min-w-full">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
                                        <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ $t('landing.order') }}</p>
                                    </th>
                                    <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
                                        <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ $t('landing.type') }}</p>
                                    </th>
                                    <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
                                        <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ $t('common.title') }}</p>
                                    </th>
                                    <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
                                        <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ $t('landing.items_count') }}</p>
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
                                <tr v-for="section in sections" :key="section.id" class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition-colors">
                                    <td class="px-4 py-3.5 border border-gray-100 dark:border-gray-800">
                                        <p class="text-sm text-gray-800 dark:text-white/90">{{ section.order }}</p>
                                    </td>
                                    <td class="px-4 py-3.5 border border-gray-100 dark:border-gray-800">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300">
                                            {{ section.type }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 border border-gray-100 dark:border-gray-800">
                                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ getLocalizedTitle(section) }}</p>
                                    </td>
                                    <td class="px-4 py-3.5 border border-gray-100 dark:border-gray-800">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ section.items_count || 0 }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 border border-gray-100 dark:border-gray-800">
                                        <button
                                            @click="toggleVisibility(section)"
                                            :class="[
                                                'inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium',
                                                section.is_active
                                                    ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300'
                                                    : 'bg-gray-50 text-gray-700 dark:bg-gray-900/20 dark:text-gray-300'
                                            ]"
                                        >
                                            {{ section.is_active ? $t('common.active') : $t('common.inactive') }}
                                        </button>
                                    </td>
                                    <td class="px-4 py-3.5 border border-gray-100 dark:border-gray-800">
                                        <div class="flex items-center gap-3">
                                            <Link
                                                :href="route('admin.landing.sections.items.index', [landingPage.id, section.id])"
                                                class="text-purple-600 hover:text-purple-700 dark:text-purple-400 dark:hover:text-purple-300 text-sm font-medium"
                                            >
                                                {{ $t('landing.manageItems') }}
                                            </Link>
                                            <Link
                                                :href="route('admin.landing.sections.edit', [landingPage.id, section.id])"
                                                class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium"
                                            >
                                                {{ $t('common.edit') }}
                                            </Link>
                                            <button
                                                @click="deleteSection(section)"
                                                class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium"
                                            >
                                                {{ $t('common.delete') }}
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-2">
                            {{ $t('landing.noSections') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                            {{ $t('landing.noSectionsDescription') }}
                        </p>
                        <Link
                            :href="route('admin.landing.sections.create', landingPage.id)"
                            class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-[11px] text-sm font-medium text-white transition"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M5 10.0002H15.0006M10.0002 5V15.0006" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            {{ $t('landing.addFirstSection') }}
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
const currentPageTitle = computed(() => t('landing.sections'))

const props = defineProps({
    landingPage: {
        type: Object,
        required: true
    },
    sections: {
        type: Array,
        default: () => []
    }
});

const getLocalizedTitle = (section) => {
    if (section.title && typeof section.title === 'object') {
        return section.title[locale.value] || section.title.ar || section.title.en || 'Untitled';
    }
    return section.title || 'Untitled';
};

const toggleVisibility = (section) => {
    if (confirm('تبديل حالة القسم؟')) {
        router.patch(
            route('admin.landing.sections.toggle', [props.landingPage.id, section.id]),
            {},
            {
                preserveScroll: true
            }
        );
    }
};

const deleteSection = (section) => {
    if (confirm('هل أنت متأكد من حذف هذا القسم؟ سيتم حذف جميع العناصر المرتبطة به.')) {
        router.delete(route('admin.landing.sections.destroy', [props.landingPage.id, section.id]), {
            preserveScroll: true
        });
    }
};
</script>
