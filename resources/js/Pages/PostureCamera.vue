<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onUnmounted, onMounted, computed } from 'vue';
import { router } from '@inertiajs/vue3'; 
import axios from 'axios';
import { useToast } from '@/Composables/useToast'; // [NEW] Import Toast

// --- SMART CONFIGURATION ---
const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
const AI_ENDPOINT = isLocal ? 'http://127.0.0.1:5000/predict' : 'https://ergovision-ai.onrender.com/predict';
const AI_BASE_URL = isLocal ? 'http://127.0.0.1:5000/' : 'https://ergovision-ai.onrender.com/';

const toast = useToast(); // [NEW] Initialize Toast

// --- State Variables ---
const videoRef = ref(null);
const isCameraOn = ref(false); 
const isDetected = ref(false); 
const isCalibrated = ref(false); 
const isLocking = ref(false);    
const calibrationCountdown = ref(0);
const currentScore = ref(100);
const isSlouching = ref(false);
const statusMessage = ref("Initializing...");
const sessionDurationSecs = ref(0);

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
let durationInterval = null; 
let slouchStartTime = null; 
let lastNotificationTime = 0;

const sessionData = ref({ scores: [], slouchFrames: 0, totalFrames: 0, alerts: 0 });

// --- Helper: Format Timer ---
const formattedDuration = computed(() => {
    const m = Math.floor(sessionDurationSecs.value / 60).toString().padStart(2, '0');
    const s = (sessionDurationSecs.value % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
});

const wakeUpServer = async () => {
    try {
        await axios.get(AI_BASE_URL, { timeout: 3000 });
        statusMessage.value = "AI Ready";
    } catch (e) {
        statusMessage.value = "Waking up AI...";
        // toast.error("AI Server is sleeping. Please wait a moment.", "Connecting...");
    }
};

onMounted(() => {
    if ("Notification" in window && Notification.permission !== "granted") {
        Notification.requestPermission();
    }
    wakeUpServer();

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
        toast.error("Could not load AI libraries. Please refresh.", "System Error");
    }
});

const uploadSessionData = () => {
    if (sessionData.value.totalFrames === 0) return;

    const now = Date.now();
    const actualDuration = Math.round((now - chunkStartTime) / 1000); 
    chunkStartTime = now;

    const count = sessionData.value.scores.length || 1;
    const avgScore = Math.round(sessionData.value.scores.reduce((a, b) => a + b, 0) / count);
    
    const slouchRatio = sessionData.value.slouchFrames / sessionData.value.totalFrames;
    const slouchSecs = Math.round(slouchRatio * actualDuration);

    const payload = {
        score: avgScore || 100,
        slouch_duration: slouchSecs || 0,
        duration_seconds: actualDuration || 30,
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
                toast.error("Please sit up straight immediately!", "Posture Critical");
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
            // toast.success("You corrected your posture!", "Well Done");
            setTimeout(() => { 
                if(!isSlouching.value) statusMessage.value = "Good Posture"; 
            }, 2000);
        } else {
            statusMessage.value = "Good Posture";
        }
        slouchStartTime = null;
    }
};

const triggerCalibration = () => {
    // [FIX 1] Remove "isCalibrated" check so the Reset button actually works!
    if (isLocking.value) return;
    
    // [FIX 2] Prevent starting if user is invisible
    if (!isDetected.value) {
        toast.error("I can't see you clearly! Please step back and show your full body.", "Calibration Failed");
        return;
    }

    // Reset everything for a fresh start
    isCalibrated.value = false;
    calibrationBuffer.value = [];
    isLocking.value = true;
    calibrationCountdown.value = 5;
    statusMessage.value = "Sit Up Straight...";
    
    const timer = setInterval(() => {
        calibrationCountdown.value--;
        if (calibrationCountdown.value <= 0) {
            clearInterval(timer);
            lockNeutralPosition();
        }
    }, 1000);
};

const lockNeutralPosition = () => {
    // [FIX 3] Check if we actually got good data during the 5s
    if (calibrationBuffer.value.length < 1) {
        isLocking.value = false;
        toast.error("You moved or disappeared during calibration. Please try again.", "Calibration Failed");
        statusMessage.value = "Calibration Failed";
        return;
    }
    
    const validReadings = calibrationBuffer.value.filter(r => r.back > 0 && r.neck > 0);
    if(validReadings.length === 0) {
         isLocking.value = false;
         toast.error("Data was blurry. Ensure good lighting.", "Calibration Failed");
         return;
    }

    myIdealBack.value = validReadings.reduce((a, b) => a + b.back, 0) / validReadings.length;
    myIdealNeck.value = validReadings.reduce((a, b) => a + b.neck, 0) / validReadings.length;
    
    isCalibrated.value = true;
    isLocking.value = false;
    statusMessage.value = "Monitoring Started";
    toast.success("Baseline set! We are now tracking your posture.", "Calibration Complete");
    successSound.play();
    
    chunkStartTime = Date.now(); 
    if (uploadInterval) clearInterval(uploadInterval);
    uploadInterval = setInterval(uploadSessionData, 30000);
};

