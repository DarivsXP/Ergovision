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
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-black text-2xl text-white tracking-tighter uppercase">
                    Admin <span class="text-indigo-500">Console</span>
                </h2>
                <div class="px-3 py-1 bg-indigo-500/20 border border-indigo-500/50 rounded-full">
                    <span class="text-indigo-400 text-xs font-mono font-bold">Secure Access Verified</span>
                </div>
            </div>
        </template>

        <div class="min-h-screen bg-[#020617] -mt-6 py-12"> 
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                    <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl shadow-[0_0_20px_rgba(0,0,0,0.3)]">
                        <p class="text-slate-500 text-[10px] font-black uppercase tracking-[0.2em]">User Base</p>
                        <h3 class="text-4xl font-black text-white mt-2">{{ stats.total_users }}</h3>
                    </div>

                    <div class="bg-slate-900 border border-indigo-500/30 p-6 rounded-3xl shadow-[0_0_30px_rgba(79,70,229,0.1)]">
                        <p class="text-indigo-400 text-[10px] font-black uppercase tracking-[0.2em]">Active Pulse (24h)</p>
                        <h3 class="text-4xl font-black text-white mt-2">{{ stats.active_sessions }}</h3>
                    </div>

                    <div class="bg-slate-900 border border-emerald-500/30 p-6 rounded-3xl shadow-[0_0_30px_rgba(16,185,129,0.1)]">
                        <p class="text-emerald-400 text-[10px] font-black uppercase tracking-[0.2em]">Platform Health</p>
                        <h3 class="text-4xl font-black text-white mt-2">{{ stats.avg_score }}<span class="text-lg">%</span></h3>
                    </div>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-[2rem] overflow-hidden shadow-2xl">
                    <div class="p-6 border-b border-slate-800 flex justify-between items-center">
                        <h4 class="text-white font-bold tracking-tight">Recent Deployments (Users)</h4>
                        <button class="text-xs text-slate-500 hover:text-white transition">View All</button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-950/50">
                                <tr class="text-slate-500 text-[10px] uppercase font-bold tracking-widest">
                                    <th class="p-5">Operator</th>
                                    <th class="p-5">Identification</th>
                                    <th class="p-5">Joined</th>
                                    <th class="p-5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/50">
                                <tr v-for="user in recent_users" :key="user.id" class="hover:bg-white/[0.02] transition">
                                    <td class="p-5 text-white font-medium">{{ user.name }}</td>
                                    <td class="p-5 text-slate-400 font-mono text-xs">{{ user.email }}</td>
                                    <td class="p-5 text-slate-500 text-xs">{{ new Date(user.created_at).toLocaleDateString() }}</td>
                                    <td class="p-5 text-right">
                                        <Link :href="route('admin.users.show', user.id)" class="text-indigo-400 hover:text-white font-bold text-xs uppercase tracking-tighter">
                                            Inspect _
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