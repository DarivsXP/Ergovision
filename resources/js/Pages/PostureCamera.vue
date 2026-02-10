<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onUnmounted, onMounted } from 'vue';
import { router } from '@inertiajs/vue3'; 
import axios from 'axios';

// --- State Variables ---
const videoRef = ref(null);
const isCameraOn = ref(false); 
const isDetected = ref(false); 
const isCalibrated = ref(false); 
const isLocking = ref(false);    
const calibrationCountdown = ref(0);
const currentScore = ref(100);
const isSlouching = ref(false);
const statusMessage = ref("Waiting for Calibration...");

// Posture Reference Data
const angles = ref({ neck: 0, back: 0 });
const myIdealBack = ref(0);
const myIdealNeck = ref(0);
const calibrationBuffer = ref([]);

// --- Audio ---
const alertSound = new Audio('/sounds/alert.mp3');
const successSound = new Audio('/sounds/success.mp3');

// --- Timers & Session Data ---
let pose = null;
let camera = null;
let chunkStartTime = Date.now();
let lastProcessTime = 0;
let uploadInterval = null;
let slouchStartTime = null; 
let lastNotificationTime = 0;

const sessionData = ref({ scores: [], slouchFrames: 0, totalFrames: 0, alerts: 0 });

onMounted(() => {
    if ("Notification" in window && Notification.permission !== "granted") {
        Notification.requestPermission();
    }

    // Initialize MediaPipe
    if (window.Pose) {
        pose = new window.Pose({
            locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/pose/${file}`,
        });
        pose.setOptions({ 
            modelComplexity: 1, 
            smoothLandmarks: true, 
            minDetectionConfidence: 0.5, 
            minTrackingConfidence: 0.5 
        });
        pose.onResults(onResults);
    } else {
        console.error("MediaPipe Pose not loaded");
        alert("Error: AI Libraries not loaded. Please refresh.");
    }
});

// --- 1. Saving Logic ---
const uploadSessionData = () => {
    if (sessionData.value.totalFrames === 0) return;

    // 1. Calculate Actual Duration
    const now = Date.now();
    // Duration in seconds (Max 30, but simpler to calculate exact diff)
    const actualDuration = Math.round((now - chunkStartTime) / 1000); 
    
    // Reset timer for NEXT chunk immediately
    chunkStartTime = now;

    const count = sessionData.value.scores.length || 1;
    const avgScore = Math.round(sessionData.value.scores.reduce((a, b) => a + b, 0) / count);
    
    // Recalculate slouch duration based on real time, not just frames
    const slouchRatio = sessionData.value.slouchFrames / sessionData.value.totalFrames;
    const slouchSecs = Math.round(slouchRatio * actualDuration);

    const payload = {
        score: avgScore || 100,
        slouch_duration: slouchSecs || 0,
        duration_seconds: actualDuration || 30, // Send the new metric
        alert_count: sessionData.value.alerts || 0
    };

    router.post('/posture-chunks', payload, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            console.log(`Saved ${actualDuration}s chunk`);
            sessionData.value = { scores: [], slouchFrames: 0, totalFrames: 0, alerts: 0 };
        },
        onError: (err) => console.error(err)
    });
};

// --- 2. Adaptive Notification Logic ---
const handleAdaptiveFeedback = (slouching) => {
    if (slouching) {
        if (!slouchStartTime) slouchStartTime = Date.now();
        const duration = (Date.now() - slouchStartTime) / 1000;

        if (duration >= 15) {
            statusMessage.value = "CRITICAL: SIT UP!";
            if (Date.now() - lastNotificationTime > 15000) {
                new Notification("Posture Alert", { body: "Sit up straight!" });
                alertSound.play().catch(() => {});
                sessionData.value.alerts++; 
                lastNotificationTime = Date.now();
            }
        } 
        else if (duration >= 5) {
            statusMessage.value = "Warning: Adjust Posture";
        } 
        else {
            statusMessage.value = "Slouching Detected";
        }
    } else {
        if (slouchStartTime && (Date.now() - slouchStartTime) / 1000 > 5) {
            successSound.play().catch(() => {});
            statusMessage.value = "Great Correction!";
            setTimeout(() => { 
                if(!isSlouching.value) statusMessage.value = "Good Posture"; 
            }, 2000);
        } else {
            statusMessage.value = "Good Posture";
        }
        slouchStartTime = null;
    }
};

// --- 3. Calibration Logic ---
const triggerCalibration = () => {
    if (isLocking.value || isCalibrated.value) return;
    
    // Reset buffer
    calibrationBuffer.value = [];
    isLocking.value = true;
    calibrationCountdown.value = 5;
    statusMessage.value = "Get Ready...";
    
    const timer = setInterval(() => {
        calibrationCountdown.value--;
        if (calibrationCountdown.value <= 0) {
            clearInterval(timer);
            lockNeutralPosition();
        }
    }, 1000);
};

const lockNeutralPosition = () => {
    // If buffer is empty, it means AI wasn't reading data during the countdown
    if (calibrationBuffer.value.length < 1) {
        isLocking.value = false;
        alert("Calibration Failed: AI could not see you clearly. Please ensure good lighting and try again.");
        statusMessage.value = "Calibration Failed";

        chunkStartTime = Date.now(); 
    
        if (uploadInterval) clearInterval(uploadInterval);
        uploadInterval = setInterval(uploadSessionData, 30000);
        return;
    }
    
    // Average buffer for rigid baseline
    myIdealBack.value = calibrationBuffer.value.reduce((a, b) => a + b.back, 0) / calibrationBuffer.value.length;
    myIdealNeck.value = calibrationBuffer.value.reduce((a, b) => a + b.neck, 0) / calibrationBuffer.value.length;
    
    isCalibrated.value = true;
    isLocking.value = false;
    statusMessage.value = "Baseline Locked - Monitoring Started";
    successSound.play();
    
    // START RECORDING
    if (uploadInterval) clearInterval(uploadInterval);
    uploadInterval = setInterval(uploadSessionData, 30000);
};

// --- 4. Main AI Loop ---
const onResults = async (results) => {
    const now = Date.now();
    if (!results.poseLandmarks || results.poseLandmarks.length < 25) {
        isDetected.value = false;
        return;
    }
    isDetected.value = true;

    if (now - lastProcessTime < 200) return;
    lastProcessTime = now;

    try {
        const res = await axios.post('http://127.0.0.1:5000/predict', {
            landmarks: results.poseLandmarks,
            ideal_back: myIdealBack.value,
            ideal_neck: myIdealNeck.value
        });

        angles.value = res.data.angles;

        // Calibration Buffer Logic
        if (isLocking.value) {
            calibrationBuffer.value.push(res.data.angles);
            if (calibrationBuffer.value.length > 15) calibrationBuffer.value.shift();
        }

        // --- MAIN LOGIC ---
        if (isCalibrated.value) {
            currentScore.value = res.data.score;
            
            // 1. AI Opinion (The Physician)
            const aiSaysSlouch = res.data.label === 1;

            // 2. Score Opinion (The Safety Net)
            // If score drops below 75, we consider it a slouch regardless of AI
            const scoreIsFailing = currentScore.value < 75;

            // COMBINED DECISION:
            isSlouching.value = aiSaysSlouch || scoreIsFailing;
            
            handleAdaptiveFeedback(isSlouching.value);

            sessionData.value.scores.push(res.data.score);
            sessionData.value.totalFrames++;
            if (isSlouching.value) sessionData.value.slouchFrames++;
        }
    } catch (err) { console.error("AI Server Error"); }
};

const startCamera = async () => {
    try {
        // Explicitly request camera access first to trigger browser prompt
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        
        // If successful, start MediaPipe
        camera = new window.Camera(videoRef.value, {
            onFrame: async () => { if (pose) await pose.send({ image: videoRef.value }); },
            width: 640, height: 480
        });
        camera.start();
        isCameraOn.value = true;
        isCalibrated.value = false;
    } catch (e) {
        alert("Camera Permission Denied or Not Found.");
        console.error(e);
    }
};

const stopCamera = () => {
    isCameraOn.value = false;
    isCalibrated.value = false;
    isLocking.value = false;
    if (uploadInterval) {
        uploadSessionData(); 
        clearInterval(uploadInterval);
    }
    if (camera) camera.stop();
};

onUnmounted(() => stopCamera());
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-12 bg-slate-900 min-h-screen font-sans selection:bg-indigo-500 selection:text-white">
            <div class="max-w-6xl mx-auto flex flex-col items-center">
                
                <div class="w-full flex justify-between items-end mb-6 px-4">
                    <div>
                        <h2 class="text-3xl font-black text-white tracking-tighter">
                            ERGO<span class="text-indigo-500">VISION</span>
                        </h2>
                        <p class="text-slate-400 text-sm font-mono uppercase tracking-widest">
                            Real-Time Postural Inference Engine
                        </p>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <div class="h-3 w-3 rounded-full animate-pulse" 
                             :class="isCalibrated ? 'bg-emerald-500' : (isCameraOn ? 'bg-amber-500' : 'bg-red-500')">
                        </div>
                        <span class="text-white font-mono text-xs">
                            {{ isCalibrated ? 'SYSTEM ACTIVE' : (isCameraOn ? 'STANDBY' : 'OFFLINE') }}
                        </span>
                    </div>
                </div>

                
                <div class="relative bg-black rounded-3xl overflow-hidden transition-all duration-500 group"
                     :class="[
                        isCalibrated && isSlouching ? 'shadow-[0_0_50px_rgba(239,68,68,0.6)] ring-4 ring-red-500' : 
                        isCalibrated ? 'shadow-[0_0_50px_rgba(16,185,129,0.4)] ring-4 ring-emerald-500' :
                        isLocking ? 'ring-4 ring-amber-400 shadow-[0_0_50px_rgba(251,191,36,0.4)]' :
                        'shadow-2xl ring-1 ring-slate-700'
                     ]"
                     style="width: 720px; height: 540px;">
                    
                    <video ref="videoRef" class="w-full h-full object-cover transform scale-x-[-1]" autoplay playsinline></video>

                    <div v-if="isCameraOn && !isDetected" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <div class="bg-black/40 p-4 rounded-xl backdrop-blur-sm">
                            <svg class="animate-spin h-10 w-10 text-indigo-500 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-white font-mono text-xs uppercase tracking-widest">Detecting Body...</p>
                        </div>
                    </div>

                    <div v-if="isCameraOn && isDetected && !isCalibrated && !isLocking" 
                         class="absolute inset-0 flex flex-col items-center justify-center bg-slate-900/40 z-30 backdrop-blur-[2px]">
                        
                        <div class="bg-black/80 p-8 rounded-3xl border border-white/10 text-center max-w-md shadow-2xl">
                            <div class="text-5xl mb-4">🧘</div>
                            <h3 class="text-white font-bold text-2xl mb-2">Calibration Required</h3>
                            <p class="text-slate-400 text-sm mb-6 leading-relaxed">
                                Sit upright in your "Ideal" posture.<br>
                                Click below to start the 5-second lock.
                            </p>
                            <button @click="triggerCalibration" 
                                    class="w-full py-4 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white font-bold rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 group">
                                <span>START 5s CALIBRATION</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="isLocking" class="absolute inset-0 flex flex-col items-center justify-center bg-black/70 z-50 backdrop-blur-md">
                        <div class="relative">
                            <h2 class="text-[12rem] leading-none font-black text-white drop-shadow-[0_0_30px_rgba(251,191,36,0.8)] font-variant-numeric tabular-nums">
                                {{ calibrationCountdown }}
                            </h2>
                            <p class="text-amber-400 font-bold uppercase tracking-[0.5em] text-sm animate-pulse text-center mt-4">Stay Rigid</p>
                        </div>
                    </div>

                    <div v-if="isCalibrated" class="absolute inset-0 z-40 pointer-events-none p-6 flex flex-col justify-between">
                        
                        <div class="flex justify-between items-start">
                            <div class="bg-black/60 backdrop-blur-md p-4 rounded-2xl border border-white/10 shadow-xl">
                                <div class="text-[10px] text-slate-400 uppercase tracking-widest font-bold mb-1">Posture Efficiency</div>
                                <div class="text-5xl font-black tracking-tight" 
                                     :class="currentScore > 85 ? 'text-emerald-400' : (currentScore > 70 ? 'text-amber-400' : 'text-red-500')">
                                    {{ currentScore }}<span class="text-2xl align-top opacity-60">%</span>
                                </div>
                            </div>

                            <div class="bg-black/60 backdrop-blur-md px-6 py-3 rounded-full border border-white/10 shadow-xl">
                                <div class="flex items-center gap-3">
                                    <span class="relative flex h-3 w-3">
                                      <span v-if="isSlouching" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                      <span class="relative inline-flex rounded-full h-3 w-3" :class="isSlouching ? 'bg-red-500' : 'bg-emerald-500'"></span>
                                    </span>
                                    <span class="font-bold uppercase tracking-wider text-sm" 
                                          :class="isSlouching ? 'text-red-400' : 'text-emerald-400'">
                                        {{ statusMessage }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end items-end opacity-50 hover:opacity-100 transition-opacity">
                            <div class="bg-black/80 backdrop-blur-md p-3 rounded-xl border border-white/10 text-right">
                                <div class="text-[9px] text-slate-500 uppercase tracking-widest font-mono mb-1">Real-Time Telemetry</div>
                                <div class="font-mono text-xs text-indigo-300">
                                    NCK: {{ angles.neck }}° | BCK: {{ angles.back }}°
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="mt-8 grid grid-cols-1 gap-4 w-full max-w-md">
                    <button v-if="!isCameraOn" @click="startCamera" 
                            class="w-full py-5 bg-indigo-600 hover:bg-indigo-500 text-white font-black text-lg rounded-2xl shadow-lg shadow-indigo-500/20 transition-all hover:translate-y-[-2px] uppercase tracking-widest">
                        Initialize Camera
                    </button>

                    <div v-else class="flex gap-4">
                        <button @click="stopCamera" 
                                class="flex-1 py-4 bg-slate-800 hover:bg-red-600 hover:text-white text-slate-200 font-bold rounded-xl transition-colors shadow-lg border border-slate-700 uppercase tracking-wider text-sm">
                            End Session & Save
                        </button>
                        
                        <button v-if="isCalibrated" @click="triggerCalibration" 
                                class="px-6 py-4 bg-slate-800 hover:bg-slate-700 text-indigo-400 hover:text-indigo-300 font-bold rounded-xl transition-colors shadow-lg border border-slate-700 uppercase tracking-wider text-sm"
                                title="Reset Baseline">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>