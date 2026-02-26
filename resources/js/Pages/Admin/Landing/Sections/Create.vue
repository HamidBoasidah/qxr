<template>
    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        {{ $t('landing.addSection') }}
                    </h2>
                    <Link
                        :href="route('admin.landing.sections.index', landingPage.id)"
                        class="text-sm text-teal-600 hover:text-teal-700 dark:text-teal-400"
                    >
                        ← {{ $t('landing.sections') }}
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6">
                        <!-- Section Type -->
                        <div class="mb-6">
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('landing.type') }} *
                            </label>
                            <select
                                id="type"
                                v-model="form.type"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:text-gray-200"
                                :class="{ 'border-red-500': form.errors.type }"
                                required
                            >
                                <option value="">-- {{ $t('common.select') }} --</option>
                                <option v-for="type in sectionTypes" :key="type" :value="type">
                                    {{ type }}
                                </option>
                            </select>
                            <p v-if="form.errors.type" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.type }}
                            </p>
                        </div>

                        <!-- Title Arabic -->
                        <div class="mb-6">
                            <label for="title_ar" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('common.title') }} (عربي)
                            </label>
                            <input
                                id="title_ar"
                                v-model="form.title.ar"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:text-gray-200"
                                :class="{ 'border-red-500': form.errors['title.ar'] }"
                            />
                            <p v-if="form.errors['title.ar']" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors['title.ar'] }}
                            </p>
                        </div>

                        <!-- Title English -->
                        <div class="mb-6">
                            <label for="title_en" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('common.title') }} (English)
                            </label>
                            <input
                                id="title_en"
                                v-model="form.title.en"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:text-gray-200"
                                :class="{ 'border-red-500': form.errors['title.en'] }"
                            />
                            <p v-if="form.errors['title.en']" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors['title.en'] }}
                            </p>
                        </div>

                        <!-- Subtitle Arabic -->
                        <div class="mb-6">
                            <label for="subtitle_ar" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('common.subtitle') }} (عربي)
                            </label>
                            <textarea
                                id="subtitle_ar"
                                v-model="form.subtitle.ar"
                                rows="2"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:text-gray-200"
                                :class="{ 'border-red-500': form.errors['subtitle.ar'] }"
                            ></textarea>
                            <p v-if="form.errors['subtitle.ar']" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors['subtitle.ar'] }}
                            </p>
                        </div>

                        <!-- Subtitle English -->
                        <div class="mb-6">
                            <label for="subtitle_en" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('common.subtitle') }} (English)
                            </label>
                            <textarea
                                id="subtitle_en"
                                v-model="form.subtitle.en"
                                rows="2"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:text-gray-200"
                                :class="{ 'border-red-500': form.errors['subtitle.en'] }"
                            ></textarea>
                            <p v-if="form.errors['subtitle.en']" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors['subtitle.en'] }}
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

                        <!-- Settings (Advanced) -->
                        <div class="mb-6">
                            <details class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('common.advancedSettings') }} (JSON)
                                </summary>
                                <textarea
                                    v-model="settingsJson"
                                    rows="4"
                                    placeholder='{"key": "value"}'
                                    class="w-full mt-3 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:text-gray-200 font-mono text-sm"
                                    :class="{ 'border-red-500': form.errors.settings || settingsError }"
                                ></textarea>
                                <p v-if="settingsError" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                    {{ settingsError }}
                                </p>
                                <p v-if="form.errors.settings" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.settings }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    اختياري - إعدادات إضافية بصيغة JSON
                                </p>
                            </details>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-3 pt-4 border-t dark:border-gray-700">
                            <Link
                                :href="route('admin.landing.sections.index', landingPage.id)"
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
import { ref, watch } from 'vue';
import AdminLayout from '@/components/layout/AdminLayout.vue';

const props = defineProps({
    landingPage: {
        type: Object,
        required: true
    },
    sectionTypes: {
        type: Array,
        required: true
    }
});

const form = useForm({
    type: '',
    title: {
        ar: '',
        en: ''
    },
    subtitle: {
        ar: '',
        en: ''
    },
    is_active: true,
    settings: {}
});

const settingsJson = ref('{}');
const settingsError = ref('');

watch(settingsJson, (newValue) => {
    try {
        if (newValue.trim()) {
            form.settings = JSON.parse(newValue);
            settingsError.value = '';
        } else {
            form.settings = {};
            settingsError.value = '';
        }
    } catch (e) {
        settingsError.value = 'صيغة JSON غير صحيحة';
    }
});

const submit = () => {
    if (settingsError.value) {
        return;
    }

    form.post(route('admin.landing.sections.store', props.landingPage.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Redirect handled by controller
        }
    });
};
</script>
