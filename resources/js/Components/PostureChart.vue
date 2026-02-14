<script setup>
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import { 
    Chart as ChartJS, 
    Title, 
    Tooltip, 
    Legend, 
    LineElement, 
    CategoryScale, 
    LinearScale, 
    PointElement,
    Filler 
} from 'chart.js';

// Added Filler to allow for the cool glow effect under the line
ChartJS.register(Title, Tooltip, Legend, LineElement, CategoryScale, LinearScale, PointElement, Filler);

const props = defineProps({
    // MATCHING: Your dashboard sends :data="postureChunks" or :data="chart_data"
    data: {
        type: Array,
        default: () => []
    },
    color: { type: String, default: '#6366f1' }
});

// REACTIVITY: This computed block is the key. It recalculates whenever props.data changes.
const chartData = computed(() => ({
    // Extracting time for X-axis labels
    labels: props.data.map(d => new Date(d.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })),
    datasets: [{
        label: 'Efficiency %',
        // Extracting score for Y-axis points
        data: props.data.map(d => d.score),
        borderColor: '#6366f1',
        backgroundColor: 'rgba(99, 102, 241, 0.1)',
        tension: 0.4,
        fill: true,
        pointRadius: 5,
        pointHoverRadius: 8
    }]
}));

const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false }, // Cleaner look for your tech aesthetic
        tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#6366f1',
            bodyColor: '#fff',
            padding: 12,
            borderColor: 'rgba(255,255,255,0.1)',
            borderWidth: 1,
            displayColors: false
        }
    },
    scales: {
        y: { 
            beginAtZero: true, 
            max: 100,
            grid: { color: 'rgba(255,255,255,0.05)' },
            ticks: { color: '#64748b', font: { size: 10 } }
        },
        x: { 
            grid: { display: false },
            ticks: { color: '#64748b', font: { size: 10 } }
        }
    }
};
</script>

<template>
    <div class="h-full w-full">
        <Line :data="chartData" :options="options" />
    </div>
</template>