import { ref, computed } from 'vue';
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
    const needsPresenceCheck = ref(false);

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
    let lastToastTime = 0;

    // Timer Variables
    let uploadInterval = null;
    let activeTimer = null;
    let chunkActiveSecs = 0;

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

            // Only pause for a presence check when posture remains poor for a long period.
            if (duration >= 60 && currentScore.value < 60 && !needsPresenceCheck.value) {
                needsPresenceCheck.value = true;
                statusMessage.value = "Tracking Paused";
                return;
            }

            if (duration >= 30) {
                statusMessage.value = "CRITICAL: POSTURAL RESET REQUIRED";
            } else if (duration >= 15) {
                statusMessage.value = "ALERT: SUSTAINED SLOUCH";

                const now = Date.now();

                // Visual toast: every 5 seconds (15s+ only)
                if (now - lastToastTime >= 5000) {
                    toast.warning("Sustained slouch detected. Please sit upright.");
                    lastToastTime = now;
                }

                // Audio + alert count: every 15 seconds (15s+ only)
                if (now - lastNotificationTime >= 15000) {
                    alertSound.currentTime = 0;
                    alertSound.play().catch(e => console.warn("Audio blocked:", e));
                    sessionData.value.alerts++;
                    lastNotificationTime = now;
                }
            } else if (duration >= 5) {
                statusMessage.value = "WARNING: DRIFT DETECTED";
            } else {
                statusMessage.value = "Monitoring Threshold...";
            }
        } else {
            statusMessage.value = "Stable State";
            slouchStartTime = null; // Clean reset
        }
    };

    const confirmPresence = () => {
        needsPresenceCheck.value = false;
        slouchStartTime = null;
        lastNotificationTime = 0;
        lastToastTime = 0;
        statusMessage.value = "Stable State";
    };

    const uploadSessionData = () => {
        if (sessionData.value.totalFrames === 0 || chunkActiveSecs === 0) return;

        const durationToLog = chunkActiveSecs;
        chunkActiveSecs = 0;
        chunkStartTime = Date.now();

        const count = sessionData.value.scores.length || 1;
        const avgScore = Math.round(sessionData.value.scores.reduce((a, b) => a + b, 0) / count);
        const slouchRatio = sessionData.value.slouchFrames / sessionData.value.totalFrames;
        const slouchSecs = Math.round(slouchRatio * durationToLog);

        const payload = {
            score: avgScore,
            slouch_duration: slouchSecs,
            duration_seconds: durationToLog,
            alert_count: sessionData.value.alerts
        };

        // Clear local array
        sessionData.value = { scores: [], slouchFrames: 0, totalFrames: 0, alerts: 0 };

        // Silently sync with backend
        axios.post('/posture-chunks', payload).catch(err => {
            console.error("Failed to sync chunk", err);
        });
    };

    const onResults = async (results) => {
        const now = Date.now();
        if (needsPresenceCheck.value) {
            // Pause processing until user confirms they are present.
            return;
        }
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
        if (now - lastProcessTime < 200) return; // Process at 5fps to save resources
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
                // RESTORED: Let the AI control the score natively without messy frontend manipulation
                scoreHistory.value.push(res.data.score);
                // Keep smoothing lightweight so the score still reacts quickly to posture changes.
                if (scoreHistory.value.length > 3) scoreHistory.value.shift();

                const avg = scoreHistory.value.reduce((a, b) => a + b, 0) / scoreHistory.value.length;
                const finalScore = Math.round(avg);

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
        // Brilliant trick to unlock browser autoplay policy! Applied to both sounds.
        [alertSound, successSound].forEach(sound => {
            sound.volume = 0.01;
            sound.play().then(() => {
                sound.pause();
                sound.volume = 1;
                sound.currentTime = 0;
            }).catch(() => { });
        });

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

        myIdealBack = calibrationBuffer.reduce((a, b) => a + (parseFloat(b.back) || 0), 0) / calibrationBuffer.length;
        myIdealNeck = calibrationBuffer.reduce((a, b) => a + (parseFloat(b.neck) || 0), 0) / calibrationBuffer.length;

        isCalibrated.value = true;
        isLocking.value = false;
        toast.success("Calibration complete!");

        // RESTORED: Play the success sound upon successful lock-in
        successSound.currentTime = 0;
        successSound.play().catch(e => console.warn("Audio blocked:", e));

        if (activeTimer) clearInterval(activeTimer);
        if (uploadInterval) clearInterval(uploadInterval);

        chunkStartTime = Date.now();
        chunkActiveSecs = 0;

        activeTimer = setInterval(() => {
            if (isCalibrated.value && isDetected.value && statusMessage.value !== "Standing - Paused" && !needsPresenceCheck.value) {
                sessionDurationSecs.value++;
                chunkActiveSecs++;
            }
        }, 1000);

        uploadInterval = setInterval(uploadSessionData, 30000);
    };

    const cleanup = () => {
        if (activeTimer) clearInterval(activeTimer);
        if (uploadInterval) clearInterval(uploadInterval);
        if (isCalibrated.value) uploadSessionData();
    };

    return {
        isCameraOn, isDetected, isCalibrated, isLocking, calibrationCountdown,
        currentScore, isSlouching, statusMessage, formattedDuration, angles,
        needsPresenceCheck, confirmPresence,
        onResults, triggerCalibration, sessionDurationSecs, cleanup
    };
}