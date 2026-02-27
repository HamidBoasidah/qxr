<template>
  <CompanyLayout>
    <PageBreadcrumb :pageTitle="t('menu.dashboard_short')" />
    
    <div class="space-y-6">
      <!-- Filters Section -->
      <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800/50">
        <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">{{ t('common.filters') }}</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <!-- Date Preset -->
          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ t('dashboard.date_range') }}
            </label>
            <select 
              v-model="localFilters.date_preset" 
              @change="onFilterChange"
              class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
              <option v-for="preset in presets" :key="preset.value" :value="preset.value">
                {{ preset.label }}
              </option>
            </select>
          </div>

          <!-- Custom Date Range (shown only when custom is selected) -->
          <div v-if="localFilters.date_preset === 'custom'" class="grid grid-cols-2 gap-4">
            <div>
              <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ t('common.from') }}
              </label>
              <input 
                type="date" 
                v-model="localFilters.date_from" 
                @change="onFilterChange"
                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              />
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ t('common.to') }}
              </label>
              <input 
                type="date" 
                v-model="localFilters.date_to" 
                @change="onFilterChange"
                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- KPI Cards -->
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Orders Card -->
        <KPICard 
          :title="t('menu.orders')"
          :value="stats.kpis.orders.total"
          :trend="stats.kpis.orders.trend"
          icon="📦"
          color="blue"
        >
          <template #details>
            <div class="mt-2 space-y-1 text-xs text-gray-600 dark:text-gray-300">
              <div class="flex justify-between">
                <span>{{ t('order.status.pending') }}:</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ stats.kpis.orders.by_status.pending }}</span>
              </div>
              <div class="flex justify-between">
                <span>{{ t('order.status.approved') }}:</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ stats.kpis.orders.by_status.approved }}</span>
              </div>
              <div class="flex justify-between">
                <span>{{ t('order.status.delivered') }}:</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ stats.kpis.orders.by_status.delivered }}</span>
              </div>
            </div>
          </template>
        </KPICard>

        <!-- Revenue Card -->
        <KPICard 
          :title="t('dashboard.total_revenue')"
          :value="formatCurrency(stats.kpis.revenue.total_revenue)"
          :trend="stats.kpis.revenue.trend"
          icon="💰"
          color="green"
        >
          <template #details>
            <div class="mt-2 text-xs text-gray-600 dark:text-gray-300">
              <div class="flex justify-between">
                <span>{{ t('dashboard.total_discount') }}:</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(stats.kpis.revenue.total_discount) }}</span>
              </div>
            </div>
          </template>
        </KPICard>

        <!-- Invoices Card -->
        <KPICard 
          :title="t('menu.invoices')"
          :value="stats.kpis.invoices.total"
          :trend="stats.kpis.invoices.trend"
          icon="📄"
          color="purple"
        >
          <template #details>
            <div class="mt-2 space-y-1 text-xs text-gray-600 dark:text-gray-300">
              <div class="flex justify-between">
                <span>{{ t('invoice.status.paid') }}:</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ stats.kpis.invoices.paid }}</span>
              </div>
              <div class="flex justify-between">
                <span>{{ t('invoice.status.unpaid') }}:</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ stats.kpis.invoices.unpaid }}</span>
              </div>
            </div>
          </template>
        </KPICard>

        <!-- Products Card -->
        <KPICard 
          :title="t('menu.products')"
          :value="stats.kpis.products.total"
          :trend="stats.kpis.products.trend"
          icon="📦"
          color="orange"
        />
      </div>

      <!-- Additional KPI Cards -->
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <!-- Offers Card -->
        <KPICard 
          :title="t('menu.offers')"
          :value="stats.kpis.offers.total"
          :trend="stats.kpis.offers.trend"
          icon="🏷️"
          color="pink"
        >
          <template #details>
            <div class="mt-2 space-y-1 text-xs text-gray-600 dark:text-gray-300">
              <div class="flex justify-between">
                <span>{{ t('offer.status.active') }}:</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ stats.kpis.offers.active }}</span>
              </div>
              <div class="flex justify-between">
                <span>{{ t('offer.status.expired') }}:</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ stats.kpis.offers.expired }}</span>
              </div>
            </div>
          </template>
        </KPICard>

        <!-- Chat Card -->
        <KPICard 
          :title="t('menu.chat')"
          :value="stats.kpis.chat.total_conversations"
          icon="💬"
          color="indigo"
        >
          <template #details>
            <div class="mt-2 text-xs text-gray-600 dark:text-gray-300">
              <div class="flex justify-between">
                <span>{{ t('dashboard.total_messages') }}:</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ stats.kpis.chat.total_messages }}</span>
              </div>
            </div>
          </template>
        </KPICard>
      </div>

      <!-- Charts Section -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Orders Over Time Chart -->
        <ChartCard :title="t('dashboard.orders_over_time')">
          <LineChart 
            :chartData="stats.charts.orders_over_time"
            chartId="orders-over-time"
          />
        </ChartCard>

        <!-- Revenue Over Time Chart -->
        <ChartCard :title="t('dashboard.revenue_over_time')">
          <LineChart 
            :chartData="stats.charts.revenue_over_time"
            chartId="revenue-over-time"
            :isRevenue="true"
          />
        </ChartCard>

        <!-- Orders by Status Chart -->
        <ChartCard :title="t('dashboard.orders_by_status')">
          <PieChart 
            :chartData="stats.charts.orders_by_status"
            chartId="orders-by-status"
          />
        </ChartCard>

        <!-- Offers Activity Chart -->
        <ChartCard :title="t('dashboard.offers_activity')">
          <DoughnutChart 
            :chartData="stats.charts.offers_activity"
            chartId="offers-activity"
          />
        </ChartCard>

        <!-- Top Products Chart -->
        <ChartCard :title="t('dashboard.top_products')" class="lg:col-span-2">
          <BarChart 
            :chartData="stats.charts.top_products"
            chartId="top-products"
          />
        </ChartCard>
      </div>

      <!-- Tables Section -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Latest Orders Table -->
        <TableCard :title="t('dashboard.latest_orders')">
          <LatestOrdersTable :orders="stats.tables.latest_orders" />
        </TableCard>

        <!-- Latest Invoices Table -->
        <TableCard :title="t('dashboard.latest_invoices')">
          <LatestInvoicesTable :invoices="stats.tables.latest_invoices" />
        </TableCard>

        <!-- Latest Offers Table -->
        <TableCard :title="t('dashboard.latest_offers')">
          <LatestOffersTable :offers="stats.tables.latest_offers" />
        </TableCard>

        <!-- Latest Products Table -->
        <TableCard :title="t('dashboard.latest_products')">
          <LatestProductsTable :products="stats.tables.latest_products" />
        </TableCard>
      </div>
    </div>
  </CompanyLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import CompanyLayout from '@/components/layout/CompanyLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import KPICard from '@/components/dashboard/KPICard.vue'
