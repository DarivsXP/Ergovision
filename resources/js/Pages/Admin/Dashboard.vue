<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PostureChart from '@/Components/PostureChart.vue';
import { Head, Link } from '@inertiajs/vue3'; 

const props = defineProps({
    stats: Object,
    recent_users: Array,
    chart_data: Array
});
</script>

<template>
    <Head title="Admin Console" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#020617] py-12"> 
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <div class="flex flex-col md:flex-row justify-between items-center gap-6 px-6 py-8 bg-white/[0.02] border border-white/5 rounded-[3rem] backdrop-blur-md shadow-2xl">
                    <div class="text-center md:text-left">
                        <h2 class="font-black text-3xl text-white tracking-tighter uppercase">
                            Admin <span class="text-indigo-500">Console</span>
                        </h2>
                        <div class="flex items-center justify-center md:justify-start gap-2 mt-1">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <p class="text-slate-400 text-[10px] font-black uppercase tracking-[0.3em]">Secure Access Verified</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 flex-wrap justify-center">
                        <a :href="route('admin.export.telemetry')"
                           class="flex items-center gap-2 px-6 py-4 bg-indigo-600 hover:bg-indigo-500 rounded-2xl text-xs font-black text-white uppercase tracking-widest transition-all shadow-[0_0_20px_rgba(79,70,229,0.4)] group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:-translate-y-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Export Telemetry Data
                        </a>
                        <a :href="route('admin.export.feedback')"
                           class="flex items-center gap-2 px-6 py-4 bg-slate-800 hover:bg-slate-700 rounded-2xl text-xs font-black text-white uppercase tracking-widest transition-all border border-white/10 group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-400 group-hover:-translate-y-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h6m-6 8l-4-4V4a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H7z" />
                            </svg>
                            Export Feedback Data
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-slate-900 border border-slate-800 p-8 rounded-[2.5rem] shadow-xl flex flex-col justify-center">
                        <p class="text-slate-500 text-[10px] font-black uppercase tracking-[0.2em] mb-2">User Base</p>
                        <h3 class="text-5xl font-black text-white tracking-tighter">{{ stats.total_users }}</h3>
                    </div>

                    <div class="bg-slate-900 border border-indigo-500/30 p-8 rounded-[2.5rem] shadow-[0_0_30px_rgba(79,70,229,0.1)] flex flex-col justify-center">
                        <p class="text-indigo-400 text-[10px] font-black uppercase tracking-[0.2em] mb-2">Sessions (Last 24h)</p>
                        <h3 class="text-5xl font-black text-white tracking-tighter">{{ stats.active_sessions }}</h3>
                    </div>

                    <div class="bg-slate-900 border border-emerald-500/30 p-8 rounded-[2.5rem] shadow-[0_0_30px_rgba(16,185,129,0.1)] flex flex-col justify-center">
                        <p class="text-emerald-400 text-[10px] font-black uppercase tracking-[0.2em] mb-2">Global Average Score</p>
                        <h3 class="text-5xl font-black text-white tracking-tighter">{{ stats.avg_score }}<span class="text-2xl text-slate-500 ml-1">%</span></h3>
                    </div>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-[3rem] overflow-hidden shadow-2xl">
                    <div class="px-8 py-6 border-b border-slate-800 flex justify-between items-center bg-white/[0.01]">
                        <h4 class="text-white text-sm font-black uppercase tracking-widest">Recent Users</h4>
                        <Link :href="route('admin.users.index')" class="text-[10px] text-indigo-400 hover:text-white transition font-black uppercase tracking-[0.2em] flex items-center gap-1">
                            View Full Roster &rarr;
                        </Link>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left min-w-[600px]">
                            <thead class="bg-slate-950/80">
                                <tr class="text-slate-500 text-[10px] uppercase font-black tracking-[0.3em] border-b border-slate-800">
                                    <th class="px-8 py-6">Name</th>
                                    <th class="px-8 py-6">Email</th>
                                    <th class="px-8 py-6">Registered</th>
                                    <th class="px-8 py-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/50">
                                <tr v-for="user in recent_users" :key="user.id" class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-8 py-5 text-white font-bold text-sm">{{ user.name }}</td>
                                    <td class="px-8 py-5 text-slate-400 font-mono text-xs">{{ user.email }}</td>
                                    <td class="px-8 py-5 text-slate-500 text-xs font-medium">{{ new Date(user.created_at).toLocaleDateString() }}</td>
                                    <td class="px-8 py-5 text-right">
                                        <Link :href="route('admin.users.show', user.id)" class="inline-flex items-center px-4 py-2 bg-slate-800 hover:bg-indigo-600 text-indigo-400 hover:text-white rounded-xl font-black text-[10px] uppercase tracking-widest transition-all">
                                            Inspect
                                        </Link>
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