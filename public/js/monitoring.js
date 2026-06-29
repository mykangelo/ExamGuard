const enableCameraBtn = document.getElementById("enableCameraBtn");
const cameraVideo = document.getElementById("cameraVideo");
const cameraPlaceholder = document.getElementById("cameraPlaceholder");
const cameraStatus = document.getElementById("cameraStatus");
const faceStatus = document.getElementById("faceStatus");
const sessionStatus = document.getElementById("sessionStatus");
const warningCountEl = document.getElementById("warningCount");
const tabStatus = document.getElementById("tabStatus");
const simulateBtn = document.getElementById("simulateBtn");
const logList = document.getElementById("logList");
const submitBtn = document.getElementById("submitBtn") || document.querySelector(".te-submit-btn");
const warningToast = document.getElementById("warningToast");

let warningCount = 0;
let sessionLocked = false;
let faceLandmarker = null;
let trackingInterval = null;
let lastNoFaceWarningAt = 0;
let lastMultipleFaceWarningAt = 0;
let sessionWarningLimit = 3;
let tabSwitchCount = 0;
let tabHiddenSince = null;
let lastMouseLeaveWarningAt = 0;

const SEVERITY = {
  MINOR: "minor",
  MODERATE: "moderate",
  CRITICAL: "critical",
};

document.addEventListener("examguard:warning-limit", (event) => {
  sessionWarningLimit = Number(event.detail) || 3;
});

document.addEventListener("examguard:session-started", (event) => {
  warningCount = Number(event.detail?.warningCount) || 0;
  if (warningCountEl) warningCountEl.textContent = String(warningCount);
});

async function setupFaceLandmarker() {
  if (faceLandmarker) return faceLandmarker;
  if (faceStatus) faceStatus.textContent = "Loading";

  const vision = await import("https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14");
  const { FaceLandmarker, FilesetResolver } = vision;
  const filesetResolver = await FilesetResolver.forVisionTasks(
    "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/wasm"
  );

  faceLandmarker = await FaceLandmarker.createFromOptions(filesetResolver, {
    baseOptions: {
      modelAssetPath:
        "https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/latest/face_landmarker.task",
      delegate: "GPU",
    },
    runningMode: "VIDEO",
    numFaces: 2,
  });

  if (faceStatus) faceStatus.textContent = "Active";
  return faceLandmarker;
}

async function enableCamera() {
  if (!navigator.mediaDevices?.getUserMedia) {
    addWarning("camera_unavailable", SEVERITY.CRITICAL, "Camera API is not available in this browser");
    return;
  }

  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
    cameraVideo.srcObject = stream;
    cameraVideo.classList.remove("hidden");
    if (cameraPlaceholder) cameraPlaceholder.style.display = "none";
    cameraStatus.textContent = "Active";
    await setupFaceLandmarker();
    startFaceTracking();
  } catch (error) {
    cameraStatus.textContent = "Blocked";
    if (faceStatus) faceStatus.textContent = "Unavailable";
    addWarning("camera_blocked", SEVERITY.CRITICAL, "Camera permission or face tracking was blocked or unavailable");
  }
}

function startFaceTracking() {
  if (!cameraVideo || !faceLandmarker || trackingInterval) return;

  trackingInterval = setInterval(() => {
    if (sessionLocked || cameraVideo.readyState < 2) return;

    const results = faceLandmarker.detectForVideo(cameraVideo, performance.now());
    const faceCount = results.faceLandmarks ? results.faceLandmarks.length : 0;
    const now = Date.now();

    if (faceCount === 1) {
      if (faceStatus) faceStatus.textContent = "Face Detected";
      return;
    }

    if (faceCount === 0) {
      if (faceStatus) faceStatus.textContent = "No Face";
      if (now - lastNoFaceWarningAt > 6000) {
        lastNoFaceWarningAt = now;
        addWarning("no_face", SEVERITY.MODERATE, "No face detected in camera frame");
      }
      return;
    }

    if (faceCount > 1 && now - lastMultipleFaceWarningAt > 6000) {
      if (faceStatus) faceStatus.textContent = "Multiple Faces";
      lastMultipleFaceWarningAt = now;
      addWarning("multiple_faces", SEVERITY.CRITICAL, "Multiple faces detected in camera frame");
    }
  }, 700);
}

