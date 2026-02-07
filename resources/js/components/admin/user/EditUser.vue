<template>
  <div class="space-y-6">
    <!-- User Information Section -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">
          {{ t('users.userInformation') }}
        </h2>
      </div>

      <div class="p-4 sm:p-6 dark:border-gray-800">
        <form @submit.prevent>
          <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <!-- User Type (DISABLED) -->
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                {{ t('users.userType') ?? 'User Type' }}
              </label>
              <select
                v-model="form.user_type"
                disabled
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 px-4 py-2.5 text-sm text-gray-700 opacity-80 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/70"
              >
                <option value="customer">{{ t('users.customer') ?? 'Customer' }}</option>
                <option value="company">{{ t('users.company') ?? 'Company' }}</option>
              </select>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ t('users.userTypeLocked') ?? 'لا يمكن تغيير نوع المستخدم بعد إنشاء الحساب' }}
              </p>
              <p v-if="form.errors.user_type" class="mt-1 text-sm text-error-500">{{ form.errors.user_type }}</p>
            </div>

            <!-- Gender -->
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                {{ t('users.gender') ?? 'Gender' }}
              </label>
              <select
                v-model="form.gender"
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
              >
                <option :value="null">{{ t('users.chooseGender') ?? 'اختر الجنس' }}</option>
                <option value="male">{{ t('users.male') ?? 'Male' }}</option>
                <option value="female">{{ t('users.female') ?? 'Female' }}</option>
              </select>
              <p v-if="form.errors.gender" class="mt-1 text-sm text-error-500">{{ form.errors.gender }}</p>
            </div>

            <!-- First Name -->
            <div>
              <label for="first-name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                {{ t('profile.labels.firstName') }}
              </label>
              <input
                v-model="form.first_name"
                type="text"
                id="first-name"
                autocomplete="given-name"
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                :placeholder="t('profile.labels.firstName')"
              />
              <p v-if="form.errors.first_name" class="mt-1 text-sm text-error-500">{{ form.errors.first_name }}</p>
            </div>

            <!-- Last Name -->
            <div>
              <label for="last-name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                {{ t('profile.labels.lastName') }}
              </label>
              <input
                v-model="form.last_name"
                type="text"
                id="last-name"
                autocomplete="family-name"
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                :placeholder="t('profile.labels.lastName')"
              />
              <p v-if="form.errors.last_name" class="mt-1 text-sm text-error-500">{{ form.errors.last_name }}</p>
            </div>

            <!-- Email -->
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                {{ t('common.email') }}
              </label>
              <input
                v-model="form.email"
                type="text"
                autocomplete="email"
                :placeholder="t('users.emailPlaceholder')"
                class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
              />
              <p v-if="form.errors.email" class="mt-1 text-sm text-error-500">{{ form.errors.email }}</p>
            </div>

            <!-- Phone Number -->
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                {{ t('users.phoneNumber') ?? 'Phone Number' }}
              </label>
              <input
                v-model="form.phone_number"
                type="text"
                autocomplete="tel"
                :placeholder="t('users.phonePlaceholder') ?? 'مثال: 009665xxxxxxxx'"
                class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
              />
              <p v-if="form.errors.phone_number" class="mt-1 text-sm text-error-500">{{ form.errors.phone_number }}</p>
            </div>

            <!-- WhatsApp Number -->
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                {{ t('users.whatsappNumber') ?? 'WhatsApp Number' }}
              </label>
              <input
                v-model="form.whatsapp_number"
                type="text"
                autocomplete="tel"
                :placeholder="t('users.whatsappPlaceholder') ?? 'مثال: 009665xxxxxxxx'"
                class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
              />
              <p v-if="form.errors.whatsapp_number" class="mt-1 text-sm text-error-500">{{ form.errors.whatsapp_number }}</p>
            </div>

            <!-- Password -->
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                {{ t('users.password') }}
              </label>
              <input
                :type="showPassword ? 'text' : 'password'"
                v-model="form.password"
                autocomplete="new-password"
                :placeholder="t('users.editPasswordHint') ?? 'اتركه فارغًا إذا لا تريد تغييره'"
                class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
              />
              <p v-if="form.errors.password" class="mt-1 text-sm text-error-500">{{ form.errors.password }}</p>
            </div>

            <!-- Confirm Password -->
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                {{ t('users.confirmPassword') }}
              </label>
              <input
                :type="showPassword ? 'text' : 'password'"
                v-model="form.password_confirmation"
                autocomplete="new-password"
                :placeholder="t('users.editPasswordConfirmHint') ?? 'تأكيد كلمة المرور'"
                class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
              />
              <p v-if="form.errors.password_confirmation" class="mt-1 text-sm text-error-500">
                {{ form.errors.password_confirmation }}
              </p>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Profile Information Section -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">
          {{ t('users.profileInformation') ?? 'Profile Information' }}
        </h2>
      </div>

      <div class="p-4 sm:p-6">
        <!-- Customer Profile -->
        <div v-if="form.user_type === 'customer'" class="grid grid-cols-1 gap-5 md:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('users.businessName') ?? 'Business Name' }}
            </label>
            <input
              v-model="form.business_name"
              type="text"
              class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
            />
            <p v-if="form.errors.business_name" class="mt-1 text-sm text-error-500">{{ form.errors.business_name }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('users.customerCategory') ?? 'Customer Category' }}
            </label>
            <select
              v-model="form.customer_category_id"
              class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            >
              <option :value="null">{{ t('users.chooseCategory') ?? 'اختر الفئة' }}</option>
              <option v-for="c in filteredCategories" :key="c.id" :value="c.id">
                {{ categoryName(c) }}
              </option>
            </select>
            <p v-if="form.errors.customer_category_id" class="mt-1 text-sm text-error-500">
              {{ form.errors.customer_category_id }}
            </p>
          </div>
        </div>

        <!-- Company Profile -->
        <div v-else class="grid grid-cols-1 gap-5 md:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('users.companyName') ?? 'Company Name' }}
            </label>
            <input
              v-model="form.company_name"
              type="text"
              class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
            />
            <p v-if="form.errors.company_name" class="mt-1 text-sm text-error-500">{{ form.errors.company_name }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('users.companyCategory') ?? 'Company Category' }}
            </label>
            <select
              v-model="form.company_category_id"
              class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            >
              <option :value="null">{{ t('users.chooseCategory') ?? 'اختر الفئة' }}</option>
              <option v-for="c in filteredCategories" :key="c.id" :value="c.id">
                {{ categoryName(c) }}
              </option>
            </select>
            <p v-if="form.errors.company_category_id" class="mt-1 text-sm text-error-500">
              {{ form.errors.company_category_id }}
            </p>
          </div>

          <!-- Company Logo Upload (with initial image) -->
          <div class="md:col-span-2">
            <ImageUploadBox
              v-model="form.logo"
              input-id="company-logo"
              :initial-image="initialCompanyLogo"
              label="users.companyLogo"
            />
            <p v-if="form.errors.logo" class="mt-1 text-sm text-error-500">{{ form.errors.logo }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- User Avatar Upload (with initial image) -->
    <ImageUploadBox
      v-model="form.avatar"
      input-id="user-image"
      :initial-image="initialUserAvatar"
      label="users.userImage"
    />
    <p v-if="form.errors.avatar" class="mt-1 text-sm text-error-500">{{ form.errors.avatar }}</p>

    <!-- Buttons -->
    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
      <Link
        :href="route('admin.users.index')"
        class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]"
      >
        {{ t('buttons.backToList') }}
      </Link>

      <button
        type="button"
        class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white transition"
        @click="update"
        :disabled="form.processing"
        :class="{ 'cursor-not-allowed opacity-60': form.processing }"
      >
        {{ t('buttons.update') ?? 'Update' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotifications } from '@/composables/useNotifications'
import ImageUploadBox from '@/Components/common/ImageUploadBox.vue'

const { t, locale } = useI18n()
const { success, error } = useNotifications()

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
  categories: {
    type: Array,
    default: () => [],
  },
  roles: {
    type: Array,
    default: () => [],
  },
})

