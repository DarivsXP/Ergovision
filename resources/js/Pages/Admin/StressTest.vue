<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    users: Array,
    enabled: { type: Boolean, default: true },
    limits: Object,
    paths: Array,
    httpPoolSize: { type: Number, default: 20 },
});

const telemetry = reactive({
    user_id: props.users?.[0]?.id ?? '',
    count: 500,
    mode: 'direct',
});

const site = reactive({
    total_requests: 200,
    concurrency: 10,
});

const telemetryRunning = ref(false);
const telemetryError = ref(null);
const telemetryProgress = ref({ batch: 0, total: 0 });
const telemetryAggregate = ref(null);

const siteRunning = ref(false);
const siteError = ref(null);
const siteResult = ref(null);

function downloadText(filename, content, mime = 'text/plain') {
    const blob = new Blob([content], { type: mime });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
}

function toCsvRow(values) {
    return values
        .map((v) => {
            const s = String(v ?? '');
            if (s.includes('"') || s.includes(',') || s.includes('\n')) {
                return `"${s.replace(/"/g, '""')}"`;
            }
            return s;
        })
        .join(',');
}

function exportTelemetryJson() {
    if (!telemetryAggregate.value) return;
    downloadText(
        `ergovision_stress_telemetry_${telemetryAggregate.value.mode}_${Date.now()}.json`,
        JSON.stringify(telemetryAggregate.value, null, 2),
        'application/json',
    );
}

function exportTelemetryCsv() {
    if (!telemetryAggregate.value) return;
    const t = telemetryAggregate.value;
    const lines = [
        toCsvRow(['metric', 'value']),
        toCsvRow(['mode', t.mode]),
        toCsvRow(['target_user_id', t.target_user_id]),
        toCsvRow(['target_email', t.target_email]),
        toCsvRow(['batches', t.batches]),
        toCsvRow(['total_chunks', t.total_chunks]),
        toCsvRow(['total_http_ok', t.total_http_ok]),
        toCsvRow(['total_http_failed', t.total_http_failed]),
        toCsvRow(['wall_ms_total', t.wall_ms_total]),
        toCsvRow(['server_time_ms_sum', t.server_time_ms_sum]),
        toCsvRow(['effective_throughput_per_s', t.effective_throughput_per_s]),
        toCsvRow(['estimated_concurrent_active_users', t.estimated_concurrent_active_users]),
        toCsvRow(['assumption_seconds_between_posts', t.assumption_seconds_between_posts]),
        toCsvRow(['capacity_note', t.capacity_note]),
    ];
    downloadText(`ergovision_stress_telemetry_${t.mode}_${Date.now()}.csv`, lines.join('\n'), 'text/csv');
}

function exportSiteJson() {
    if (!siteResult.value) return;
    downloadText(`ergovision_stress_site_${Date.now()}.json`, JSON.stringify(siteResult.value, null, 2), 'application/json');
}

function exportSiteCsv() {
    if (!siteResult.value) return;
    const s = siteResult.value;
    const lines = [
        toCsvRow(['metric', 'value']),
        toCsvRow(['wall_ms', s.wall_ms]),
        toCsvRow(['total_requests', s.total_requests]),
        toCsvRow(['success', s.success]),
        toCsvRow(['failed', s.failed]),
        toCsvRow(['success_rate_pct', s.success_rate_pct]),
        toCsvRow(['successful_req_per_s', s.successful_req_per_s]),
        toCsvRow(['latency_ms_avg', s.latency_ms_avg]),
        toCsvRow(['latency_ms_p95', s.latency_ms_p95]),
        toCsvRow(['concurrency', s.concurrency]),
        toCsvRow(['estimated_concurrent_visitors', s.estimated_concurrent_visitors]),
        toCsvRow(['assumption_pages_per_user_per_minute', s.assumption_pages_per_user_per_minute]),
        toCsvRow(['status_counts', JSON.stringify(s.status_counts ?? {})]),
        toCsvRow(['paths', (s.paths ?? []).join(' ')]),
    ];
    downloadText(`ergovision_stress_site_${Date.now()}.csv`, lines.join('\n'), 'text/csv');
}

