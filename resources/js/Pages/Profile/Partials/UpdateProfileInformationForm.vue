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
    user_type: user.user_type || '', 
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-bold text-white">Profile Information</h2>
            <p class="mt-1 text-sm text-slate-400">
                Update your account's profile information and email address.
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="mt-6 space-y-6">
            <div>
                <InputLabel for="name" value="Name" class="text-slate-300" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full bg-slate-950 text-white border-slate-800 focus:border-indigo-500 focus:ring-indigo-500"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" class="text-slate-300" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full bg-slate-950 text-white border-slate-800 focus:border-indigo-500 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    :disabled="!!user.google_id"
                />
                <InputError class="mt-2" :message="form.errors.email" />
                
                <p v-if="user.google_id" class="text-xs text-indigo-400 mt-2 font-mono">
                    * Managed via Google Login
                </p>
            </div>

            <div>
                <InputLabel for="user_type" value="I am a..." class="text-slate-300" />
                <select
                    id="user_type"
                    class="mt-1 block w-full bg-slate-950 text-white border-slate-800 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    v-model="form.user_type"
                >
                    <option value="" disabled>Select your role</option>
                    <option value="student">Student</option>
                    <option value="worker">Office Worker</option>
                    <option value="other">Other</option>
                </select>
                <InputError class="mt-2" :message="form.errors.user_type" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="text-sm mt-2 text-slate-200">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="underline text-sm text-indigo-400 hover:text-indigo-300 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 font-medium text-sm text-emerald-400"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-500 border-none">Save Changes</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-emerald-400 font-bold">Saved.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>