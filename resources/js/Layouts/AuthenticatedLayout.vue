<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';
import ToastList from '@/Components/ToastList.vue'; // <--- Added Toast Component

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div>
        <ToastList />

        <div class="min-h-screen bg-slate-900">
            
            <nav class="sticky top-0 z-50 border-b border-white/10 bg-slate-900/80 backdrop-blur-md transition-all duration-300">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')" class="group flex items-center gap-2">
                                    <div class="relative flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 transition-transform group-hover:scale-110 group-hover:rotate-3 shadow-lg shadow-indigo-500/20">
                                        <ApplicationLogo class="block h-5 w-auto fill-current text-white" />
                                    </div>
                                    <span class="font-bold text-xl tracking-tight text-white group-hover:text-indigo-400 transition-colors">
                                        Ergo<span class="text-indigo-500">Vision</span>
                                    </span>
                                </Link>
                            </div>

                            <div class="hidden space-x-4 sm:-my-px sm:ms-10 sm:flex items-center">
                                
                                <Link :href="route('dashboard')" 
                                      class="px-3 py-2 rounded-md text-sm font-medium transition-all duration-200"
                                      :class="route().current('dashboard') 
                                          ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20' 
                                          : 'text-slate-300 hover:bg-white/10 hover:text-white'">
                                    Dashboard
                                </Link>

                                <Link :href="route('camera')" 
                                      class="px-3 py-2 rounded-md text-sm font-medium transition-all duration-200"
                                      :class="route().current('camera') 
                                          ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20' 
                                          : 'text-slate-300 hover:bg-white/10 hover:text-white'">
                                    Start Monitor
                                </Link>

                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-sm font-medium text-slate-300 transition duration-150 ease-in-out hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-slate-900">
                                            
                                            <div class="h-6 w-6 rounded-full bg-indigo-500 flex items-center justify-center text-xs font-bold text-white">
                                                {{ $page.props.auth?.user?.name?.charAt(0) || 'U' }}
                                            </div>

                                            <span>{{ $page.props.auth?.user?.name || 'User' }}</span>

                                            <svg class="-me-0.5 ms-1 h-4 w-4 opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </template>

                                    <template #content>
                                        <div class="py-1 bg-slate-800 border border-white/10 rounded-md shadow-xl">
                                            <DropdownLink :href="route('profile.edit')" class="text-slate-300 hover:bg-slate-700 hover:text-white">
                                                Profile
                                            </DropdownLink>
                                            <DropdownLink :href="route('logout')" method="post" as="button" class="text-slate-300 hover:bg-slate-700 hover:text-white">
                                                Log Out
                                            </DropdownLink>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-md p-2 text-slate-400 transition duration-150 ease-in-out hover:bg-white/10 hover:text-white focus:bg-white/10 focus:text-white focus:outline-none"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden bg-slate-900 border-b border-white/10">
                    <div class="space-y-1 pb-3 pt-2">
                        <Link :href="route('dashboard')"
                              class="block w-full border-l-4 py-2 pl-3 pr-4 text-base font-medium transition duration-150 ease-in-out focus:outline-none"
                              :class="route().current('dashboard') 
                                  ? 'border-indigo-400 bg-indigo-900/50 text-indigo-300' 
                                  : 'border-transparent text-slate-400 hover:border-slate-300 hover:bg-slate-800 hover:text-white'">
                            Dashboard
                        </Link>
                        
                        <Link :href="route('camera')"
                              class="block w-full border-l-4 py-2 pl-3 pr-4 text-base font-medium transition duration-150 ease-in-out focus:outline-none"
                              :class="route().current('camera') 
                                  ? 'border-indigo-400 bg-indigo-900/50 text-indigo-300' 
                                  : 'border-transparent text-slate-400 hover:border-slate-300 hover:bg-slate-800 hover:text-white'">
                            Start Monitor
                        </Link>
                    </div>

                    <div class="border-t border-white/10 pb-1 pt-4 bg-slate-800/50">
                        <div class="px-4">
                            <div class="text-base font-medium text-white">
                                {{ $page.props.auth?.user?.name || 'User' }}
                            </div>
                            <div class="text-sm font-medium text-slate-400">
                                {{ $page.props.auth?.user?.email || '' }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <Link :href="route('profile.edit')"
                                  class="block w-full px-4 py-2 text-start text-base font-medium text-slate-400 transition duration-150 ease-in-out hover:bg-slate-800 hover:text-white focus:bg-slate-800 focus:text-white focus:outline-none">
                                Profile
                            </Link>

                            <Link :href="route('logout')" 
                                  method="post" 
                                  as="button"
                                  class="block w-full px-4 py-2 text-start text-base font-medium text-slate-400 transition duration-150 ease-in-out hover:bg-slate-800 hover:text-white focus:bg-slate-800 focus:text-white focus:outline-none">
                                Log Out
                            </Link>
                        </div>
                    </div>
                </div>
            </nav>

            <header class="bg-slate-900/50 shadow border-b border-white/5" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <h2 class="text-xl font-semibold leading-tight text-white">
                        <slot name="header" />
                    </h2>
                </div>
            </header>

            <main>
                <slot />
            </main>
        </div>
    </div>
</template>