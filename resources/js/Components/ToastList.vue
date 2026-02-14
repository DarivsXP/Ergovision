<script setup>
import { useToast } from '@/Composables/useToast';
import { TransitionGroup } from 'vue';

const toast = useToast();

const icons = {
    success: `<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
    error: `<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
    warning: `<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>`,
    info: `<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
};

const colors = {
    success: 'bg-emerald-500 border-emerald-600 shadow-emerald-500/20',
    error: 'bg-rose-500 border-rose-600 shadow-rose-500/20',
    warning: 'bg-amber-500 border-amber-600 shadow-amber-500/20',
    info: 'bg-indigo-500 border-indigo-600 shadow-indigo-500/20'
};
</script>

<template>
    <div class="fixed top-4 right-4 z-[9999] flex flex-col gap-3 w-full max-w-sm pointer-events-none">
        <TransitionGroup 
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div 
                v-for="item in toast.items.value" 
                :key="item.id"
                class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg shadow-lg border ring-1 ring-black/5 flex items-start p-4 backdrop-blur-md"
                :class="[
                    item.type === 'error' ? 'bg-rose-50 border-rose-100' : 
                    item.type === 'success' ? 'bg-emerald-50 border-emerald-100' : 'bg-white border-slate-100'
                ]"
            >
                <div class="flex-shrink-0">
                    <div class="p-1 rounded-full text-white shadow-md" :class="colors[item.type]" v-html="icons[item.type]"></div>
                </div>
                <div class="ml-3 w-0 flex-1 pt-0.5">
                    <p class="text-sm font-bold text-gray-900">{{ item.title }}</p>
                    <p class="mt-1 text-sm text-gray-600">{{ item.message }}</p>
                </div>
                <div class="ml-4 flex flex-shrink-0">
                    <button @click="toast.remove(item.id)" class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">Close</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>