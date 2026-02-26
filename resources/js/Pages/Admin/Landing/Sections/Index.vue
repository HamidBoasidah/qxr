<template>
    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        {{ $t('landing.sections') }} - {{ landingPage.title }}
                    </h2>
                    <Link
                        :href="route('admin.landing.index')"
                        class="text-sm text-teal-600 hover:text-teal-700 dark:text-teal-400"
                    >
                        ← {{ $t('landing.pages') }}
                    </Link>
                </div>
                <Link
                    :href="route('admin.landing.sections.create', landingPage.id)"
                    class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors"
                >
                    + {{ $t('landing.addSection') }}
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div v-if="sections.length > 0" class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ $t('landing.order') }}
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ $t('landing.type') }}
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ $t('common.title') }}
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ $t('landing.items_count') }}
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ $t('common.status') }}
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ $t('common.actions') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="section in sections" :key="section.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                            {{ section.order }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ section.type }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-200">
                                            {{ getLocalizedTitle(section) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ section.items_count || 0 }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <button
                                                @click="toggleVisibility(section)"
                                                :class="[
                                                    'px-2 py-1 text-xs font-medium rounded-full',
                                                    section.is_active
                                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                                        : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                                ]"
                                            >
                                                {{ section.is_active ? $t('common.active') : $t('common.inactive') }}
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <Link
                                                :href="route('admin.landing.sections.items.index', [landingPage.id, section.id])"
                                                class="text-purple-600 hover:text-purple-900 dark:text-purple-400"
                                            >
                                                {{ $t('landing.manageItems') }}
                                            </Link>
                                            <Link
                                                :href="route('admin.landing.sections.edit', [landingPage.id, section.id])"
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400"
                                            >
                                                {{ $t('common.edit') }}
                                            </Link>
                                            <button
                                                @click="deleteSection(section)"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400"
                                            >
                                                {{ $t('common.delete') }}
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Reorder Info -->
                        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                💡 {{ $t('landing.reorderInfo') }}
                            </p>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="p-12 text-center">
                        <div class="text-gray-400 dark:text-gray-500 mb-4">
                            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                            {{ $t('landing.noSections') }}
                        </h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6">
                            {{ $t('landing.noSectionsDescription') }}
                        </p>
                        <Link
                            :href="route('admin.landing.sections.create', landingPage.id)"
                            class="inline-block px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700"
                        >
                            {{ $t('landing.addFirstSection') }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();

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
