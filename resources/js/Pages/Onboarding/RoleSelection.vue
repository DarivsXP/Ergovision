<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const form = useForm({ user_type: '' });

const selectRole = (role) => {
    form.user_type = role;
    form.post(route('onboarding.store'));
};
</script>

<template>
    <Head title="Select your Role" />
    <div class="min-h-screen bg-[#020617] flex items-center justify-center p-4">
        <div class="max-w-lg w-full text-center space-y-8">
            
            <div class="flex flex-col items-center">
                <div class="h-12 w-12 rounded-xl bg-indigo-600 flex items-center justify-center shadow-xl shadow-indigo-500/20 mb-4">
                    <ApplicationLogo class="h-6 w-auto fill-current text-white" />
                </div>
                <h1 class="text-2xl font-black text-white tracking-tight uppercase">
                    Welcome to <span class="text-indigo-500">ErgoVision</span>
                </h1>
                <p class="text-slate-400 mt-2 text-sm max-w-xs mx-auto">
                    To provide better analysis, tell us what you do.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <button @click="selectRole('student')" 
                    class="group p-5 bg-slate-900 border border-slate-800 rounded-3xl hover:border-indigo-500 transition-all text-left">
                    <div class="h-10 w-10 bg-indigo-500/10 rounded-lg flex items-center justify-center text-indigo-500 mb-3 group-hover:bg-indigo-500 group-hover:text-white transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-white uppercase tracking-tight">Student</h3>
                    <p class="text-slate-500 text-[11px] mt-1 leading-relaxed">Most of my time is spent studying.</p>
                </button>

                <button @click="selectRole('worker')" 
                    class="group p-5 bg-slate-900 border border-slate-800 rounded-3xl hover:border-indigo-500 transition-all text-left">
                    <div class="h-10 w-10 bg-indigo-500/10 rounded-lg flex items-center justify-center text-indigo-500 mb-3 group-hover:bg-indigo-500 group-hover:text-white transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-white uppercase tracking-tight">Worker</h3>
                    <p class="text-slate-500 text-[11px] mt-1 leading-relaxed">I work at a desk most of the day.</p>
                </button>
            </div>
            
            <button @click="selectRole('other')" 
                class="text-slate-600 hover:text-slate-400 text-[10px] font-black uppercase tracking-[0.2em] transition">
                Skip for now
            </button>
        </div>
    </div>
</template>