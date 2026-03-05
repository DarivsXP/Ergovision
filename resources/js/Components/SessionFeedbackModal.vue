<script setup>
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    // We will pass the session ID here later so the backend knows which session this feedback belongs to
    sessionId: {
        type: [Number, String],
        default: null
    }
});

const emit = defineEmits(['close', 'submit']);

const form = useForm({
    fatigue_level: 3, // Default to neutral
    accuracy_rating: 4, // Default to reasonably accurate
    comments: '',
});

const submitFeedback = () => {
    // For now, we emit the data back to the parent camera page. 
    // Later we can hook this up to form.post(route('feedback.store'))
    emit('submit', form);
};
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#020617]/90 backdrop-blur-md">
            
            <div class="max-w-md w-full bg-slate-900 border border-white/10 rounded-[2rem] shadow-[0_0_50px_rgba(79,70,229,0.15)] overflow-hidden relative">
                
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-1 bg-gradient-to-r from-transparent via-indigo-500 to-transparent opacity-50"></div>

                <div class="p-8">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-black text-white uppercase tracking-widest mb-2">Session Complete</h2>
                        <p class="text-[10px] font-bold text-indigo-400 uppercase tracking-[0.2em]">Log your subjective telemetry</p>
                    </div>

                    <form @submit.prevent="submitFeedback" class="space-y-8">
                        
                        <div>
                            <div class="flex justify-between items-end mb-3">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Perceived Physical Fatigue</label>
                                <span class="text-lg font-black text-white tabular-nums leading-none">{{ form.fatigue_level }} <span class="text-[10px] text-slate-500">/ 5</span></span>
                            </div>
                            
                            <input type="range" v-model="form.fatigue_level" min="1" max="5" step="1"
                                class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-6 [&::-webkit-slider-thumb]:h-6 [&::-webkit-slider-thumb]:bg-indigo-500 [&::-webkit-slider-thumb]:rounded-full hover:[&::-webkit-slider-thumb]:scale-110 transition-all" />
                            
                            <div class="flex justify-between text-[9px] font-bold text-slate-500 uppercase tracking-widest mt-2">
                                <span :class="{'text-emerald-400': form.fatigue_level == 1}">1 - Energetic</span>
                                <span :class="{'text-rose-400': form.fatigue_level == 5}">5 - Exhausted</span>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-white/5">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 text-center">How accurate were the posture alerts?</label>
                            
                            <div class="flex justify-between gap-2">
                                <label v-for="rating in 5" :key="rating" class="flex-1 cursor-pointer group">
                                    <input type="radio" v-model="form.accuracy_rating" :value="rating" class="sr-only peer">
                                    <div class="py-3 text-center rounded-xl border border-white/5 text-slate-500 font-black text-sm transition-all peer-checked:bg-indigo-500 peer-checked:text-white peer-checked:border-indigo-400 peer-checked:shadow-[0_0_15px_rgba(79,70,229,0.4)] group-hover:border-white/20">
                                        {{ rating }}
                                    </div>
                                </label>
                            </div>
                            <div class="flex justify-between text-[9px] font-bold text-slate-500 uppercase tracking-widest mt-2 px-1">
                                <span>Inaccurate</span>
                                <span>Perfect</span>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-white/5">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Additional Context (Optional)</label>
                            <textarea v-model="form.comments" rows="2" placeholder="e.g., The system missed my slouching when I leaned left..."
                                class="w-full bg-slate-950 border border-white/5 rounded-xl px-4 py-3 text-[11px] text-white placeholder-slate-600 focus:border-indigo-500 focus:ring-indigo-500 transition-all resize-none"></textarea>
                        </div>

                        <div class="pt-4 flex gap-3">
                            <button type="button" @click="$emit('close')"
                                class="px-6 py-4 rounded-xl border border-white/10 text-slate-400 hover:text-white hover:bg-slate-800 text-[10px] font-black uppercase tracking-widest transition-all">
                                Skip
                            </button>
                            <PrimaryButton 
                                :disabled="form.processing"
                                class="flex-1 justify-center py-4 bg-indigo-600 hover:bg-indigo-500 font-black text-[10px] uppercase tracking-widest rounded-xl shadow-[0_0_20px_rgba(79,70,229,0.3)] border-none">
                                Submit Telemetry
                            </PrimaryButton>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </Transition>
</template>