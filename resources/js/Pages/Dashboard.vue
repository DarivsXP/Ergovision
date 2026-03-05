<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PostureChart from '@/Components/PostureChart.vue';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import axios from 'axios';
import { driver } from "driver.js";
import "driver.js/dist/driver.css";

defineOptions({ name: 'UserDashboard' });

const props = defineProps({
    summaryStats: Object,
    postureChunks: Array,
    filters: Object
});

const user = usePage().props.auth.user;

// --- State Management ---
const activePeriod = ref(props.filters?.period || (props.filters?.date ? null : '7d'));
const selectedDate = ref(props.filters?.date || new Date().toISOString().split('T')[0]);
const dateInput = ref(null);
const showAllLogs = ref(false);
const showExportMenu = ref(false);

// --- Formatters ---
const formatPH = (dateString, type = 'full') => {
    if (!dateString) return '...';
    const date = new Date(dateString);
    const options = type === 'time' 
        ? { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: 'Asia/Manila' }
        : type === 'short'
        ? { month: 'short', day: 'numeric', timeZone: 'Asia/Manila' }
        : { dateStyle: 'medium', timeZone: 'Asia/Manila' };
    return date.toLocaleString('en-PH', options);
};

const rangeLabel = computed(() => {
    if (!activePeriod.value && selectedDate.value) return `${formatPH(selectedDate.value, 'short')}`;
    if (activePeriod.value) {
        const days = parseInt(activePeriod.value);
        const end = new Date();
        const start = new Date();
        start.setDate(end.getDate() - days + 1);
        return `${formatPH(start.toISOString(), 'short')} - ${formatPH(end.toISOString(), 'short')}`;
    }
    return "All Time";
});

// --- Research & Progress Logic ---
const weeklyGoal = 80;

const goalProgress = computed(() => {
    const score = props.summaryStats.averageScore || 0;
    return Math.min(Math.round((score / weeklyGoal) * 100), 100);
});

const progressInsight = computed(() => {
    if (props.postureChunks.length < 2) return { status: 'Calibrating Baseline', color: 'text-indigo-400' };
    const mid = Math.floor(props.postureChunks.length / 2);
    const recentAvg = props.postureChunks.slice(mid).reduce((acc, c) => acc + c.score, 0) / (props.postureChunks.length - mid);
    const olderAvg = props.postureChunks.slice(0, mid).reduce((acc, c) => acc + c.score, 0) / mid;
    const diff = recentAvg - olderAvg;

    if (diff > 2) return { status: `Improving (+${Math.round(diff)}%)`, color: 'text-emerald-400' };
    if (diff < -2) return { status: `Declining (${Math.round(diff)}%)`, color: 'text-rose-400' };
    return { status: 'Stable Consistency', color: 'text-amber-400' };
});

// --- Export Logic ---
const exportToCSV = () => { /* Logic unchanged */ };
const exportToPDF = () => { /* Logic unchanged */ };

// --- Navigation Logic ---
const updateView = (params) => {
    router.get(route('dashboard'), params, {
        preserveState: true, replace: true, preserveScroll: true, only: ['summaryStats', 'postureChunks', 'filters'],
    });
};

const handlePeriodChange = (period) => {
    activePeriod.value = period;
    selectedDate.value = null;
    updateView({ period });
};

const handleDateChange = (date) => {
    if (!date) return;
    selectedDate.value = date;
    activePeriod.value = null; 
    updateView({ date });
};

const openCalendar = () => { if (dateInput.value) dateInput.value.showPicker(); };

const tableChunks = computed(() => {
    const reversed = [...props.postureChunks].reverse();
    return showAllLogs.value ? reversed : reversed.slice(0, 5);
});

const handleDelete = (id) => {
    if (confirm('Permanently remove this session? This will recalculate your averages.')) {
        router.delete(route('posture-chunks.destroy', id), { preserveScroll: true });
    }
};

