<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Reset Password" />

        <div class="mb-8 text-center">
             <h2 class="text-2xl font-black text-white uppercase tracking-widest">Reset Password</h2>
             <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-2">Create a new secure password</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">

            <div>
                <label for="password" class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">New Password</label>
                <input 
                    id="password" 
                    type="password" 
                    class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-inner" 
                    v-model="form.password" 
                    required 
                    autocomplete="new-password" 
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <label for="password_confirmation" class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Confirm Password</label>
                <input 
                    id="password_confirmation" 
                    type="password" 
                    class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-inner" 
                    v-model="form.password_confirmation" 
                    required 
                    autocomplete="new-password" 
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="pt-4">
                <button 
                    type="submit" 
                    class="w-full py-4 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-2xl shadow-[0_0_20px_rgba(79,70,229,0.3)] transition-all uppercase tracking-widest text-xs disabled:opacity-50" 
                    :disabled="form.processing"
                >
                    Confirm Reset Password
                </button>
            </div>
        </form>
    </GuestLayout>
</template>