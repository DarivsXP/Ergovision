<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ name: 'FeedbackHistory' });

const props = defineProps({
    feedback: {
        type: Array,
        default: () => [],
    },
});

const formatPH = (dateString, type = 'full') => {
    if (!dateString) return '...';
    const date = new Date(dateString);
    const options =
        type === 'time'
            ? { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: 'Asia/Manila' }
            : type === 'short'
              ? { month: 'short', day: 'numeric', timeZone: 'Asia/Manila' }
              : { dateStyle: 'medium', timeZone: 'Asia/Manila' };
    return date.toLocaleString('en-PH', options);
};

const rows = computed(() => [...props.feedback]);
</script>

<template>
    <Head title="Feedback History" />

    <AuthenticatedLayout>
        <div class="py-12 bg-[#020617] min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 px-4 space-y-8">
                <div class="bg-slate-900 rounded-[3rem] p-8 md:p-10 border border-white/5 shadow-2xl">
                    <div class="flex items-start justify-between gap-6 flex-col sm:flex-row">
                        <div>
                            <div class="flex items-center gap-3">
                                <div class="w-1.5 h-10 bg-indigo-500 rounded-full shadow-[0_0_20px_rgba(99,102,241,0.6)]"></div>
                                <div>
                                    <h1 class="text-sm font-black text-white uppercase tracking-widest">Feedback History</h1>
                                    <p class="text-slate-500 text-[10px] font-bold uppercase tracking-[0.2em]">
                                        Your submitted session feedback
                                    </p>
                                </div>
                            </div>
                        </div>

                        <Link
                            :href="route('dashboard')"
                            class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-[10px] font-black uppercase tracking-widest text-slate-200 transition-all"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Back to Dashboard
                        </Link>
                    </div>
                </div>

                <div class="bg-slate-900 rounded-[3rem] overflow-hidden border border-white/5 shadow-2xl">
                    <div class="px-6 md:px-10 py-8 border-b border-white/5 flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h2 class="text-sm font-black text-white uppercase tracking-widest">Sessions</h2>
                        <span class="ml-auto text-[10px] font-black uppercase tracking-[0.3em] text-slate-500 tabular-nums">
                            {{ rows.length }} total
                        </span>
                    </div>

                    <div v-if="rows.length === 0" class="p-10 text-center">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-400">No feedback submitted yet</p>
                        <p class="text-[11px] text-slate-600 mt-2">Complete a camera session and submit feedback to see it here.</p>
                    </div>

                    <div v-else class="w-full overflow-x-auto">
                        <table class="w-full text-left min-w-[900px]">
                            <thead class="bg-white/[0.03] text-slate-500 text-[10px] font-black uppercase tracking-[0.3em] border-b border-white/5">
                                <tr>
                                    <th class="px-4 py-4 md:px-10 md:py-6 text-indigo-400">Timestamp</th>
                                    <th class="px-4 py-4 md:px-10 md:py-6 text-center">Fatigue</th>
                                    <th class="px-4 py-4 md:px-10 md:py-6 text-center">Accuracy</th>
                                    <th class="px-4 py-4 md:px-10 md:py-6">Comments</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="item in rows" :key="item.id" class="hover:bg-white/[0.01] transition-colors">
                                    <td class="px-4 py-4 md:px-10 md:py-6 font-mono text-xs text-slate-400">
                                        <span class="text-[10px] text-indigo-400/50 mr-2">{{ formatPH(item.created_at, 'short') }}</span>
                                        {{ formatPH(item.created_at, 'time') }}
                                    </td>
                                    <td class="px-4 py-4 md:px-10 md:py-6 text-center">
                                        <span class="px-4 py-1.5 rounded-xl text-[10px] font-black border bg-white/5 text-slate-200 border-white/10 tabular-nums">
                                            {{ item.fatigue_level }} / 5
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 md:px-10 md:py-6 text-center">
                                        <span class="px-4 py-1.5 rounded-xl text-[10px] font-black border bg-white/5 text-slate-200 border-white/10 tabular-nums">
                                            {{ item.accuracy_rating }} / 5
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 md:px-10 md:py-6 text-[11px] text-slate-300 leading-relaxed">
                                        <span v-if="item.comments && item.comments.trim().length > 0">{{ item.comments }}</span>
                                        <span v-else class="text-slate-600">—</span>
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

