<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';

const toast = useToast();

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
        onError: (errors) => {
            toast.error("Invalid email or password.", "Login Failed");
        }
    });
};

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

</script>

<template>
    <Head title="Log in" />

    <div class="min-h-screen flex w-full">
        
        <div class="hidden lg:flex w-1/2 bg-cover bg-center relative" 
             style="background-image: url('https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=2072&auto=format&fit=crop');">
            <div class="absolute inset-0 bg-indigo-900/40 backdrop-blur-[2px]"></div> 
            <div class="w-full h-full flex items-center justify-center p-12 relative z-10">
                <div class="text-white text-center">
                    <h1 class="text-4xl font-bold mb-4">Ergovision AI</h1>
                    <p class="text-lg text-indigo-100">Your personal posture assistant. Log in to track your progress.</p>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center bg-gray-50 px-8 py-12">
            <div class="w-full max-w-md space-y-8">
                
                <div class="text-center lg:text-left relative">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900">
                        Welcome back
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Don't have an account? 
                        <Link :href="route('register')" class="font-medium text-indigo-600 hover:text-indigo-500 transition duration-150 ease-in-out">
                            Sign up for free
                        </Link>
                    </p>
                </div>

                <div v-if="status" class="rounded-md bg-green-50 p-4">
                    <div class="flex">
                        <div class="ml-3 text-sm font-medium text-green-800">{{ status }}</div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="mt-8 space-y-6">
                    
                    <div class="space-y-1">
                        <InputLabel for="email" value="Email address" />
                        <TextInput
                            id="email"
                            type="email"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 text-gray-900 bg-white"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="you@example.com"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <InputLabel for="password" value="Password" />
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                            >
                                Forgot password?
                            </Link>
                        </div>
                        <TextInput
                            id="password"
                            type="password"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 text-gray-900 bg-white"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="flex items-center">
                        <Checkbox name="remember" v-model:checked="form.remember" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        <label for="remember" class="ml-2 block text-sm text-gray-900">Remember me</label>
                    </div>

                    <PrimaryButton
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                        :class="{ 'opacity-75 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                    >
                        Sign in
                    </PrimaryButton>
                </form>

                <div class="relative mt-6">
                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="bg-gray-50 px-2 text-gray-500">Or continue with</span>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="/auth/google" 
                       class="flex w-full items-center justify-center gap-3 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-300 transition-all">
                        <img class="h-5 w-5" src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google logo" />
                        Sign in with Google
                    </a>
                </div>

            </div>
        </div>
    </div>
</template>