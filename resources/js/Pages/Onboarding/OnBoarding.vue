<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const form = useForm({
    occupation: '', 
    age: null,
    daily_sitting_hours: 8,
    has_musculoskeletal_issues: 'no',
    musculoskeletal_details: '',
});

const submit = () => {
    // Transform 'yes'/'no' into a boolean for the database
    const data = form.transform((data) => ({
        ...data,
        has_musculoskeletal_issues: data.has_musculoskeletal_issues === 'yes'
    }));
    
    data.post(route('onboarding.store'));
};
</script>

<template>
    <Head title="Complete Your Profile" />
    
    <div class="min-h-screen bg-[#020617] flex items-center justify-center p-4 sm:p-8">
        <div class="max-w-3xl w-full bg-slate-900 border border-white/10 rounded-[2.5rem] p-8 md:p-12 shadow-[0_0_50px_rgba(79,70,229,0.1)] relative overflow-hidden">
            
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-1 bg-gradient-to-r from-transparent via-indigo-500 to-transparent opacity-50"></div>

            <div class="flex flex-col items-center mb-10">
                <div class="h-14 w-14 rounded-2xl bg-indigo-600 flex items-center justify-center shadow-xl shadow-indigo-500/20 mb-5">
                    <ApplicationLogo class="h-8 w-auto fill-current text-white" />
                </div>
                <h1 class="text-3xl font-black text-white tracking-tight uppercase">
                    Welcome to <span class="text-indigo-500">ErgoVision</span>
                </h1>
                <p class="text-slate-400 mt-2 text-xs font-bold uppercase tracking-widest text-center">
                    Calibrate the AI by completing your profile
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 text-center">Select Your Primary Role</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        
                        <button type="button" @click="form.occupation = 'Student'" 
                            class="group p-5 border rounded-3xl transition-all text-left"
                            :class="form.occupation === 'Student' ? 'bg-indigo-500/10 border-indigo-500 ring-1 ring-indigo-500/50' : 'bg-slate-950 border-white/5 hover:border-indigo-500/50'">
                            <h3 class="text-sm font-bold text-white uppercase tracking-tight">Student</h3>
                            <p class="text-slate-500 text-[10px] mt-1 leading-relaxed">Most of my time is spent studying.</p>
                        </button>

                        <button type="button" @click="form.occupation = 'Office Professional'" 
                            class="group p-5 border rounded-3xl transition-all text-left"
                            :class="form.occupation === 'Office Professional' ? 'bg-indigo-500/10 border-indigo-500 ring-1 ring-indigo-500/50' : 'bg-slate-950 border-white/5 hover:border-indigo-500/50'">
                            <h3 class="text-sm font-bold text-white uppercase tracking-tight">Worker</h3>
                            <p class="text-slate-500 text-[10px] mt-1 leading-relaxed">I work at a desk most of the day.</p>
                        </button>

                        <button type="button" @click="form.occupation = 'Recreational User'" 
                            class="group p-5 border rounded-3xl transition-all text-left"
                            :class="form.occupation === 'Recreational User' ? 'bg-indigo-500/10 border-indigo-500 ring-1 ring-indigo-500/50' : 'bg-slate-950 border-white/5 hover:border-indigo-500/50'">
                            <h3 class="text-sm font-bold text-white uppercase tracking-tight">Hobbyist</h3>
                            <p class="text-slate-500 text-[10px] mt-1 leading-relaxed">Gaming, browsing, or casual use.</p>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-8 pt-6 border-t border-white/5">
                    
                    <div class="sm:col-span-4 lg:col-span-3">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Age</label>
                        <input type="number" v-model="form.age" min="13" max="100" required
                            class="w-full max-w-[120px] bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-indigo-500 transition-all text-center tabular-nums" />
                    </div>

                    <div class="sm:col-span-8 lg:col-span-9">
                        <div class="flex justify-between items-end mb-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Daily Sitting</label>
                            
                            <span class="text-xl font-black text-indigo-400 tabular-nums leading-none">
                                <template v-if="form.daily_sitting_hours == 0">&lt; 1</template>
                                <template v-else-if="form.daily_sitting_hours == 13">12+</template>
                                <template v-else>{{ form.daily_sitting_hours }}</template>
                                <span class="text-[10px] text-slate-500 ml-1">HRS</span>
                            </span>
                        </div>
                        
                        <div class="relative pt-2">
                            <input type="range" v-model="form.daily_sitting_hours" min="0" max="13" step="1"
                                class="w-full h-3 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-8 [&::-webkit-slider-thumb]:h-8 [&::-webkit-slider-thumb]:bg-indigo-500 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:shadow-[0_0_15px_rgba(79,70,229,0.5)] [&::-webkit-slider-thumb]:transition-transform hover:[&::-webkit-slider-thumb]:scale-110" />
                            
                            <div class="flex justify-between text-[9px] font-bold text-slate-500 uppercase tracking-widest mt-3">
                                <span>&lt; 1 Hour</span>
                                <span>12+ Hours</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-white/5 space-y-4">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest text-center mb-4">Do you have pre-existing back or neck pain?</label>
                    <div class="flex gap-4 max-w-sm mx-auto">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" v-model="form.has_musculoskeletal_issues" value="yes" class="peer sr-only">
                            <div class="text-center py-3 rounded-xl border border-white/10 text-slate-400 font-black uppercase text-xs tracking-widest peer-checked:bg-amber-500/20 peer-checked:text-amber-400 peer-checked:border-amber-500/50 transition-all">Yes</div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" v-model="form.has_musculoskeletal_issues" value="no" class="peer sr-only">
                            <div class="text-center py-3 rounded-xl border border-white/10 text-slate-400 font-black uppercase text-xs tracking-widest peer-checked:bg-emerald-500/20 peer-checked:text-emerald-400 peer-checked:border-emerald-500/50 transition-all">No</div>
                        </label>
                    </div>

                    <div v-if="form.has_musculoskeletal_issues === 'yes'" class="animate-fade-in-down pt-2">
                        <label class="block text-[10px] font-black text-amber-400/70 uppercase tracking-widest mb-2">Please specify</label>
                        <textarea v-model="form.musculoskeletal_details" rows="2" required
                            class="w-full bg-slate-950 border border-amber-500/20 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-amber-500 transition-all resize-none"></textarea>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" :disabled="form.processing || !form.occupation"
                        class="w-full py-5 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-[2rem] shadow-[0_0_20px_rgba(79,70,229,0.3)] transition-all uppercase tracking-widest text-xs disabled:opacity-50 disabled:cursor-not-allowed">
                        Complete Setup & Enter Dashboard
                    </button>
                    <p v-if="!form.occupation" class="text-center text-rose-500 text-[10px] uppercase font-bold mt-3 animate-pulse">
                        Please select a role to continue
                    </p>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in-down {
    animation: fadeInDown 0.3s ease-out forwards;
}
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>