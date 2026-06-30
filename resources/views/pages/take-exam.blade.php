@extends('layouts.app')

@section('title', 'Take Exam - ExamGuard')

@section('body_attrs')
data-role="student" class="te-exam-body"
@endsection

@push('head')
<style>
body.te-exam-body {
    --te-col-left: 88px;
    --te-col-right: 220px;
    --te-center-width: 640px;
    --te-sandwich-gap: 20px;
    background: #07111f !important;
    background-image: none !important;
}
body.te-exam-body .eg-btn-primary {
    background: #3b82f6 !important;
    color: #fff !important;
}
body.te-exam-body .eg-btn-primary:hover:not(:disabled) {
    background: #2563eb !important;
}

/* ── Pre-flight gate ── */
.te-prefight {
    position: fixed;
    inset: 0;
    z-index: 300;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(0,0,0,0.60);
    backdrop-filter: blur(10px);
}
.te-prefight[hidden] { display: none !important; }
.te-prefight-card {
    width: 100%;
    max-width: 720px;
    background: #162444;
    border: 0.5px solid rgba(255,255,255,0.10);
    border-radius: 16px;
    box-shadow: 0 18px 60px rgba(0,0,0,0.45);
    padding: 22px 22px 18px;
}
.te-prefight-title {
    margin: 0 0 6px;
    font-size: 18px;
    font-weight: 700;
    color: #fff;
}
.te-prefight-sub {
    margin: 0 0 16px;
    font-size: 13px;
    line-height: 1.55;
    color: rgba(255,255,255,0.55);
}
.te-checks {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px 12px;
    margin-bottom: 16px;
}
.te-check {
    background: rgba(255,255,255,0.04);
    border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 12px 12px 10px;
}
.te-check-h {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 6px;
}
.te-check-name {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgba(255,255,255,0.45);
}
.te-check-status {
    font-size: 10px;
    font-weight: 700;
    padding: 4px 8px;
    border-radius: 9999px;
    border: 0.5px solid rgba(255,255,255,0.10);
    background: rgba(255,255,255,0.05);
    color: rgba(255,255,255,0.60);
}
.te-check-status.pass {
    background: rgba(34,197,94,0.14);
    border-color: rgba(34,197,94,0.25);
    color: rgba(134,239,172,0.95);
}
.te-check-status.fail {
    background: rgba(239,68,68,0.14);
    border-color: rgba(239,68,68,0.25);
    color: rgba(252,165,165,0.95);
}
.te-check-msg {
    margin: 0;
    font-size: 12px;
    line-height: 1.5;
    color: rgba(255,255,255,0.52);
}
.te-prefight-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
}
.te-prefight-note {
    margin: 10px 0 0;
    font-size: 11px;
    color: rgba(255,255,255,0.38);
    line-height: 1.45;
}
.te-prefight-note strong { color: rgba(251,191,36,0.85); font-weight: 600; }

.te-camera-inline[hidden] { display: none !important; }

/* ── Three-column sandwich layout ── */
.te-shell {
    display: grid;
    grid-template-columns: var(--te-col-left) var(--te-center-width) var(--te-col-right);
    gap: var(--te-sandwich-gap);
    width: fit-content;
    max-width: 100%;
    margin: 0 auto;
    padding: 28px 24px 64px;
    align-items: start;
    box-sizing: border-box;
}

