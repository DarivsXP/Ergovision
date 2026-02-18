<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue'; // <--- Import ref and watch

const props = defineProps({
    users: Object,
    filters: Object // <--- Accept the filters prop from Laravel
});

// 1. Initialize search with the existing value (if any)
const search = ref(props.filters?.search || '');

// 2. Watch for changes (with a small delay to avoid lag)
let timeout = null;

watch(search, (value) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('admin.users.index'), { search: value }, {
            preserveState: true, // Don't reload the page completely
            replace: true,       // Don't clutter browser history
            preserveScroll: true // Stay at the same scroll position
        });
    }, 300); // Wait 300ms after you stop typing
});

// ... keep your deleteUser function here ...
const deleteUser = (user) => {
    if (confirm(`Are you sure you want to delete ${user.name}?`)) {
        router.delete(route('admin.users.destroy', user.id));
    }
};
</script>

<template>
    <Head title="User Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-black text-2xl text-white tracking-tighter uppercase">
                    User <span class="text-indigo-500">Management</span>
                </h2>
                
                <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold uppercase tracking-widest transition shadow-lg shadow-indigo-500/20">
                    + Add New User
                </button>
            </div>
        </template>

        <div class="py-12 min-h-screen bg-[#020617]">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-slate-900 border border-slate-800 rounded-[2rem] overflow-hidden shadow-2xl">
                    <div class="p-6 border-b border-slate-800 flex flex-col md:flex-row justify-between md:items-center gap-4">
                        <h4 class="text-white font-bold tracking-tight">System Roster ({{ users.total }})</h4>
                        
                        <div class="relative">
                            <input v-model="search" 
       type="text" 
       placeholder="Search users..." 
       class="bg-slate-950 border border-slate-800 text-slate-300 text-sm rounded-full focus:ring-indigo-500 focus:border-indigo-500 block w-64 pl-4 p-2.5 placeholder-slate-600 transition">
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-950/50">
                                <tr class="text-slate-500 text-[10px] uppercase font-bold tracking-widest">
                                    <th class="p-5">ID</th>
                                    <th class="p-5">Name</th>
                                    <th class="p-5">Email</th>
                                    <th class="p-5">Role</th>
                                    <th class="p-5">Registered</th>
                                    <th class="p-5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/50">
                                <tr v-for="user in users.data" :key="user.id" class="hover:bg-white/[0.02] transition group">
                                    <td class="p-5 text-slate-600 font-mono text-xs">#{{ user.id }}</td>
                                    
                                    <td class="p-5">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-full bg-gradient-to-br from-slate-700 to-slate-800 flex items-center justify-center text-xs font-bold text-white shadow-inner">
                                                {{ user.name.charAt(0) }}
                                            </div>
                                            <span class="text-white font-medium group-hover:text-indigo-400 transition">{{ user.name }}</span>
                                        </div>
                                    </td>
                                    
                                    <td class="p-5 text-slate-400 font-mono text-xs">{{ user.email }}</td>
                                    
                                    <td class="p-5">
                                        <span v-if="user.is_admin" class="px-2 py-1 rounded bg-indigo-500/10 text-indigo-400 text-[10px] font-black uppercase tracking-widest border border-indigo-500/20">
                                            Admin
                                        </span>
                                        <span v-else class="px-2 py-1 rounded bg-slate-800 text-slate-400 text-[10px] font-bold uppercase tracking-widest border border-slate-700">
                                            User
                                        </span>
                                    </td>
                                    
                                    <td class="p-5 text-slate-500 text-xs">{{ new Date(user.created_at).toLocaleDateString() }}</td>
                                    
                                    <td class="p-5 text-right">
                                        <Link :href="route('admin.users.edit', user.id)" class="text-indigo-400 hover:text-white transition font-bold text-xs uppercase tracking-tighter mr-4">
                                            Edit
                                        </Link>

                                        <button @click="deleteUser(user)" class="text-rose-500 hover:text-rose-400 transition font-bold text-xs uppercase tracking-tighter">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 border-t border-slate-800 flex justify-center">
                        <div class="flex gap-2">
                            <Link v-for="link in users.links" 
                                  :key="link.label"
                                  :href="link.url ?? '#'" 
                                  v-html="link.label"
                                  class="px-3 py-1 rounded-md text-xs font-bold transition"
                                  :class="link.active ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:bg-slate-800'"
                            />
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>