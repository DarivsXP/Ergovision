<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3'; 
import PostureChart from '@/Components/PostureChart.vue';

const props = defineProps({
    summaryStats: Object,    // total_time, corrections, avg_score
    history: Array,         // Session history log
    postureChunks: Array,   // Data for the Growth Curve chart
    filters: Object
});

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
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <h2 class="font-black text-2xl text-white tracking-tighter uppercase">
                    My <span class="text-indigo-500">Progress</span>
                </h2>
                
                <div class="flex bg-slate-900 border border-white/10 p-1 rounded-xl shadow-inner">
                    <button v-for="time in ['3d', '7d', '30d']" :key="time"
                        @click="updateFilter(time)"
                        class="px-5 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all"
                        :class="activeFilter === time ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-500 hover:text-slate-300'"
                    >
                        {{ time === '30d' ? '1 Month' : time }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-10">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-slate-900 border border-white/5 p-10 rounded-[2.5rem] text-center shadow-xl">
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">Active Time</p>
                        <h3 class="text-6xl font-black text-white mt-4 tracking-tighter">
                            {{ summaryStats?.total_time || 0 }}<span class="text-xl text-indigo-500 ml-1">h</span>
                        </h3>
                    </div>

                    <div class="bg-slate-900 border border-white/5 p-10 rounded-[2.5rem] text-center shadow-xl">
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">Slouch Alerts</p>
                        <h3 class="text-6xl font-black text-white mt-4 tracking-tighter">
                            {{ summaryStats?.corrections || 0 }}
                        </h3>
                    </div>

                    <div class="bg-slate-900 border border-white/5 p-10 rounded-[2.5rem] text-center shadow-xl">
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">Overall Grade</p>
                        <div class="flex justify-center items-baseline gap-1 mt-4">
                            <h3 class="text-6xl font-black text-white tracking-tighter">{{ summaryStats?.avg_score || 0 }}</h3>
                            <span class="text-emerald-500 font-black text-2xl">%</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <div class="bg-slate-900/50 border border-white/5 p-8 rounded-[2rem]">
                        <h4 class="text-white font-bold text-sm uppercase tracking-widest mb-6">Growth Curve</h4>
                        <div class="h-[250px]">
                            <PostureChart :data="postureChunks" color="#6366f1" />
                        </div>
                    </div>

                    <div class="bg-slate-900/50 border border-white/5 rounded-[2rem] overflow-hidden">
                        <div class="p-6 border-b border-white/5">
                            <h4 class="text-white font-bold text-sm uppercase tracking-widest">Recent Sessions</h4>
                        </div>
                        <div class="max-h-[250px] overflow-y-auto">
                            <table class="w-full text-left">
                                <tbody class="divide-y divide-white/5">
                                    <tr v-for="session in history" :key="session.id" class="text-xs">
                                        <td class="px-6 py-4 text-slate-300 font-medium">
                                            {{ new Date(session.created_at).toLocaleDateString() }}
                                        </td>
                                        <td class="px-6 py-4 text-slate-500">
                                            {{ session.duration_formatted }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span :class="session.score > 80 ? 'text-emerald-500' : 'text-rose-500'" class="font-black">
                                                {{ session.score }}%
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="!history || history.length === 0">
                                        <td colspan="3" class="px-6 py-10 text-center text-slate-600 font-bold uppercase tracking-widest">
                                            No Data Found
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>