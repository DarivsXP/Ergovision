<script setup>
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';

const { items, remove, add } = useToast(); 
const page = usePage();

const theme = {
    success: 'bg-emerald-50 border-emerald-500 text-emerald-900 shadow-emerald-500/10',
    error: 'bg-rose-50 border-rose-500 text-rose-900 shadow-rose-500/10',
    warning: 'bg-amber-50 border-amber-500 text-amber-900 shadow-amber-500/10',
    info: 'bg-blue-50 border-blue-500 text-blue-900 shadow-blue-500/10',
};

watch(() => page.props.flash, (flash) => {
    // 1. SAFETY CHECK (The Fix): Stop if flash is null/undefined
    if (!flash) return;

    // 2. Use Optional Chaining (?.) for extra safety
    if (flash.message) {
        add({ 
            title: 'Success', 
            message: flash.message, 
            type: 'success' 
        });
    }
    
    if (flash.error) {
        add({ 
            title: 'Error', 
            message: flash.error, 
            type: 'error' 
        });
    }

    if (flash.warning) {
        add({ 
            title: 'Warning', 
            message: flash.warning, 
            type: 'warning' 
        });
    }
}, { deep: true, immediate: true });
</script>

    <template>
        <div class="fixed top-6 right-6 z-[9999] flex flex-col gap-4 w-full max-w-sm pointer-events-none">
            
            <TransitionGroup 
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-x-12 opacity-0"
                enter-to-class="translate-x-0 opacity-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div 
                    v-for="item in items" 
                    :key="item.id"
                    class="pointer-events-auto flex w-full overflow-hidden rounded-xl border-l-4 p-4 shadow-2xl backdrop-blur-md"
                    :class="theme[item.type]"
                >
                    <div class="flex-shrink-0 mt-0.5">
                        <span v-if="item.type === 'success'" class="text-emerald-600 font-bold">✓</span>
                        <span v-if="item.type === 'error'" class="text-rose-600 font-bold">✕</span>
                        <span v-if="item.type === 'warning'" class="text-amber-600 font-bold">!</span>
                        <span v-if="item.type === 'info'" class="text-blue-600 font-bold">i</span>
                    </div>

                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-black uppercase tracking-tight">{{ item.title }}</h3>
                        <p class="mt-1 text-xs opacity-90 leading-relaxed">{{ item.message }}</p>
                    </div>

                    <button 
                        @click="remove(item.id)" 
                        class="ml-4 flex-shrink-0 text-gray-400 hover:text-gray-900 transition-colors"
                    >
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" />
                        </svg>
                    </button>
                </div>
            </TransitionGroup>

        </div>
    </template>

    <style scoped>
    /* Ensure the list stacks correctly */
    .v-move {
        transition: transform 0.3s ease;
    }
    </style>