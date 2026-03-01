<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password" />

        <div class="mb-6 text-sm text-slate-400 leading-relaxed text-center">
            Forgot your password? No problem. Let us know your email address and we will email you a password reset link to choose a new one.
        </div>

        <div v-if="status" class="mb-6 text-xs font-black text-emerald-400 bg-emerald-500/10 p-4 rounded-xl border border-emerald-500/20 uppercase tracking-widest text-center">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <label for="email" class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Email Address</label>
                <input 
                    id="email" 
                    type="email" 
                    class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-inner" 
                    v-model="form.email" 
                    required 
                    autofocus 
                    autocomplete="username" 
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <button 
                type="submit" 
                class="w-full py-4 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-2xl shadow-[0_0_20px_rgba(79,70,229,0.3)] transition-all uppercase tracking-widest text-xs disabled:opacity-50" 
                :disabled="form.processing"
            >
                Email Password Reset Link
            </button>
        </form>
    </GuestLayout>
</template>