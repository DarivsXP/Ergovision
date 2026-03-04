<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    occupation: user.occupation ?? '', 
    age: user.age ?? null, 
    daily_sitting_hours: user.daily_sitting_hours ?? 8,
    has_musculoskeletal_issues: user.has_musculoskeletal_issues ? 'yes' : 'no',
    musculoskeletal_details: user.musculoskeletal_details ?? '',
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        has_musculoskeletal_issues: data.has_musculoskeletal_issues === 'yes'
    })).patch(route('profile.update'));
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-bold text-white uppercase tracking-widest">Profile Information</h2>
            <p class="mt-1 text-[11px] text-slate-400 font-bold uppercase tracking-widest">
                Update your account's profile and telemetry configuration.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-8">
            
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <InputLabel for="name" value="Name" class="text-slate-300 font-black text-[10px] uppercase tracking-widest" />
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full bg-slate-950 text-white border-white/10 rounded-xl px-4 py-3 focus:border-indigo-500 focus:ring-indigo-500 transition-all"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="email" value="Email" class="text-slate-300 font-black text-[10px] uppercase tracking-widest" />
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full bg-slate-950 text-white border-white/10 rounded-xl px-4 py-3 focus:border-indigo-500 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                        v-model="form.email"
                        required
                        autocomplete="username"
                        :disabled="!!user.google_id"
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                    
                    <p v-if="user.google_id" class="text-[10px] font-black tracking-widest text-indigo-400 mt-2 uppercase">
                        * Managed via Google
                    </p>
                </div>
            </div>

            <div class="pt-4 border-t border-white/5">
                <InputLabel for="occupation" value="Primary Role" class="text-slate-300 font-black text-[10px] uppercase tracking-widest" />
                <select
                    id="occupation"
                    class="mt-1 block w-full sm:max-w-md bg-slate-950 text-white border-white/10 rounded-xl px-4 py-3 focus:border-indigo-500 focus:ring-indigo-500 transition-all appearance-none"
                    v-model="form.occupation"
                    required
                >
                    <option value="" disabled>Select Role...</option>
                    <option value="Student">Student</option>
                    <option value="Office Professional">Office Professional</option>
                    <option value="Digital Hobbyist">Digital Hobbyist</option> 
                </select>
                <InputError class="mt-2" :message="form.errors.occupation" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-12 gap-8 pt-6 border-t border-white/5">
                
                <div class="sm:col-span-4 lg:col-span-3">
                    <InputLabel for="age" value="Age" class="text-slate-300 font-black text-[10px] uppercase tracking-widest" />
                    <input 
                        type="number" 
                        id="age"
                        v-model="form.age" 
                        min="13" 
                        max="100" 
                        required
                        class="mt-1 block w-full max-w-[120px] bg-slate-950 text-white border-white/10 rounded-xl px-4 py-3 focus:border-indigo-500 focus:ring-indigo-500 transition-all text-center tabular-nums" 
                    />
                    <InputError class="mt-2" :message="form.errors.age" />
                </div>

                <div class="sm:col-span-8 lg:col-span-9">
                    <div class="flex justify-between items-end mb-2">
                        <InputLabel value="Average Daily Sitting" class="text-slate-300 font-black text-[10px] uppercase tracking-widest" />
                        
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
                <InputLabel value="Pre-existing back or neck pain?" class="text-slate-300 font-black text-[10px] uppercase tracking-widest mb-4" />
                <div class="flex gap-4 max-w-sm">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" v-model="form.has_musculoskeletal_issues" value="yes" class="peer sr-only">
                        <div class="text-center py-3 rounded-xl border border-white/10 text-slate-400 font-black uppercase text-xs tracking-widest peer-checked:bg-amber-500/20 peer-checked:text-amber-400 peer-checked:border-amber-500/50 transition-all">
                            Yes
                        </div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" v-model="form.has_musculoskeletal_issues" value="no" class="peer sr-only">
                        <div class="text-center py-3 rounded-xl border border-white/10 text-slate-400 font-black uppercase text-xs tracking-widest peer-checked:bg-emerald-500/20 peer-checked:text-emerald-400 peer-checked:border-emerald-500/50 transition-all">
                            No
                        </div>
                    </label>
                </div>

                <div v-if="form.has_musculoskeletal_issues === 'yes'" class="animate-fade-in-down pt-2">
                    <InputLabel for="musculoskeletal_details" value="Please specify (e.g., Scoliosis, lower back pain)" class="text-amber-400/70 font-black text-[10px] uppercase tracking-widest mb-2" />
                    <textarea 
                        id="musculoskeletal_details"
                        v-model="form.musculoskeletal_details" 
                        rows="2" 
                        required
                        class="w-full bg-slate-950 border border-amber-500/20 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-amber-500 transition-all resize-none"
                    ></textarea>
                </div>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="text-[11px] font-bold mt-2 text-slate-400 uppercase tracking-widest">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="underline text-indigo-400 hover:text-indigo-300 ml-1"
                    >
                        Click here to re-send
                    </Link>
                </p>

                <div v-show="status === 'verification-link-sent'" class="mt-2 font-black text-[10px] uppercase tracking-widest text-emerald-400">
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4 pt-6 border-t border-white/5">
                <PrimaryButton 
                    :disabled="form.processing" 
                    class="px-8 py-4 bg-indigo-600 hover:bg-indigo-500 font-black text-xs uppercase tracking-widest rounded-2xl shadow-[0_0_20px_rgba(79,70,229,0.3)] transition-all">
                    Save Configuration
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0 translate-x-[-10px]"
                    leave-active-class="transition ease-in-out duration-300"
                    leave-to-class="opacity-0 translate-x-[10px]"
                >
                    <p v-if="form.recentlySuccessful" class="text-[10px] uppercase tracking-widest text-emerald-400 font-black flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Saved
                    </p>
                </Transition>
            </div>
        </form>
    </section>
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