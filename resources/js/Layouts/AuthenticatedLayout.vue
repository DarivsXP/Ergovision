<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ToastList from '@/Components/ToastList.vue';

const page = usePage();

const firstName = computed(() => {
    const user = page.props.auth?.user;

    if (!user) return 'Guest';

    const placeholders = ['User', 'Admin', 'Guest', 'Account'];
    const hasRealName = user.name && !placeholders.includes(user.name);

    const displayName = hasRealName 
        ? user.name 
        : (user.email ? user.email.split('@')[0] : 'Member');

    const name = displayName.split(/[ ._]/)[0]; 
    return name.charAt(0).toUpperCase() + name.slice(1);
});


const isAdminPage = computed(() => {
    try { return route().current('admin.*'); } catch { return false; }
});
</script>

<template>
    <div class="min-h-screen bg-[#020617] text-slate-200 selection:bg-indigo-500/30">
        <ToastList />

        <div class="flex flex-col min-h-screen">
            <nav class="sticky top-0 z-50 border-b border-white/5 bg-slate-900/60 backdrop-blur-xl">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-20 justify-between items-center">
                        
                        <div class="flex items-center gap-10">
                            <Link :href="isAdminPage ? route('admin.dashboard') : route('dashboard')" class="flex items-center gap-3 group outline-none">
                                <div class="h-12 w-12 rounded-xl bg-indigo-600 flex items-center justify-center shadow-2xl shadow-indigo-500/40">
                                    <ApplicationLogo class="h-8 w-auto fill-current text-white" />
                                </div>
                                <span class="font-black text-2xl tracking-tighter text-white uppercase">
                                    ERGO<span class="text-indigo-500">VISION</span>
                                </span>
                            </Link>

                            <div class="hidden sm:flex items-center gap-2">
                                <template v-if="isAdminPage">
                                    <Link :href="route('admin.dashboard')" 
                                          class="px-5 py-2 rounded-full text-xs font-black uppercase tracking-widest"
                                          :class="route().current('admin.dashboard') ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:text-white'">
                                        Analytics
                                    </Link>
                                </template>
                                <template v-else>
                                    <Link :href="route('dashboard')" 
                                          class="px-5 py-2 rounded-full text-xs font-black uppercase tracking-widest"
                                          :class="route().current('dashboard') ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:text-white'">
                                        Dashboard
                                    </Link>
                                    <Link :href="route('camera')" 
                                          class="px-5 py-2 rounded-full text-xs font-black uppercase tracking-widest"
                                          :class="route().current('camera') ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:text-white'">
                                        Monitor
                                    </Link>
                                </template>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <Link v-if="page.props.auth?.user?.is_admin" 
                                  :href="isAdminPage ? route('dashboard') : route('admin.dashboard')"
                                  class="text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-lg border border-indigo-500/30 text-indigo-400 hover:bg-indigo-500/10">
                                {{ isAdminPage ? 'User Mode' : 'Admin Mode' }}
                            </Link>

                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="flex items-center gap-3 p-1.5 pr-4 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition-all focus:outline-none">
                                        <div class="h-8 w-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-xs font-black text-white uppercase">
                                            {{ firstName.charAt(0) }}
                                        </div>
                                        <span class="text-sm font-bold text-slate-200">{{ firstName }}</span>
                                    </button>
                                </template>

                                <template #content>
                                    <DropdownLink :href="route('profile.edit')" class="text-slate-400 hover:text-white px-4 py-3">
                                        Profile Settings
                                    </DropdownLink>
                                    <div class="h-px bg-white/5 my-1 mx-2"></div>
                                    <DropdownLink :href="route('logout')" method="post" as="button" class="w-full text-left text-rose-400 hover:bg-rose-500/10 px-4 py-3">
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
        </div>
    </div>
</template>