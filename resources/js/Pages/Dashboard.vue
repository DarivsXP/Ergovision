<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3'; 
import PostureChart from '@/Components/PostureChart.vue';

const props = defineProps({
    // Updated to match your backend data structure
    summaryStats: Object,    // total_time, corrections, avg_score
    history: Array,         // Session history log
    postureChunks: Array,   // Data for the Growth Curve chart
    filters: Object
});

// Period management
const activeFilter = ref(props.filters?.period || '7d');

const updateFilter = (period) => {
    activeFilter.value = period;
    router.get(route('dashboard'), { period }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};
</script>

<template>
    <Head title="My Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="font-black text-2xl text-white tracking-tighter uppercase">
                        Personal <span class="text-indigo-500">Analytics</span>
                    </h2>
                    <p class="text-slate-500 text-[10px] font-bold tracking-widest uppercase mt-1">
                        Individual Ergonomic Performance Tracking
                    </p>
                </div>

                <div class="flex bg-slate-900/50 p-1 rounded-xl border border-white/5 backdrop-blur-sm">
                    <button 
                        @click="updateFilter('3d')"
                        class="px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all"
                        :class="activeFilter === '3d' ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-500 hover:text-slate-300'"
                    >
                        3D
                    </button>
                    <button 
                        @click="updateFilter('7d')"
                        class="px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all"
                        :class="activeFilter === '7d' ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-500 hover:text-white'"
                    >
                        7D
                    </button>
                    <button 
                        @click="updateFilter('30d')"
                        class="px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all"
                        :class="activeFilter === '30d' ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-500 hover:text-white'"
                    >
                        1M
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12 bg-[#020617]"> 
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-slate-900/40 border border-white/5 p-8 rounded-[2rem] hover:border-indigo-500/30 transition-all duration-500">
                        <p class="text-slate-500 text-[10px] font-black uppercase tracking-[0.2em]">Monitoring Time</p>
                        <h3 class="text-5xl font-black text-white mt-4 tracking-tighter">
                            {{ summaryStats?.total_time || 0 }}<span class="text-lg text-indigo-500 ml-1">hrs</span>
                        </h3>
                        <p class="text-indigo-400/50 text-[10px] font-bold mt-2 uppercase">Total protection active</p>
                    </div>

                    <div class="bg-slate-900/40 border border-white/5 p-8 rounded-[2rem] hover:border-rose-500/30 transition-all duration-500">
                        <p class="text-slate-500 text-[10px] font-black uppercase tracking-[0.2em]">Slouch Alerts</p>
                        <h3 class="text-5xl font-black text-white mt-4 tracking-tighter">{{ summaryStats?.corrections || 0 }}</h3>
                        <p class="text-rose-400/50 text-[10px] font-bold mt-2 uppercase">Corrections issued this period</p>
                    </div>

                    <div class="bg-slate-900/40 border border-white/5 p-8 rounded-[2rem] hover:border-emerald-500/30 transition-all duration-500">
                        <p class="text-slate-500 text-[10px] font-black uppercase tracking-[0.2em]">Posture Grade</p>
                        <div class="flex items-baseline gap-2 mt-4">
                            <h3 class="text-5xl font-black text-white tracking-tighter">{{ summaryStats?.avg_score || 0 }}</h3>
                            <span class="text-emerald-500 font-black text-xl">%</span>
                        </div>
                        <p class="text-emerald-400/50 text-[10px] font-bold mt-2 uppercase">Alignment Accuracy</p>
                    </div>
                </div>

                <div class="bg-slate-900/40 border border-white/5 p-8 rounded-[2.5rem]">
                    <div class="mb-8">
                        <h4 class="text-white font-black text-sm uppercase tracking-widest">Growth Curve</h4>
                        <p class="text-slate-500 text-[10px] uppercase font-bold tracking-tighter">Your postural stability over the last {{ activeFilter }}</p>
                    </div>
                    <div class="h-[350px]">
                        <PostureChart :data="postureChunks" color="#6366f1" />
                    </div>
                </div>

                <div class="bg-slate-900/40 border border-white/5 rounded-[2.5rem] overflow-hidden">
                    <div class="p-8 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
                        <div>
                            <h4 class="text-white font-black text-sm uppercase tracking-widest">Session Log</h4>
                            <p class="text-slate-500 text-[10px] font-bold uppercase mt-1">Detailed history of recent monitoring</p>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-slate-500 text-[9px] uppercase font-black tracking-[0.2em] bg-black/20">
                                    <th class="px-8 py-5">Timestamp</th>
                                    <th class="px-8 py-5">Duration</th>
                                    <th class="px-8 py-5 text-right">Score</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="session in history" :key="session.id" class="hover:bg-indigo-500/[0.03] transition-colors">
                                    <td class="px-8 py-6 text-slate-200 font-bold text-sm">
                                        {{ new Date(session.created_at).toLocaleString() }}
                                    </td>
                                    <td class="px-8 py-6 text-slate-500 font-mono text-xs">
                                        {{ session.duration_formatted }}
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <span class="px-3 py-1 rounded-full text-[10px] font-black border"
                                            :class="session.score > 80 ? 'border-emerald-500/30 text-emerald-500 bg-emerald-500/10' : 'border-rose-500/30 text-rose-500 bg-rose-500/10'">
                                            {{ session.score }}%
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!history || history.length === 0">
                                    <td colspan="3" class="px-8 py-20 text-center text-slate-600 uppercase text-[10px] font-black tracking-widest">
                                        No session data available for this window
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>