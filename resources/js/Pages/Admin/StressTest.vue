<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    users: Array,
    lastResult: Object,
    httpPoolSize: { type: Number, default: 20 },
});

const form = useForm({
    user_id: props.users?.[0]?.id ?? '',
    count: 500,
    mode: 'direct',
});

function submit() {
    form.post(route('admin.stress-test.store'), { preserveScroll: true });
}
</script>

<template>
    <Head title="Stress Test" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#020617] py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <div class="px-6 py-8 bg-white/[0.02] border border-white/5 rounded-[3rem] backdrop-blur-md shadow-2xl">
                    <h2 class="font-black text-3xl text-white tracking-tighter uppercase">
                        Load <span class="text-amber-500">Stress Test</span>
                    </h2>
                    <p class="mt-3 text-slate-400 text-sm leading-relaxed max-w-2xl">
                        Generate synthetic posture telemetry for benchmarking. <strong class="text-slate-300">Direct</strong>
                        bulk-inserts rows (database write throughput). <strong class="text-slate-300">HTTP API</strong>
                        exercises Sanctum auth, validation, and the same <code class="text-indigo-400">POST /api/posture-chunks</code>
                        path used by clients. Use a dedicated test account and remove test data afterward if needed.
                    </p>
                </div>

                <div v-if="!users?.length" class="bg-amber-950/40 border border-amber-500/30 rounded-[2.5rem] p-8 text-amber-100/90 text-sm">
                    No users found. Create an account first, then return here to attach test telemetry to a user.
                </div>

                <div v-else class="bg-slate-900 border border-slate-800 rounded-[2.5rem] p-8 shadow-xl space-y-6">
                    <h3 class="text-white text-xs font-black uppercase tracking-[0.25em]">Run from browser</h3>

                    <form class="space-y-6" @submit.prevent="submit">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Target user</label>
                            <select
                                v-model="form.user_id"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                required
                            >
                                <option v-for="u in users" :key="u.id" :value="u.id">
                                    {{ u.name }} — {{ u.email }}
                                </option>
                            </select>
                            <p v-if="form.errors.user_id" class="mt-2 text-xs text-red-400">{{ form.errors.user_id }}</p>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Number of chunks</label>
                            <input
                                v-model.number="form.count"
                                type="number"
                                min="1"
                                max="10000"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-indigo-500"
                                required
                            />
                            <p class="mt-1 text-[10px] text-slate-500">Direct: up to 10,000. HTTP API: max 2,000 per run.</p>
                            <p v-if="form.errors.count" class="mt-2 text-xs text-red-400">{{ form.errors.count }}</p>
                        </div>

                        <div>
                            <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-3">Mode</span>
                            <div class="flex flex-col sm:flex-row gap-4">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input v-model="form.mode" type="radio" value="direct" class="text-indigo-600 focus:ring-indigo-500" />
                                    <span class="text-sm text-slate-300">Direct (bulk DB insert)</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input v-model="form.mode" type="radio" value="http_api" class="text-indigo-600 focus:ring-indigo-500" />
                                    <span class="text-sm text-slate-300">HTTP API (Sanctum + Laravel stack)</span>
                                </label>
                            </div>
                            <p v-if="form.errors.mode" class="mt-2 text-xs text-red-400">{{ form.errors.mode }}</p>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full sm:w-auto px-8 py-4 bg-amber-600 hover:bg-amber-500 disabled:opacity-50 rounded-2xl text-xs font-black text-white uppercase tracking-widest transition-all shadow-[0_0_24px_rgba(245,158,11,0.35)]"
                        >
                            {{ form.processing ? 'Running…' : 'Run stress test' }}
                        </button>
                    </form>
                </div>

                <div v-if="lastResult" class="bg-slate-900 border border-emerald-500/30 rounded-[2.5rem] p-8 shadow-[0_0_30px_rgba(16,185,129,0.08)]">
                    <h3 class="text-emerald-400 text-xs font-black uppercase tracking-[0.25em] mb-4">Last result</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-slate-500 text-[10px] uppercase font-black tracking-wider">Mode</dt><dd class="text-white font-mono mt-1">{{ lastResult.mode }}</dd></div>
                        <div><dt class="text-slate-500 text-[10px] uppercase font-black tracking-wider">Target</dt><dd class="text-white font-mono mt-1">{{ lastResult.target_email }} (ID {{ lastResult.target_user_id }})</dd></div>
                        <div><dt class="text-slate-500 text-[10px] uppercase font-black tracking-wider">Duration</dt><dd class="text-white font-mono mt-1">{{ lastResult.duration_ms }} ms</dd></div>
                        <div><dt class="text-slate-500 text-[10px] uppercase font-black tracking-wider">Throughput</dt><dd class="text-white font-mono mt-1">{{ lastResult.throughput_per_s }} /s</dd></div>
                        <template v-if="lastResult.mode === 'http_api'">
                            <div><dt class="text-slate-500 text-[10px] uppercase font-black tracking-wider">HTTP OK</dt><dd class="text-white font-mono mt-1">{{ lastResult.success }}</dd></div>
                            <div><dt class="text-slate-500 text-[10px] uppercase font-black tracking-wider">HTTP failed</dt><dd class="text-white font-mono mt-1">{{ lastResult.failed }}</dd></div>
                        </template>
                    </dl>
                </div>

                <div class="bg-slate-900/80 border border-slate-800 rounded-[2rem] p-8">
                    <h3 class="text-white text-xs font-black uppercase tracking-[0.25em] mb-3">Reproducible CLI (paper / appendix)</h3>
                    <pre class="text-[11px] leading-relaxed text-slate-300 font-mono bg-slate-950 border border-slate-800 rounded-xl p-4 overflow-x-auto">php artisan stress:posture --user=1 --count=5000 --mode=direct
php artisan stress:posture --user=1 --count=500 --mode=http_api</pre>
                    <p class="mt-3 text-[10px] text-slate-500 leading-relaxed">
                        Report in your paper: machine specs, PHP and Laravel versions, database driver, <code class="text-slate-400">APP_URL</code>,
                        and concurrent pool size ({{ httpPoolSize }} for HTTP mode — see <code class="text-slate-400">PostureStressTestService::HTTP_POOL_SIZE</code>).
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
