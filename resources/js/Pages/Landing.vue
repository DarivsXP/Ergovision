<script setup>
import { useTheme } from '@/Composables/useTheme';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const { isDark, toggle } = useTheme();
const navScrolled = ref(false);
const visibleSections = ref(new Set());

onMounted(() => {
    const onScroll = () => {
        navScrolled.value = window.scrollY > 24;
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    visibleSections.value.add(entry.target.id);
                }
            });
        },
        { threshold: 0.12, rootMargin: '0px 0px -40px 0px' },
    );

    document.querySelectorAll('[data-reveal]').forEach((el) => observer.observe(el));
});

const features = [
    {
        title: 'Gentle nudges, not nagging',
        body: 'When you start to slouch, Ergovision gives you a quiet cue — sound or toast — so you can reset without breaking your flow.',
        icon: 'bell',
        accent: 'indigo',
    },
    {
        title: 'A score you can actually read',
        body: 'Your posture score updates in real time and rolls into session history, so you see patterns instead of guessing.',
        icon: 'chart',
        accent: 'emerald',
    },
    {
        title: 'Your webcam stays yours',
        body: 'Video never gets saved. We only process landmarks in the moment, then discard the frame. Your face never hits our servers.',
        icon: 'shield',
        accent: 'rose',
    },
    {
        title: 'Built for long desk days',
        body: 'Telemetry chunks every ~30 seconds, trend views for 3/7/30 days, and feedback after sessions — made for students and remote workers.',
        icon: 'clock',
        accent: 'amber',
    },
];

const steps = [
    {
        n: '01',
        title: 'Calibrate once',
        body: 'Sit the way you want to sit. Ergovision learns your “good” baseline in about five seconds.',
    },
    {
        n: '02',
        title: 'Work as usual',
        body: 'Keep the tab open while you study or work. The AI checks posture about five times a second — light on your machine.',
    },
    {
        n: '03',
        title: 'Review your progress',
        body: 'Open your dashboard to see scores, slouch time, and alerts. Small wins add up over a week.',
    },
];

function isVisible(id) {
    return visibleSections.value.has(id);
}
</script>

