<template>
    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        {{ $t('common.edit') }} {{ $t('landing.items') }}
                    </h2>
                    <Link
                        :href="route('admin.landing.sections.items.index', [landingPage.id, section.id])"
                        class="text-sm text-teal-600 hover:text-teal-700 dark:text-teal-400"
                    >
                        ← {{ $t('landing.items') }}
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6">
                        <!-- Order -->
                        <div class="mb-6">
                            <label for="order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('landing.order') }}
                            </label>
                            <input
                                id="order"
                                v-model.number="form.order"
                                type="number"
                                min="0"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:text-gray-200"
                                :class="{ 'border-red-500': form.errors.order }"
                            />
                            <p v-if="form.errors.order" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.order }}
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

                        <!-- Description Arabic -->
                        <div class="mb-6">
                            <label for="description_ar" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('common.description') }} (عربي)
                            </label>
                            <textarea
                                id="description_ar"
                                v-model="form.description.ar"
                                rows="3"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:text-gray-200"
                                :class="{ 'border-red-500': form.errors['description.ar'] }"
                            ></textarea>
                            <p v-if="form.errors['description.ar']" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors['description.ar'] }}
                            </p>
                        </div>

                        <!-- Description English -->
                        <div class="mb-6">
                            <label for="description_en" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('common.description') }} (English)
                            </label>
                            <textarea
                                id="description_en"
                                v-model="form.description.en"
                                rows="3"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:text-gray-200"
                                :class="{ 'border-red-500': form.errors['description.en'] }"
                            ></textarea>
                            <p v-if="form.errors['description.en']" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors['description.en'] }}
                            </p>
                        </div>

                        <!-- Current Image -->
                        <div v-if="item.image_url" class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('common.currentImage') }}
                            </label>
                            <img :src="item.image_url" :alt="item.title" class="h-32 w-32 object-cover rounded-lg" />
                        </div>

                        <!-- Image Upload -->
                        <div class="mb-6">
                            <label for="image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ item.image_url ? $t('common.changeImage') : $t('common.image') }}
                            </label>
                            <input
                                id="image"
                                type="file"
                                accept="image/*"
                                @change="handleImageChange"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:text-gray-200"
                                :class="{ 'border-red-500': form.errors.image }"
                            />
                            <p v-if="form.errors.image" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.image }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Max size: 2MB (JPG, PNG, GIF) - اترك فارغاً للإبقاء على الصورة الحالية
                            </p>
                            
                            <!-- New Image Preview -->
                            <div v-if="imagePreview" class="mt-3">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $t('common.newImage') }}:</p>
                                <img :src="imagePreview" alt="Preview" class="h-32 w-32 object-cover rounded-lg" />
                            </div>
                        </div>

                        <!-- Icon -->
                        <div class="mb-6">
                            <label for="icon" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('common.icon') }}
                            </label>
                            <input
                                id="icon"
                                v-model="form.icon"
                                type="text"
                                placeholder="e.g., search, video, document"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:text-gray-200"
                                :class="{ 'border-red-500': form.errors.icon }"
                            />
                            <p v-if="form.errors.icon" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.icon }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                اختياري - اسم الأيقونة للاستخدام في القالب
                            </p>
                        </div>

                        <!-- Link -->
                        <div class="mb-6">
                            <label for="link" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('common.link') }}
                            </label>
                            <input
                                id="link"
                                v-model="form.link"
                                type="url"
                                placeholder="https://example.com"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:text-gray-200"
                                :class="{ 'border-red-500': form.errors.link }"
                            />
                            <p v-if="form.errors.link" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.link }}
                            </p>
                        </div>

                        <!-- Link Text -->
                        <div class="mb-6">
                            <label for="link_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('common.linkText') }}
                            </label>
                            <input
                                id="link_text"
                                v-model="form.link_text"
                                type="text"
                                placeholder="Learn More"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:text-gray-200"
                                :class="{ 'border-red-500': form.errors.link_text }"
                            />
                            <p v-if="form.errors.link_text" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.link_text }}
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

                        <!-- Data (Advanced) -->
                        <div class="mb-6">
                            <details class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('common.additionalData') }} (JSON)
                                </summary>
                                <textarea
                                    v-model="dataJson"
                                    rows="4"
                                    placeholder='{"key": "value"}'
                                    class="w-full mt-3 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:text-gray-200 font-mono text-sm"
                                    :class="{ 'border-red-500': form.errors.data || dataError }"
                                ></textarea>
                                <p v-if="dataError" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                    {{ dataError }}
                                </p>
                                <p v-if="form.errors.data" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.data }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    اختياري - بيانات إضافية بصيغة JSON (مثل: color, value, suffix)
                                </p>
                            </details>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-3 pt-4 border-t dark:border-gray-700">
                            <Link
                                :href="route('admin.landing.sections.items.index', [landingPage.id, section.id])"
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
    section: {
        type: Object,
        required: true
    },
    item: {
        type: Object,
        required: true
    }
});

const form = useForm({
    order: props.item.order,
    title: {
        ar: props.item.title?.ar || '',
        en: props.item.title?.en || ''
    },
    description: {
        ar: props.item.description?.ar || '',
        en: props.item.description?.en || ''
    },
    image: null,
    icon: props.item.icon || '',
    link: props.item.link || '',
    link_text: props.item.link_text || '',
    is_active: props.item.is_active,
    data: props.item.data || {}
});

const imagePreview = ref(null);
const dataJson = ref(JSON.stringify(props.item.data || {}, null, 2));
const dataError = ref('');

const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.image = file;
        
        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

watch(dataJson, (newValue) => {
    try {
        if (newValue.trim()) {
            form.data = JSON.parse(newValue);
            dataError.value = '';
        } else {
            form.data = {};
            dataError.value = '';
        }
    } catch (e) {
        dataError.value = 'صيغة JSON غير صحيحة';
    }
});

const submit = () => {
    if (dataError.value) {
        return;
    }

    form.post(route('admin.landing.sections.items.update', [props.landingPage.id, props.section.id, props.item.id]), {
        preserveScroll: true,
        onSuccess: () => {
            // Redirect handled by controller
        },
        forceFormData: true
    });
};
</script>