const batchSize = computed(() =>
    telemetry.mode === 'direct'
        ? props.limits?.telemetry_direct_batch ?? 400
        : props.limits?.telemetry_http_batch ?? 40,
);

function estimateTelemetryUsers(okPerSecond) {
    const interval = 30;
    if (okPerSecond <= 0) return 0;
    return Math.floor(okPerSecond * interval);
}

async function runTelemetryBatched() {
    telemetryError.value = null;
    telemetryAggregate.value = null;
    telemetryRunning.value = true;

    const total = Math.max(1, Math.min(50000, Number(telemetry.count) || 0));
    const size = batchSize.value;
    const batches = [];
    let left = total;
    while (left > 0) {
        const n = Math.min(size, left);
        batches.push(n);
        left -= n;
    }

    telemetryProgress.value = { batch: 0, total: batches.length };

    let countSum = 0;
    let successSum = 0;
    let failedSum = 0;
    let serverDurationSum = 0;

    const wallT0 = performance.now();

    try {
        if (!props.enabled) {
            telemetryError.value = 'Stress testing is disabled on this environment.';
            return;
        }
        for (let i = 0; i < batches.length; i++) {
            telemetryProgress.value = { batch: i + 1, total: batches.length };
            const { data } = await axios.post(
                route('admin.stress-test.telemetry'),
                {
                    user_id: telemetry.user_id,
                    mode: telemetry.mode,
                    count: batches[i],
                },
                { headers: { Accept: 'application/json' } },
            );

            const b = data.batch;
            serverDurationSum += b.duration_ms;
            countSum += b.count;
            if (telemetry.mode === 'http_api') {
                successSum += b.success;
                failedSum += b.failed;
            }

            // Brief breather to reduce sustained load / proxy pressure.
            // (Especially helpful on small VPS deployments.)
            await new Promise((r) => setTimeout(r, 75));
        }

        const wallMs = performance.now() - wallT0;
        const wallSec = wallMs / 1000;

        const effectiveThroughput =
            telemetry.mode === 'http_api'
                ? wallSec > 0
                    ? successSum / wallSec
                    : 0
                : wallSec > 0
                  ? countSum / wallSec
                  : 0;

        telemetryAggregate.value = {
            mode: telemetry.mode,
            target_email: props.users?.find((u) => u.id === telemetry.user_id)?.email,
            target_user_id: telemetry.user_id,
            batches: batches.length,
            total_chunks: countSum,
            total_http_ok: telemetry.mode === 'http_api' ? successSum : null,
            total_http_failed: telemetry.mode === 'http_api' ? failedSum : null,
            wall_ms_total: Math.round(wallMs * 100) / 100,
            server_time_ms_sum: Math.round(serverDurationSum * 100) / 100,
            effective_throughput_per_s: Math.round(effectiveThroughput * 100) / 100,
            estimated_concurrent_active_users:
                telemetry.mode === 'http_api' ? estimateTelemetryUsers(effectiveThroughput) : null,
            assumption_seconds_between_posts: 30,
            capacity_note:
                telemetry.mode === 'direct'
                    ? 'Direct mode: bulk DB inserts; throughput is not the same as concurrent browser/API users.'
                    : null,
        };
    } catch (e) {
        telemetryError.value =
            e.response?.data?.message || e.response?.data?.errors?.count?.[0] || e.message || 'Request failed';
    } finally {
        telemetryRunning.value = false;
        telemetryProgress.value = { batch: 0, total: 0 };
    }
}

async function runSiteVisits() {
    siteError.value = null;
    siteResult.value = null;
    siteRunning.value = true;

    try {
        if (!props.enabled) {
            siteError.value = 'Stress testing is disabled on this environment.';
            return;
        }
        const { data } = await axios.post(
            route('admin.stress-test.site-visits'),
            {
                total_requests: site.total_requests,
                concurrency: site.concurrency,
            },
            { headers: { Accept: 'application/json' } },
        );
        siteResult.value = data.site;
    } catch (e) {
        siteError.value =
            e.response?.data?.message || e.response?.data?.errors?.total_requests?.[0] || e.message || 'Request failed';
    } finally {
        siteRunning.value = false;
    }
}
</script>

