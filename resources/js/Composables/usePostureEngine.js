import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

export function usePostureEngine(videoRef, toast) {
    // --- Configuration ---
    const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    const AI_ENDPOINT = isLocal ? 'http://127.0.0.1:5000/predict' : 'https://ergovision-ai.onrender.com/predict';

    // --- State ---
    const isCameraOn = ref(false);
    const isDetected = ref(false);
    const isCalibrated = ref(false);
    const isLocking = ref(false);
    const calibrationCountdown = ref(0);
    const currentScore = ref(100);
    const isSlouching = ref(false);
    const statusMessage = ref("Initializing...");
    const sessionDurationSecs = ref(0);
    const angles = ref({ neck: 0, back: 0 });
    const scoreHistory = ref([]);

    // --- Audio Assets ---
    const alertSound = new Audio('/sounds/alert.mp3');
    const successSound = new Audio('/sounds/success.mp3');

    // Internal Logic State
    let myIdealBack = 0;
    let myIdealNeck = 0;
    let calibrationBuffer = [];
    let chunkStartTime = Date.now();
    let lastProcessTime = 0;
    let slouchStartTime = null;
    let lastNotificationTime = 0;
    let uploadInterval = null;

    const sessionData = ref({ scores: [], slouchFrames: 0, totalFrames: 0, alerts: 0 });

    const formattedDuration = computed(() => {
        const m = Math.floor(sessionDurationSecs.value / 60).toString().padStart(2, '0');
        const s = (sessionDurationSecs.value % 60).toString().padStart(2, '0');
        return `${m}:${s}`;
    });

    const handleAdaptiveFeedback = (slouching) => {
        if (slouching) {
            if (!slouchStartTime) slouchStartTime = Date.now();
            const duration = (Date.now() - slouchStartTime) / 1000;

            // 1. CRITICAL STATE (> 60 Seconds) - Forced Reset
            if (duration >= 60) {
                statusMessage.value = "CRITICAL: POSTURAL RESET REQUIRED";
                // In this state, we might even trigger a toast that doesn't disappear
                toast.error("High-risk duration exceeded. Please stand up and reset.", { timeout: 0 });
            }

            // 2. ALERT STATE (15 - 60 Seconds) - Audible + Persistent UI
            else if (duration >= 15) {
                statusMessage.value = "ALERT: SUSTAINED SLOUCH";
                if (Date.now() - lastNotificationTime > 15000) {
                    if (Notification.permission === "granted") {
                        new Notification("Posture Alert", { body: "Audible Intervention Triggered." });
                    }
                    alertSound.play().catch(() => { });
                    sessionData.value.alerts++;
                    lastNotificationTime = Date.now();
                    toast.warning("Persistent deviation detected.");
                }
            }

            // 3. WARNING STATE (5 - 15 Seconds) - Visual Cues Only
            else if (duration >= 5) {
                statusMessage.value = "WARNING: DRIFT DETECTED";
                // This is where your UI color change (red ring) should be most visible
            }

            // 4. STABLE STATE (0 - 5 Seconds) - Monitoring / Buffer
            else {
                statusMessage.value = "Monitoring Threshold...";
            }
        } else {
            // Positive Reinforcement / Reset Logic
            if (slouchStartTime && (Date.now() - slouchStartTime) / 1000 > 5) {
                successSound.play().catch(() => { });
                statusMessage.value = "Stable State Restored";
            } else {
                statusMessage.value = "Stable State";
            }
            slouchStartTime = null;
        }
    };

    const uploadSessionData = () => {
        if (sessionData.value.totalFrames === 0) return;
        const now = Date.now();
        const actualDuration = Math.round((now - chunkStartTime) / 1000);
        chunkStartTime = now;

        const count = sessionData.value.scores.length || 1;
        const avgScore = Math.round(sessionData.value.scores.reduce((a, b) => a + b, 0) / count);
        const slouchRatio = sessionData.value.slouchFrames / sessionData.value.totalFrames;
        const slouchSecs = Math.round(slouchRatio * actualDuration);

        router.post('/posture-chunks', {
            score: avgScore,
            slouch_duration: slouchSecs,
            duration_seconds: actualDuration,
            alert_count: sessionData.value.alerts
        }, {
            preserveScroll: true,
            onSuccess: () => {
                sessionData.value = { scores: [], slouchFrames: 0, totalFrames: 0, alerts: 0 };
            }
        });
    };

    const onResults = async (results) => {
        const now = Date.now();
        if (!results.poseLandmarks || results.poseLandmarks.length < 25) {
            isDetected.value = false;
            statusMessage.value = "Searching...";
            return;
        }

        const lm = results.poseLandmarks;
        const isVisible = [8, 12, 24].every(id => lm[id].visibility > 0.5);
        if (!isVisible) { isDetected.value = false; statusMessage.value = "Align with camera"; return; }
        if (lm[24].y < 0.4) { statusMessage.value = "Standing - Paused"; return; }

        isDetected.value = true;
        if (now - lastProcessTime < 200) return;
        lastProcessTime = now;

        try {
            const res = await axios.post(AI_ENDPOINT, {
                landmarks: results.poseLandmarks,
                ideal_back: myIdealBack,
                ideal_neck: myIdealNeck,
                is_calibrating: isLocking.value
            });

            if (res.data.status === 'calibration_error') {
                statusMessage.value = "BAD POSTURE DETECTED";
                return;
            }

            angles.value = res.data.angles;

            if (isLocking.value) {
                calibrationBuffer.push(res.data.angles);
                statusMessage.value = "Sit Straight...";
            }

            if (isCalibrated.value) {
                scoreHistory.value.push(res.data.score);
                if (scoreHistory.value.length > 5) scoreHistory.value.shift();

                const avg = scoreHistory.value.reduce((a, b) => a + b, 0) / scoreHistory.value.length;
                const finalScore = Math.round(avg);

                // Jitter Prevention
                if (Math.abs(currentScore.value - finalScore) >= 1) {
                    currentScore.value = finalScore;
                }

                isSlouching.value = res.data.label === 1 || currentScore.value < 75;
                handleAdaptiveFeedback(isSlouching.value);

                sessionData.value.scores.push(currentScore.value);
                sessionData.value.totalFrames++;
                if (isSlouching.value) sessionData.value.slouchFrames++;
            }
        } catch (e) {
            statusMessage.value = "AI Offline";
        }
    };

    const triggerCalibration = () => {
        isLocking.value = true;
        calibrationCountdown.value = 5;
        calibrationBuffer = [];
        const timer = setInterval(() => {
            if (statusMessage.value !== "BAD POSTURE DETECTED") {
                calibrationCountdown.value--;
            }
            if (calibrationCountdown.value <= 0) {
                clearInterval(timer);
                lockNeutralPosition();
            }
        }, 1000);
    };

    const lockNeutralPosition = () => {
        if (calibrationBuffer.length < 5) {
            isLocking.value = false;
            toast.error("Calibration failed. Please sit straight.");
            return;
        }
        myIdealBack = calibrationBuffer.reduce((a, b) => a + b.back, 0) / calibrationBuffer.length;
        myIdealNeck = calibrationBuffer.reduce((a, b) => a + b.neck, 0) / calibrationBuffer.length;

        isCalibrated.value = true;
        isLocking.value = false;
        toast.success("Calibration complete!");
        successSound.play().catch(() => { });

        chunkStartTime = Date.now();
        uploadInterval = setInterval(uploadSessionData, 30000);
    };

    const cleanup = () => {
        if (uploadInterval) clearInterval(uploadInterval);
        if (isCalibrated.value) uploadSessionData();
    };

    return {
        isCameraOn, isDetected, isCalibrated, isLocking, calibrationCountdown,
        currentScore, isSlouching, statusMessage, formattedDuration, angles,
        onResults, triggerCalibration, sessionDurationSecs, cleanup
    };
}