<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirm Password" />

        <div class="mb-6 text-sm text-slate-400 leading-relaxed text-center">
            This is a secure area of the application. Please confirm your password before continuing.
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <label for="password" class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Password</label>
                <input 
                    id="password" 
                    type="password" 
                    class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-inner" 
                    v-model="form.password" 
                    required 
                    autocomplete="current-password" 
                    autofocus 
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <button 
                type="submit" 
                class="w-full py-4 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-2xl shadow-[0_0_20px_rgba(79,70,229,0.3)] transition-all uppercase tracking-widest text-xs disabled:opacity-50" 
                :disabled="form.processing"
            >
                Confirm Password
            </button>
        </form>
    </GuestLayout>
</template>