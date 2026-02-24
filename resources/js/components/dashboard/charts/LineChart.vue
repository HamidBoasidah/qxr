<template>
  <div class="relative h-64 w-full">
    <div v-if="!hasData" class="flex h-full items-center justify-center text-gray-500 dark:text-gray-400">
      <div class="text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        <p class="mt-2 text-sm">لا توجد بيانات</p>
      </div>
    </div>
    <canvas v-else :id="chartId"></canvas>
  </div>
</template>

<script setup>
import { onMounted, watch, ref, computed } from 'vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

const props = defineProps({
  chartData: {
    type: Object,
    required: true
  },
  chartId: {
    type: String,
    required: true
  },
  isRevenue: {
    type: Boolean,
    default: false
  }
})

const hasData = computed(() => {
  return props.chartData?.data && props.chartData.data.length > 0
})

let chartInstance = null

const createChart = () => {
  const ctx = document.getElementById(props.chartId)
  if (!ctx) return

  // Debug: Log chart data
  console.log(`Creating chart: ${props.chartId}`, {
    labels: props.chartData.labels,
    data: props.chartData.data,
    labelsCount: props.chartData.labels?.length || 0,
    dataCount: props.chartData.data?.length || 0
  })

  // Destroy existing chart if any
  if (chartInstance) {
    chartInstance.destroy()
  }

  const isDark = document.documentElement.classList.contains('dark')
  const textColor = isDark ? '#e5e7eb' : '#374151'
  const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'

  chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: props.chartData.labels || [],
      datasets: [{
        label: props.isRevenue ? 'Revenue' : 'Count',
        data: props.chartData.data || [],
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        tension: 0.4,
        fill: true,
        pointRadius: 3,
        pointHoverRadius: 5,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          mode: 'index',
          intersect: false,
          callbacks: {
            label: function(context) {
              let label = context.dataset.label || ''
              if (label) {
                label += ': '
              }
              if (props.isRevenue) {
                label += new Intl.NumberFormat('en-US', {
                  style: 'decimal',
                  minimumFractionDigits: 2
                }).format(context.parsed.y)
              } else {
                label += context.parsed.y
              }
              return label
            }
          }
        }
      },
      scales: {
        x: {
          grid: {
            color: gridColor
          },
          ticks: {
            color: textColor
          }
        },
        y: {
          beginAtZero: true,
          grid: {
            color: gridColor
          },
          ticks: {
            color: textColor,
            callback: function(value) {
              if (props.isRevenue) {
                return new Intl.NumberFormat('en-US', {
                  notation: 'compact',
                  minimumFractionDigits: 0
                }).format(value)
              }
              return value
            }
          }
        }
      }
    }
  })
}

onMounted(() => {
  createChart()
})

watch(() => props.chartData, () => {
  createChart()
}, { deep: true })
</script>