const onResults = async (results) => {
    const now = Date.now();
    
    // 1. Check if landmarks exist
    if (!results.poseLandmarks || results.poseLandmarks.length < 25) {
        isDetected.value = false;
        statusMessage.value = "Searching for body...";
        return;
    }

    const lm = results.poseLandmarks;

    // 2. Strict Body Visibility Check (MediaPipe 0.0 to 1.0)
    // If visibility is too low, the person is likely missing or lighting is poor.
    const visibilityThreshold = 0.5;
    const requiredLandmarks = [8, 12, 24, 23, 11, 7]; // Ears, Shoulders, Hips
    const isVisible = requiredLandmarks.every(id => lm[id].visibility > visibilityThreshold);

    if (!isVisible) {
        isDetected.value = false;
        statusMessage.value = "Please align with camera";
        return;
    }

    // 3. Standing Detection
    // In sit-down ergonomics, if the hips (Y-axis) are in the top 40% of the screen, 
    // the user is likely standing or walking away.
    if (lm[24].y < 0.4) {
        isDetected.value = true; 
        statusMessage.value = "Standing Detected - Paused";
        return; 
    }

    isDetected.value = true;

    // Throttle AI hits to ~5 per second to prevent lag
    if (now - lastProcessTime < 200) return;
    lastProcessTime = now;

    try {
        const res = await axios.post(AI_ENDPOINT, {
            landmarks: results.poseLandmarks,
            ideal_back: myIdealBack.value,
            ideal_neck: myIdealNeck.value
        });

        // Handle Status from Python (user_not_detected, standing, active)
        if (res.data.status !== 'active' && res.data.status !== 'standing') {
             statusMessage.value = res.data.message;
             return;
        }

        angles.value = res.data.angles;

        // Calibration logic
        if (isLocking.value) {
            calibrationBuffer.value.push(res.data.angles);
            if (calibrationBuffer.value.length > 15) calibrationBuffer.value.shift();
        }

        if (isCalibrated.value) {
            // --- SMOOTHING LOGIC (Moving Average) ---
            // We take the last 5 scores from the AI and average them to stop jitter.
            scoreHistory.value.push(res.data.score);
            if (scoreHistory.value.length > 5) scoreHistory.value.shift();
            
            const average = scoreHistory.value.reduce((a, b) => a + b, 0) / scoreHistory.value.length;
            currentScore.value = Math.round(average);

            const aiSaysSlouch = res.data.label === 1;
            const scoreIsFailing = currentScore.value < 75;
            
            isSlouching.value = aiSaysSlouch || scoreIsFailing;
            handleAdaptiveFeedback(isSlouching.value);

            // Record session telemetry
            sessionData.value.scores.push(currentScore.value);
            sessionData.value.totalFrames++;
            if (isSlouching.value) sessionData.value.slouchFrames++;
        }
    } catch (err) { 
        console.error("AI Communication Error"); 
    }
};

const startCamera = async () => {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ 
            video: { facingMode: "user", width: { ideal: 640 }, height: { ideal: 480 } } 
        });
        
        camera = new window.Camera(videoRef.value, {
            onFrame: async () => { if (pose) await pose.send({ image: videoRef.value }); },
            width: 640, height: 480
        });
        camera.start();
        isCameraOn.value = true;
        isCalibrated.value = false;

        sessionDurationSecs.value = 0;
        if (durationInterval) clearInterval(durationInterval);
        durationInterval = setInterval(() => {
            sessionDurationSecs.value++;
        }, 1000);

    } catch (e) {
        toast.error("Please check browser permissions.", "Camera Access Denied");
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
    if (durationInterval) clearInterval(durationInterval);
    if (camera) camera.stop();
};

