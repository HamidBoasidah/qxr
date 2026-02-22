<template>
  <div class="space-y-6">
    <!-- Order Information -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('order.orderDetails') || 'تفاصيل الطلب' }}</h2>
      </div>
      <div class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-x-5 gap-y-6 md:grid-cols-2">
          <InfoItem :label="t('order.order_no') || 'رقم الطلب'" :value="order.order_no" />
          <InfoItem :label="t('common.status')">
            <Badge :color="statusColor(order.status)" variant="light" size="sm">
              {{ statusLabel(order.status) }}
            </Badge>
          </InfoItem>
          <InfoItem :label="t('order.submitted_at') || 'تاريخ الإنشاء'" :value="order.submitted_at" />
          <InfoItem :label="t('users.company') || 'الشركة'" :value="order.company_name" />
          <InfoItem :label="t('users.customer') || 'العميل'" :value="order.customer_name" />
          <InfoItem :label="t('order.approved_at') || 'تاريخ الموافقة'" :value="order.approved_at || '—'" />
          <InfoItem :label="t('order.delivered_at') || 'تاريخ التسليم'" :value="order.delivered_at || '—'" />
        </div>
      </div>
    </div>

    <!-- Delivery Address -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('order.deliveryAddress') || 'عنوان التوصيل' }}</h2>
      </div>
      <div class="p-4 sm:p-6">
        <template v-if="order.delivery_address">
          <div class="grid grid-cols-1 gap-x-5 gap-y-6 md:grid-cols-2">
            <InfoItem :label="t('addresses.label') || 'التسمية'" :value="order.delivery_address.label || '—'" />
            <InfoItem :label="t('addresses.address') || 'العنوان'" :value="order.delivery_address.address || '—'" />
            <InfoItem :label="t('governorates.governorate') || 'المحافظة'" :value="order.delivery_address.governorate || '—'" />
            <InfoItem :label="t('districts.district') || 'المديرية'" :value="order.delivery_address.district || '—'" />
            <InfoItem :label="t('areas.area') || 'المنطقة'" :value="order.delivery_address.area || '—'" />
            <InfoItem v-if="order.delivery_address.lat" :label="t('order.coordinates') || 'الإحداثيات'">
              <span class="text-sm text-gray-700 dark:text-gray-200 font-mono">
                {{ order.delivery_address.lat }}, {{ order.delivery_address.lng }}
              </span>
            </InfoItem>
          </div>
        </template>
        <p v-else class="text-sm text-gray-500 dark:text-gray-400">{{ t('order.noDeliveryAddress') || 'لم يُحدَّد عنوان توصيل' }}</p>
      </div>
    </div>

    <!-- Totals -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('order.totals') || 'الإجماليات' }}</h2>
      </div>
      <div class="p-4 sm:p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <InfoItem :label="t('order.subtotal') || 'الإجمالي قبل الخصم'" :value="priceLabel(order.subtotal)" />
        <InfoItem :label="t('order.total_discount') || 'إجمالي الخصم'" :value="priceLabel(order.total_discount)" />
        <InfoItem :label="t('order.final_total') || 'الإجمالي النهائي'" :value="priceLabel(order.final_total)" />
      </div>
    </div>

    <!-- Items -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('order.items') || 'العناصر' }}</h2>
      </div>
      <div class="p-4 sm:p-6 overflow-x-auto">
        <table class="w-full min-w-full border-separate border-spacing-0">
          <thead>
            <tr class="bg-gray-50 dark:bg-white/[0.06]">
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('product.name') || 'المنتج' }}</th>
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('common.quantity') || 'الكمية' }}</th>
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('product.unit') || 'الوحدة' }}</th>
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('product.base_price') || 'سعر الوحدة' }}</th>
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('order.discount') || 'الخصم' }}</th>
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('order.total') || 'الإجمالي' }}</th>
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('offers.selectedOffer') || 'العرض' }}</th>
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('offers.bonuses') || 'الهدايا' }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, rowIdx) in order.items || []"
              :key="item.id || rowIdx"
              class="odd:bg-white even:bg-gray-50 dark:odd:bg-white/[0.03] dark:even:bg-white/[0.06] transition-colors"
            >
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-gray-200">{{ item.product_name || '—' }}</td>
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-gray-200">{{ item.qty }}</td>
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-gray-200">{{ item.unit || '—' }}</td>
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-gray-200">{{ priceLabel(item.unit_price) }}</td>
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-gray-200">{{ priceLabel(item.discount_amount) }}</td>
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-gray-200">{{ priceLabel(item.final_total) }}</td>
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-gray-200">{{ item.selected_offer_title || '—' }}</td>
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-gray-200">
                <div class="flex flex-col gap-1" v-if="(item.bonuses || []).length">
                  <div v-for="(b, idx) in item.bonuses" :key="idx" class="text-sm text-gray-700 dark:text-gray-100">
                    • {{ b.bonus_product_name || '—' }} (x{{ b.bonus_qty }})
                    <span v-if="b.offer_title" class="text-xs text-gray-500 dark:text-gray-400">— {{ b.offer_title }}</span>
                  </div>
                </div>
                <span v-else class="text-sm text-gray-500 dark:text-gray-400">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Status Logs -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('order.statusHistory') || 'سجل الحالات' }}</h2>
      </div>
      <div class="p-4 sm:p-6">
        <div class="space-y-3">
          <div
            v-for="(log, idx) in order.status_logs || []"
            :key="idx"
            class="rounded-lg border border-gray-200 p-3 dark:border-gray-800 dark:bg-white/[0.02]"
          >
            <div class="flex flex-wrap justify-between gap-2">
              <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ statusLabel(log.from_status) }} {{ directionArrow }} {{ statusLabel(log.to_status) }}</span>
              </div>
              <span class="text-xs text-gray-500 dark:text-gray-400">{{ log.changed_at || '—' }}</span>
            </div>
            <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ log.note || '—' }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ log.changed_by || '—' }}</div>
          </div>
          <p v-if="!(order.status_logs || []).length" class="text-sm text-gray-500">{{ t('order.noStatusLogs') || 'لا يوجد سجلات' }}</p>
        </div>
      </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
      <Link :href="route('admin.orders.index')" class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
        {{ t('buttons.backToList') || 'العودة للقائمة' }}
      </Link>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import InfoItem from '@/components/common/InfoItem.vue'
import Badge from '@/components/ui/Badge.vue'

const { t, locale } = useI18n()

const props = defineProps({
  order: { type: Object, required: true },
})

const order = computed(() => props.order || {})

const directionArrow = computed(() => (locale.value === 'ar' ? '←' : '→'))

function priceLabel(price) {
  if (price === null || price === undefined) return '—'
  return Number(price).toLocaleString(locale.value)
}

function statusLabel(status) {
  if (!status) return '—'
  const key = `order.statuses.${status}`
  const translated = t(key)
  return translated !== key ? translated : status
}

function statusColor(status) {
  const map = {
    pending: 'warning',
    approved: 'info',
    preparing: 'info',
    shipped: 'info',
    delivered: 'success',
    rejected: 'error',
    cancelled: 'dark',
  }
  return map[status] || 'light'
}
</script>
