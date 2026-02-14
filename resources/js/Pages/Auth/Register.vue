<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const showPassword = ref(false);

const form = useForm({
    name: '',
    email: '',
    user_type: '', 
    password: '',
    password_confirmation: '',
});

const submit = () => {
    if (form.processing) return;
    
    form.post(route('register'), {
        preserveScroll: true,
        // Resets password fields only on a finished request
        onFinish: () => form.reset('password', 'password_confirmation'),
        // Success and Error handlers to help debug redirection
        onSuccess: () => console.log('Account created successfully.'),
        onError: (errors) => console.error('Registration errors:', errors),
    });
};
</script>

<template>
    <Head title="Register" />

    <div class="min-h-screen flex w-full">
        
        <div class="hidden lg:flex w-1/2 bg-cover bg-center relative" 
             style="background-image: url('https://images.unsplash.com/photo-1519389950473-47ba0277781c?q=80&w=2070&auto=format&fit=crop');">
            <div class="absolute inset-0 bg-indigo-900/60 backdrop-blur-[1px]"></div>
            <div class="w-full h-full flex items-center justify-center p-12 relative z-10">
                <div class="text-white text-center">
                    <h1 class="text-5xl font-black mb-4 tracking-tighter uppercase">ERGOVISION</h1>
                    <p class="text-xl text-indigo-100 font-medium">Join thousands improving their health through telemetry.</p>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center bg-white px-8 py-12 overflow-y-auto">
            <div class="w-full max-w-md space-y-8">
                
                <div class="text-center lg:text-left">
                    <h2 class="text-3xl font-black tracking-tighter text-slate-900 uppercase">
                        Create your account
                    </h2>
                    <p class="mt-2 text-sm text-slate-500 font-medium">
                        Already have an account? 
                        <Link :href="route('login')" class="font-bold text-indigo-600 hover:text-indigo-500 underline decoration-indigo-200 underline-offset-4">
                            Log in here
                        </Link>
                    </p>
                </div>

                <form @submit.prevent="submit" class="mt-8 space-y-5">
                    
                    <div class="space-y-1">
                        <InputLabel for="name" value="Full Name" class="text-slate-700 font-bold" />
                        <TextInput
                            id="name"
                            type="text"
                            class="block w-full rounded-xl border-slate-400 shadow-sm focus:border-indigo-600 focus:ring-indigo-600 sm:text-sm py-3 px-4 text-slate-900 placeholder-slate-400 bg-white"
                            v-model="form.name"
                            required
                            autofocus
                            placeholder="e.g. John Doe"
                        />
                        <InputError :message="form.errors.name" class="mt-1 font-bold text-xs" />
                    </div>

                    <div class="space-y-1">
                        <InputLabel for="email" value="Email address" class="text-slate-700 font-bold" />
                        <TextInput
                            id="email"
                            type="email"
                            class="block w-full rounded-xl border-slate-400 shadow-sm focus:border-indigo-600 focus:ring-indigo-600 sm:text-sm py-3 px-4 text-slate-900 placeholder-slate-400 bg-white"
                            v-model="form.email"
                            required
                            placeholder="you@example.com"
                        />
                        <InputError :message="form.errors.email" class="mt-1 font-bold text-xs" />
                    </div>

                    <div class="space-y-1">
                        <InputLabel for="user_type" value="I am a..." class="text-slate-700 font-bold" />
                        <select
                            id="user_type"
                            class="block w-full rounded-xl border-slate-400 shadow-sm focus:border-indigo-600 focus:ring-indigo-600 sm:text-sm py-3 px-4 bg-white text-slate-900 font-medium transition-all"
                            v-model="form.user_type"
                            required
                        >
                            <option value="" disabled>Select your role</option>
                            <option value="student">Student</option>
                            <option value="worker">Office Professional</option>
                            <option value="other">Other</option>
                        </select>
                        <InputError :message="form.errors.user_type" class="mt-1 font-bold text-xs" />
                    </div>

                    <div class="space-y-1">
                        <div class="flex items-center justify-between px-1">
                            <InputLabel for="password" value="Password" class="text-slate-700 font-bold" />
                            <button 
                                type="button" 
                                @click="showPassword = !showPassword"
                                class="text-[10px] font-black uppercase tracking-widest text-indigo-600 hover:text-indigo-400 transition"
                            >
                                {{ showPassword ? 'Hide' : 'Show' }}
                            </button>
                        </div>
                        <div class="relative">
                            <TextInput
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="block w-full rounded-xl border-slate-400 shadow-sm focus:border-indigo-600 focus:ring-indigo-600 sm:text-sm py-3 px-4 text-slate-900 placeholder-slate-400 bg-white"
                                v-model="form.password"
                                required
                                placeholder="Min. 8 characters"
                            />
                        </div>
                        <InputError :message="form.errors.password" class="mt-1 font-bold text-xs" />
                    </div>

                    <div class="space-y-1">
                        <InputLabel for="password_confirmation" value="Confirm Password" class="text-slate-700 font-bold" />
                        <TextInput
                            id="password_confirmation"
                            :type="showPassword ? 'text' : 'password'"
                            class="block w-full rounded-xl border-slate-400 shadow-sm focus:border-indigo-600 focus:ring-indigo-600 sm:text-sm py-3 px-4 text-slate-900 placeholder-slate-400 bg-white"
                            v-model="form.password_confirmation"
                            required
                            placeholder="Repeat password"
                        />
                        <InputError :message="form.errors.password_confirmation" class="mt-1 font-bold text-xs" />
                    </div>

                    <PrimaryButton
                        class="w-full flex justify-center py-4 px-4 border border-transparent rounded-xl shadow-xl text-xs font-black uppercase tracking-[0.2em] text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 transition-all active:scale-[0.98]"
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Synchronizing...' : 'Create Account' }}
                    </PrimaryButton>
                </form>

                <div class="relative mt-8">
                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-slate-300"></div>
                    </div>
                    <div class="relative flex justify-center text-[10px] font-black uppercase tracking-widest">
                        <span class="bg-white px-4 text-slate-400">Or authenticate with</span>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="/auth/google" 
                       class="flex w-full items-center justify-center gap-3 rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50 hover:border-slate-300 transition-all active:scale-[0.98]">
                        <img class="h-5 w-5" src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google logo" />
                        Sign up with Google
                    </a>
                </div>

            </div>
        </div>
    </div>
</template>