onUnmounted(() => stopCamera());
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-6 md:py-12 bg-slate-900 min-h-screen font-sans selection:bg-indigo-500 selection:text-white">
            <div class="max-w-6xl mx-auto flex flex-col items-center">
                
                <div class="w-full flex justify-between items-end mb-4 md:mb-6 px-4">
                    <div>
                        <h2 class="text-2xl md:text-3xl font-black text-white tracking-tighter">
                            ERGO<span class="text-indigo-500">VISION</span>
                        </h2>
                        <p class="text-slate-400 text-[10px] md:text-sm font-mono uppercase tracking-widest">
                            AI Posture Engine
                        </p>
                    </div>
                    
                    <div class="flex flex-col items-end gap-1">
                        <div v-if="isCameraOn" class="font-mono text-xl md:text-2xl font-bold text-white tabular-nums tracking-widest">
                            {{ formattedDuration }}
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="h-2 w-2 md:h-3 md:w-3 rounded-full animate-pulse" 
                                 :class="isCalibrated ? 'bg-emerald-500' : (isCameraOn ? 'bg-amber-500' : 'bg-red-500')">
                            </div>
                            <span class="text-white font-mono text-[10px] md:text-xs">
                                {{ isCalibrated ? 'ACTIVE' : (isCameraOn ? 'STANDBY' : 'OFFLINE') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="relative w-full max-w-4xl aspect-video bg-black rounded-2xl md:rounded-3xl overflow-hidden shadow-2xl transition-all duration-500 group mx-4"
                     :class="[
                        isCalibrated && isSlouching ? 'shadow-red-900/40 ring-2 ring-red-500' : 
                        isCalibrated ? 'shadow-emerald-900/40 ring-2 ring-emerald-500' :
                        isLocking ? 'ring-2 ring-amber-400' :
                        'ring-1 ring-slate-700'
                      ]">
                    
                    <video ref="videoRef" class="w-full h-full object-cover transform scale-x-[-1]" autoplay playsinline></video>

                    <div v-if="isCameraOn && !isDetected" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none bg-black/50 backdrop-blur-sm">
                        <svg class="animate-spin h-8 w-8 text-indigo-500 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-white font-mono text-[10px] uppercase tracking-widest">Detecting...</p>
                    </div>

                    <div v-if="isCameraOn && isDetected && !isCalibrated && !isLocking" 
                         class="absolute inset-0 flex flex-col items-center justify-center bg-slate-900/60 z-30 backdrop-blur-sm p-4">
                        <div class="text-center max-w-sm">
                            <div class="text-4xl mb-2">🧘</div>
                            <h3 class="text-white font-bold text-xl mb-1">Calibration</h3>
                            <p class="text-slate-300 text-xs mb-4">
                                Sit straight. Click start. Hold for 5s.
                            </p>
                            <button @click="triggerCalibration" 
                                    class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg transition-all flex items-center justify-center gap-2">
                                <span>START 5s LOCK</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="isLocking" class="absolute inset-0 flex flex-col items-center justify-center bg-black/80 z-50 backdrop-blur-md">
                        <h2 class="text-9xl font-black text-white font-variant-numeric tabular-nums">
                            {{ calibrationCountdown }}
                        </h2>
                        <p class="text-amber-400 font-bold uppercase tracking-widest text-xs animate-pulse mt-2">Don't Move</p>
                    </div>

                    <div v-if="isCalibrated" class="absolute inset-0 z-40 pointer-events-none p-4 md:p-6 flex flex-col justify-between">
                        
                        <div class="flex justify-between items-start">
                            <div class="bg-black/60 backdrop-blur-md p-3 rounded-xl border border-white/10 shadow-lg">
                                <div class="text-[8px] text-slate-400 uppercase tracking-widest font-bold mb-0.5">Score</div>
                                <div class="text-3xl md:text-4xl font-black tracking-tight leading-none" 
                                     :class="currentScore > 85 ? 'text-emerald-400' : (currentScore > 70 ? 'text-amber-400' : 'text-red-500')">
                                    {{ currentScore }}
                                </div>
                            </div>

                            <div class="bg-black/60 backdrop-blur-md px-4 py-2 rounded-full border border-white/10 shadow-lg">
                                <div class="flex items-center gap-2">
                                    <span class="relative flex h-2 w-2">
                                      <span v-if="isSlouching" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                      <span class="relative inline-flex rounded-full h-2 w-2" :class="isSlouching ? 'bg-red-500' : 'bg-emerald-500'"></span>
                                    </span>
                                    <span class="font-bold uppercase tracking-wider text-[10px] md:text-xs" 
                                          :class="isSlouching ? 'text-red-400' : 'text-emerald-400'">
                                        {{ statusMessage }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end items-end opacity-60">
                            <div class="bg-black/80 backdrop-blur-md p-2 rounded-lg border border-white/10 text-right">
                                <div class="font-mono text-[10px] text-indigo-300">
                                    N:{{ angles.neck }}° B:{{ angles.back }}°
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 md:mt-8 w-full max-w-md px-4">
                    <button v-if="!isCameraOn" @click="startCamera" 
                            class="w-full py-4 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-lg rounded-2xl shadow-lg transition-all uppercase tracking-widest">
                        Start Camera
                    </button>

                    <div v-else class="flex gap-3">
                        <button @click="stopCamera" 
                                class="flex-1 py-3 bg-slate-800 hover:bg-red-600 hover:text-white text-slate-200 font-bold rounded-xl transition-colors shadow-lg border border-slate-700 uppercase tracking-wider text-xs md:text-sm">
                            End Session
                        </button>
                        
                        <button v-if="isCalibrated" @click="triggerCalibration" 
                                class="px-4 py-3 bg-slate-800 hover:bg-slate-700 text-indigo-400 hover:text-indigo-300 font-bold rounded-xl transition-colors shadow-lg border border-slate-700"
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