<template>
    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ $t('common.edit') }} {{ landingPage.title }}
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6">
                        <!-- Title -->
                        <div class="mb-6">
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('common.title') }}
                            </label>
                            <input
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:text-gray-200"
                                :class="{ 'border-red-500': form.errors.title }"
                                required
                            />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <!-- Slug -->
                        <div class="mb-6">
                            <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Slug
                            </label>
                            <input
                                id="slug"
                                v-model="form.slug"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:text-gray-200"
                                :class="{ 'border-red-500': form.errors.slug }"
                                required
                            />
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                URL-friendly identifier (e.g., home, about-us)
                            </p>
                            <p v-if="form.errors.slug" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.slug }}
                            </p>
                        </div>

                        <!-- Is Active -->
                        <div class="mb-6">
                            <label class="flex items-center">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500"
                                />
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $t('common.active') }}
                                </span>
                            </label>
                        </div>

                        <!-- Meta Title -->
                        <div class="mb-6">
                            <label for="meta_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Meta Title (SEO)
                            </label>
                            <input
                                id="meta_title"
                                v-model="form.meta_title"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:text-gray-200"
                                :class="{ 'border-red-500': form.errors.meta_title }"
                            />
                            <p v-if="form.errors.meta_title" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.meta_title }}
                            </p>
                        </div>

                        <!-- Meta Description -->
                        <div class="mb-6">
                            <label for="meta_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Meta Description (SEO)
                            </label>
                            <textarea
                                id="meta_description"
                                v-model="form.meta_description"
                                rows="3"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:text-gray-200"
                                :class="{ 'border-red-500': form.errors.meta_description }"
                            ></textarea>
                            <p v-if="form.errors.meta_description" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.meta_description }}
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-3 pt-4 border-t dark:border-gray-700">
                            <Link
                                :href="route('admin.landing.index')"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100"
                            >
                                {{ $t('common.cancel') }}
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                {{ form.processing ? $t('common.saving') : $t('common.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/components/layout/AdminLayout.vue';

const props = defineProps({
    landingPage: {
        type: Object,
        required: true
    }
});

const form = useForm({
    title: props.landingPage.title,
    slug: props.landingPage.slug,
    is_active: props.landingPage.is_active,
    meta_title: props.landingPage.meta_title || '',
    meta_description: props.landingPage.meta_description || ''
});

const submit = () => {
    form.put(route('admin.landing.update', props.landingPage.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Redirect handled by controller
        }
    });
};
</script>
