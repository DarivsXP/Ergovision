<script setup>
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import { 
    Chart as ChartJS, 
    Title, Tooltip, Legend, LineElement, CategoryScale, LinearScale, PointElement, Filler 
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, LineElement, CategoryScale, LinearScale, PointElement, Filler);

const props = defineProps({
    data: { type: Array, default: () => [] },
    color: { type: String, default: '#6366f1' }
});

const chartData = computed(() => ({
    // FIX: Send the raw created_at string so the options callback can parse it properly
    labels: props.data.map(d => d.created_at), 
    datasets: [{
        label: 'Efficiency %',
        data: props.data.map(d => d.score),
        borderColor: '#6366f1',
        backgroundColor: 'rgba(99, 102, 241, 0.1)',
        tension: 0.4,
        fill: true,
        pointRadius: props.data.length > 50 ? 0 : 4, // Hide points if data is dense
        pointHoverRadius: 6
    }]
}));

const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#6366f1',
            bodyColor: '#fff',
            // Customizing tooltip to look better with the raw date labels
            callbacks: {
                title: (items) => {
                    const d = new Date(items[0].label);
                    return d.toLocaleString('en-PH', { 
                        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' 
                    });
                }
            }
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
            ticks: {
                maxRotation: 0,
                autoSkip: true,
                maxTicksLimit: 8, // Keeps the chart from looking cluttered
                callback: function(val, index) {
                    const rawLabel = this.getLabelForValue(val);
                    const date = new Date(rawLabel);
                    
                    // Logic to check if we are spanning multiple days
                    const isMultiDay = props.data.length > 0 && 
                        new Date(props.data[0].created_at).toDateString() !== 
                        new Date(props.data[props.data.length - 1].created_at).toDateString();

                    if (isMultiDay) {
                        return date.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
                    } else {
                        return date.toLocaleTimeString('en-PH', { 
                            hour: 'numeric', minute: '2-digit', hour12: true 
                        });
                    }
                },
                color: '#64748b',
                font: { size: 10, weight: 'bold' }
            }
        }
    }
};
</script>

<template>
    <div class="h-full w-full">
        <Line :data="chartData" :options="options" />
    </div>
</template>