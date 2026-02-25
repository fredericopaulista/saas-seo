<script setup lang="ts">
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'
import { format, parseISO } from 'date-fns'
import { ptBR } from 'date-fns/locale'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

const props = defineProps<{
  chartData: any[]
}>()

const chartDataConfig = computed(() => {
  if (!props.chartData || props.chartData.length === 0) {
    return { labels: [], datasets: [] }
  }

  const labels = props.chartData.map(item => 
    format(parseISO(item.date), 'dd/MM', { locale: ptBR })
  )

  const clicks = props.chartData.map(item => item.total_clicks)
  const impressions = props.chartData.map(item => item.total_impressions)

  return {
    labels,
    datasets: [
      {
        label: 'Cliques',
        backgroundColor: 'rgba(59, 130, 246, 0.2)', // primary-500 with opacity
        borderColor: '#3b82f6', // primary-500
        data: clicks,
        tension: 0.3,
        fill: true,
        yAxisID: 'y'
      },
      {
        label: 'Impressões',
        backgroundColor: 'transparent',
        borderColor: '#9ca3af', // gray-400
        borderDash: [5, 5],
        data: impressions,
        tension: 0.3,
        yAxisID: 'y1'
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index' as const,
    intersect: false,
  },
  plugins: {
    legend: {
      position: 'top' as const,
    },
    tooltip: {
      backgroundColor: 'rgba(17, 24, 39, 0.9)',
      padding: 12,
      cornerRadius: 8,
    }
  },
  scales: {
    x: {
      grid: {
        display: false
      }
    },
    y: {
      type: 'linear' as const,
      display: true,
      position: 'left' as const,
      beginAtZero: true,
      grid: {
        color: '#f3f4f6'
      }
    },
    y1: {
      type: 'linear' as const,
      display: true,
      position: 'right' as const,
      grid: {
        drawOnChartArea: false, // only want the grid lines for one axis to show up
      },
    },
  }
}
</script>

<template>
  <div class="h-full w-full min-h-[300px]">
    <Line v-if="chartDataConfig.datasets.length > 0" :data="chartDataConfig" :options="chartOptions" />
    <div v-else class="h-full flex items-center justify-center text-gray-400 text-sm">
      Sem dados suficientes para renderizar o gráfico neste período.
    </div>
  </div>
</template>