function captureSnapshot() {
  if (!cameraVideo || cameraVideo.readyState < 2 || !cameraVideo.videoWidth) {
    return null;
  }

  const canvas = document.createElement("canvas");
  canvas.width = cameraVideo.videoWidth;
  canvas.height = cameraVideo.videoHeight;
  const ctx = canvas.getContext("2d");
  if (!ctx) return null;

  ctx.drawImage(cameraVideo, 0, 0, canvas.width, canvas.height);
  try {
    return canvas.toDataURL("image/jpeg", 0.72);
  } catch (_) {
    return null;
  }
}

function showWarningToast() {
  if (!warningToast) return;
  warningToast.textContent = "A warning has been recorded. Please stay focused on your exam.";
  warningToast.classList.add("show");
  setTimeout(() => warningToast.classList.remove("show"), 3500);
}

function appendLogEntry(reason) {
  if (!logList) return;
  const empty = logList.querySelector("[data-empty]");
  if (empty) empty.remove();
  const row = document.createElement("div");
  row.className = "rounded-xl bg-white/5 px-4 py-2";
  row.textContent = `${new Date().toLocaleTimeString()} — ${reason}`;
  logList.prepend(row);
}

async function addWarning(type, severity, reason) {
  if (sessionLocked || !warningCountEl) return;

  showWarningToast();
  appendLogEntry(reason);

  const snapshot = captureSnapshot();
  const payload = {
    type,
    severity,
    message: "Proctoring warning recorded",
    snapshot,
    occurredAt: new Date().toISOString(),
  };

  document.dispatchEvent(new CustomEvent("examguard:violation", { detail: payload }));

  let nextCount = warningCount + 1;
  if (window.ExamGuardSession?.reportViolation) {
    try {
      const result = await window.ExamGuardSession.reportViolation(payload);
      if (typeof result?.warningCount === "number") {
        nextCount = result.warningCount;
      }
    } catch (_) {}
  }

  warningCount = nextCount;
  warningCountEl.textContent = String(warningCount);
  document.dispatchEvent(new CustomEvent("examguard:warning-added", { detail: { type, severity } }));

  if (warningCount >= sessionWarningLimit) {
    // Outcome is configured per exam (default notify); student never sees details.
    const action = window.ExamGuardProctoring?.maxWarningAction || "notify";
    document.dispatchEvent(new CustomEvent("examguard:max-warnings", { detail: { action, count: warningCount, limit: sessionWarningLimit } }));
    if (action === "lock") {
      lockSession();
    }
  } else if (sessionStatus) {
    sessionStatus.textContent = "Warning recorded";
  }
}

function lockSession() {
  sessionLocked = true;
  if (sessionStatus) sessionStatus.textContent = "Locked";
  if (faceStatus) faceStatus.textContent = "Stopped";
  document.querySelectorAll(".te-submit-btn").forEach((btn) => { btn.disabled = true; });
  if (simulateBtn) simulateBtn.disabled = true;
  if (trackingInterval) {
    clearInterval(trackingInterval);
    trackingInterval = null;
  }

  document.dispatchEvent(new CustomEvent("examguard:locked"));
}

if (enableCameraBtn) enableCameraBtn.addEventListener("click", enableCamera);

if (simulateBtn) {
  simulateBtn.addEventListener("click", () => addWarning("simulated", SEVERITY.MINOR, "Simulated monitoring violation"));
}