const user = computed(() => props.user ?? {})
const categories = computed(() => props.categories ?? [])

const showPassword = ref(false)

// دعم اسم العلاقات
const customerProfile = computed(() => user.value.customer_profile || user.value.customerProfile || null)
const companyProfile = computed(() => user.value.company_profile || user.value.companyProfile || null)

/**
 * initial image URLs (مثل صفحة المدير)
 * - avatar: user.avatar
 * - logo: companyProfile.logo_path
 * ملاحظة: إذا عندك أسماء أعمدة مختلفة أخبرني وسأعدل سطرين فقط.
 */
const initialUserAvatar = computed(() => {
  const path = user.value?.avatar
  return path ? `/storage/${path}` : null
})

const initialCompanyLogo = computed(() => {
  const p = companyProfile.value
  const path = p?.logo_path || p?.logo
  return path ? `/storage/${path}` : null
})

const form = useForm({
  _method: 'PUT',

  first_name: user.value?.first_name ?? '',
  last_name: user.value?.last_name ?? '',
  email: user.value?.email ?? '',

  phone_number: user.value?.phone_number ?? '',
  whatsapp_number: user.value?.whatsapp_number ?? '',

  is_active: user.value?.is_active ?? true,
  locale: user.value?.locale ?? 'ar',

  // النوع ثابت
  user_type: user.value?.user_type ?? 'customer',
  gender: user.value?.gender ?? null,

  facebook: user.value?.facebook ?? '',
  x_url: user.value?.x_url ?? '',
  linkedin: user.value?.linkedin ?? '',
  instagram: user.value?.instagram ?? '',

  // كلمة المرور اختيارية
  password: '',
  password_confirmation: '',

  // ملفات
  avatar: null,

  // customer profile
  business_name: customerProfile.value?.business_name ?? '',
  customer_category_id: customerProfile.value?.category_id ?? null,
  customer_is_active: customerProfile.value?.is_active ?? true,

  // company profile
  company_name: companyProfile.value?.company_name ?? '',
  company_category_id: companyProfile.value?.category_id ?? null,
  company_is_active: companyProfile.value?.is_active ?? true,
  logo: null,
})

/**
 * فلترة الأقسام حسب النوع (customer/company فقط)
 */
const filteredCategories = computed(() => {
  return categories.value.filter(c => c.category_type === form.user_type)
})

function categoryName(c) {
  return c?.name?.[locale.value] ?? c?.name_ar ?? c?.name_en ?? c?.name ?? `#${c?.id ?? ''}`
}

function update() {
  const formData = { ...form.data() }

  // إذا لم يُرفع ملف جديد: لا ترسل avatar/logo
  if (!form.avatar) delete formData.avatar
  if (!form.logo) delete formData.logo

  // إذا لم تُكتب كلمة مرور: لا ترسل password/password_confirmation
  if (!formData.password) {
    delete formData.password
    delete formData.password_confirmation
  }

  form.transform(() => formData).post(route('admin.users.update', props.user.id), {
    preserveScroll: true,
    onSuccess: () => success(t('users.userUpdatedSuccessfully') ?? 'Updated'),
    onError: () => error(t('users.userUpdateFailed') ?? 'Failed'),
  })
}
</script>