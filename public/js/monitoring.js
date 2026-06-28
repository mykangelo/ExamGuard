const enableCameraBtn = document.getElementById("enableCameraBtn");
const cameraVideo = document.getElementById("cameraVideo");
const cameraPlaceholder = document.getElementById("cameraPlaceholder");
const cameraStatus = document.getElementById("cameraStatus");
const faceStatus = document.getElementById("faceStatus");
const sessionStatus = document.getElementById("sessionStatus");
const warningCountEl = document.getElementById("warningCount");
const tabStatus = document.getElementById("tabStatus");
const simulateBtn = document.getElementById("simulateBtn");
const submitBtn = document.getElementById("submitBtn") || document.getElementById("submitExamBtn");
const logList = document.getElementById("logList");
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
    cameraPlaceholder.style.display = "none";
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

function showWarningToast(message) {
  if (!warningToast) return;
  warningToast.textContent = message;
  warningToast.classList.add("show");
  setTimeout(() => warningToast.classList.remove("show"), 3500);
}

function severityLabel(severity) {
  if (severity === SEVERITY.CRITICAL) return "Critical";
  if (severity === SEVERITY.MODERATE) return "Moderate";
  return "Minor";
}

function addWarning(type, severity, reason) {
  if (sessionLocked || !warningCountEl || !logList) return;

  warningCount += 1;
  warningCountEl.textContent = warningCount;
  showWarningToast(reason);

  if (logList.children.length === 1 && logList.children[0].textContent === "No violations recorded.") {
    logList.innerHTML = "";
  }

  const item = document.createElement("div");
  item.className = `rounded-xl bg-white/5 px-4 py-2 pg-violation-log-item pg-severity-${severity}`;
  item.innerHTML = `<span class="pg-severity-pill pg-severity-${severity}">${severityLabel(severity)}</span> ${reason} — ${new Date().toLocaleTimeString()}`;
  logList.prepend(item);

  const snapshot = captureSnapshot();
  const payload = {
    type,
    severity,
    message: reason,
    snapshot,
    occurredAt: new Date().toISOString(),
  };

  document.dispatchEvent(new CustomEvent("examguard:violation", { detail: payload }));
  window.ExamGuardSession?.reportViolation?.(payload);

  if (warningCount >= sessionWarningLimit) {
    lockSession();
  } else if (sessionStatus) {
    sessionStatus.textContent = "Warning Recorded";
  }
}

function lockSession() {
  sessionLocked = true;
  if (sessionStatus) sessionStatus.textContent = "Locked";
  if (faceStatus) faceStatus.textContent = "Stopped";
  if (submitBtn) submitBtn.disabled = true;
  if (simulateBtn) simulateBtn.disabled = true;
  if (trackingInterval) {
    clearInterval(trackingInterval);
    trackingInterval = null;
  }

  const item = document.createElement("div");
  item.className = "rounded-xl bg-white/5 px-4 py-2";
  item.textContent = "Session locked after reaching the warning limit.";
  logList.prepend(item);
  document.dispatchEvent(new CustomEvent("examguard:locked"));
}

if (enableCameraBtn) enableCameraBtn.addEventListener("click", enableCamera);
if (simulateBtn) {
  simulateBtn.addEventListener("click", () => addWarning("simulated", SEVERITY.MINOR, "Simulated monitoring violation"));
}

if (submitBtn) {
  submitBtn.addEventListener("click", () => {
    sessionLocked = true;
    if (sessionStatus) sessionStatus.textContent = "Submitted";
    if (faceStatus) faceStatus.textContent = "Stopped";
    submitBtn.disabled = true;
    if (simulateBtn) simulateBtn.disabled = true;
    if (trackingInterval) clearInterval(trackingInterval);
  });
}

document.addEventListener("visibilitychange", () => {
  if (!tabStatus) return;

  if (document.hidden) {
    tabHiddenSince = Date.now();
    tabStatus.textContent = "Tab Switched";
    tabStatus.className = "eg-badge-danger";
    tabSwitchCount += 1;
    const severity = tabSwitchCount >= 3 ? SEVERITY.MODERATE : SEVERITY.MINOR;
    addWarning("tab_switch", severity, "Tab switching detected");
  } else {
    tabHiddenSince = null;
    tabStatus.textContent = "Tab Active";
    tabStatus.className = "eg-badge-success";
    if (!sessionLocked && sessionStatus && warningCount > 0) {
      sessionStatus.textContent = "Warning Recorded";
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
