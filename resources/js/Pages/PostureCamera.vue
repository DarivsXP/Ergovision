<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { usePostureEngine } from '@/Composables/usePostureEngine';
import SessionFeedbackModal from '@/Components/SessionFeedbackModal.vue'; 
import { driver } from "driver.js";
import "driver.js/dist/driver.css";

const videoRef = ref(null);
const toast = useToast();

const {
    isCameraOn, isDetected, isCalibrated, isLocking, calibrationCountdown,
    currentScore, isSlouching, statusMessage, formattedDuration, angles,
    needsPresenceCheck, confirmPresence,
    onResults, triggerCalibration, sessionDurationSecs, cleanup
} = usePostureEngine(videoRef, toast);

let pose = null;
let camera = null;

const showFeedbackModal = ref(false);

const startCamera = async () => {
    try {
        const isMobile = window.innerWidth < 768;
        
        camera = new window.Camera(videoRef.value, {
            onFrame: async () => { if (pose) await pose.send({ image: videoRef.value }); },
            width: isMobile ? 480 : 640,
            height: isMobile ? 640 : 480
        });
        
        camera.start();
        isCameraOn.value = true;
        sessionDurationSecs.value = 0;
    } catch (e) { 
        toast.error("Camera Access Denied"); 
    }
};

const stopCamera = () => {
    if (camera) camera.stop();
    isCameraOn.value = false;
    cleanup();

    if (sessionDurationSecs.value >= 60) {
        showFeedbackModal.value = true;
    } else {
        router.visit(route('dashboard'));
    }
};

const handleFeedbackSubmission = (formData) => {
    formData.post(route('feedback.store'), {
        onSuccess: () => {
            showFeedbackModal.value = false;
        }
    });
};

const skipFeedback = () => {
    showFeedbackModal.value = false;
    router.visit(route('dashboard'));
};

onUnmounted(() => {
    if (camera) camera.stop();
    cleanup();
});

// CAMERA TOUR LOGIC
const cameraTour = driver({
    showProgress: true,
    animate: true,
    overlayColor: 'rgba(2, 6, 23, 0.95)',
    allowClose: false,
    nextBtnText: 'Next →',
    prevBtnText: '← Back',
    doneBtnText: 'Got It!',
    steps: [
        {
            popover: {
                title: 'Camera Positioning',
                description: `
                    <div class="mt-4 leading-relaxed">
                        <p class="mb-3">For the AI to accurately map your spine, position your camera at a <strong class="text-indigo-400">45° to 90° angle</strong> (side-profile).</p>
                        <p>Do not face the camera directly. Face your monitor normally, and let the camera view you from the side.</p>
                    </div>
                `,
            }
        },
        {
            popover: {
                title: 'The 3 Critical Points',
                description: `
                    <div class="mt-4 text-sm">
                        <p class="mb-3">The AI skeleton needs a clear line of sight to three body parts:</p>
                        <ul class="list-disc pl-5 font-bold text-white mb-4 space-y-1">
                            <li>Your Ear</li>
                            <li>Your Shoulder</li>
                            <li>Your Hip</li>
                        </ul>
                        <div class="p-3 bg-indigo-500/10 border border-indigo-500/20 rounded-xl text-xs text-indigo-300">
                            <strong>Tip:</strong> Tuck away loose hair and avoid highly baggy clothing that hides your neck or hips.
                        </div>
                    </div>
                `,
            }
        },
        {
            element: '#tour-step-engine',
            popover: {
                title: 'Start the Engine',
                description: '<div class="mt-2 text-sm">Click <strong class="text-indigo-400">Initialize Engine</strong> to turn on your webcam and let the AI find your skeleton.</div>',
                side: "top", align: 'center'
            }
        },
        {
            popover: {
                title: 'Locking Your Baseline',
                description: `
                    <div class="mt-4 text-sm">
                        <p class="mb-3">Once the camera is on, sit in your absolute best posture.</p>
                        <p>Click <strong class="text-white">Start 5s Lock</strong> and hold perfectly still while the AI calculates your unique ergonomic baseline.</p>
                    </div>
                `,
            }
        }
    ],
    onDestroyed: () => {
        localStorage.setItem('has_seen_camera_tour', 'true');
    }
});