import ChartCard from '@/components/dashboard/ChartCard.vue'
import TableCard from '@/components/dashboard/TableCard.vue'
import LineChart from '@/components/dashboard/charts/LineChart.vue'
import PieChart from '@/components/dashboard/charts/PieChart.vue'
import DoughnutChart from '@/components/dashboard/charts/DoughnutChart.vue'
import BarChart from '@/components/dashboard/charts/BarChart.vue'
import LatestOrdersTable from '@/components/dashboard/tables/LatestOrdersTable.vue'
import LatestInvoicesTable from '@/components/dashboard/tables/LatestInvoicesTable.vue'
import LatestOffersTable from '@/components/dashboard/tables/LatestOffersTable.vue'
import LatestProductsTable from '@/components/dashboard/tables/LatestProductsTable.vue'

const { t } = useI18n()

const props = defineProps({
  stats: {
    type: Object,
    required: true
  },
  profile: {
    type: Object,
    default: () => ({})
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  presets: {
    type: Array,
    default: () => []
  }
})

const localFilters = ref({
  date_preset: props.filters.date_preset || 'last_30_days',
  date_from: props.filters.date_from || null,
  date_to: props.filters.date_to || null,
})

const onFilterChange = () => {
  const filters = {
    date_preset: localFilters.value.date_preset,
  }

  if (localFilters.value.date_preset === 'custom') {
    filters.date_from = localFilters.value.date_from
    filters.date_to = localFilters.value.date_to
  }

  router.get(route('company.dashboard'), filters, {
    preserveState: true,
    preserveScroll: true,
  })
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'decimal',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value)
}
</script>
