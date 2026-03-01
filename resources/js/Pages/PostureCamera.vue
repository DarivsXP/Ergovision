<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onMounted, onUnmounted } from 'vue';
import { useToast } from '@/Composables/useToast';
import { usePostureEngine } from '@/Composables/usePostureEngine';

const videoRef = ref(null);
const toast = useToast();

const {
    isCameraOn, isDetected, isCalibrated, isLocking, calibrationCountdown,
    currentScore, isSlouching, statusMessage, formattedDuration, angles,
    onResults, triggerCalibration, sessionDurationSecs, durationInterval, cleanup
} = usePostureEngine(videoRef, toast);

let pose = null;
let camera = null;

onMounted(() => {
    if (window.Pose) {
        pose = new window.Pose({ locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/pose/${file}` });
        pose.setOptions({ modelComplexity: 1, smoothLandmarks: true, minDetectionConfidence: 0.5 });
        pose.onResults(onResults);
    }
});

const startCamera = async () => {
    try {
        camera = new window.Camera(videoRef.value, {
            onFrame: async () => { if (pose) await pose.send({ image: videoRef.value }); },
            width: 640, height: 480
        });
        camera.start();
        isCameraOn.value = true;
        sessionDurationSecs.value = 0;
        // Start the timer
        setInterval(() => { if (isCameraOn.value) sessionDurationSecs.value++; }, 1000);
    } catch (e) { toast.error("Camera Access Denied"); }
};

const stopCamera = () => {
    if (camera) camera.stop();
    isCameraOn.value = false;
    cleanup();
};

onUnmounted(() => stopCamera());
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-6 md:py-12 bg-slate-900 min-h-screen">
            <div class="max-w-5xl mx-auto flex flex-col items-center px-4">
                
                <div class="w-full flex justify-between items-end mb-8">
                    <div>
                        <h2 class="text-3xl font-black text-white tracking-tighter uppercase">ERGO<span class="text-indigo-500">VISION</span></h2>
                        <p class="text-slate-400 text-[10px] uppercase font-bold tracking-widest">{{ isCalibrated ? 'ACTIVE' : 'READY' }}</p>
                    </div>
                    <div v-if="isCameraOn" class="text-right tabular-nums">
                        <p class="text-[10px] font-black text-indigo-400 uppercase mb-1">Duration</p>
                        <p class="text-2xl font-mono font-bold text-white">{{ formattedDuration }}</p>
                    </div>
                </div>

                <div class="relative w-full aspect-video bg-black rounded-[3rem] overflow-hidden shadow-2xl border border-white/5"
                     :class="{
                        'ring-4 ring-emerald-500/50': statusMessage === 'Stable State',
                        'ring-4 ring-amber-500/50 animate-pulse': statusMessage === 'WARNING: DRIFT DETECTED',
                        'ring-4 ring-rose-500 shadow-rose-900/40': statusMessage === 'ALERT: SUSTAINED SLOUCH',
                        'ring-8 ring-rose-700 shadow-2xl': statusMessage === 'CRITICAL: POSTURAL RESET REQUIRED'    
                     }">
                    
                    <video ref="videoRef" class="w-full h-full object-cover scale-x-[-1]" autoplay playsinline></video>

                    <div v-if="isLocking" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-950/90 backdrop-blur-md z-50">
                        <h2 class="text-[10rem] font-black leading-none" :class="statusMessage === 'BAD POSTURE DETECTED' ? 'text-rose-500' : 'text-white'">
                            {{ calibrationCountdown }}
                        </h2>
                        <p class="font-black uppercase tracking-[0.3em] text-xs mt-4" :class="statusMessage === 'BAD POSTURE DETECTED' ? 'text-rose-400 animate-pulse' : 'text-amber-400'">
                            {{ statusMessage === 'BAD POSTURE DETECTED' ? 'Sit Straight to Resume' : "Calibrating Baseline..." }}
                        </p>
                    </div>

                    <div v-if="isCalibrated" class="absolute inset-0 p-8 flex flex-col justify-between pointer-events-none">
                        <div class="flex justify-between items-start">
                            <div class="bg-slate-950/80 backdrop-blur-md p-4 rounded-3xl border border-white/10 shadow-2xl">
                                <p class="text-[10px] font-black text-slate-500 uppercase mb-1">Efficiency</p>
                                <div class="text-5xl font-black tabular-nums" :class="currentScore > 85 ? 'text-emerald-400' : (currentScore > 70 ? 'text-amber-400' : 'text-rose-500')">
                                    {{ currentScore }}%
                                </div>
                            </div>
                            <div class="bg-slate-950/80 backdrop-blur-md px-6 py-3 rounded-full border border-white/10 flex items-center gap-3">
                                <span class="h-3 w-3 rounded-full" :class="isSlouching ? 'bg-rose-500 animate-ping' : 'bg-emerald-500'"></span>
                                <span class="text-xs font-black uppercase tracking-widest text-white">{{ statusMessage }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-10 w-full max-w-xl">
                    <button v-if="!isCameraOn" @click="startCamera" class="w-full py-5 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-[2rem] shadow-2xl uppercase tracking-widest">
                        Initialize Engine
                    </button>
                    <div v-else class="flex gap-4">
                        <button @click="stopCamera" class="flex-1 py-4 bg-slate-800 text-white font-black rounded-2xl border border-white/5 uppercase text-xs">End Session</button>
                        <button @click="triggerCalibration" class="flex-[2] py-4 bg-indigo-600 text-white font-black rounded-2xl shadow-xl uppercase text-xs">
                            {{ isCalibrated ? 'Recalibrate' : 'Start 5s Lock' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>