<template>
    <Head title="Stress Test" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#020617] py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <div class="px-6 py-8 bg-white/[0.02] border border-white/5 rounded-[3rem] backdrop-blur-md shadow-2xl">
                    <h2 class="font-black text-3xl text-white tracking-tighter uppercase">
                        Research <span class="text-amber-500">Load Suite</span>
                    </h2>
                    <p class="mt-3 text-slate-400 text-sm leading-relaxed max-w-3xl">
                        Two workloads: <strong class="text-slate-300">public page visits</strong> (anonymous GETs to home, login, and legal pages)
                        and <strong class="text-slate-300">posture telemetry</strong> (DB bulk insert vs. real <code class="text-indigo-400">POST /api/posture-chunks</code>).
                        Runs are <strong class="text-slate-300">split into small server batches</strong> so reverse proxies (504 Gateway Timeout) stay happy.
                        Concurrent-user figures are <strong class="text-slate-300">heuristic</strong> — document the stated assumptions in your paper.
                    </p>
                </div>

                <div v-if="!enabled" class="bg-amber-950/40 border border-amber-500/30 rounded-[2rem] p-6 text-amber-100/90 text-sm leading-relaxed">
                    This stress-testing suite is <strong>disabled</strong> on this environment. To enable on production, set
                    <code class="text-amber-200">STRESS_TEST_ENABLED=true</code> (and consider using a staging environment).
                </div>

                <div class="bg-slate-900/80 border border-slate-800 rounded-[2rem] p-6 text-[11px] text-slate-400 leading-relaxed space-y-3">
                    <p class="font-black uppercase tracking-widest text-slate-500">Avoiding 504 timeouts</p>
                    <p>
                        Long single requests often hit nginx/cloud <code class="text-slate-300">proxy_read_timeout</code> (often 60s). This UI sends many short requests
                        (max {{ limits?.telemetry_direct_batch }} direct rows or {{ limits?.telemetry_http_batch }} API posts per call). For huge CLI runs use
                        <code class="text-slate-300">php artisan stress:posture</code> / <code class="text-slate-300">php artisan stress:site</code> on the server.
                    </p>
                    <p>
                        If you still see 504 behind nginx, raise timeouts (example):
                    </p>
                    <pre class="text-[10px] text-slate-300 font-mono bg-slate-950 border border-slate-800 rounded-lg p-3 overflow-x-auto">proxy_connect_timeout 120s;
