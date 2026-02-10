<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import LineChart from '@/Components/LineChart.vue';

const props = defineProps({
    postureChunks: {
        type: Array,
        default: () => []
    },
    summaryStats: {
        type: Object,
        default: () => ({
            averageScore: 0,
            totalAlerts: 0,
            totalSlouch: 0,
            totalDuration: '0m', // Added default for new metric
            totalLogs: 0
        })
    },
    filters: Object
});

const page = usePage();
const user = computed(() => page.props.auth?.user || { name: 'User' });

// --- 1. Date Filter Logic ---
const selectedDate = ref(props.filters.date);

// Watch for date changes and fetch new data from Laravel
watch(selectedDate, (newDate) => {
    router.get('/dashboard', { date: newDate }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
});

// --- 2. PHT Time Formatting Helper ---
const formatPH = (dateString, type = 'full') => {
    const options = type === 'time' 
        ? { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: 'Asia/Manila' }
        : { dateStyle: 'medium', timeZone: 'Asia/Manila' };
    
    return new Date(dateString).toLocaleString('en-PH', options);
};

// --- 3. New Duration Formatter (Seconds -> M:SS) ---
const formatDuration = (seconds) => {
    if (!seconds) return '30s'; // Default fallback
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return m > 0 ? `${m}m ${s}s` : `${s}s`;
};

// --- 4. Chart Data Logic ---
const chartData = computed(() => {
    if (!props.postureChunks || props.postureChunks.length === 0) return null;

    // Clone and reverse so graph goes Left(Old) -> Right(New)
    const chunks = [...props.postureChunks].reverse();
    const labels = chunks.map(chunk => formatPH(chunk.created_at, 'time'));
    const scoreData = chunks.map(chunk => chunk.score);

    return {
        labels: labels,
        datasets: [{
            label: 'Posture Efficiency %',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            borderColor: '#6366f1',
            data: scoreData,
            fill: true,
            tension: 0.4,
            pointRadius: 4
        }]
    };
});

// --- 5. Action Handlers ---
const deleteEntry = (id) => {
    if (!confirm('Permanently delete this session log?')) return;

    router.delete(`/posture-chunks/${id}`, {
        preserveScroll: true,
        onSuccess: () => console.log('Entry deleted')
    });
};

// Dev Tool: create a fake entry to test the table
const testCreateChunk = () => {
    router.post('/posture-chunks', {
        score: Math.floor(Math.random() * 20 + 75),
        slouch_duration: Math.floor(Math.random() * 10),
        duration_seconds: 30, // Default test duration
        alert_count: 0
    }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="py-12 bg-slate-900 min-h-screen font-sans selection:bg-indigo-500 selection:text-white">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                <div class="flex flex-col md:flex-row md:justify-between md:items-end gap-6 px-2">
                    <div>
                        <h2 class="text-3xl font-black text-white tracking-tighter">
                            ERGO<span class="text-indigo-500">VISION</span>
                        </h2>
                        <p class="text-slate-400 text-xs font-mono uppercase tracking-widest mt-1">
                            Analytics & Historical Telemetry
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="flex items-center bg-slate-800 border border-slate-700 rounded-xl px-4 py-2 shadow-lg">
                            <span class="text-[10px] font-bold text-slate-400 uppercase mr-3">Filter Date</span>
                            <input 
                                type="date" 
                                v-model="selectedDate" 
                                class="bg-transparent border-none p-0 text-sm font-bold text-white focus:ring-0 cursor-pointer w-32"
                            >
                        </div>

                        <button @click="testCreateChunk" 
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-all shadow-lg shadow-indigo-500/20 uppercase tracking-wider">
                            + Add Test
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    
                    <div class="bg-slate-800/50 backdrop-blur-sm p-6 rounded-3xl border border-slate-700 shadow-xl relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-indigo-500" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" /></svg>
                        </div>
                        <p class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest mb-1">Avg Efficiency</p>
                        <p class="text-4xl font-black text-white">{{ summaryStats.averageScore }}<span class="text-lg text-slate-500">%</span></p>
                    </div>

                    <div class="bg-slate-800/50 backdrop-blur-sm p-6 rounded-3xl border border-slate-700 shadow-xl relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-emerald-500" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" /></svg>
                        </div>
                        <p class="text-[10px] font-bold text-emerald-400 uppercase tracking-widest mb-1">Total Time</p>
                        <p class="text-4xl font-black text-white">{{ summaryStats.totalDuration }}</p>
                    </div>

                    <div class="bg-slate-800/50 backdrop-blur-sm p-6 rounded-3xl border border-slate-700 shadow-xl relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-amber-500" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                        </div>
                        <p class="text-[10px] font-bold text-amber-400 uppercase tracking-widest mb-1">Slouch Time</p>
                        <p class="text-4xl font-black text-white">
                            {{ Math.round(summaryStats.totalSlouch / 60) }}<span class="text-lg text-slate-500">m</span> 
                            {{ summaryStats.totalSlouch % 60 }}<span class="text-lg text-slate-500">s</span>
                        </p>
                    </div>

                    <div class="bg-slate-800/50 backdrop-blur-sm p-6 rounded-3xl border border-slate-700 shadow-xl relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-red-500" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                        </div>
                        <p class="text-[10px] font-bold text-red-400 uppercase tracking-widest mb-1">Corrections</p>
                        <p class="text-4xl font-black text-white">{{ summaryStats.totalAlerts }}</p>
                    </div>

                </div>

                <div class="bg-slate-800 rounded-3xl p-8 border border-slate-700 shadow-2xl">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-2 h-8 bg-indigo-500 rounded-full"></div>
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest">
                            Postural Trend: <span class="text-white">{{ formatPH(selectedDate) }}</span>
                        </h3>
                    </div>
                    
                    <div class="h-[350px] w-full">
                        <LineChart v-if="chartData" :chart-data="chartData" />
                        <div v-else class="h-full flex flex-col items-center justify-center text-slate-500 border-2 border-dashed border-slate-700 rounded-2xl">
                            <p class="text-lg font-bold">No telemetry recorded</p>
                            <p class="text-xs uppercase tracking-widest mt-2">Start a session to generate data</p>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-800 shadow-xl rounded-3xl overflow-hidden border border-slate-700">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-900/50 text-slate-400 font-bold uppercase text-[10px] tracking-widest border-b border-slate-700">
                            <tr>
                                <th class="px-8 py-5">Timestamp</th>
                                <th class="px-8 py-5">Duration</th> <th class="px-8 py-5 text-center">Score</th>
                                <th class="px-8 py-5 text-center">Slouching</th>
                                <th class="px-8 py-5 text-center">Alerts</th>
                                <th class="px-8 py-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/50 text-slate-300">
                            <tr v-for="chunk in postureChunks" :key="chunk.id" class="hover:bg-slate-700/50 transition group">
                                <td class="px-8 py-5 font-mono text-xs text-indigo-300">{{ formatPH(chunk.created_at, 'time') }}</td>
                                
                                <td class="px-8 py-5 font-mono text-xs text-emerald-400">
                                    {{ formatDuration(chunk.duration_seconds || 30) }}
                                </td>

                                <td class="px-8 py-5 text-center">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold border" 
                                          :class="chunk.score >= 80 ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20'">
                                        {{ chunk.score }}%
                                    </span>
                                </td>
                                
                                <td class="px-8 py-5 text-center text-slate-400">{{ chunk.slouch_duration }}s</td>
                                
                                <td class="px-8 py-5 text-center">
                                    <span v-if="chunk.alert_count > 0" class="text-white font-bold bg-red-500 px-2 py-0.5 rounded text-xs">{{ chunk.alert_count }}</span>
                                    <span v-else class="text-slate-600">-</span>
                                </td>
                                
                                <td class="px-8 py-5 text-right">
                                    <button @click="deleteEntry(chunk.id)" class="text-slate-600 hover:text-red-400 transition opacity-0 group-hover:opacity-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div v-if="postureChunks.length === 0" class="p-12 text-center text-slate-500 font-mono text-xs uppercase tracking-widest">
                        No logs found for this date.
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>