onMounted(() => {
    if (!localStorage.getItem('has_seen_camera_tour')) {
        setTimeout(() => cameraTour.drive(), 800); 
    }
    
    if (window.Pose) {
        pose = new window.Pose({ locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/pose/${file}` });
        pose.setOptions({ modelComplexity: 1, smoothLandmarks: true, minDetectionConfidence: 0.5 });
        pose.onResults(onResults);
    }
});

const replayCameraTour = () => {
    cameraTour.drive();
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-6 md:py-12 bg-[#020617] min-h-screen">
            <div class="max-w-5xl mx-auto flex flex-col items-center px-4">
                
                <div class="w-full flex justify-between items-end mb-8">
                    <div>
                        <div class="flex items-center gap-4 mb-1">
                            <h2 class="text-3xl font-black text-white tracking-tighter uppercase">ERGO<span class="text-indigo-500">VISION</span></h2>
                            
                            <button @click="replayCameraTour" class="hidden sm:flex items-center text-[10px] font-black text-slate-500 hover:text-indigo-400 uppercase tracking-widest transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Replay Setup Guide
                            </button>
                        </div>
                        <p class="text-slate-400 text-[10px] uppercase font-bold tracking-widest">{{ isCalibrated ? 'ACTIVE' : 'READY' }}</p>
                    </div>
                    
                    <div class="flex flex-col items-end">
                        <button @click="replayCameraTour" class="sm:hidden mb-2 text-[10px] font-black text-slate-500 uppercase tracking-widest">
                            Setup Guide
                        </button>
                        <div v-if="isCameraOn" class="text-right tabular-nums">
                            <p class="text-[10px] font-black text-indigo-400 uppercase mb-1">Active Duration</p>
                            <p class="text-2xl font-mono font-bold text-white">{{ formattedDuration }}</p>
                        </div>
                    </div>
                </div>

                <div class="relative w-full aspect-[3/4] md:aspect-video bg-black rounded-[3rem] overflow-hidden shadow-2xl border border-white/5 transition-all duration-500"
                     :class="{
                        'ring-4 ring-emerald-500/50': statusMessage === 'Stable State',
                        'ring-4 ring-amber-500/50 animate-pulse': statusMessage === 'WARNING: DRIFT DETECTED',
                        'ring-4 ring-rose-500 shadow-rose-900/40': statusMessage === 'ALERT: SUSTAINED SLOUCH',
                        'ring-8 ring-rose-700 shadow-2xl': statusMessage === 'CRITICAL: POSTURAL RESET REQUIRED',
                        'opacity-50 grayscale': !isDetected || statusMessage === 'Standing - Paused'
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

                    <div v-if="needsPresenceCheck" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-950/90 backdrop-blur-md z-50 p-8 text-center">
                        <div class="max-w-md w-full bg-slate-900 border border-white/10 rounded-[2rem] p-8 shadow-[0_0_50px_rgba(79,70,229,0.15)]">
                            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-400">Presence Check</p>
                            <h3 class="mt-3 text-2xl font-black text-white uppercase tracking-widest">Are you still there?</h3>
                            <p class="mt-3 text-[11px] text-slate-400 leading-relaxed">
                                We paused monitoring after a long sustained slouch to avoid alert fatigue.
                                If you're still present, resume when you're ready.
                            </p>

                            <div class="mt-6 flex gap-3">
                                <button
                                    type="button"
                                    @click="stopCamera"
                                    class="flex-1 py-4 rounded-xl border border-white/10 text-slate-300 hover:text-white hover:bg-slate-800 text-[10px] font-black uppercase tracking-widest transition-all"
                                >
                                    End Session
                                </button>
                                <button
                                    type="button"
                                    @click="confirmPresence"
                                    class="flex-1 py-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-[10px] font-black uppercase tracking-widest transition-all shadow-[0_0_20px_rgba(79,70,229,0.3)]"
                                >
                                    Resume
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="isCameraOn && isCalibrated && (!isDetected || statusMessage === 'Standing - Paused')" class="absolute inset-0 flex flex-col items-center justify-center bg-black/60 backdrop-blur-sm z-40">
                        <svg class="animate-spin h-10 w-10 text-indigo-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-white font-black text-xs uppercase tracking-[0.3em]">Tracking Paused</p>
                        <p class="text-slate-400 text-[10px] mt-2">Waiting for user to return...</p>
                    </div>

                    <div v-if="isCalibrated" class="absolute inset-0 p-8 flex flex-col justify-between pointer-events-none transition-opacity duration-300"
                         :class="!isDetected || statusMessage === 'Standing - Paused' ? 'opacity-0' : 'opacity-100'">
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
                    <button id="tour-step-engine" v-if="!isCameraOn" @click="startCamera" class="w-full py-5 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-[2rem] shadow-2xl uppercase tracking-widest transition-all">
                        Initialize Engine
                    </button>
                    <div v-else class="flex gap-4">
                        <button @click="stopCamera" class="flex-1 py-4 bg-slate-800 text-white font-black rounded-2xl border border-white/5 uppercase text-xs hover:bg-rose-600 transition-colors">End Session</button>
                        <button @click="triggerCalibration" class="flex-[2] py-4 bg-indigo-600 text-white font-black rounded-2xl shadow-xl uppercase text-xs hover:bg-indigo-500 transition-colors">
                            {{ isCalibrated ? 'Recalibrate' : 'Start 5s Lock' }}
                        </button>
                    </div>
                </div>
            </div>
            
            <SessionFeedbackModal 
                :show="showFeedbackModal" 
                @close="skipFeedback" 
                @submit="handleFeedbackSubmission" 
            />

        </div>
    </AuthenticatedLayout>
</template>