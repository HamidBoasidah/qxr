<template>
    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        {{ $t('landing.items') }} - {{ getLocalizedTitle(section) }}
                    </h2>
                    <Link
                        :href="route('admin.landing.sections.index', landingPage.id)"
                        class="text-sm text-teal-600 hover:text-teal-700 dark:text-teal-400"
                    >
                        ← {{ $t('landing.sections') }}
                    </Link>
                </div>
                <Link
                    :href="route('admin.landing.sections.items.create', [landingPage.id, section.id])"
                    class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors"
                >
                    + {{ $t('landing.addItem') }}
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div v-if="items.length > 0" class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ $t('landing.order') }}
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ $t('common.title') }}
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ $t('common.description') }}
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ $t('common.image') }}
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
                                    <tr v-for="item in items" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                            {{ item.order }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-200">
                                            {{ getLocalizedTitle(item) }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            <div class="max-w-xs truncate">
                                                {{ getLocalizedDescription(item) }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <img
                                                v-if="item.image_url"
                                                :src="item.image_url"
                                                :alt="getLocalizedTitle(item)"
                                                class="h-10 w-10 rounded object-cover"
                                            />
                                            <span v-else class="text-xs text-gray-400">{{ $t('common.noImage') }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <button
                                                @click="toggleVisibility(item)"
                                                :class="[
                                                    'px-2 py-1 text-xs font-medium rounded-full',
                                                    item.is_active
                                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                                        : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                                ]"
                                            >
                                                {{ item.is_active ? $t('common.active') : $t('common.inactive') }}
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <Link
                                                :href="route('admin.landing.sections.items.edit', [landingPage.id, section.id, item.id])"
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400"
                                            >
                                                {{ $t('common.edit') }}
                                            </Link>
                                            <button
                                                @click="deleteItem(item)"
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                            {{ $t('landing.noItems') }}
                        </h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6">
                            {{ $t('landing.noItemsDescription') }}
                        </p>
                        <Link
                            :href="route('admin.landing.sections.items.create', [landingPage.id, section.id])"
                            class="inline-block px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700"
                        >
                            {{ $t('landing.addFirstItem') }}
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