document.addEventListener("visibilitychange", () => {
  if (!tabStatus) return;

  if (document.hidden) {
    tabHiddenSince = Date.now();
    tabStatus.textContent = "Switched";
    // grace period: don't log unless hidden for > 2s
    const hiddenAt = tabHiddenSince;
    setTimeout(() => {
      if (!document.hidden) return;
      if (tabHiddenSince !== hiddenAt) return;
      tabSwitchCount += 1;
      const severity = tabSwitchCount >= 3 ? SEVERITY.MODERATE : SEVERITY.MINOR;
      addWarning("tab_switch", severity, "Tab switching detected");
    }, 2000);
  } else {
    tabHiddenSince = null;
    tabStatus.textContent = "Active";
    if (!sessionLocked && sessionStatus && warningCount > 0) {
      sessionStatus.textContent = "Warning recorded";
    }
  }
});

document.addEventListener("mouseleave", () => {
  if (sessionLocked || document.hidden) return;
  const now = Date.now();
  if (now - lastMouseLeaveWarningAt < 8000) return;
  lastMouseLeaveWarningAt = now;
  addWarning("mouse_leave", SEVERITY.MINOR, "Mouse left the exam window");
});

// Copy/paste + right click blocking (student privacy: do not reveal details)
document.addEventListener("contextmenu", (e) => {
  e.preventDefault();
  addWarning("context_menu", SEVERITY.MINOR, "Context menu blocked");
});

document.addEventListener("copy", (e) => {
  e.preventDefault();
  addWarning("copy_attempt", SEVERITY.MINOR, "Copy attempt blocked");
});

document.addEventListener("cut", (e) => {
  e.preventDefault();
  addWarning("copy_attempt", SEVERITY.MINOR, "Cut attempt blocked");
});

document.addEventListener("paste", (e) => {
  e.preventDefault();
  addWarning("paste_attempt", SEVERITY.MINOR, "Paste attempt blocked");
});

// Fullscreen enforcement (force + detect exit)
async function ensureFullscreen() {
  try {
    if (!document.fullscreenElement && document.documentElement.requestFullscreen) {
      await document.documentElement.requestFullscreen();
    }
  } catch (_) {}
}

document.addEventListener("examguard:session-started", () => {
  ensureFullscreen();
});

document.addEventListener("fullscreenchange", () => {
  if (sessionLocked) return;
  if (!document.fullscreenElement) {
    addWarning("fullscreen_exit", SEVERITY.CRITICAL, "Exited fullscreen during exam");
  }
});

// Basic audio loudness detection (demo-only): logs when sustained loud level is detected
let audioCtx = null;
let audioAnalyser = null;
let audioData = null;
let audioStream = null;
let loudSince = null;

async function startAudioMonitor() {
  if (audioCtx || !navigator.mediaDevices?.getUserMedia) return;
  try {
    audioStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
    audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    const source = audioCtx.createMediaStreamSource(audioStream);
    audioAnalyser = audioCtx.createAnalyser();
    audioAnalyser.fftSize = 512;
    audioData = new Uint8Array(audioAnalyser.fftSize);
    source.connect(audioAnalyser);

    setInterval(() => {
      if (sessionLocked || !audioAnalyser || !audioData) return;
      audioAnalyser.getByteTimeDomainData(audioData);
      let sum = 0;
      for (let i = 0; i < audioData.length; i += 1) {
        const v = (audioData[i] - 128) / 128;
        sum += v * v;
      }
      const rms = Math.sqrt(sum / audioData.length);
      const now = Date.now();
      const loud = rms > 0.22; // conservative threshold
      if (loud) {
        if (!loudSince) loudSince = now;
        if (now - loudSince > 1500) {
          loudSince = now + 999999; // prevent spam; next warning via cooldown below
          addWarning("audio_loud", SEVERITY.MODERATE, "Unusually loud audio detected");
          setTimeout(() => { loudSince = null; }, 8000);
        }
      } else {
        loudSince = null;
      }
    }, 300);
  } catch (_) {
    // ignore mic monitor failure (preflight handles required mic availability)
  }
}

document.addEventListener("examguard:session-started", () => {
  startAudioMonitor();
});
