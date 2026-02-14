<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
    sessions: Object, // This is the paginated object
    stats: Object
});
</script>

<template>
    <Head :title="'Inspect: ' + user.name" />

    <AuthenticatedLayout>
        <div class="py-12 bg-slate-900 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <Link :href="route('admin.dashboard')" class="text-indigo-400 hover:text-indigo-300 mb-6 inline-flex items-center gap-2">
                    <span>←</span> Back to Command Center
                </Link>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="bg-slate-800 border border-white/10 p-6 rounded-3xl shadow-2xl h-fit">
                        <div class="h-20 w-20 rounded-2xl bg-indigo-600 flex items-center justify-center text-3xl font-black text-white mb-4">
                            {{ user.name.charAt(0) }}
                        </div>
                        <h3 class="text-2xl font-bold text-white">{{ user.name }}</h3>
                        <p class="text-slate-400 font-mono text-sm mb-6">{{ user.email }}</p>
                        
                        <div class="space-y-4">
                            <div class="bg-slate-900/50 p-4 rounded-xl border border-white/5">
                                <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold">Avg Score</p>
                                <p class="text-2xl font-black text-emerald-400">{{ stats.avg_score }}%</p>
                            </div>
                            <div class="bg-slate-900/50 p-4 rounded-xl border border-white/5">
                                <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold">Total Slouch Time</p>
                                <p class="text-2xl font-black text-rose-400">{{ Math.round(stats.total_slouch_time / 60) }} mins</p>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2 bg-slate-800 border border-white/10 rounded-3xl overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-slate-900/50 border-b border-white/10 text-slate-400 text-[10px] uppercase font-bold tracking-widest">
                                <tr>
                                    <th class="p-4">Date</th>
                                    <th class="p-4">Score</th>
                                    <th class="p-4">Slouch Duration</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="session in sessions.data" :key="session.id" class="hover:bg-white/5 transition">
                                    <td class="p-4 text-white text-sm">
                                        {{ new Date(session.created_at).toLocaleString() }}
                                    </td>
                                    <td class="p-4 font-mono">
                                        <span :class="session.score > 80 ? 'text-emerald-400' : 'text-amber-400'">
                                            {{ session.score }}%
                                        </span>
                                    </td>
                                    <td class="p-4 text-slate-400 text-sm">{{ session.slouch_duration }}s</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>