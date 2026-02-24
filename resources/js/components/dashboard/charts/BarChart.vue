<template>
  <div class="relative h-64 w-full">
    <canvas :id="chartId"></canvas>
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue'
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

let chartInstance = null

const createChart = () => {
  const ctx = document.getElementById(props.chartId)
  if (!ctx) return

  if (chartInstance) {
    chartInstance.destroy()
  }

  const isDark = document.documentElement.classList.contains('dark')
  const textColor = isDark ? '#e5e7eb' : '#374151'
  const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'

  chartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: props.chartData.labels || [],
      datasets: [{
        label: props.isRevenue ? 'Revenue' : 'Count',
        data: props.chartData.data || [],
        backgroundColor: [
          'rgba(59, 130, 246, 0.8)',
          'rgba(16, 185, 129, 0.8)',
          'rgba(251, 191, 36, 0.8)',
          'rgba(239, 68, 68, 0.8)',
          'rgba(139, 92, 246, 0.8)',
          'rgba(236, 72, 153, 0.8)',
          'rgba(14, 165, 233, 0.8)',
          'rgba(245, 158, 11, 0.8)',
          'rgba(34, 197, 94, 0.8)',
          'rgba(168, 85, 247, 0.8)',
        ],
        borderWidth: 0
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
          callbacks: {
            label: function(context) {
              let label = context.label || ''
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
            display: false
          },
          ticks: {
            color: textColor,
            maxRotation: 45,
            minRotation: 45
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