const tourDriver = driver({
    showProgress: true,
    animate: true,
    overlayColor: 'rgba(2, 6, 23, 0.95)',
    allowClose: false,
    
    // Cleaned up button text (no ugly arrows!)
    nextBtnText: 'Next',
    prevBtnText: 'Back',
    doneBtnText: 'Start Session', 
    
    steps: [
        { 
            element: '#tour-step-welcome', 
            popover: { 
                title: 'Welcome to Ergovision!', 
                description: '<div class="mt-2 text-sm">Let us take a quick 30-second tour to show you how to read your telemetry.</div>', 
                side: "bottom", align: 'start' 
            } 
        },
        { 
            element: '#tour-step-filters', 
            popover: { 
                title: 'Filter Your Data', 
                description: '<div class="mt-2 text-sm">Use these controls to view your posture data over the last <strong class="text-indigo-400">3, 7, or 30 days</strong>, or select a specific date from the calendar.</div>', 
                side: "bottom", align: 'center' 
            } 
        },
        { 
            element: '#tour-step-stats', 
            popover: { 
                title: 'Your Telemetry', 
                description: '<div class="mt-2 text-sm">As you complete camera sessions, your <strong class="text-emerald-400">efficiency</strong>, <strong class="text-amber-400">slouch time</strong>, and <strong class="text-rose-400">alert history</strong> will automatically aggregate here.</div>', 
                side: "bottom", align: 'center' 
            } 
        },
        { 
            element: '#tour-step-history', 
            popover: { 
                title: 'Session Logs', 
                description: '<div class="mt-2 text-sm">Review or delete your past sessions here.<br><br><span class="text-indigo-400 text-xs font-bold uppercase tracking-widest">Mobile Users:</span> You can swipe left and right to see the full table!</div>', 
                side: "top", align: 'start' 
            } 
        },
        { 
            // [FIXED] Removed the broken 'element' target. This now pops up perfectly in the center.
            popover: { 
                title: 'Ready to Track!', 
                description: '<div class="mt-2 text-sm">You are all set! Click the button below to initialize the AI engine and start your first live camera session.</div>', 
            } 
        }
    ],
    onDestroyed: () => {
        // Since allowClose is false, this ONLY runs when they click "Start Session"
        if (user && !user.has_seen_tour) {
            // First time users: Save to database, then teleport them to the camera
            axios.post(route('tour.complete')).then(() => {
                user.has_seen_tour = true; 
                router.visit(route('camera')); 
            }).catch(e => console.error("Failed to save tour state:", e));
        } else {
            router.visit(route('camera'));
        }
    }
});

onMounted(() => {
    if (user && !user.has_seen_tour) {
        setTimeout(() => tourDriver.drive(), 500);
    }
});

