<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ToastList from '@/Components/ToastList.vue';

const page = usePage();


// SIMPLIFIED: Just get the name directly from the database
const userName = computed(() => {
    return page.props.auth?.user?.name || 'User';
});

// Get just the first letter for the avatar circle
const userInitial = computed(() => {
    return userName.value.charAt(0).toUpperCase();
});

// Stable Role Logic
const userIsAdmin = computed(() => !!page.props.auth?.user?.is_admin);

const isAdminPage = computed(() => {
    try { 
        return route().current('admin.*'); 
    } catch { 
        return false; 
    }
});

// Routing for primary dashboard
const dashboardRoute = computed(() => {
    return userIsAdmin.value ? route('admin.dashboard') : route('dashboard');
});
</script>

<template>
    <div class="min-h-screen bg-[#020617] text-slate-200 selection:bg-indigo-500/30 font-sans">
        <ToastList />

        <div class="flex flex-col min-h-screen">
            <nav class="sticky top-0 z-50 border-b border-white/5 bg-slate-900/60 backdrop-blur-xl">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-20 justify-between items-center">
                        
                        <div class="flex items-center gap-10">
                            <Link :href="dashboardRoute" class="flex items-center gap-3 group outline-none">
                                <div class="h-12 w-12 rounded-xl bg-indigo-600 flex items-center justify-center shadow-2xl shadow-indigo-500/40 group-hover:bg-indigo-500 transition-colors">
                                    <ApplicationLogo class="h-8 w-auto fill-current text-white" />
                                </div>
                                <span class="font-black text-2xl tracking-tighter text-white uppercase">
                                    ERGO<span class="text-indigo-500">VISION</span>
                                </span>
                            </Link>

                            <div class="hidden sm:flex items-center gap-2">
                                <template v-if="isAdminPage">
                                    <Link :href="route('admin.dashboard')" 
                                          class="px-5 py-2 rounded-full text-xs font-black uppercase tracking-widest transition-all"
                                          :class="route().current('admin.dashboard') ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-500 hover:text-white'">
                                        Analytics
                                    </Link>
                                    <Link :href="route('admin.users.index')" 
                                          class="px-5 py-2 rounded-full text-xs font-black uppercase tracking-widest transition-all text-slate-500 hover:text-white"
                                          :class="route().current('admin.users.index') ? 'bg-indigo-600 text-white' : ''">
                                        User Management
                                    </Link>
                                </template>

                                <template v-else>
                                    <Link :href="route('dashboard')" 
                                          class="px-5 py-2 rounded-full text-xs font-black uppercase tracking-widest transition-all"
                                          :class="route().current('dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/20' : 'text-slate-500 hover:text-white'">
                                        Dashboard
                                    </Link>
                                    <Link :href="route('camera')" 
                                          class="px-5 py-2 rounded-full text-xs font-black uppercase tracking-widest transition-all"
                                          :class="route().current('camera') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/20' : 'text-slate-500 hover:text-white'">
                                        Monitor
                                    </Link>
                                </template>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <Link v-if="userIsAdmin" 
                                  :href="isAdminPage ? route('dashboard') : route('admin.dashboard')"
                                  class="hidden md:flex items-center gap-2 text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-lg border border-indigo-500/30 text-indigo-400 hover:bg-indigo-500/10 transition-all">
                                {{ isAdminPage ? 'View Site' : 'Admin Area' }}
                            </Link>

                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="flex items-center gap-3 p-1.5 pr-4 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition-all focus:outline-none">
                                        
                                        <div class="h-8 w-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-xs font-black text-white uppercase shadow-lg">
                                            {{ userInitial }}
                                        </div>

                                        <span class="text-sm font-bold text-slate-200">{{ userName }}</span>

                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </template>

                                <template #content>
                                    <div class="px-4 py-2 text-[10px] font-black text-slate-500 uppercase tracking-widest border-b border-white/5 mb-1">
                                        Account Details
                                    </div>
                                    <DropdownLink :href="route('profile.edit')" class="px-4 py-3 text-slate-400 hover:text-white">
                                        Profile Settings
                                    </DropdownLink>
                                    <div class="h-px bg-white/5 my-1 mx-2"></div>
                                    <DropdownLink :href="route('logout')" method="post" as="button" class="w-full text-left text-rose-400 hover:bg-rose-500/10 px-4 py-3 text-[11px] font-black uppercase tracking-widest">
                                        Sign Out
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>
            </nav>

            <main class="flex-1">
                <slot />
            </main>

            <nav class="sm:hidden sticky bottom-0 z-50 bg-slate-900/80 backdrop-blur-xl border-t border-white/5 px-6 py-3">
                <div class="flex justify-around items-center">
                    <Link :href="dashboardRoute" class="p-2" :class="route().current('dashboard') || route().current('admin.dashboard') ? 'text-indigo-500' : 'text-slate-500'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z" /></svg>
                    </Link>
                    <Link v-if="!userIsAdmin" :href="route('camera')" class="p-2" :class="route().current('camera') ? 'text-indigo-500' : 'text-slate-500'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    </Link>
                    <Link :href="route('profile.edit')" class="p-2" :class="route().current('profile.edit') ? 'text-indigo-500' : 'text-slate-500'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </Link>
                </div>
            </nav>
        </div>
    </div>
</template>