proxy_send_timeout 120s;
proxy_read_timeout 120s;
fastcgi_send_timeout 120s;
fastcgi_read_timeout 120s;</pre>
                    <p>
                        CLI <code class="text-slate-300">stress:site</code> must resolve <code class="text-slate-300">APP_URL</code> from PHP (hosts entry + web server running).
                        The browser UI runs against the same origin and does not need that.
                    </p>
                </div>

                <!-- Site visits -->
                <div class="bg-slate-900 border border-cyan-500/20 rounded-[2.5rem] p-8 shadow-xl space-y-6">
                    <h3 class="text-cyan-400 text-xs font-black uppercase tracking-[0.25em]">1. Public site visits (anonymous pages)</h3>
                    <p class="text-sm text-slate-400">
                        Rotates across: <span class="text-slate-300 font-mono text-xs">{{ paths?.join(', ') }}</span>. Measures success rate, throughput, and latency (avg / p95).
                        <strong class="text-slate-300">Estimated concurrent visitors</strong> assumes ~2 page views per user per minute while browsing.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Total GET requests</label>
                            <input
                                v-model.number="site.total_requests"
                                type="number"
                                min="1"
                                max="5000"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Concurrency (parallel per wave)</label>
                            <input
                                v-model.number="site.concurrency"
                                type="number"
                                min="1"
                                max="50"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white"
                            />
                        </div>
                    </div>

                    <button
                        type="button"
                        :disabled="siteRunning"
                        class="px-8 py-4 bg-cyan-600 hover:bg-cyan-500 disabled:opacity-50 rounded-2xl text-xs font-black text-white uppercase tracking-widest"
                        @click="runSiteVisits"
                    >
                        {{ siteRunning ? 'Running…' : 'Run site visit test' }}
                    </button>
                    <p v-if="siteError" class="text-sm text-red-400">{{ siteError }}</p>
                </div>

                <div v-if="siteResult" class="bg-slate-900 border border-emerald-500/30 rounded-[2.5rem] p-8">
                    <h3 class="text-emerald-400 text-xs font-black uppercase tracking-[0.25em] mb-4">Site visit results</h3>
                    <div class="flex flex-wrap gap-3 mb-6">
                        <button type="button" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-[10px] font-black uppercase tracking-widest text-white" @click="exportSiteJson">
                            Export JSON
                        </button>
                        <button type="button" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-[10px] font-black uppercase tracking-widest text-white" @click="exportSiteCsv">
                            Export CSV
                        </button>
                    </div>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-slate-500 text-[10px] uppercase font-black tracking-wider">Wall time</dt>
                            <dd class="text-white font-mono mt-1">{{ siteResult.wall_ms }} ms</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 text-[10px] uppercase font-black tracking-wider">Success / failed</dt>
                            <dd class="text-white font-mono mt-1">{{ siteResult.success }} / {{ siteResult.failed }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 text-[10px] uppercase font-black tracking-wider">Success rate</dt>
                            <dd class="text-white font-mono mt-1">{{ siteResult.success_rate_pct }}%</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 text-[10px] uppercase font-black tracking-wider">Successful req/s</dt>
                            <dd class="text-white font-mono mt-1">{{ siteResult.successful_req_per_s }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 text-[10px] uppercase font-black tracking-wider">Latency avg / p95</dt>
                            <dd class="text-white font-mono mt-1">{{ siteResult.latency_ms_avg }} / {{ siteResult.latency_ms_p95 }} ms</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 text-[10px] uppercase font-black tracking-wider">Est. concurrent visitors</dt>
                            <dd class="text-white font-mono mt-1">
                                ~{{ siteResult.estimated_concurrent_visitors }}
                                <span class="text-slate-500 text-xs">(assumption: {{ siteResult.assumption_pages_per_user_per_minute }} pages/user/min)</span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Telemetry -->
                <div v-if="!users?.length" class="bg-amber-950/40 border border-amber-500/30 rounded-[2.5rem] p-8 text-amber-100/90 text-sm">
                    No users found. Create an account first for telemetry tests.
                </div>

                <div v-else class="bg-slate-900 border border-amber-500/20 rounded-[2.5rem] p-8 shadow-xl space-y-6">
                    <h3 class="text-amber-400 text-xs font-black uppercase tracking-[0.25em]">2. Posture telemetry</h3>
                    <p class="text-sm text-slate-400">
                        Total workload is split into batches of up to <strong class="text-white">{{ batchSize }}</strong> per request
                        ({{ telemetry.mode === 'direct' ? 'direct' : 'HTTP API' }}). End-to-end wall time includes network + all batches.
                    </p>

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Target user</label>
                        <select
                            v-model="telemetry.user_id"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white"
                        >
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} — {{ u.email }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Total chunks (all batches)</label>
                        <input
                            v-model.number="telemetry.count"
                            type="number"
                            min="1"
                            max="100000"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white"
                        />
                    </div>

                    <div>
                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-3">Mode</span>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input v-model="telemetry.mode" type="radio" value="direct" class="text-amber-600" />
                                <span class="text-sm text-slate-300">Direct (bulk DB insert)</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input v-model="telemetry.mode" type="radio" value="http_api" class="text-amber-600" />
                                <span class="text-sm text-slate-300">HTTP API (Sanctum + Laravel)</span>
                            </label>
                        </div>
                    </div>

                    <button
                        type="button"
                        :disabled="telemetryRunning"
                        class="px-8 py-4 bg-amber-600 hover:bg-amber-500 disabled:opacity-50 rounded-2xl text-xs font-black text-white uppercase tracking-widest"
                        @click="runTelemetryBatched"
                    >
                        {{ telemetryRunning ? 'Running…' : 'Run telemetry test' }}
                    </button>

                    <p v-if="telemetryRunning && telemetryProgress.total" class="text-xs text-slate-400">
                        Batch {{ telemetryProgress.batch }} / {{ telemetryProgress.total }}
                    </p>
                    <p v-if="telemetryError" class="text-sm text-red-400">{{ telemetryError }}</p>
                </div>

                <div v-if="telemetryAggregate" class="bg-slate-900 border border-emerald-500/30 rounded-[2.5rem] p-8">
                    <h3 class="text-emerald-400 text-xs font-black uppercase tracking-[0.25em] mb-4">Telemetry aggregate</h3>
                    <div class="flex flex-wrap gap-3 mb-6">
                        <button type="button" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-[10px] font-black uppercase tracking-widest text-white" @click="exportTelemetryJson">
                            Export JSON
                        </button>
                        <button type="button" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-[10px] font-black uppercase tracking-widest text-white" @click="exportTelemetryCsv">
                            Export CSV
                        </button>
                    </div>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-slate-500 text-[10px] uppercase font-black tracking-wider">Batches</dt>
                            <dd class="text-white font-mono mt-1">{{ telemetryAggregate.batches }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 text-[10px] uppercase font-black tracking-wider">Total chunks</dt>
                            <dd class="text-white font-mono mt-1">{{ telemetryAggregate.total_chunks }}</dd>
                        </div>
                        <div v-if="telemetryAggregate.total_http_ok !== null">
                            <dt class="text-slate-500 text-[10px] uppercase font-black tracking-wider">HTTP OK / failed</dt>
                            <dd class="text-white font-mono mt-1">{{ telemetryAggregate.total_http_ok }} / {{ telemetryAggregate.total_http_failed }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 text-[10px] uppercase font-black tracking-wider">Client wall time (all batches)</dt>
                            <dd class="text-white font-mono mt-1">{{ telemetryAggregate.wall_ms_total }} ms</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 text-[10px] uppercase font-black tracking-wider">Effective throughput</dt>
                            <dd class="text-white font-mono mt-1">{{ telemetryAggregate.effective_throughput_per_s }} /s</dd>
                        </div>
                        <div v-if="telemetryAggregate.estimated_concurrent_active_users !== null">
                            <dt class="text-slate-500 text-[10px] uppercase font-black tracking-wider">Est. concurrent active users (telemetry)</dt>
                            <dd class="text-white font-mono mt-1">
                                ~{{ telemetryAggregate.estimated_concurrent_active_users }}
                                <span class="text-slate-500 text-xs">(1 post / {{ telemetryAggregate.assumption_seconds_between_posts }}s per user)</span>
                            </dd>
                        </div>
                        <div v-if="telemetryAggregate.capacity_note" class="sm:col-span-2">
                            <dt class="text-slate-500 text-[10px] uppercase font-black tracking-wider">Note</dt>
                            <dd class="text-slate-300 text-xs mt-1">{{ telemetryAggregate.capacity_note }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-slate-900/80 border border-slate-800 rounded-[2rem] p-8">
                    <h3 class="text-white text-xs font-black uppercase tracking-[0.25em] mb-3">CLI (appendix / reproducibility)</h3>
                    <pre class="text-[11px] leading-relaxed text-slate-300 font-mono bg-slate-950 border border-slate-800 rounded-xl p-4 overflow-x-auto">php artisan stress:site --requests=800 --concurrency=25
php artisan stress:posture --user=1 --count=5000 --mode=direct
php artisan stress:posture --user=1 --count=500 --mode=http_api</pre>
                    <p class="mt-3 text-[10px] text-slate-500 leading-relaxed">
                        Report: hardware, PHP &amp; Laravel versions, database, web server, <code class="text-slate-400">APP_URL</code>, reverse-proxy timeouts, HTTP pool size
                        {{ httpPoolSize }} (telemetry API waves), and the assumptions above for concurrent-user estimates.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
