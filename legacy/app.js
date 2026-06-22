const enableCameraBtn = document.getElementById("enableCameraBtn");
const cameraVideo = document.getElementById("cameraVideo");
const cameraPlaceholder = document.getElementById("cameraPlaceholder");
const cameraStatus = document.getElementById("cameraStatus");
const faceStatus = document.getElementById("faceStatus");
const sessionStatus = document.getElementById("sessionStatus");
const warningCountEl = document.getElementById("warningCount");
const tabStatus = document.getElementById("tabStatus");
const simulateBtn = document.getElementById("simulateBtn");
const submitBtn = document.getElementById("submitBtn");
const logList = document.getElementById("logList");
const warningToast = document.getElementById("warningToast");

let warningCount = 0;
let sessionLocked = false;
let faceLandmarker = null;
let trackingInterval = null;
let lastNoFaceWarningAt = 0;
let lastMultipleFaceWarningAt = 0;
let sessionWarningLimit = 3;
document.addEventListener("examguard:warning-limit", (event) => { sessionWarningLimit = Number(event.detail) || 3; });

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
      delegate: "GPU"
    },
    runningMode: "VIDEO",
    numFaces: 2
  });

  if (faceStatus) faceStatus.textContent = "Active";
  return faceLandmarker;
}

async function enableCamera() {
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    addWarning("Camera API is not available in this browser");
    return;
  }

  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
    cameraVideo.srcObject = stream;
    cameraVideo.style.display = "block";
    cameraPlaceholder.style.display = "none";
    cameraStatus.textContent = "Active";

    await setupFaceLandmarker();
    startFaceTracking();
  } catch (error) {
    cameraStatus.textContent = "Blocked";
    if (faceStatus) faceStatus.textContent = "Unavailable";
    addWarning("Camera permission or face tracking was blocked or unavailable");
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
        addWarning("No face detected in camera frame");
      }
      return;
    }

    if (faceCount > 1) {
      if (faceStatus) faceStatus.textContent = "Multiple Faces";
      if (now - lastMultipleFaceWarningAt > 6000) {
        lastMultipleFaceWarningAt = now;
        addWarning("Multiple faces detected in camera frame");
      }
    }
  }, 700);
}

function showWarningToast(message) {
  if (!warningToast) return;

  warningToast.textContent = message;
  warningToast.classList.add("show");

  setTimeout(() => {
    warningToast.classList.remove("show");
  }, 3500);
}

function addWarning(reason) {
  if (sessionLocked || !warningCountEl || !logList) return;

  warningCount += 1;
  warningCountEl.textContent = warningCount;
  showWarningToast(reason);

  if (logList.children.length === 1 && logList.children[0].textContent === "No violations recorded.") {
    logList.innerHTML = "";
  }

  const item = document.createElement("div");
  item.className = "log-item";
  item.textContent = `${reason} - ${new Date().toLocaleTimeString()}`;
  logList.prepend(item);

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
  item.className = "log-item";
  item.textContent = "Session locked after reaching the warning limit.";
  logList.prepend(item);
  document.dispatchEvent(new CustomEvent("examguard:locked"));
}

if (enableCameraBtn) {
  enableCameraBtn.addEventListener("click", enableCamera);
}

if (simulateBtn) {
  simulateBtn.addEventListener("click", () => {
    addWarning("Simulated monitoring violation");
  });
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
    tabStatus.textContent = "Tab Switched";
    tabStatus.className = "status-badge danger";
    addWarning("Tab switching detected");
  } else {
    tabStatus.textContent = "Tab Active";
    tabStatus.className = "status-badge success";
    if (!sessionLocked && sessionStatus && warningCount > 0) {
      sessionStatus.textContent = "Warning Recorded";
    }
  }
});
