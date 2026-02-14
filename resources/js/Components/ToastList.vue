<script setup>
import { useToast } from '@/Composables/useToast';
import { TransitionGroup } from 'vue';

const { items, remove } = useToast();
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
            <div v-for="item in items" :key="item.id"
                class="pointer-events-auto w-full overflow-hidden rounded-lg shadow-xl border ring-1 ring-black/5 flex items-start p-4 backdrop-blur-md"
                :class="{
                    'bg-white/90 border-slate-200': item.type === 'info',
                    'bg-emerald-50/90 border-emerald-200': item.type === 'success',
                    'bg-rose-50/90 border-rose-200': item.type === 'error',
                    'bg-amber-50/90 border-amber-200': item.type === 'warning',
                }"
            >
                <div class="flex-shrink-0">
                    <span v-if="item.type === 'success'" class="text-emerald-500 font-bold text-xl">✓</span>
                    <span v-if="item.type === 'error'" class="text-rose-500 font-bold text-xl">✕</span>
                    <span v-if="item.type === 'warning'" class="text-amber-500 font-bold text-xl">!</span>
                    <span v-if="item.type === 'info'" class="text-blue-500 font-bold text-xl">i</span>
                </div>
                
                <div class="ml-3 w-0 flex-1">
                    <p class="text-sm font-bold text-slate-900">{{ item.title }}</p>
                    <p class="mt-1 text-sm text-slate-600 leading-tight">{{ item.message }}</p>
                </div>

                <button @click="remove(item.id)" class="ml-4 text-slate-400 hover:text-slate-900 transition-colors">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>