<template>
    <Head title="Home">
        <meta head-key="description" name="description" content="Ergovision helps you sit better at your desk with real-time AI posture feedback — no video stored, just honest progress you can see." />
    </Head>

    <div class="relative min-h-screen overflow-x-hidden bg-slate-50 text-slate-800 transition-colors duration-500 dark:bg-[#020617] dark:text-slate-200">
        <!-- Ambient orbs -->
        <div class="landing-orb -top-32 left-1/4 h-96 w-96 bg-indigo-500/20 dark:bg-indigo-600/25 landing-float" aria-hidden="true" />
        <div class="landing-orb top-1/3 -right-20 h-80 w-80 bg-purple-500/15 dark:bg-purple-600/20 landing-float landing-delay-2" aria-hidden="true" />
        <div class="landing-orb bottom-0 left-0 h-72 w-72 bg-emerald-500/10 dark:bg-emerald-500/15 landing-float landing-delay-3" aria-hidden="true" />

        <!-- Nav -->
        <header
            :class="[
                'fixed inset-x-0 top-0 z-50 transition-all duration-300',
                navScrolled
                    ? 'border-b border-slate-200/80 bg-white/85 shadow-lg shadow-slate-900/5 backdrop-blur-xl dark:border-white/5 dark:bg-slate-950/80 dark:shadow-black/20'
                    : 'bg-transparent',
            ]"
        >
            <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link href="/" class="group flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-600 shadow-lg shadow-indigo-500/30 transition group-hover:bg-indigo-500 overflow-hidden p-1.5">
                        <img src="/logo.png" alt="" class="h-full w-full object-contain" />
                    </div>
                    <span class="font-black text-xl tracking-tighter text-slate-900 uppercase dark:text-white">
                        ERGO<span class="text-indigo-600 dark:text-indigo-500">VISION</span>
                    </span>
                </Link>

                <nav class="hidden items-center gap-8 md:flex">
                    <a href="#features" class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 transition hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400">Features</a>
                    <a href="#how" class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 transition hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400">How it works</a>
                    <a href="#privacy" class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 transition hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400">Privacy</a>
                </nav>

                <div class="flex items-center gap-2 sm:gap-3">
                    <button
                        type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-indigo-300 hover:text-indigo-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400"
                        :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                        @click="toggle"
                    >
                        <svg v-if="isDark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>

                    <Link
                        :href="route('login')"
                        class="hidden rounded-xl px-4 py-2.5 text-[11px] font-black uppercase tracking-widest text-slate-600 transition hover:text-indigo-600 sm:inline-flex dark:text-slate-300 dark:hover:text-white"
                    >
                        Log in
                    </Link>

                    <Link
                        :href="route('register')"
                        class="rounded-xl bg-indigo-600 px-4 py-2.5 text-[11px] font-black uppercase tracking-widest text-white shadow-lg shadow-indigo-500/30 transition hover:bg-indigo-500 hover:shadow-indigo-500/40 sm:px-5"
                    >
                        Get started
                    </Link>
                </div>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative px-4 pb-24 pt-32 sm:px-6 lg:px-8 lg:pb-32 lg:pt-40">
            <div class="mx-auto grid max-w-7xl items-center gap-16 lg:grid-cols-2">
                <div class="landing-fade-up relative z-10 space-y-8">
                    <p class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1.5 text-[10px] font-black uppercase tracking-[0.25em] text-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75" />
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500" />
                        </span>
                        Real-time posture, right in your browser
                    </p>

                    <h1 class="text-4xl font-black leading-[1.08] tracking-tight text-slate-900 sm:text-5xl lg:text-6xl dark:text-white">
                        Your desk shouldn’t win
                        <span class="landing-gradient-text">every round.</span>
                    </h1>

                    <p class="max-w-xl text-lg leading-relaxed text-slate-600 dark:text-slate-400">
                        Ergovision is a posture companion for long study sessions and WFH days. It watches how you sit,
                        gives you a nudge when you drift, and shows your progress over time — without storing your video.
                    </p>

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <Link
                            :href="route('register')"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-8 py-4 text-xs font-black uppercase tracking-[0.2em] text-white shadow-xl shadow-indigo-500/30 transition hover:-translate-y-0.5 hover:bg-indigo-500 hover:shadow-indigo-500/40"
                        >
                            Start free
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </Link>
                        <Link
                            :href="route('login')"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-8 py-4 text-xs font-black uppercase tracking-[0.2em] text-slate-700 transition hover:border-indigo-300 hover:text-indigo-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:border-indigo-500/40"
                        >
                            I already have an account
                        </Link>
                    </div>

                    <div class="flex flex-wrap gap-6 pt-2 text-sm text-slate-500 dark:text-slate-400">
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                            No video stored
                        </span>
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                            Works with your webcam
                        </span>
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                            Session history & trends
                        </span>
                    </div>
                </div>

                <!-- Mock dashboard preview -->
                <div class="landing-fade-up landing-delay-2 relative z-10">
                    <div class="absolute -inset-4 rounded-[2.5rem] bg-gradient-to-br from-indigo-500/20 via-purple-500/10 to-emerald-500/10 blur-2xl dark:from-indigo-500/30" aria-hidden="true" />
                    <div class="relative overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white/90 shadow-2xl shadow-slate-900/10 backdrop-blur dark:border-white/10 dark:bg-slate-900/90 dark:shadow-black/40">
                        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-white/5">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Live session</p>
                                <p class="text-sm font-bold text-slate-800 dark:text-white">Posture monitor</p>
                            </div>
                            <span class="flex items-center gap-2 rounded-full bg-emerald-500/10 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-400">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" />
                                Tracking
                            </span>
                        </div>

                        <div class="grid grid-cols-3 gap-3 p-5">
                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 dark:border-white/5 dark:bg-white/[0.03]">
                                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Score</p>
                                <p class="mt-1 text-3xl font-black text-indigo-600 dark:text-indigo-400">87<span class="text-lg text-slate-400">%</span></p>
                            </div>
                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 dark:border-white/5 dark:bg-white/[0.03]">
                                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Slouch</p>
                                <p class="mt-1 text-3xl font-black text-amber-600 dark:text-amber-400">4<span class="text-lg text-slate-400">m</span></p>
                            </div>
                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 dark:border-white/5 dark:bg-white/[0.03]">
                                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Alerts</p>
                                <p class="mt-1 text-3xl font-black text-rose-500">2</p>
                            </div>
                        </div>

                        <div class="mx-5 mb-5 rounded-2xl border border-dashed border-indigo-200 bg-indigo-50/50 p-6 text-center dark:border-indigo-500/20 dark:bg-indigo-500/5">
                            <div class="relative mx-auto mb-3 flex h-16 w-16 items-center justify-center">
                                <span class="absolute inset-0 rounded-full border-2 border-indigo-400/40 animate-ping" />
                                <div class="relative flex h-14 w-14 items-center justify-center rounded-full bg-indigo-600 text-white shadow-lg shadow-indigo-500/40">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs font-bold text-slate-600 dark:text-slate-300">Camera active — landmarks only, no recording</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="features" data-reveal class="px-4 py-20 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div
                    :class="['mb-14 text-center transition-all duration-700', isVisible('features') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8']"
                >
                    <p class="text-[10px] font-black uppercase tracking-[0.35em] text-indigo-600 dark:text-indigo-400">Why Ergovision</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                        Posture help that feels human
                    </h2>
                    <p class="mx-auto mt-4 max-w-2xl text-slate-600 dark:text-slate-400">
                        Not another guilt-trip app. Ergovision is designed for real desks, real fatigue, and real people who forget to sit up straight until their neck reminds them.
                    </p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="(f, i) in features"
                        :key="f.title"
                        :class="[
                            'group rounded-[2rem] border p-7 transition-all duration-700 hover:-translate-y-1',
                            isVisible('features') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8',
                            'border-slate-200 bg-white shadow-lg shadow-slate-900/5 hover:border-indigo-200 hover:shadow-xl dark:border-white/10 dark:bg-slate-900/60 dark:shadow-black/20 dark:hover:border-indigo-500/30',
                        ]"
                        :style="{ transitionDelay: `${i * 80}ms` }"
                    >
                        <div
                            :class="[
                                'mb-5 flex h-12 w-12 items-center justify-center rounded-2xl',
                                f.accent === 'indigo' && 'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400',
                                f.accent === 'emerald' && 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400',
                                f.accent === 'rose' && 'bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400',
                                f.accent === 'amber' && 'bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400',
                            ]"
                        >
                            <svg v-if="f.icon === 'bell'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                            <svg v-else-if="f.icon === 'chart'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            <svg v-else-if="f.icon === 'shield'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">{{ f.title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ f.body }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section id="how" data-reveal class="px-4 py-20 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl rounded-[3rem] border border-slate-200 bg-white p-8 shadow-xl shadow-slate-900/5 sm:p-12 dark:border-white/10 dark:bg-slate-900/50 dark:shadow-black/30">
                <div
                    :class="['mb-12 transition-all duration-700', isVisible('how') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8']"
                >
                    <p class="text-[10px] font-black uppercase tracking-[0.35em] text-indigo-600 dark:text-indigo-400">How it works</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-900 dark:text-white">Three steps. No lecture.</h2>
                </div>

                <div class="grid gap-8 md:grid-cols-3">
                    <div
                        v-for="(step, i) in steps"
                        :key="step.n"
                        :class="[
                            'relative transition-all duration-700',
                            isVisible('how') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8',
                        ]"
                        :style="{ transitionDelay: `${i * 100}ms` }"
                    >
                        <span class="text-5xl font-black text-indigo-100 dark:text-indigo-500/20">{{ step.n }}</span>
                        <h3 class="mt-2 text-xl font-black text-slate-900 dark:text-white">{{ step.title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ step.body }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Privacy -->
        <section id="privacy" data-reveal class="px-4 py-20 sm:px-6 lg:px-8">
            <div
                :class="[
                    'mx-auto max-w-4xl rounded-[2.5rem] border p-8 text-center transition-all duration-700 sm:p-12',
                    isVisible('privacy') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8',
                    'border-rose-200 bg-rose-50/80 dark:border-rose-500/20 dark:bg-rose-500/5',
                ]"
            >
                <h2 class="text-2xl font-black text-slate-900 dark:text-white">We don’t save your face. Period.</h2>
                <p class="mx-auto mt-4 max-w-2xl text-sm leading-relaxed text-slate-600 dark:text-rose-100/80">
                    Your webcam feed is processed in the browser and on our AI service to extract body landmarks — math, not photos.
                    Once angles are calculated, the frame is discarded. What we keep is telemetry: scores, slouch time, and session stats.
                </p>
                <Link
                    :href="route('privacy')"
                    class="mt-6 inline-flex text-[11px] font-black uppercase tracking-[0.2em] text-rose-600 transition hover:text-rose-500 dark:text-rose-400"
                >
                    Read our privacy policy →
                </Link>
            </div>
        </section>

        <!-- CTA -->
        <section class="px-4 pb-8 pt-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-5xl overflow-hidden rounded-[3rem] bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-800 p-10 text-center shadow-2xl shadow-indigo-500/25 sm:p-16">
                <h2 class="text-3xl font-black tracking-tight text-white sm:text-4xl">
                    Ready to sit like you mean it?
                </h2>
                <p class="mx-auto mt-4 max-w-xl text-indigo-100/90">
                    Create a free account, calibrate in seconds, and let Ergovision handle the reminders while you focus on your work.
                </p>
                <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <Link
                        :href="route('register')"
                        class="inline-flex rounded-2xl bg-white px-8 py-4 text-xs font-black uppercase tracking-[0.2em] text-indigo-700 shadow-lg transition hover:-translate-y-0.5 hover:bg-indigo-50"
                    >
                        Create account
                    </Link>
                    <a
                        href="/auth/google"
                        class="inline-flex items-center gap-2 rounded-2xl border border-white/25 bg-white/10 px-8 py-4 text-xs font-black uppercase tracking-[0.2em] text-white backdrop-blur transition hover:bg-white/20"
                    >
                        <img class="h-4 w-4" src="https://www.svgrepo.com/show/475656/google-color.svg" alt="" />
                        Continue with Google
                    </a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-slate-200 px-4 py-12 dark:border-white/5 sm:px-6 lg:px-8">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 sm:flex-row">
                <p class="text-[10px] font-black uppercase tracking-[0.25em] text-slate-400">
                    © {{ new Date().getFullYear() }} Ergovision · FAITH Colleges research project
                </p>
                <div class="flex flex-wrap items-center justify-center gap-6">
                    <Link :href="route('privacy')" class="text-[10px] font-black uppercase tracking-widest text-slate-500 transition hover:text-indigo-600 dark:hover:text-indigo-400">Privacy</Link>
                    <Link :href="route('terms')" class="text-[10px] font-black uppercase tracking-widest text-slate-500 transition hover:text-indigo-600 dark:hover:text-indigo-400">Terms</Link>
                    <Link :href="route('login')" class="text-[10px] font-black uppercase tracking-widest text-slate-500 transition hover:text-indigo-600 dark:hover:text-indigo-400">Log in</Link>
                </div>
            </div>
        </footer>
    </div>
</template>
