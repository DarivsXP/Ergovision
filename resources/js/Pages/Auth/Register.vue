<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

// State for toggling password visibility
const showPassword = ref(false);

const form = useForm({
    name: '',
    email: '',
    user_type: '', 
    password: '',
    password_confirmation: '',
});

const submit = () => {
    // Only submit if not already processing
    if (form.processing) return;
    
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Register" />

    <div class="min-h-screen flex w-full">
        
        <div class="hidden lg:flex w-1/2 bg-cover bg-center relative" 
             style="background-image: url('https://images.unsplash.com/photo-1519389950473-47ba0277781c?q=80&w=2070&auto=format&fit=crop');">
            <div class="absolute inset-0 bg-indigo-900/40 backdrop-blur-[2px]"></div>
            <div class="w-full h-full flex items-center justify-center p-12 relative z-10">
                <div class="text-white text-center">
                    <h1 class="text-4xl font-bold mb-4">Join Ergovision</h1>
                    <p class="text-lg text-indigo-100">Start your journey to better posture and health today.</p>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center bg-gray-50 px-8 py-12 overflow-y-auto">
            <div class="w-full max-w-md space-y-8">
                
                <div class="text-center lg:text-left">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900">
                        Create an account
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Already have an account? 
                        <Link :href="route('login')" class="font-medium text-indigo-600 hover:text-indigo-500 transition duration-150 ease-in-out">
                            Log in here
                        </Link>
                    </p>
                </div>

                <form @submit.prevent="submit" class="mt-8 space-y-6">
                    
                    <div class="space-y-1">
                        <InputLabel for="name" value="Full Name" />
                        <TextInput
                            id="name"
                            type="text"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                            placeholder="John Doe"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="space-y-1">
                        <InputLabel for="email" value="Email address" />
                        <TextInput
                            id="email"
                            type="email"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="you@example.com"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="space-y-1">
                        <InputLabel for="user_type" value="I am a..." />
                        <select
                            id="user_type"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 bg-white"
                            v-model="form.user_type"
                            required
                        >
                            <option value="" disabled>Select your role</option>
                            <option value="student">Student</option>
                            <option value="worker">Office Worker</option>
                            <option value="other">Other</option>
                        </select>
                        <InputError :message="form.errors.user_type" />
                    </div>

                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <InputLabel for="password" value="Password" />
                            <button 
                                type="button" 
                                @click="showPassword = !showPassword"
                                class="text-xs text-indigo-600 hover:text-indigo-500 focus:outline-none"
                            >
                                {{ showPassword ? 'Hide' : 'Show' }} Password
                            </button>
                        </div>
                        
                        <div class="relative">
                            <TextInput
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 pr-10"
                                v-model="form.password"
                                required
                                autocomplete="new-password"
                                placeholder="Create a password"
                            />
                            <div 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer"
                                @click="showPassword = !showPassword"
                            >
                                <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-400 hover:text-gray-600">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-indigo-500 hover:text-indigo-700">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </div>
                        </div>
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="space-y-1">
                        <InputLabel for="password_confirmation" value="Confirm Password" />
                        <div class="relative">
                            <TextInput
                                id="password_confirmation"
                                :type="showPassword ? 'text' : 'password'"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 pr-10"
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                                placeholder="Confirm your password"
                            />
                        </div>
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <PrimaryButton
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                        :class="{ 'opacity-75 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Creating Account...
                        </span>
                        <span v-else>Create Account</span>
                    </PrimaryButton>
                </form>

                <div class="relative mt-6">
                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="bg-gray-50 px-2 text-gray-500">Or sign up with</span>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="/auth/google" 
                       class="flex w-full items-center justify-center gap-3 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-300 transition-all">
                        <img class="h-5 w-5" src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google logo" />
                        Sign up with Google
                    </a>
                </div>

            </div>
        </div>
    </div>
</template>