.te-side-panel {
    position: sticky;
    top: 24px;
    background: #162444;
    border: 0.5px solid rgba(255,255,255,0.10);
    border-radius: 14px;
    box-shadow: 0 8px 28px rgba(0,0,0,0.22);
    max-height: calc(100vh - 48px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.te-col-left {
    width: var(--te-col-left);
    padding: 0;
    overflow: hidden;
}
.te-col-right {
    width: var(--te-col-right);
    padding: 0;
}

.te-panel-header, .te-details-header {
    padding: 12px 14px;
    font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.1em;
    color: rgba(255,255,255,0.40);
    border-bottom: 0.5px solid rgba(255,255,255,0.10);
    flex-shrink: 0;
}
.te-panel-body {
    overflow-y: auto;
    flex: 1;
    min-height: 0;
    padding: 10px 12px 12px;
}

/* ── Left checklist ── */
.te-checklist { display: flex; flex-direction: column; gap: 2px; list-style: none; margin: 0; padding: 0; }
.te-check-item {
    display: flex; align-items: center; gap: 7px;
    padding: 4px 2px; font-size: 10px; font-weight: 600;
    color: rgba(255,255,255,0.42); user-select: none;
}
.te-check-box {
    width: 11px; height: 11px; flex-shrink: 0;
    border: 1px solid rgba(255,255,255,0.22); border-radius: 2px;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.15s, border-color 0.15s;
}
.te-check-item.done { color: rgba(255,255,255,0.72); }
.te-check-item.done .te-check-box {
    background: #3b82f6; border-color: #3b82f6;
}
.te-check-item.done .te-check-box::after {
    content: ''; width: 3px; height: 5px;
    border: solid #fff; border-width: 0 1px 1px 0;
    transform: rotate(45deg) translate(-0.5px, -0.5px);
}

/* ── Center column ── */
.te-col-center {
    width: var(--te-center-width);
    max-width: 100%;
    min-width: 0;
    padding: 0;
    box-sizing: border-box;
    position: relative;
}
.te-scroll-track {
    position: absolute; top: 0; right: -10px;
    width: 3px; height: 100%;
    background: rgba(255,255,255,0.06);
    border-radius: 3px;
    pointer-events: none;
}
.te-scroll-fill {
    width: 100%; height: 0%;
    background: rgba(59,130,246,0.55);
    border-radius: 3px;
    transition: height 0.12s ease-out;
}
.te-exam-head { margin-bottom: 12px; text-align: left; }
.te-exam-title {
    margin: 0 0 4px; font-size: 18px; font-weight: 600; color: #fff; line-height: 1.35;
}
.te-exam-class { font-size: 12px; color: rgba(255,255,255,0.42); }
.te-instructions-wrap { margin-bottom: 0; }
.te-instructions-toggle {
    display: inline-flex; align-items: center; gap: 6px;
    background: none; border: none; padding: 0; cursor: pointer;
    font-size: 12px; font-weight: 600; color: #93c5fd; font-family: inherit;
    text-decoration: underline; text-underline-offset: 3px;
}
.te-instructions-icon { font-size: 14px; color: #60a5fa; text-decoration: none; }
.te-instructions-toggle:hover { color: #bfdbfe; }
.te-instructions-toggle:hover .te-instructions-icon { color: #93c5fd; }
.te-instructions {
    margin-top: 12px; font-size: 14px; font-weight: 400;
    color: rgba(255,255,255,0.62); line-height: 1.6;
}
.te-content-divider {
    margin: 24px 0 8px;
    border: none;
    border-top: 0.5px solid rgba(255,255,255,0.08);
}

/* ── Questions ── */
.te-question {
    padding: 28px 0;
    border-bottom: 0.5px solid rgba(255,255,255,0.07);
}
.te-question:last-of-type { border-bottom: none; }
.te-question h3 {
    margin: 0 0 16px; font-size: 14px; font-weight: 500;
    color: rgba(255,255,255,0.88); line-height: 1.55;
}
.te-q-num {
    font-weight: 700; color: #3b82f6; margin-right: 6px;
}
.te-q-text { font-weight: 500; color: rgba(255,255,255,0.88); }
.te-choices { display: flex; flex-direction: column; gap: 2px; }
.te-choice {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 10px 12px 10px 8px; margin: 0;
    border-left: 3px solid transparent; cursor: pointer;
    background: transparent; transition: border-color 0.15s, background 0.15s;
    border-radius: 0 6px 6px 0;
}
.te-choice:not(.selected):hover { background: rgba(255,255,255,0.045); }
.te-choice input[type="radio"] {
    appearance: none; -webkit-appearance: none;
    width: 16px; height: 16px; margin-top: 2px; flex-shrink: 0;
    border: 1.5px solid rgba(255,255,255,0.28); border-radius: 50%;
    background: transparent; cursor: pointer;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.te-choice input[type="radio"]:checked {
    border-color: #3b82f6;
    box-shadow: inset 0 0 0 3px #07111f, inset 0 0 0 5px #3b82f6;
}
.te-choice span { font-size: 14px; font-weight: 400; color: rgba(255,255,255,0.62); line-height: 1.5; }
.te-choice.selected { border-left-color: #3b82f6; }
.te-choice.selected span { color: rgba(255,255,255,0.90); }

.te-submit-bottom {
    margin-top: 36px; padding-top: 28px;
    border-top: 0.5px solid rgba(255,255,255,0.08);
    text-align: center;
}
.te-submit-bottom-btn {
    min-width: 200px; padding: 10px 24px !important;
    font-size: 13px !important; border-radius: 16px !important;
}

/* ── Right panel — Canvas-style details sidebar ── */
.te-details-header {
    padding: 12px 16px;
    font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.1em;
    color: rgba(255,255,255,0.40);
    border-bottom: 0.5px solid rgba(255,255,255,0.10);
    flex-shrink: 0;
}
.te-details-body {
    overflow-y: auto;
    flex: 1;
    min-height: 0;
}
.te-details-row {
    padding: 14px 16px;
    border-bottom: 0.5px solid rgba(255,255,255,0.07);
}
.te-details-row:last-child { border-bottom: none; }
.te-details-label {
    display: block;
    font-size: 9px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.1em;
    color: rgba(255,255,255,0.35);
    margin-bottom: 8px;
}
.te-sidebar-meta {
    margin: 0 0 4px; font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.82);
}
.te-sidebar-meta--muted {
    font-size: 11px; font-weight: 400; color: rgba(255,255,255,0.42);
}
.te-sidebar-meta:last-child { margin-bottom: 0; }
.te-details-hint {
    margin: 8px 0 0; font-size: 10px; line-height: 1.4;
    color: rgba(255,255,255,0.40);
}
.te-details-hint[hidden] { display: none !important; }
.te-details-hint--warn { color: #fbbf24; }
.te-details-hint--danger { color: #f87171; }
.te-timer-block { width: 100%; }
.te-timer-display {
    font-size: 28px; font-weight: 700; font-variant-numeric: tabular-nums;
    color: #fff; line-height: 1.1; transition: color 0.2s;
}
.te-timer-block.te-timer--warn .te-timer-display { color: #fbbf24; }
.te-timer-block.te-timer--danger .te-timer-display { color: #f87171; }
#warningBadge { display: inline-flex; }
#warningBadge.te-warn-flash {
    animation: teWarnFlash 0.55s ease;
    background: rgba(239, 68, 68, 0.22) !important;
    color: #fca5a5 !important;
    border: 0.5px solid rgba(239, 68, 68, 0.35);
}
/* warning badge escalation: 1 = yellow, 2 = orange (stronger), 3 = red pulse */
.te-warn-orange {
    background: rgba(245,158,11,0.25) !important;
    border-color: rgba(245,158,11,0.35) !important;
    color: #fdba74 !important;
}
.te-warn-max {
    animation: teWarnPulse 0.9s ease-in-out infinite;
}
@keyframes teWarnPulse {
    0%, 100% { transform: translateX(0) scale(1); }
    30% { transform: translateX(-1px) scale(1.01); }
    60% { transform: translateX(1px) scale(1.02); }
}
@keyframes teWarnFlash {
    0%, 100% { transform: translateX(0); }
    20% { transform: translateX(-3px); }
    40% { transform: translateX(3px); }
    60% { transform: translateX(-2px); }
    80% { transform: translateX(2px); }
}
.te-camera-toggle {
    position: relative; width: 44px; height: 44px;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 12px; cursor: pointer;
    background: rgba(255,255,255,0.04);
    border: 0.5px solid rgba(255,255,255,0.08);
    color: rgba(255,255,255,0.55);
    transition: background 0.15s, color 0.15s;
}
.te-camera-toggle:hover { background: rgba(255,255,255,0.08); color: #fff; }
.te-camera-toggle i { font-size: 20px; }
.te-cam-dot {
    position: absolute; top: 7px; right: 7px;
    width: 7px; height: 7px; border-radius: 50%;
    background: rgba(255,255,255,0.30); border: 1.5px solid #162444;
}
.te-cam-dot.ok { background: #22c55e; }
.te-cam-dot.warn { background: rgba(255,255,255,0.35); }
.te-cam-dot.bad { background: #ef4444; }
.te-camera-inline { margin-top: 10px; }
.te-camera-inline[hidden] { display: none !important; }
.te-camera-box {
    aspect-ratio: 4/3; border-radius: 8px; background: #101c36;
    border: 0.5px solid rgba(255,255,255,0.06); overflow: hidden; margin-bottom: 8px;
    display: flex; align-items: center; justify-content: center;
}
.te-camera-box video { width: 100%; height: 100%; object-fit: cover; }
.te-camera-placeholder { text-align: center; padding: 12px 8px; }
.te-camera-placeholder i { font-size: 22px; color: rgba(255,255,255,0.18); display: block; margin-bottom: 4px; }
.te-camera-placeholder span { font-size: 10px; color: rgba(255,255,255,0.38); }
.te-enable-cam { width: 100%; padding: 7px; font-size: 11px; border-radius: 16px; }
.te-submit-side { width: 100%; padding: 10px 12px !important; font-size: 12px !important; border-radius: 16px !important; }
.te-proctoring-notice {
    margin: 0; padding: 10px 16px 12px;
    font-size: 10px; line-height: 1.45;
    color: rgba(255,255,255,0.38);
    border-top: 0.5px solid rgba(255,255,255,0.07);
    background: rgba(0,0,0,0.12);
}
.te-proctoring-notice strong { color: rgba(251, 191, 36, 0.85); font-weight: 600; }
.te-sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }

/* ── Modal ── */
.te-modal-backdrop {
    position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,0.55);
    display: flex; align-items: center; justify-content: center; padding: 20px;
}
.te-modal-backdrop[hidden] { display: none !important; }
.te-modal {
    background: #162444; border: 0.5px solid rgba(255,255,255,0.10);
    border-radius: 14px; padding: 24px; max-width: 400px; width: 100%;
}
.te-modal h3 { margin: 0 0 10px; font-size: 17px; font-weight: 700; color: #fff; }
.te-modal p { margin: 0 0 20px; font-size: 13px; color: rgba(255,255,255,0.55); line-height: 1.5; }
.te-modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
.te-submit-success-score {
    display: flex; align-items: baseline; justify-content: center; gap: 4px;
    margin: 0 0 8px; font-size: 36px; font-weight: 700; color: #fff;
}
.te-submit-success-score span { font-size: 18px; font-weight: 500; color: rgba(255,255,255,0.45); }
.te-submit-success-note { margin: 0 0 20px; text-align: center; }
.te-submit-success-modal h3 { text-align: center; }
.te-submit-success-modal .te-modal-actions { justify-content: center; }

/* ── Max warnings modal (student) ── */
.te-max-backdrop {
    position: fixed; inset: 0; z-index: 260;
    display: flex; align-items: center; justify-content: center;
    padding: 24px;
    background: rgba(0,0,0,0.70);
    backdrop-filter: blur(10px);
}
.te-max-backdrop[hidden] { display: none !important; }
.te-max-card {
    width: 100%;
    max-width: 520px;
    background: #162444;
    border: 0.5px solid rgba(255,255,255,0.10);
    border-radius: 16px;
    box-shadow: 0 18px 60px rgba(0,0,0,0.45);
    padding: 22px;
    text-align: center;
}
.te-max-card h3 { margin: 0 0 10px; font-size: 18px; font-weight: 700; color: #fff; }
.te-max-card p { margin: 0 0 20px; font-size: 13px; line-height: 1.55; color: rgba(255,255,255,0.55); }
.te-max-actions { display: flex; justify-content: center; }
.te-max-return { min-width: 200px; padding: 10px 20px !important; font-size: 13px !important; border-radius: 16px !important; }

@media (max-width: 1100px) {
    body.te-exam-body { --te-center-width: 520px; --te-col-right: 200px; }
    .te-shell {
        grid-template-columns: var(--te-col-left) 1fr var(--te-col-right);
        width: 100%;
        padding-left: 16px;
        padding-right: 16px;
    }
}
@media (max-width: 768px) {
    body.te-exam-body { --te-col-left: 56px; --te-col-right: 100%; }
    .te-shell {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    .te-side-panel {
        position: static;
        max-height: none;
        width: 100% !important;
    }
    .te-col-center { width: 100%; }
    .te-check-label { display: none; }
    .te-timer-display { font-size: 24px; }
}
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
@endpush

@section('content')
<div class="warning-toast" id="warningToast">Monitoring warning recorded.</div>

<div class="te-shell">
    <aside class="te-side-panel te-col-left" aria-label="Question progress">
        <div class="te-panel-header">Progress</div>
        <div class="te-panel-body">
            <ul class="te-checklist" id="questionChecklist"></ul>
        </div>
    </aside>

    <main class="te-col-center">
        <div class="te-scroll-track" aria-hidden="true"><div class="te-scroll-fill" id="scrollProgressFill"></div></div>
        <header class="te-exam-head">
            <h1 class="te-exam-title" id="activeExamTitle">Loading exam...</h1>
            <span class="te-exam-class" id="activeExamClass"></span>
        </header>

        <div class="te-instructions-wrap" id="instructionsWrap" hidden>
            <button type="button" class="te-instructions-toggle" id="instructionsToggle" aria-expanded="false">
                <i class="ti ti-info-circle te-instructions-icon" aria-hidden="true"></i>
                <span class="te-instructions-toggle-text">View instructions</span>
            </button>
            <div class="te-instructions" id="instructionsBlock" hidden>
                <p id="activeExamInstructions" style="margin:0"></p>
            </div>
        </div>

        <hr class="te-content-divider" id="contentDivider">

        <div id="examQuestions"></div>

        <div class="te-submit-bottom">
            <button type="button" class="eg-btn-primary te-submit-btn te-submit-bottom-btn" id="submitExamBtnBottom">Submit exam</button>
        </div>
    </main>

    <aside class="te-side-panel te-col-right" aria-label="Exam details">
        <div class="te-details-header">Exam Details</div>
        <div class="te-details-body">
            <div class="te-details-row">
                <p class="te-sidebar-meta" id="sidebarProfessor">—</p>
                <p class="te-sidebar-meta te-sidebar-meta--muted" id="sidebarQuestionCount">—</p>
                <p class="te-sidebar-meta te-sidebar-meta--muted" id="sidebarDueDate" hidden></p>
            </div>

            <div class="te-details-row">
                <span class="te-details-label">Time Left</span>
                <div class="te-timer-block" id="examTimerWrap">
                    <div class="te-timer-display" id="examTimer">--:--</div>
                </div>
            </div>

            <div class="te-details-row">
                <span class="te-details-label">Warnings</span>
                <span class="eg-badge-warning" id="warningBadge">
                    <i class="ti ti-alert-triangle" style="font-size:12px;margin-right:3px"></i>
                    <span id="warningCount">0</span>/<span id="warningLimitDisplay">3</span>
                </span>
            </div>

            <div class="te-details-row">
                <span class="te-details-label">Camera</span>
                <button type="button" class="te-camera-toggle" id="teCameraToggle" aria-label="Camera status" aria-expanded="false">
                    <i class="ti ti-camera"></i>
                    <span class="te-cam-dot" id="cameraStatusDot"></span>
                </button>
                <div class="te-camera-inline" id="teCameraExpand" hidden>
                    <div class="te-camera-box">
                        <video id="cameraVideo" class="hidden w-full h-full" autoplay muted playsinline></video>
                        <div id="cameraPlaceholder" class="te-camera-placeholder">
                            <i class="ti ti-camera-off"></i>
                            <span>Camera not enabled</span>
                        </div>
                    </div>
                    <button type="button" class="eg-btn-primary te-enable-cam" id="enableCameraBtn">Enable camera</button>
                </div>
            </div>

            <div class="te-details-row">
                <button type="button" class="eg-btn-primary te-submit-side te-submit-btn" id="submitExamBtn">Submit</button>
            </div>
            @if(!empty($proctoringDemo))
            <p class="te-proctoring-notice">
                <strong>Demo proctoring.</strong>
                Violations are stored server-side when reported. Browser monitoring is assistive only — not production exam security.
            </p>
            @endif
        </div>
    </aside>
</div>

<span class="te-sr-only" id="cameraStatus">Waiting</span>
<span class="te-sr-only" id="sessionStatus">In progress</span>
<span class="te-sr-only" id="tabStatus">Active</span>

<div class="te-prefight" id="preflightGate" hidden>
    <div class="te-prefight-card" role="dialog" aria-labelledby="preflightTitle" aria-modal="true">
        <h2 class="te-prefight-title" id="preflightTitle">System check required</h2>
        <p class="te-prefight-sub">Complete these checks before entering your exam. You can’t proceed until all required checks pass.</p>

        <div class="te-checks">
            <div class="te-check">
                <div class="te-check-h">
                    <span class="te-check-name">Camera</span>
                    <span class="te-check-status" id="pfCameraStatus">Checking…</span>
                </div>
                <p class="te-check-msg" id="pfCameraMsg">Detecting camera access.</p>
            </div>
            <div class="te-check">
                <div class="te-check-h">
                    <span class="te-check-name">Microphone</span>
                    <span class="te-check-status" id="pfMicStatus">Checking…</span>
                </div>
                <p class="te-check-msg" id="pfMicMsg">Detecting microphone access.</p>
            </div>
            <div class="te-check">
                <div class="te-check-h">
                    <span class="te-check-name">Browser</span>
                    <span class="te-check-status" id="pfBrowserStatus">Checking…</span>
                </div>
                <p class="te-check-msg" id="pfBrowserMsg">Confirming compatibility.</p>
            </div>
            <div class="te-check">
                <div class="te-check-h">
                    <span class="te-check-name">Internet</span>
                    <span class="te-check-status" id="pfNetStatus">Checking…</span>
                </div>
                <p class="te-check-msg" id="pfNetMsg">Verifying connection stability.</p>
            </div>
            <div class="te-check">
                <div class="te-check-h">
                    <span class="te-check-name">Screen share</span>
                    <span class="te-check-status" id="pfScreenStatus">Optional</span>
                </div>
                <p class="te-check-msg" id="pfScreenMsg">If required by your professor, grant screen share permission.</p>
            </div>
            <div class="te-check">
                <div class="te-check-h">
                    <span class="te-check-name">Ready</span>
                    <span class="te-check-status" id="pfReadyStatus">Pending</span>
                </div>
                <p class="te-check-msg" id="pfReadyMsg">All required checks must pass.</p>
            </div>
        </div>

        <div class="te-prefight-actions">
            <button type="button" class="eg-btn-secondary" id="pfRetryBtn">Retry checks</button>
            <button type="button" class="eg-btn-primary" id="pfProceedBtn" disabled>Enter exam</button>
        </div>
        <p class="te-prefight-note"><strong>Note:</strong> This is a demo-style browser proctoring flow, not hardened exam security.</p>
    </div>
</div>

<div class="te-max-backdrop" id="maxWarningsModal" hidden>
    <div class="te-max-card" role="dialog" aria-labelledby="maxWarningsTitle" aria-modal="true">
        <h3 id="maxWarningsTitle">Maximum warnings reached</h3>
        <p id="maxWarningsMessage">You have reached the maximum number of warnings. Your professor has been notified.</p>
        <div class="te-max-actions">
            <button type="button" class="eg-btn-primary te-max-return" id="maxWarningsReturn">Return to dashboard</button>
        </div>
    </div>
</div>

<div class="te-modal-backdrop" id="submitConfirmModal" hidden>
    <div class="te-modal" role="dialog" aria-labelledby="submitConfirmTitle">
        <h3 id="submitConfirmTitle">Submit exam?</h3>
        <p>Are you sure you want to submit? This cannot be undone.</p>
        <div class="te-modal-actions">
            <button type="button" class="eg-btn-secondary" id="submitConfirmCancel">Cancel</button>
            <button type="button" class="eg-btn-primary" id="submitConfirmOk">Submit Exam</button>
        </div>
    </div>
</div>

<div class="te-modal-backdrop" id="unansweredConfirmModal" hidden>
    <div class="te-modal" role="dialog" aria-labelledby="unansweredConfirmTitle">
        <h3 id="unansweredConfirmTitle">Unanswered questions</h3>
        <p>You still have unanswered questions. You can go back to review your answers, or submit your exam now.</p>
        <div class="te-modal-actions">
            <button type="button" class="eg-btn-secondary" id="unansweredConfirmBack">Review answers</button>
            <button type="button" class="eg-btn-primary" id="unansweredConfirmSubmit">Submit anyway</button>
        </div>
    </div>
</div>

<div class="te-modal-backdrop" id="submitSuccessModal" hidden>
    <div class="te-modal te-submit-success-modal" role="dialog" aria-labelledby="submitSuccessTitle">
        <h3 id="submitSuccessTitle">Exam submitted</h3>
        <p class="te-submit-success-score" id="submitSuccessScore" aria-live="polite">0<span>/0</span></p>
        <p class="te-submit-success-note" id="submitSuccessMessage">Your answers have been recorded.</p>
        <div class="te-modal-actions">
            <button type="button" class="eg-btn-primary" id="submitSuccessOk">View results</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/js/api-client.js"></script>
<script src="/js/auth-guard.js"></script>
<script src="/js/monitoring.js"></script>
<script src="/js/take-exam.js?v=17"></script>
@endpush
