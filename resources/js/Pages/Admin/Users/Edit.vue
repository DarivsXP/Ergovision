<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

// 1. We accept 'userToEdit' from the Controller
const props = defineProps({
    userToEdit: Object,
});

// 2. We must use 'props.userToEdit' to fill the form
const form = useForm({
    name: props.userToEdit.name,       // <--- FIXED (Was props.user.name)
    email: props.userToEdit.email,     // <--- FIXED
    is_admin: props.userToEdit.is_admin ? '1' : '0', 
});

const submit = () => {
    // 3. Debug using the correct variable
    console.log("Submitting ID:", props.userToEdit?.id);

    // 4. Check the correct ID
    if (!props.userToEdit?.id) {
        alert("Error: User ID is missing!");
        return;
    }

    // 5. Submit using the correct ID
    form.patch(route('admin.users.update', props.userToEdit.id), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Edit User" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-2xl text-white tracking-tighter uppercase">
                Edit <span class="text-indigo-500">User</span>
            </h2>
        </template>

        <div class="py-12 min-h-screen bg-[#020617]">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-slate-900 border border-slate-800 p-8 rounded-3xl shadow-2xl">
                    
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="block text-slate-400 text-xs font-bold uppercase tracking-widest mb-2">Name</label>
                            <input v-model="form.name" type="text" class="w-full bg-slate-950 border border-slate-800 text-white rounded-lg p-3 focus:ring-indigo-500 focus:border-indigo-500" required />
                            <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block text-slate-400 text-xs font-bold uppercase tracking-widest mb-2">Email</label>
                            <input v-model="form.email" type="email" class="w-full bg-slate-950 border border-slate-800 text-white rounded-lg p-3 focus:ring-indigo-500 focus:border-indigo-500" required />
                            <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                        </div>

                        <div>
                            <label class="block text-slate-400 text-xs font-bold uppercase tracking-widest mb-2">Role</label>
                            <select v-model="form.is_admin" class="w-full bg-slate-950 border border-slate-800 text-white rounded-lg p-3 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="0">Standard User</option>
                                <option value="1">Administrator</option>
                            </select>
                        </div>

                        <div class="flex justify-end gap-4 pt-4">
                            <Link :href="route('admin.users.index')" class="px-4 py-2 text-slate-400 hover:text-white text-xs font-bold uppercase tracking-widest transition">
                                Cancel
                            </Link>
                            <button :disabled="form.processing" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold uppercase tracking-widest transition shadow-lg shadow-indigo-500/20">
                                Save Changes
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>