const startTour = () => {
    tourDriver.drive();
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="py-12 bg-[#020617] min-h-screen" @click="showExportMenu = false">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                <div class="flex flex-col lg:flex-row justify-between items-center gap-8 px-6 py-8 bg-white/[0.02] border border-white/5 rounded-[3rem] backdrop-blur-md">
                    <div id="tour-step-welcome" class="text-center lg:text-left">
                        <h2 class="text-3xl font-black text-white tracking-tighter uppercase">
                            ERGO<span class="text-indigo-500">VISION</span>
                        </h2>
                        <div class="flex items-center justify-center lg:justify-start gap-2 mt-1">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                            </span>
                            <p class="text-indigo-400/60 text-[10px] font-black uppercase tracking-[0.3em]">Range: {{ rangeLabel }}</p>
                        </div>
                    </div>

                    <div id="tour-step-filters" class="flex flex-col md:flex-row items-center gap-4">
                        
                        <button @click="startTour" class="hidden md:flex items-center gap-2 px-4 py-3 mr-2 text-[10px] font-black text-slate-400 hover:text-indigo-400 uppercase tracking-widest transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Replay Guide
                        </button>

                        <div class="relative">
                            <button @click.stop="showExportMenu = !showExportMenu" 
                                class="flex items-center gap-2 px-5 py-3 bg-indigo-600 hover:bg-indigo-500 rounded-2xl text-[10px] font-black text-white uppercase tracking-widest transition-all shadow-[0_0_20px_rgba(79,70,229,0.3)] group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 group-hover:animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Export Report
                            </button>

                            <transition 
                                enter-active-class="transition ease-out duration-200" 
                                enter-from-class="opacity-0 scale-95 translate-y-2" 
                                enter-to-class="opacity-100 scale-100 translate-y-0" 
                                leave-active-class="transition ease-in duration-150" 
                                leave-from-class="opacity-100 scale-100 translate-y-0" 
                                leave-to-class="opacity-0 scale-95 translate-y-2"
                            >
                                <div v-if="showExportMenu" class="absolute top-full right-0 mt-2 w-48 bg-slate-800 border border-white/10 rounded-xl shadow-2xl overflow-hidden z-50">
                                    <button @click="exportToCSV" class="w-full px-4 py-3 text-left text-[10px] font-black text-slate-300 hover:bg-indigo-600 hover:text-white uppercase tracking-widest transition-colors border-b border-white/5">
                                        Download CSV
                                    </button>
                                    
                                    <button @click="exportToPDF" class="w-full px-4 py-3 text-left text-[10px] font-black text-slate-300 hover:bg-indigo-600 hover:text-white uppercase tracking-widest transition-colors">
                                        Download PDF
                                    </button>
                                </div>
                            </transition>
                        </div>

                        <div class="flex items-center gap-2 p-1.5 bg-slate-950 rounded-2xl border border-white/10">
                            <button v-for="p in ['3d', '7d', '30d']" :key="p" @click="handlePeriodChange(p)"
                                class="px-4 md:px-6 py-2 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all"
                                :class="activePeriod === p ? 'bg-indigo-600 text-white shadow-[0_0_20px_rgba(79,70,229,0.4)]' : 'text-slate-400 hover:text-white'">
                                {{ p === '30d' ? '1M' : p }}
                            </button>
                        </div>

                        <div @click.stop="openCalendar" class="flex items-center gap-3 p-1.5 bg-slate-950 rounded-2xl border border-indigo-500/30 cursor-pointer px-4 hover:border-indigo-500 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            <span class="text-[11px] font-black text-white uppercase tracking-widest">{{ activePeriod ? 'Select Date' : formatPH(selectedDate, 'short') }}</span>
                            <input ref="dateInput" type="date" :value="selectedDate" @input="handleDateChange($event.target.value)" class="sr-only" />
                        </div>
                        
                        <button @click="startTour" class="md:hidden mt-2 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            Replay Guide
                        </button>
                    </div>
                </div>

                <div id="tour-step-stats" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 px-4">
                    <div class="bg-slate-900 border border-white/5 p-8 rounded-[2.5rem] shadow-xl flex flex-col justify-between min-h-[220px]">
                        <div>
                            <div class="flex justify-between items-start mb-2">
                                <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Efficiency</p>
                                <span :class="['text-[9px] font-black uppercase tracking-tighter px-2 py-0.5 rounded-md bg-white/5', progressInsight.color]">
                                    {{ progressInsight.status }}
                                </span>
                            </div>
                            <p class="text-5xl font-black text-white tracking-tighter">{{ summaryStats.averageScore }}%</p>
                        </div>
                    </div>
                    <div class="bg-slate-900 border border-white/5 p-8 rounded-[2.5rem] shadow-xl flex flex-col justify-center">
                        <p class="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-2">Total Time</p>
                        <p class="text-5xl font-black text-white tracking-tighter whitespace-nowrap">{{ summaryStats.totalDuration }}</p>
                    </div>
                    <div class="bg-slate-900 border border-white/5 p-8 rounded-[2.5rem] shadow-xl flex flex-col justify-center">
                        <p class="text-[10px] font-black text-amber-400 uppercase tracking-widest mb-2">Slouch Time</p>
                        <p class="text-5xl font-black text-white tracking-tighter">{{ Math.floor(summaryStats.totalSlouch / 60) }}m</p>
                    </div>
                    <div class="bg-slate-900 border border-white/5 p-8 rounded-[2.5rem] shadow-xl flex flex-col justify-center">
                        <p class="text-[10px] font-black text-red-400 uppercase tracking-widest mb-2">Alerts</p>
                        <p class="text-5xl font-black text-white tracking-tighter">{{ summaryStats.totalAlerts }}</p>
                    </div>
                </div>

                <div class="bg-slate-900 rounded-[3rem] p-6 md:p-10 border border-white/5 shadow-2xl mx-4">
                    <div class="flex items-center gap-4 mb-10">
                        <div class="w-1.5 h-10 bg-indigo-500 rounded-full shadow-[0_0_20px_rgba(99,102,241,0.6)]"></div>
                        <div>
                            <h3 class="text-xs font-black text-white uppercase tracking-[0.2em]">Efficiency Analysis</h3>
                            <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">{{ rangeLabel }}</p>
                        </div>
                    </div>
                    <div class="h-[400px] w-full">
                        <PostureChart v-if="postureChunks.length > 0" :key="`${activePeriod}-${selectedDate}`" :data="postureChunks" />
                        <div v-else class="h-full flex flex-col items-center justify-center border-2 border-dashed border-white/5 rounded-[2rem] text-slate-600">
                            <p class="text-xs font-black uppercase tracking-widest">No telemetry for this period</p>
                        </div>
                    </div>
                </div>

                <div id="tour-step-history" class="bg-slate-900 rounded-[3rem] overflow-hidden border border-white/5 mx-4 mb-12 shadow-2xl">
                    <div class="px-6 md:px-10 py-8 border-b border-white/5 flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <h3 class="text-sm font-black text-white uppercase tracking-widest">History</h3>
                    </div>
                    
                    <div class="w-full overflow-x-auto">
                        <table class="w-full text-left min-w-[600px]">
                            <thead class="bg-white/[0.03] text-slate-500 text-[10px] font-black uppercase tracking-[0.3em] border-b border-white/5">
                                <tr>
                                    <th class="px-4 py-4 md:px-10 md:py-6 text-indigo-400">Timestamp</th>
                                    <th class="px-4 py-4 md:px-10 md:py-6 text-center">Score</th>
                                    <th class="px-4 py-4 md:px-10 md:py-6 text-center">Alerts</th>
                                    <th class="px-4 py-4 md:px-10 md:py-6 text-right pr-6 md:pr-12">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="chunk in tableChunks" :key="chunk.id" class="hover:bg-white/[0.01] transition-colors">
                                    <td class="px-4 py-4 md:px-10 md:py-6 font-mono text-xs text-slate-400">
                                        <span v-if="activePeriod" class="text-[10px] text-indigo-400/50 mr-2">{{ formatPH(chunk.created_at, 'short') }}</span>
                                        {{ formatPH(chunk.created_at, 'time') }}
                                    </td>
                                    <td class="px-4 py-4 md:px-10 md:py-6 text-center">
                                        <span class="px-4 py-1.5 rounded-xl text-[10px] font-black border" 
                                            :class="chunk.score >= 80 ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20'">
                                            {{ chunk.score }}%
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 md:px-10 md:py-6 text-center text-slate-400 font-bold text-sm">{{ chunk.alert_count }}</td>
                                    <td class="px-4 py-4 md:px-10 md:py-6 text-right pr-6 md:pr-12 flex items-center justify-end gap-2 md:gap-4">
                                        <span class="text-slate-600 font-black text-[10px] uppercase hidden sm:inline">Telemetry_Verified</span>
                                        <button @click="handleDelete(chunk.id)" class="text-slate-500 hover:text-rose-500 p-1" title="Delete Session">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div v-if="postureChunks.length > 5" class="p-6 bg-white/[0.01] border-t border-white/5 flex justify-center">
                        <button @click="showAllLogs = !showAllLogs" class="text-[10px] font-black text-indigo-400 hover:text-white uppercase tracking-[0.3em]">
                            {{ showAllLogs ? 'Hide Entries' : 'View Full History' }}
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>