@extends('layouts.app')

@section('title', 'Student Dashboard — ExamGuard')

@section('body_attrs')
data-role="student" class="eg-shell-body"
@endsection

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3/dist/tabler-icons.min.css">

<style>
*, *::before, *::after { box-sizing: border-box; }
html, body {
    margin: 0; padding: 0; height: 100%;
    background: #0f1e3d; color: #fff;
    font-family: 'Plus Jakarta Sans', ui-sans-serif, sans-serif;
    overflow: hidden;
}
.sd-layout { display: flex; height: 100vh; overflow: hidden; }

/* ── Sidebar (matches professor shell) ── */
.pg-sidebar {
    width: 220px; flex-shrink: 0;
    background: #0f1e3d;
    border-right: 0.5px solid rgba(255,255,255,0.08);
    display: flex; flex-direction: column;
    height: 100vh;
}
.pg-sidebar-top { padding: 20px 16px 16px; flex-shrink: 0; }
.pg-sidebar-brand {
    display: flex; align-items: center; justify-content: center;
    text-decoration: none;
}
.pg-sidebar-brand img {
    height: 68px; width: auto; object-fit: contain;
    filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.5)) drop-shadow(0 0 22px rgba(59, 130, 246, 0.28));
    animation: pg-logo-glow 3.5s ease-in-out infinite;
}
.pg-sidebar-nav { flex: 1; padding: 8px 0; overflow-y: auto; min-height: 0; }
.pg-sidebar .pg-nav-link {
    display: flex; align-items: center; gap: 10px;
    width: 100%; padding: 10px 20px;
    font-size: 14px; color: rgba(255,255,255,0.55);
    text-decoration: none; background: none; border: none;
    border-left: 3px solid transparent;
    cursor: pointer; text-align: left; font-family: inherit;
    transition: background 0.12s, color 0.12s;
}
.pg-sidebar .pg-nav-link:hover { background: rgba(255,255,255,0.05); color: #fff; }
.pg-sidebar .pg-nav-link.active {
    background: rgba(59,130,246,0.10);
    color: #fff; border-left-color: #3b82f6;
}
.pg-nav-link i { font-size: 17px; flex-shrink: 0; }
.pg-nav-label {
    font-size: 10px; font-weight: 500;
    letter-spacing: 2px; text-transform: uppercase;
    color: rgba(255,255,255,0.30);
    padding: 8px 20px 6px;
}
.pg-sidebar .sd-sidebar-foot {
    padding: 12px 16px 16px;
    border-top: 0.5px solid rgba(255,255,255,0.08);
    flex-shrink: 0;
}

.sd-class-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 20px; border: none; background: none; cursor: pointer;
    color: rgba(255,255,255,0.60); font-size: 13px; font-family: inherit;
    width: 100%; text-align: left; transition: color 0.15s; white-space: nowrap;
}
.sd-class-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.sd-class-item:hover, .sd-class-item.active { color: #fff; }
.sd-join-link {
    display: flex; align-items: center; gap: 6px; margin: 4px 20px 0;
    background: none; border: none; color: #3b82f6; font-size: 13px;
    font-family: inherit; cursor: pointer; padding: 6px 0;
}
.sd-join-link:hover { text-decoration: underline; }
.sd-sidebar-flash { animation: sdSidebarFlash 1.2s ease; }
@keyframes sdSidebarFlash {
    0%, 100% { background: transparent; }
    30% { background: rgba(59,130,246,0.12); }
}
.sd-exam-shortcut {
    display: flex; align-items: center; gap: 8px; width: 100%;
    background: none; border: none; color: rgba(255,255,255,0.55);
    font-size: 13px; font-family: inherit; cursor: pointer; padding: 6px 0;
    white-space: nowrap;
}
.sd-exam-shortcut:hover { color: #fff; }

/* ── Main workspace (matches professor shell) ── */
.pg-main {
    flex: 1; display: flex; flex-direction: column;
    min-width: 0; height: 100vh; background: #0f1e3d;
    position: relative;
}
.pg-floating-actions {
    position: absolute;
    top: 16px;
    right: 20px;
    z-index: 30;
    display: flex;
    align-items: center;
    gap: 10px;
}
.pg-floating-profile,
.pg-floating-notify {
    position: relative;
    cursor: pointer;
    padding: 4px;
    border-radius: 50%;
    background: rgba(22, 36, 68, 0.94);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 0.5px solid rgba(255,255,255,0.12);
    box-shadow:
        0 8px 28px rgba(0, 0, 0, 0.32),
        0 0 0 1px rgba(255, 255, 255, 0.04);
    transition: border-color 0.18s, box-shadow 0.18s, transform 0.18s;
    appearance: none;
    font-family: inherit;
    color: inherit;
}
.pg-floating-profile:hover,
.pg-floating-notify:hover {
    border-color: rgba(255,255,255,0.20);
    box-shadow:
        0 10px 32px rgba(0, 0, 0, 0.38),
        0 0 0 1px rgba(255, 255, 255, 0.06);
    transform: translateY(-1px);
}
.pg-floating-notify {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: rgba(255,255,255,0.78);
}
.pg-floating-notify i { font-size: 18px; }
.pg-floating-notify.has-unread {
    border-color: rgba(59,130,246,0.35);
    color: #fff;
}
.pg-notify-badge {
    position: absolute;
    top: 2px;
    right: 2px;
    min-width: 16px;
    height: 16px;
    padding: 0 4px;
    border-radius: 999px;
    background: #ef4444;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    line-height: 16px;
    text-align: center;
    box-shadow: 0 0 0 2px #162444;
}
.pg-notify-badge.hidden { display: none; }
.pg-notify-panel {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: min(340px, calc(100vw - 32px));
    max-height: 380px;
    background: #162444;
    border: 0.5px solid rgba(255,255,255,0.12);
    border-radius: 12px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.45);
    display: none;
    flex-direction: column;
    overflow: hidden;
    z-index: 210;
}
.pg-notify-panel.open { display: flex; }
.pg-notify-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    border-bottom: 0.5px solid rgba(255,255,255,0.08);
}
.pg-notify-head span { font-size: 13px; font-weight: 600; color: #fff; }
.pg-notify-mark-all {
    background: none; border: none; color: #3b82f6;
    font-size: 11px; font-family: inherit; cursor: pointer; padding: 0;
}
.pg-notify-mark-all:hover { color: #93c5fd; }
.pg-notify-mark-all:disabled { opacity: 0.35; cursor: not-allowed; }
.pg-notify-list { overflow-y: auto; max-height: 320px; }
.pg-notify-item {
    display: flex; align-items: flex-start; gap: 10px; width: 100%;
    padding: 12px 14px; background: none; border: none; border-bottom: 0.5px solid rgba(255,255,255,0.06);
    color: inherit; text-align: left; cursor: pointer; font-family: inherit;
}
.pg-notify-item:last-child { border-bottom: none; }
.pg-notify-item:hover { background: rgba(255,255,255,0.05); }
.pg-notify-item.unread { background: rgba(59,130,246,0.08); }
.pg-notify-item.unread:hover { background: rgba(59,130,246,0.12); }
.pg-notify-item-icon {
    width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 15px;
}
.pg-notify-item-icon.is-assigned { background: rgba(59,130,246,0.15); color: #3b82f6; }
.pg-notify-item-icon.is-removed { background: rgba(239,68,68,0.12); color: #ef4444; }
.pg-notify-item-icon.is-default { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.55); }
.pg-notify-item-body { min-width: 0; flex: 1; }
.pg-notify-item-title { display: block; font-size: 12px; font-weight: 600; color: #fff; margin-bottom: 2px; }
.pg-notify-item-message { display: block; font-size: 11px; color: rgba(255,255,255,0.55); line-height: 1.4; }
.pg-notify-item-time { display: block; margin-top: 4px; font-size: 10px; color: rgba(255,255,255,0.32); }
.pg-notify-empty,
.pg-notify-loading {
    padding: 28px 16px; text-align: center; font-size: 12px; color: rgba(255,255,255,0.42);
}
.pg-floating-profile .pg-avatar {
    width: 36px; height: 36px;
    font-size: 13px;
}
.pg-floating-profile .pg-dropdown {
    bottom: auto;
    top: calc(100% + 10px);
    left: auto;
    right: 0;
}
.pg-avatar {
    width: 32px; height: 32px; border-radius: 50%;
    background: #3b82f6; color: #fff;
    font-size: 12px; font-weight: 600;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.pg-dropdown {
    position: absolute; bottom: calc(100% + 6px); left: 0;
    width: 180px; background: #162444;
    border: 0.5px solid rgba(255,255,255,0.12);
    border-radius: 10px; padding: 6px 0;
    display: none; z-index: 200;
    box-shadow: 0 8px 32px rgba(0,0,0,0.4);
}
.pg-dropdown.open { display: block; }
.pg-dropdown button {
    display: flex; align-items: center; gap: 8px;
    width: 100%; padding: 8px 14px;
    font-size: 12px; color: rgba(255,255,255,0.8);
    text-decoration: none; background: none; border: none;
    cursor: pointer; text-align: left; font-family: inherit;
}
.pg-dropdown button:hover { background: rgba(255,255,255,0.06); color: #fff; }
.pg-dropdown .divider { height: 0.5px; background: rgba(255,255,255,0.08); margin: 4px 0; }
.pg-dropdown .danger { color: #f87171; }
.pg-body {
    flex: 1; overflow-y: auto; padding: 20px;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.08) transparent;
}
@keyframes pg-logo-glow {
    0%, 100% {
        filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.45)) drop-shadow(0 0 24px rgba(59, 130, 246, 0.22));
    }
    50% {
        filter: drop-shadow(0 0 18px rgba(59, 130, 246, 0.65)) drop-shadow(0 0 40px rgba(96, 165, 250, 0.38));
    }
}

/* ── Student page content ── */
.sd-view { display: none; padding: 56px 0 24px; min-height: 100%; }
.sd-view.active { display: block; }
.sd-home-feed { max-width: 1100px; margin: 0 auto; }
.sd-home-columns {
    display: flex; gap: 20px; align-items: flex-start;
}
.sd-home-main { flex: 1; min-width: 0; }
.sd-home-side {
    width: 280px; flex-shrink: 0;
    display: flex; flex-direction: column; gap: 14px;
}
.sd-home-brand { margin-bottom: 18px; }
.sd-home-brand-link {
    display: inline-flex; align-items: center; gap: 14px;
    text-decoration: none;
}
.sd-home-brand-link img {
    height: 52px; width: auto; object-fit: contain;
    filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.5)) drop-shadow(0 0 22px rgba(59, 130, 246, 0.28));
    animation: pg-logo-glow 3.5s ease-in-out infinite;
}
.sd-home-brand-link span {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 26px; font-weight: 600;
    color: rgba(255,255,255,0.88); letter-spacing: -0.5px;
    text-shadow: 0 0 24px rgba(59, 130, 246, 0.35);
}
.sd-greeting { margin-bottom: 20px; }
.sd-greeting h1 { margin: 0 0 6px; font-size: 20px; font-weight: 600; color: #fff; }
.sd-greeting p { margin: 0; font-size: 13px; color: rgba(255,255,255,0.50); }
.sd-home-section { margin-bottom: 28px; }
.sd-home-section:last-child { margin-bottom: 0; }
.sd-section-label {
    margin: 0 0 14px;
    font-size: 11px; font-weight: 400;
    letter-spacing: 2px; text-transform: uppercase;
    color: rgba(255,255,255,0.40);
}
.sd-section-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 14px;
}
.sd-section-header .sd-section-label { margin-bottom: 0; }
.sd-section-link {
    background: none; border: none; padding: 0;
    color: #3b82f6; font-size: 12px; font-family: inherit; cursor: pointer;
}
.sd-section-link:hover { text-decoration: underline; }

.sd-class-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
}
.sd-class-card {
    border-radius: 14px;
    overflow: hidden;
    cursor: pointer;
    border: 0.5px solid transparent;
    transition: border-color 0.2s ease, transform 0.2s ease;
}
.sd-class-card:hover {
    border-color: rgba(255,255,255,0.18);
    transform: translateY(-3px);
}
.sd-class-card-banner {
    position: relative;
    height: 88px;
    padding: 14px 14px 0;
}
.sd-class-card-banner-text h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    line-height: 1.2;
    color: #fff;
}
.sd-class-card-banner-text p {
    margin: 4px 0 0;
    font-size: 12px;
    font-weight: 400;
    color: rgba(255,255,255,0.80);
}
.sd-class-card-avatar {
    position: absolute;
    right: 14px;
    bottom: -16px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid rgba(0,0,0,0.25);
    background: rgba(255,255,255,0.18);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}
.sd-class-card-body {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    background: #162444;
    padding: 22px 14px 14px;
}
.sd-class-card-exams {
    font-size: 12px;
    color: rgba(255,255,255,0.40);
}
.sd-class-card-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}
.sd-class-card-icon {
    background: none;
    border: none;
    padding: 0;
    color: rgba(255,255,255,0.35);
    font-size: 16px;
    cursor: pointer;
    transition: color 0.15s;
}
.sd-class-card-icon:hover { color: #fff; }
.sd-class-ghost-card {
    display: flex; align-items: center; justify-content: center;
    min-height: 138px; border-radius: 14px;
    border: 1.5px dashed rgba(255,255,255,0.18);
    background: rgba(255,255,255,0.02);
    color: rgba(255,255,255,0.55);
    cursor: pointer; font-family: inherit;
    transition: border-color 0.2s ease, background 0.2s ease, color 0.2s ease, transform 0.2s ease;
}
.sd-class-ghost-card:hover {
    border-color: rgba(59,130,246,0.45);
    background: rgba(59,130,246,0.06);
    color: #fff;
    transform: translateY(-2px);
}
.sd-class-ghost-inner {
    display: flex; flex-direction: column; align-items: center; gap: 8px;
}
.sd-class-ghost-inner i { font-size: 22px; color: #3b82f6; }
.sd-class-ghost-inner span { font-size: 13px; font-weight: 500; }
.sd-classes-empty {
    grid-column: 1 / -1;
    text-align: center;
    background: #162444;
    border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 14px;
    padding: 36px 20px;
}
.sd-classes-empty i {
    display: block;
    font-size: 48px;
    color: rgba(255,255,255,0.12);
    margin-bottom: 12px;
}
.sd-classes-empty p {
    margin: 0 0 10px;
    font-size: 15px;
    color: #fff;
}

.sd-live-banner {
    display: none; align-items: center; justify-content: space-between; gap: 12px;
    background: rgba(34,197,94,0.08); border: 0.5px solid rgba(34,197,94,0.25);
    border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; position: relative;
}
.sd-live-banner.visible { display: flex; }
.sd-live-banner-left { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; font-size: 13px; }
.sd-pulse-dot {
    width: 5px; height: 5px; border-radius: 50%; background: #22c55e; flex-shrink: 0;
    animation: sdPulse 1.5s infinite;
}
.sd-live-name { color: #22c55e; font-weight: 500; }
.sd-live-enter {
    background: #22c55e; color: #0f1e3d; border: none; border-radius: 50px;
    padding: 6px 16px; font-size: 12px; font-weight: 600; cursor: pointer; font-family: inherit;
}
.sd-live-dismiss {
    position: absolute; top: 8px; right: 8px; background: none; border: none;
    color: rgba(255,255,255,0.35); cursor: pointer; opacity: 0; transition: opacity 0.15s;
}
.sd-live-banner:hover .sd-live-dismiss { opacity: 1; }

.sd-stream-card {
    display: flex; gap: 12px; background: #162444;
    border: 0.5px solid rgba(255,255,255,0.07); border-radius: 10px;
    padding: 14px 16px; margin-bottom: 8px;
    transition: border 0.15s ease, background 0.15s ease;
}
.sd-stream-list .sd-stream-card:last-child,
#sdClassStream .sd-stream-card:last-child { margin-bottom: 0; }
.sd-stream-card:hover {
    background: rgba(255,255,255,0.025);
    border-color: rgba(255,255,255,0.13);
}
.sd-stream-card--live {
    border-left: 3px solid rgba(34,197,94,0.35);
}
.sd-stream-card--live:hover {
    border-left-color: rgba(34,197,94,0.55);
}
.sd-stream-icon {
    width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 16px;
}
.sd-stream-body { flex: 1; min-width: 0; }
.sd-stream-title-row {
    display: flex; align-items: center; flex-wrap: wrap;
    gap: 8px; margin-bottom: 4px;
}
.sd-stream-title { font-size: 14px; font-weight: 600; color: #fff; }
.sd-stream-meta {
    font-size: 12px; color: rgba(255,255,255,0.45);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.sd-stream-actions {
    display: flex; align-items: center; gap: 8px;
    margin-top: 8px; opacity: 0; transition: opacity 0.15s ease;
}
.sd-stream-card:hover .sd-stream-actions { opacity: 1; }
.sd-stream-empty {
    margin-top: 24px;
    text-align: center;
    font-size: 13px;
    font-style: italic;
    color: rgba(255,255,255,0.35);
}
.sd-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; flex-shrink: 0;
}
.sd-badge-live { background: rgba(34,197,94,0.15); color: #22c55e; display: inline-flex; align-items: center; gap: 5px; }
.sd-badge-live .sd-pulse-dot { width: 5px; height: 5px; }
.sd-badge-locked { background: rgba(239,68,68,0.15); color: #f87171; }
.sd-upcoming-locked { color: #f87171 !important; }
.sd-upcoming-row.is-locked { opacity: 0.72; cursor: not-allowed; }
.sd-locked-note { font-size: 12px; color: rgba(255,255,255,0.45); }
.pg-notify-item-icon.is-joined { background: rgba(16,185,129,0.14); color: #34d399; }
.sd-badge-due { background: rgba(239,68,68,0.12); color: #ef4444; }
.sd-badge-scheduled { background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.45); }
.sd-enter-now-pill {
    display: inline-flex; align-items: center; justify-content: center;
    background: #22c55e; color: #0f1e3d; border: none; border-radius: 50px;
    padding: 4px 12px; font-size: 11px; font-weight: 600; cursor: pointer; font-family: inherit;
}
.sd-score-pill {
    display: inline-flex; align-items: center;
    padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600;
}
.sd-score-pill.pass { background: rgba(34,197,94,0.15); color: #22c55e; }
.sd-score-pill.fail { background: rgba(239,68,68,0.12); color: #ef4444; }
.sd-btn-pill {
    display: inline-flex; align-items: center; justify-content: center;
    background: #3b82f6; color: #fff; border: none; border-radius: 50px;
    padding: 5px 14px; font-size: 12px; font-weight: 500; cursor: pointer; font-family: inherit;
    text-decoration: none; transition: transform 0.2s, filter 0.2s;
}
.sd-btn-pill:hover { transform: scale(1.02); filter: brightness(1.08); }
.sd-link { background: none; border: none; color: #3b82f6; font-size: 12px; cursor: pointer; font-family: inherit; padding: 0; }
.sd-link:hover { text-decoration: underline; }

.sd-home-grid { display: flex; gap: 20px; max-width: 1100px; margin: 0 auto; align-items: flex-start; }
.sd-home-main { flex: 1; min-width: 0; }
.sd-home-side { width: 280px; flex-shrink: 0; display: flex; flex-direction: column; gap: 14px; }

.sd-panel {
    background: #162444; border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 12px; padding: 16px;
}
.sd-panel-head { font-size: 14px; font-weight: 600; color: #fff; margin-bottom: 12px; }
.sd-upcoming-row { padding: 10px 0; border-bottom: 0.5px solid rgba(255,255,255,0.05); }
.sd-upcoming-row:last-child { border-bottom: none; }
.sd-upcoming-row-btn {
    display: block; width: 100%; text-align: left;
    background: none; border: none; border-bottom: 0.5px solid rgba(255,255,255,0.05);
    padding: 10px 4px; margin: 0 -4px; border-radius: 8px;
    cursor: pointer; font-family: inherit; color: inherit;
    transition: background 0.15s;
}
.sd-upcoming-row-btn:last-child { border-bottom: none; }
.sd-upcoming-row-btn:hover { background: rgba(255,255,255,0.04); }
.sd-upcoming-live { color: #22c55e; display: inline-flex; align-items: center; gap: 5px; }

/* Calendar */
.sd-cal-wrap { max-width: 1100px; margin: 0 auto; }
.sd-cal-header-bar {
    display: flex; align-items: center; justify-content: space-between; gap: 20px;
    padding: 18px 20px; margin-bottom: 14px;
    background: #162444; border: 0.5px solid rgba(255,255,255,0.08); border-radius: 14px;
}
.sd-cal-header-left { min-width: 0; }
.sd-cal-eyebrow {
    margin: 0 0 6px; font-size: 10px; font-weight: 600; letter-spacing: 0.14em;
    text-transform: uppercase; color: rgba(255,255,255,0.32);
}
.sd-cal-header-left h1 {
    margin: 0; font-size: 26px; font-weight: 700; color: #fff; line-height: 1.15;
}
.sd-cal-subtitle {
    margin: 5px 0 0; font-size: 13px; font-weight: 400; color: rgba(255,255,255,0.42); line-height: 1.4;
}
.sd-cal-controls { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.sd-cal-filter {
    background: #101c36; border: 0.5px solid rgba(255,255,255,0.12); color: #fff;
    border-radius: 10px; padding: 8px 12px; font-size: 13px; font-weight: 500;
    font-family: inherit; min-width: 140px;
}
.sd-cal-view-toggle {
    display: inline-flex; background: #101c36; border: 0.5px solid rgba(255,255,255,0.10);
    border-radius: 10px; padding: 3px;
}
.sd-cal-view-btn {
    border: none; background: transparent; color: rgba(255,255,255,0.55); font-size: 12px;
    font-weight: 600; padding: 6px 12px; border-radius: 8px; cursor: pointer; font-family: inherit;
}
.sd-cal-view-btn.active { background: #3b82f6; color: #fff; }
.sd-cal-layout { display: flex; gap: 16px; align-items: flex-start; }
.sd-cal-main { flex: 1; min-width: 0; }
.sd-cal-nav-row {
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 10px; padding: 4px 0;
}
.sd-cal-nav-cluster {
    display: inline-flex; align-items: center; gap: 8px;
}
.sd-cal-month {
    margin: 0; font-size: 15px; font-weight: 500; color: rgba(255,255,255,0.88);
    min-width: 148px; text-align: center; line-height: 1.3;
}
.sd-cal-nav, .sd-cal-today {
    background: #162444; border: 0.5px solid rgba(255,255,255,0.10); color: rgba(255,255,255,0.75);
    border-radius: 10px; cursor: pointer; font-family: inherit; transition: background 0.15s, border-color 0.15s;
}
.sd-cal-nav { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; padding: 0; flex-shrink: 0; }
.sd-cal-nav:hover, .sd-cal-today:hover { background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.18); }
.sd-cal-today { padding: 6px 12px; font-size: 12px; font-weight: 600; }
.sd-cal-weekdays {
    display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 4px;
    margin-bottom: 6px; padding: 0 2px;
}
.sd-cal-weekdays span {
    text-align: center; font-size: 11px; font-weight: 600; letter-spacing: 0.04em;
    text-transform: uppercase; color: rgba(255,255,255,0.35);
}
.sd-cal-body { display: flex; flex-direction: column; gap: 6px; }
.sd-cal-week-row {
    background: rgba(255,255,255,0.02); border: 0.5px solid rgba(255,255,255,0.06);
    border-radius: 12px; padding: 6px; position: relative;
}
.sd-cal-week-empty {
    position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);
    font-size: 11px; color: rgba(255,255,255,0.22); pointer-events: none; white-space: nowrap;
}
.sd-cal-week-days {
    display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 4px; align-items: start;
}
.sd-cal-week-days--expanded { min-height: 280px; }
.sd-cal-cell {
    position: relative; isolation: isolate; min-width: 0; align-self: start;
    background: #101c36; border: 0.5px solid rgba(255,255,255,0.06);
    border-radius: 10px; padding: 6px; text-align: left; cursor: pointer;
    font-family: inherit; color: inherit; display: flex; flex-direction: column; gap: 4px;
    transition: background 0.15s ease, border-color 0.15s ease, transform 0.12s ease;
    box-sizing: border-box; overflow: hidden; height: auto;
}
.sd-cal-cell--empty { min-height: 40px; height: 40px; max-height: 40px; }
.sd-cal-cell--filled { min-height: 96px; }
.sd-cal-cell--multi { min-height: 112px; }
.sd-cal-cell--week { min-height: 120px; padding: 10px 8px; }
.sd-cal-cell::after {
    content: ''; position: absolute; inset: 0; border-radius: inherit; pointer-events: none;
    box-shadow: inset 0 0 0 2px transparent; opacity: 0; transition: opacity 0.15s ease, box-shadow 0.15s ease;
}
.sd-cal-cell:not(.outside):hover { background: #182a4f; border-color: rgba(96,165,250,0.30); z-index: 1; }
.sd-cal-cell:not(.outside):hover::after { opacity: 1; box-shadow: inset 0 0 0 2px rgba(96,165,250,0.40); }
.sd-cal-cell:not(.outside):active { transform: scale(0.99); }
.sd-cal-cell:not(.outside):focus-visible { outline: none; z-index: 1; }
.sd-cal-cell:not(.outside):focus-visible::after { opacity: 1; box-shadow: inset 0 0 0 2px rgba(59,130,246,0.65); }
.sd-cal-cell.pressed::after { opacity: 1; box-shadow: inset 0 0 0 2px rgba(147,197,253,0.90); }
.sd-cal-cell.outside { opacity: 0.32; cursor: default; }
.sd-cal-cell.outside:hover { background: #101c36; border-color: rgba(255,255,255,0.06); transform: none; }
.sd-cal-cell.outside:hover::after { opacity: 0; }
.sd-cal-cell.has-exams {
    background: linear-gradient(180deg, rgba(59,130,246,0.08) 0%, #101c36 40%);
    border-color: rgba(59,130,246,0.22);
}
.sd-cal-cell.has-exams .sd-cal-day-num {
    background: rgba(59,130,246,0.15); border-radius: 6px; padding: 2px 6px; display: inline-block;
}
.sd-cal-cell.today .sd-cal-day-num {
    width: 26px; height: 26px; border-radius: 50%; background: #3b82f6; color: #fff;
    display: inline-flex; align-items: center; justify-content: center; padding: 0;
    box-shadow: 0 0 0 2px rgba(59,130,246,0.35);
}
.sd-cal-cell.selected:not(.today) {
    border-color: rgba(251,191,36,0.50); background: #1a2844;
    box-shadow: inset 0 0 0 1px rgba(251,191,36,0.20);
}
.sd-cal-cell.selected:not(.today) .sd-cal-day-num { color: #fbbf24; font-weight: 700; }
.sd-cal-cell.today.selected { box-shadow: inset 0 0 0 2px rgba(251,191,36,0.45); }
.sd-cal-day-num {
    position: relative; z-index: 1; font-size: 12px; font-weight: 600;
    color: rgba(255,255,255,0.85); line-height: 1; flex-shrink: 0;
}
.sd-cal-events {
    position: relative; z-index: 1; display: flex; flex-direction: column; gap: 3px;
    min-width: 0; width: 100%; flex: 1 1 auto;
}
.sd-cal-cell:not(.sd-cal-cell--week) .sd-cal-pill-class { display: none; }
.sd-cal-pill {
    display: flex; flex-direction: column; gap: 1px; width: 100%; max-width: 100%; box-sizing: border-box;
    border: none; border-radius: 6px; padding: 4px 6px; text-align: left; cursor: pointer;
    font-family: inherit; overflow: hidden; flex-shrink: 0;
}
.sd-cal-pill-time { font-size: 9px; font-weight: 700; opacity: 0.85; line-height: 1.2; }
.sd-cal-pill-name { font-size: 10px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.25; }
.sd-cal-pill-class { font-size: 9px; opacity: 0.75; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sd-cal-pill--live { background: rgba(34,197,94,0.20); color: #86efac; }
.sd-cal-pill--scheduled, .sd-cal-pill--open { background: rgba(59,130,246,0.18); color: #93c5fd; }
.sd-cal-pill--submitted { background: rgba(255,255,255,0.10); color: rgba(255,255,255,0.65); }
.sd-cal-pill--failed, .sd-cal-pill--missed { background: rgba(239,68,68,0.18); color: #fca5a5; }
.sd-cal-pill--week { padding: 8px; gap: 3px; }
.sd-cal-pill--week .sd-cal-pill-time { font-size: 10px; }
.sd-cal-pill--week .sd-cal-pill-name { font-size: 12px; white-space: normal; }
.sd-cal-pill--week .sd-cal-pill-class { font-size: 10px; }
.sd-cal-more {
    display: block; width: 100%; font-size: 10px; color: rgba(255,255,255,0.40); padding: 2px 4px;
    background: none; border: none; cursor: pointer; font-family: inherit; font-weight: 600;
    text-align: left; transition: color 0.15s; flex-shrink: 0;
}
.sd-cal-more:hover { color: rgba(255,255,255,0.65); text-decoration: underline; }
.sd-cal-detail {
    width: 268px; flex-shrink: 0; margin-top: 0;
    background: #162444; border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 12px; padding: 14px;
    animation: sdCalDetailIn 0.2s ease;
    position: sticky; top: 12px;
    max-height: calc(100vh - 100px);
    display: flex; flex-direction: column; overflow: hidden;
}
@keyframes sdCalDetailIn { from { opacity: 0.6; transform: translateX(6px); } to { opacity: 1; transform: translateX(0); } }
.sd-cal-detail-head {
    display: flex; flex-direction: column; gap: 2px;
    margin-bottom: 10px; padding-bottom: 10px;
    border-bottom: 0.5px solid rgba(255,255,255,0.06);
}
.sd-cal-detail-head h3 { margin: 0; font-size: 13px; font-weight: 600; color: #fff; line-height: 1.35; }
.sd-cal-detail-count { font-size: 11px; color: rgba(255,255,255,0.38); }
.sd-cal-detail-list { display: flex; flex-direction: column; gap: 8px; overflow-y: auto; flex: 1; min-height: 0; }
.sd-cal-detail-card {
    display: flex; flex-direction: column; gap: 8px;
    padding: 10px; border-radius: 8px; background: rgba(255,255,255,0.03);
    border: 0.5px solid rgba(255,255,255,0.06);
}
.sd-cal-detail-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
.sd-cal-detail-side { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-shrink: 0; }
.sd-cal-detail-card-main { min-width: 0; flex: 1; }
.sd-cal-detail-title {
    font-size: 12px; font-weight: 600; color: #fff; margin: 0 0 3px;
    display: flex; align-items: center; gap: 5px; line-height: 1.3;
}
.sd-cal-detail-meta { font-size: 10px; color: rgba(255,255,255,0.42); margin: 0; line-height: 1.4; }
.sd-cal-detail-score { font-size: 10px; font-weight: 600; color: rgba(255,255,255,0.65); margin: 4px 0 0; }
.sd-cal-detail-badge {
    display: inline-flex; align-items: center; gap: 3px; font-size: 9px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.03em; padding: 2px 6px; border-radius: 20px; flex-shrink: 0;
}
.sd-cal-detail .sd-btn-pill { padding: 4px 10px; font-size: 10px; border-radius: 20px; }
.sd-cal-detail .sd-link { font-size: 11px; }
.sd-cal-detail-today {
    display: inline-block; font-size: 9px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.04em; color: #60a5fa; background: rgba(59,130,246,0.15);
    padding: 1px 5px; border-radius: 4px; vertical-align: middle;
}
.sd-cal-detail-empty { font-size: 11px; color: rgba(255,255,255,0.38); text-align: center; padding: 16px 4px; }
.sd-cal-detail-badge.live { background: rgba(34,197,94,0.15); color: #22c55e; }
.sd-cal-detail-badge.scheduled, .sd-cal-detail-badge.open { background: rgba(59,130,246,0.15); color: #60a5fa; }
.sd-cal-detail-badge.submitted { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.55); }
.sd-cal-detail-badge.failed, .sd-cal-detail-badge.missed { background: rgba(239,68,68,0.15); color: #f87171; }
@media (max-width: 900px) {
    .sd-cal-layout { flex-direction: column; }
    .sd-cal-detail { width: 100%; position: static; max-height: none; }
    .sd-cal-header-bar { flex-direction: column; align-items: stretch; }
    .sd-cal-controls { justify-content: flex-end; }
}
@media (max-width: 720px) {
    .sd-cal-header-left h1 { font-size: 22px; }
    .sd-cal-nav-cluster { flex-wrap: wrap; justify-content: center; }
    .sd-cal-month { min-width: 0; font-size: 14px; }
    .sd-cal-cell--filled { min-height: 88px; }
    .sd-cal-cell--multi { min-height: 104px; }
    .sd-cal-cell--week { min-height: 110px; }
    .sd-cal-pill-class { display: none; }
}

.sd-upcoming-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.sd-upcoming-name { font-size: 13px; font-weight: 500; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sd-upcoming-when { font-size: 11px; color: rgba(255,255,255,0.40); flex-shrink: 0; }
.sd-upcoming-sub { font-size: 11px; color: rgba(255,255,255,0.40); margin-top: 3px; }
.sd-panel-empty { text-align: center; font-size: 13px; color: rgba(255,255,255,0.35); padding: 12px 0; }
.sd-panel-foot { text-align: right; margin-top: 10px; }
.sd-todo-item {
    display: flex; align-items: center; gap: 10px; padding: 8px 0;
    font-size: 13px; color: rgba(255,255,255,0.60);
    width: 100%; text-align: left; background: none; border: none;
    font-family: inherit; border-radius: 6px; transition: background 0.15s;
}
.sd-todo-item:not(.done) { cursor: pointer; }
.sd-todo-item:not(.done):hover { background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.85); }
.sd-todo-item.done { text-decoration: line-through; color: rgba(255,255,255,0.30); }
.sd-todo-check {
    width: 14px; height: 14px; border-radius: 50%; border: 1.5px solid rgba(255,255,255,0.25); flex-shrink: 0;
}
.sd-todo-item.done .sd-todo-check { background: #3b82f6; border-color: #3b82f6; }

/* Class stream */
.sd-class-banner {
    height: 160px; border-radius: 14px; position: relative; overflow: hidden;
    margin-bottom: 20px; max-width: 1100px; margin-left: auto; margin-right: auto;
}
.sd-class-banner-inner {
    position: absolute; inset: 0; display: flex; align-items: flex-end;
    justify-content: space-between; padding: 20px 24px;
}
.sd-class-banner h2 { margin: 0; font-size: 22px; font-weight: 700; }
.sd-class-banner p { margin: 4px 0 0; font-size: 14px; color: rgba(255,255,255,0.75); }
.sd-class-avatar {
    width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.20);
    border: 2px solid rgba(255,255,255,0.80); display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 600;
}
.sd-info-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 8px 0; border-bottom: 0.5px solid rgba(255,255,255,0.05); font-size: 13px;
}
.sd-info-row:last-child { border-bottom: none; }
.sd-info-label { font-size: 11px; color: rgba(255,255,255,0.40); }
.sd-info-value { color: #fff; display: flex; align-items: center; gap: 6px; }
.sd-quick-key {
    display: flex; align-items: center; gap: 8px; margin-top: 12px;
    background: #0f1e3d; border: 0.5px solid rgba(59,130,246,0.30); border-radius: 8px; padding: 4px 4px 4px 12px;
}
.sd-quick-key input {
    flex: 1; background: none; border: none; color: #fff; font-size: 13px;
    font-family: inherit; outline: none; min-width: 0;
}
.sd-quick-key input::placeholder { color: rgba(255,255,255,0.30); }
.sd-quick-key button {
    background: #3b82f6; border: none; color: #fff; width: 32px; height: 32px;
    border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center;
}

/* Exam room */
.sd-exam-room { max-width: 560px; margin: 0 auto; padding-top: 48px; text-align: center; }
.sd-eyebrow { color: #3b82f6; font-size: 11px; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; }
.sd-exam-room h1 { font-size: 40px; font-weight: 700; line-height: 1.1; margin: 12px 0; }
.sd-exam-room .sub { font-size: 15px; color: rgba(255,255,255,0.50); margin-bottom: 32px; }
.sd-key-field { position: relative; margin-bottom: 8px; }
.sd-key-input {
    width: 100%; height: 56px; background: #162444;
    border: 1.5px solid rgba(255,255,255,0.12); border-radius: 12px;
    color: #fff; font-size: 20px; font-weight: 700; letter-spacing: 6px;
    text-transform: uppercase; text-align: center; font-family: inherit; outline: none;
}
.sd-key-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.12); }
.sd-key-input::placeholder { letter-spacing: 4px; color: rgba(255,255,255,0.20); font-weight: 400; }
.sd-key-count { text-align: right; font-size: 11px; color: rgba(255,255,255,0.35); margin-bottom: 16px; }
.sd-enter-btn {
    width: 100%; height: 52px; border-radius: 50px; border: none;
    background: #3b82f6; color: #fff; font-size: 15px; font-weight: 600;
    font-family: inherit; cursor: pointer; transition: transform 0.2s, filter 0.2s;
}
.sd-enter-btn:disabled { opacity: 0.40; cursor: not-allowed; transform: none; }
.sd-enter-btn:not(:disabled):hover { transform: scale(1.02); filter: brightness(1.08); }
.sd-key-status {
    margin-top: 20px; text-align: left; border-radius: 14px; padding: 18px 20px;
    border: 0.5px solid rgba(255,255,255,0.10); background: #162444;
    display: flex; gap: 14px; align-items: flex-start;
}
.sd-key-status[hidden] { display: none !important; }
.sd-key-status-icon { font-size: 22px; line-height: 1; flex-shrink: 0; margin-top: 2px; }
.sd-key-status-scheduled .sd-key-status-icon { color: #3b82f6; }
.sd-key-status-ready .sd-key-status-icon { color: #22c55e; }
.sd-key-status-submitted .sd-key-status-icon { color: #22c55e; }
.sd-key-status-error {
    align-items: center; justify-content: center; text-align: center;
    color: #fca5a5; background: rgba(239,68,68,0.08); border-color: rgba(239,68,68,0.20);
}
.sd-key-status-error i { font-size: 16px; }
.sd-key-status-body { flex: 1; min-width: 0; }
.sd-key-status-body strong { display: block; font-size: 14px; color: #fff; margin-bottom: 4px; }
.sd-key-status-title { margin: 0 0 4px; font-size: 15px; font-weight: 600; color: #fff; }
.sd-key-status-meta { margin: 0 0 10px; font-size: 13px; color: rgba(255,255,255,0.50); }
.sd-key-status-hint { margin: 0 0 10px; font-size: 12px; color: rgba(255,255,255,0.40); line-height: 1.45; }
.sd-key-status-enter { margin-top: 4px; }
.sd-key-status .sd-link { margin-right: 12px; font-size: 13px; }
.sd-readiness {
    display: flex; flex-wrap: wrap; justify-content: center; gap: 16px;
    margin-top: 24px; font-size: 13px; color: rgba(255,255,255,0.45);
}
.sd-ready-item { display: flex; align-items: center; gap: 6px; }
.sd-status-dot { width: 8px; height: 8px; border-radius: 50%; }
.sd-status-dot.ok { background: #22c55e; }
.sd-status-dot.warn { background: #f59e0b; }
.sd-status-dot.bad { background: #ef4444; }
.sd-fix-link { color: #ef4444; background: none; border: none; cursor: pointer; font-size: 12px; font-family: inherit; }

/* Results */
.sd-page-head { max-width: 900px; margin: 0 auto 20px; }
.sd-page-head h1 { margin: 0; font-size: 24px; font-weight: 700; }
.sd-page-sub { margin: 6px 0 0; font-size: 14px; color: rgba(255,255,255,0.45); }
.sd-filter-pills { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
.sd-pill {
    background: none; border: 0.5px solid rgba(255,255,255,0.15); color: rgba(255,255,255,0.55);
    border-radius: 50px; padding: 6px 14px; font-size: 12px; cursor: pointer; font-family: inherit;
}
.sd-pill.active { background: #3b82f6; border-color: #3b82f6; color: #fff; }
.sd-stats-row {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
    max-width: 900px; margin: 0 auto 20px;
}
.sd-stat-card {
    background: #162444; border: 0.5px solid rgba(255,255,255,0.07);
    border-radius: 10px; padding: 16px 18px;
}
.sd-stat-num { font-size: 28px; font-weight: 700; color: #fff; line-height: 1; }
.sd-stat-lbl { font-size: 12px; color: rgba(255,255,255,0.42); margin-top: 6px; }
.sd-result-card {
    max-width: 900px; margin: 0 auto 10px; background: #162444;
    border: 0.5px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 18px 20px;
    transition: border 0.15s, transform 0.15s;
}
.sd-result-card:hover { border-color: rgba(255,255,255,0.14); transform: translateY(-1px); }
.sd-result-top { display: flex; align-items: center; gap: 14px; }
.sd-result-avatar {
    width: 36px; height: 36px; border-radius: 8px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 600; color: #fff;
}
.sd-result-info { flex: 1; min-width: 0; }
.sd-result-info h3 { margin: 0; font-size: 15px; font-weight: 600; }
.sd-result-info p { margin: 2px 0 0; font-size: 12px; color: rgba(255,255,255,0.40); }
.sd-score-ring {
    width: 52px; height: 52px; border-radius: 50%; border: 2px solid;
    display: flex; flex-direction: column; align-items: center; justify-content: center; flex-shrink: 0;
}
.sd-score-ring.pass { border-color: #22c55e; color: #22c55e; }
.sd-score-ring.fail { border-color: #ef4444; color: #ef4444; }
.sd-score-num { font-size: 18px; font-weight: 700; line-height: 1; }
.sd-score-of { font-size: 10px; color: rgba(255,255,255,0.40); }
.sd-result-foot {
    display: flex; align-items: center; flex-wrap: wrap; gap: 12px;
    margin-top: 12px; padding-top: 12px; border-top: 0.5px solid rgba(255,255,255,0.06);
    font-size: 12px; color: rgba(255,255,255,0.40);
}
.sd-violations-warn { color: #f59e0b; display: inline-flex; align-items: center; gap: 4px; }
.sd-result-score-pill {
    font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 50px;
    background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.55);
}
.sd-result-modal { max-width: 440px; }
.sd-result-detail { display: grid; gap: 12px; }
.sd-result-detail-row {
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    padding: 10px 12px; border-radius: 8px; background: rgba(255,255,255,0.04);
    font-size: 13px;
}
.sd-result-detail-row span:first-child { color: rgba(255,255,255,0.45); }
.sd-result-detail-row strong { color: #fff; font-weight: 600; text-align: right; }
.sd-result-badge {
    display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px;
    border-radius: 50px; font-size: 11px; font-weight: 600;
}
.sd-result-badge.pass { background: rgba(34,197,94,0.15); color: #22c55e; }
.sd-result-badge.fail { background: rgba(239,68,68,0.15); color: #ef4444; }

.sd-cert-modal { max-width: 400px; text-align: center; padding: 28px 24px 24px; }
.sd-cert-modal-icon {
    width: 64px; height: 64px; margin: 0 auto 16px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    background: rgba(245,158,11,0.14); border: 1px solid rgba(245,158,11,0.35);
    color: #f59e0b; font-size: 30px;
}
.sd-cert-modal-title { margin: 0 0 8px; font-size: 18px; font-weight: 700; color: #fff; }
.sd-cert-modal-msg {
    margin: 0 0 22px; font-size: 13px; line-height: 1.55;
    color: rgba(255,255,255,0.52);
}
.sd-cert-modal-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    min-width: 140px; padding: 10px 22px; border-radius: 50px; border: none;
    background: #f59e0b; color: #0f1e3d; font-size: 13px; font-weight: 600;
    cursor: pointer; font-family: inherit;
}
.sd-cert-modal-btn:hover { background: #d97706; }

/* Certificates */
.sd-cert-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px;
    max-width: 900px; margin: 0 auto;
}
.sd-cert-card {
    background: #162444; border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 14px; overflow: hidden; text-align: center;
    transition: border 0.2s, transform 0.2s;
}
.sd-cert-card:hover { border-color: rgba(245,158,11,0.30); transform: translateY(-2px); }
.sd-cert-strip { height: 4px; background: #f59e0b; }
.sd-cert-body { padding: 20px; }
.sd-cert-body i { font-size: 44px; color: #f59e0b; }
.sd-cert-body h3 { margin: 10px 0 4px; font-size: 15px; font-weight: 600; }
.sd-cert-body p { margin: 2px 0; font-size: 12px; color: rgba(255,255,255,0.45); }
.sd-cert-divider { height: 0.5px; background: rgba(255,255,255,0.06); margin: 14px 0; }
.sd-cert-dl {
    display: inline-flex; border: 0.5px solid #f59e0b; color: #f59e0b;
    background: transparent; border-radius: 50px; padding: 7px 18px;
    font-size: 12px; cursor: pointer; font-family: inherit;
}
.sd-cert-dl:hover { background: #f59e0b; color: #0f1e3d; }
.sd-empty-state { text-align: center; padding: 48px 24px; max-width: 400px; margin: 0 auto; }
.sd-empty-state i { font-size: 64px; color: rgba(255,255,255,0.10); }
.sd-empty-state h3 { margin: 16px 0 8px; font-size: 16px; font-weight: 600; }
.sd-empty-state p { margin: 0; font-size: 13px; color: rgba(255,255,255,0.45); }

.sd-placeholder-page { max-width: 560px; margin: 48px auto; text-align: center; }
.sd-placeholder-page h1 { font-size: 24px; font-weight: 700; margin-bottom: 8px; }
.sd-placeholder-page p { color: rgba(255,255,255,0.45); font-size: 14px; }

/* Modal */
.sd-modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 300;
    display: none; align-items: center; justify-content: center; padding: 20px;
}
.sd-modal-overlay.open { display: flex; }
.sd-modal {
    background: #162444; border: 0.5px solid rgba(255,255,255,0.12);
    border-radius: 16px; padding: 28px; width: min(400px, 100%);
    animation: sdModalIn 0.25s ease-out; position: relative;
}
.sd-modal-head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.sd-modal-head h2 { margin: 0; font-size: 18px; font-weight: 600; }
.sd-modal-close { background: none; border: none; color: rgba(255,255,255,0.40); cursor: pointer; font-size: 18px; }
.sd-modal-close:hover { color: #fff; }
.sd-modal-sub { font-size: 13px; color: rgba(255,255,255,0.45); margin-bottom: 20px; }
.sd-field-label { font-size: 11px; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; color: rgba(255,255,255,0.40); margin-bottom: 6px; display: block; }
.sd-field { margin-bottom: 14px; }
.sd-field input {
    width: 100%; height: 44px; background: #0f1e3d;
    border: 0.5px solid rgba(255,255,255,0.10); border-radius: 8px;
    color: #fff; font-size: 14px; padding: 0 12px; font-family: inherit; outline: none;
}
.sd-field input:focus { border-color: #3b82f6; }
.sd-field input:read-only { opacity: 0.70; }
.sd-field input.uppercase { letter-spacing: 3px; text-transform: uppercase; }
.sd-modal-submit {
    width: 100%; height: 46px; margin-top: 8px; border: none; border-radius: 50px;
    background: #3b82f6; color: #fff; font-size: 14px; font-weight: 600;
    font-family: inherit; cursor: pointer;
}
.sd-modal-submit:disabled { opacity: 0.40; cursor: not-allowed; }

.sd-fade-up { animation: sdFadeUp 0.4s ease-out both; }
@keyframes sdFadeDown { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }
@keyframes sdFadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes sdModalIn { from { opacity: 0; transform: scale(0.96); } to { opacity: 1; transform: scale(1); } }
@keyframes sdPulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.4); opacity: 0; } }
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation: none !important; transition: none !important; }
    .pg-sidebar-brand img { animation: none; }
    .sd-home-brand-link img { animation: none; }
}
@media (max-width: 1023px) {
    .sd-class-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    .pg-sidebar { width: 64px; }
    .pg-sidebar-brand img { height: 52px; }
    .pg-sidebar .pg-nav-link span, .pg-nav-label { display: none; }
    .pg-sidebar .pg-nav-link { justify-content: center; padding: 10px 0; border-left: none; border-right: 3px solid transparent; }
    .pg-sidebar .pg-nav-link.active { border-right-color: #3b82f6; }
    .sd-class-item span, .sd-join-link span, .sd-exam-shortcut span { display: none; }
    .sd-class-item { justify-content: center; padding: 8px 0; }
    .sd-join-link { justify-content: center; margin: 4px 0 0; width: 100%; }
    .sd-exam-shortcut { justify-content: center; }
    .pg-floating-actions { top: 12px; right: 12px; gap: 8px; }
    .pg-floating-notify { width: 40px; height: 40px; }
    .pg-floating-notify i { font-size: 17px; }
    .pg-floating-profile .pg-avatar { width: 32px; height: 32px; font-size: 12px; }
    .sd-class-grid { grid-template-columns: 1fr; }
    .sd-home-columns { flex-direction: column; }
    .sd-home-side { width: 100%; }
    .sd-cert-grid { grid-template-columns: 1fr; }
    .sd-stats-row { grid-template-columns: 1fr; }
    .pg-settings-row,
    .pg-settings-account-grid { grid-template-columns: 1fr; }
}

</style>
@include('partials.settings-shared-styles')
@include('partials.settings-dialog-shell')

<div class="sd-layout">
    <aside class="pg-sidebar" id="sdSidebar">
        <div class="pg-sidebar-top">
            <a href="/student" class="pg-sidebar-brand">
                <img src="/images/logo.png" alt="ExamGuard">
            </a>
        </div>

        <nav class="pg-sidebar-nav">
            <p class="pg-nav-label">Navigation</p>
            <button type="button" class="pg-nav-link active" data-sd-view="home"><i class="ti ti-layout-dashboard"></i><span>Home</span></button>
            <button type="button" class="pg-nav-link" data-sd-view="calendar"><i class="ti ti-calendar"></i><span>Calendar</span></button>
            <button type="button" class="pg-nav-link" data-sd-view="exam-room"><i class="ti ti-writing"></i><span>Exam room</span></button>
            <button type="button" class="pg-nav-link" data-sd-view="results"><i class="ti ti-chart-bar"></i><span>Results</span></button>
            <button type="button" class="pg-nav-link" data-sd-view="certificates"><i class="ti ti-award"></i><span>Certificates</span></button>
            <button type="button" class="pg-nav-link" data-sd-view="settings"><i class="ti ti-settings"></i><span>Settings</span></button>

            <p class="pg-nav-label">My classes</p>
            <div id="sdClassList"></div>
            <button type="button" class="sd-join-link" id="sdJoinClassOpen"><i class="ti ti-plus"></i><span>Join a class</span></button>
        </nav>

        <div class="sd-sidebar-foot">
            <button type="button" class="sd-exam-shortcut" data-sd-view="exam-room"><i class="ti ti-writing"></i><span>Enter exam room</span></button>
        </div>
    </aside>

    <div class="pg-main">
        <div class="pg-floating-actions">
            <div class="pg-floating-notify" id="sdNotifyToggle" role="button" tabindex="0" aria-label="Notifications" aria-haspopup="true">
                <i class="ti ti-bell" aria-hidden="true"></i>
                <span class="pg-notify-badge hidden" id="sdNotifyDot" aria-hidden="true">0</span>
                <div class="pg-notify-panel" id="sdNotifyPanel">
                    <div class="pg-notify-head">
                        <span>Notifications</span>
                        <button type="button" class="pg-notify-mark-all" id="sdNotifyMarkAll" disabled>Mark all read</button>
                    </div>
                    <div class="pg-notify-list" id="sdNotifyList">
                        <div class="pg-notify-loading">Loading notifications…</div>
                    </div>
                </div>
            </div>
            <div class="pg-floating-profile" id="sdProfileToggle" role="button" tabindex="0" aria-label="Profile menu" aria-haspopup="true">
                <div class="pg-avatar" id="sdAvatarBtn">—</div>
                <div class="pg-dropdown" id="sdProfileMenu">
                    <button type="button" data-sd-view="settings"><i class="ti ti-user"></i> View profile</button>
                    <button type="button" data-sd-view="settings"><i class="ti ti-settings"></i> Settings</button>
                    <div class="divider"></div>
                    <button type="button" class="danger" data-logout><i class="ti ti-logout"></i> Log out</button>
                </div>
            </div>
        </div>

        <div class="pg-body" id="sdMain">
            {{-- Home --}}
            <div class="sd-view active" id="sd-view-home" data-sd-view="home">
                <div class="sd-home-feed">
                    <div class="sd-home-brand">
                        <a href="/student" class="sd-home-brand-link">
                            <img src="/images/logo.png" alt="ExamGuard">
                            <span>examguard.</span>
                        </a>
                    </div>
                    <div class="sd-greeting">
                        <h1 id="sdGreeting">Good morning.</h1>
                        <p id="sdGreetingSub">Loading your dashboard…</p>
                    </div>
                    <div class="sd-live-banner" id="sdLiveBanner">
                        <button type="button" class="sd-live-dismiss" id="sdLiveDismiss" aria-label="Dismiss"><i class="ti ti-x"></i></button>
                        <div class="sd-live-banner-left">
                            <span class="sd-pulse-dot"></span>
                            <strong>Live exam available:</strong>
                            <span class="sd-live-name" id="sdLiveExamName"></span>
                        </div>
                        <button type="button" class="sd-live-enter" id="sdLiveEnter">Enter now</button>
                    </div>

                    <div class="sd-home-columns">
                        <div class="sd-home-main">
                            <section class="sd-home-section">
                                <div class="sd-section-header">
                                    <h2 class="sd-section-label">Your classes</h2>
                                    <button type="button" class="sd-section-link" id="sdClassesSeeAll">See all</button>
                                </div>
                                <div id="sdClassCards" class="sd-class-grid"></div>
                            </section>

                            <section class="sd-home-section">
                                <div class="sd-section-header">
                                    <h2 class="sd-section-label">Activity stream</h2>
                                    <button type="button" class="sd-section-link" id="sdStreamSeeAll">See all</button>
                                </div>
                                <div id="sdStream" class="sd-stream-list"></div>
                            </section>
                        </div>

                        <aside class="sd-home-side">
                            <div class="sd-panel">
                                <div class="sd-panel-head">Upcoming</div>
                                <div id="sdUpcoming"></div>
                                <div class="sd-panel-foot"><button type="button" class="sd-link" data-sd-view="calendar">View all</button></div>
                            </div>
                            <div class="sd-panel">
                                <div class="sd-panel-head">To do</div>
                                <div id="sdTodo"></div>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>

            {{-- Calendar --}}
            <div class="sd-view" id="sd-view-calendar" data-sd-view="calendar">
                <div class="sd-cal-wrap">
                    <header class="sd-cal-header-bar">
                        <div class="sd-cal-header-left">
                            <p class="sd-cal-eyebrow">My schedule</p>
                            <h1>Calendar</h1>
                            <p class="sd-cal-subtitle">Your scheduled exams and deadlines</p>
                        </div>
                        <div class="sd-cal-controls">
                            <select id="sdCalClassFilter" class="sd-cal-filter" aria-label="Filter by class">
                                <option value="">All classes</option>
                            </select>
                            <div class="sd-cal-view-toggle" role="group" aria-label="Calendar view">
                                <button type="button" class="sd-cal-view-btn active" data-cal-view-mode="month">Month</button>
                                <button type="button" class="sd-cal-view-btn" data-cal-view-mode="week">Week</button>
                            </div>
                        </div>
                    </header>
                    <div class="sd-cal-layout">
                        <div class="sd-cal-main">
                            <div class="sd-cal-nav-row">
                                <div class="sd-cal-nav-cluster">
                                    <button type="button" class="sd-cal-today" id="sdCalToday">Today</button>
                                    <button type="button" class="sd-cal-nav" id="sdCalPrev" aria-label="Previous"><i class="ti ti-chevron-left"></i></button>
                                    <h2 class="sd-cal-month" id="sdCalMonthLabel"></h2>
                                    <button type="button" class="sd-cal-nav" id="sdCalNext" aria-label="Next"><i class="ti ti-chevron-right"></i></button>
                                </div>
                            </div>
                            <div class="sd-cal-weekdays" id="sdCalWeekdays" aria-hidden="true">
                                <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                            </div>
                            <div id="sdCalendarGrid" class="sd-cal-body" role="grid" aria-label="Exam calendar"></div>
                        </div>
                        <aside id="sdCalendarDayDetail" class="sd-cal-detail" aria-label="Selected day details"></aside>
                    </div>
                </div>
            </div>

            {{-- Exam room --}}
            <div class="sd-view" id="sd-view-exam-room" data-sd-view="exam-room">
                <div class="sd-exam-room">
                    <div class="sd-eyebrow">Exam room</div>
                    <h1>Enter your exam key</h1>
                    <p class="sub">Enter the 8-character key from your professor. Scheduled exams can be validated before they open.</p>
                    <div id="sdExamKeyFormWrap">
                    <form id="sdExamKeyForm">
                        <div class="sd-key-field">
                            <input type="text" class="sd-key-input" id="sdExamKeyInput" maxlength="8" autocomplete="off" placeholder="· · · · · · · ·" spellcheck="false">
                        </div>
                        <div class="sd-key-count" id="sdKeyCount">0 / 8</div>
                        <button type="submit" class="sd-enter-btn" id="sdEnterExamBtn" disabled>Enter exam room</button>
                    </form>
                    </div>
                    <div id="sdExamKeyStatus" class="sd-key-status" hidden></div>
                    <div class="sd-readiness" id="sdReadiness"></div>
                </div>
            </div>

            {{-- Results --}}
            <div class="sd-view" id="sd-view-results" data-sd-view="results">
                <div class="sd-page-head">
                    <h1>Your results</h1>
                    <div class="sd-filter-pills" id="sdResultFilters">
                        <button type="button" class="sd-pill active" data-filter="all">All</button>
                        <button type="button" class="sd-pill" data-filter="passed">Passed</button>
                        <button type="button" class="sd-pill" data-filter="failed">Failed</button>
                        <button type="button" class="sd-pill" data-filter="month">This month</button>
                    </div>
                </div>
                <div class="sd-stats-row" id="sdResultStats"></div>
                <div id="sdResultsList"></div>
            </div>

            {{-- Certificates --}}
            <div class="sd-view" id="sd-view-certificates" data-sd-view="certificates">
                <div class="sd-page-head"><h1>Your certificates</h1></div>
                <div class="sd-cert-grid" id="sdCertGrid"></div>
            </div>

            {{-- Settings --}}
            <div class="sd-view" id="sd-view-settings" data-sd-view="settings">
                @include('partials.student-settings')
            </div>

            {{-- Class stream --}}
            <div class="sd-view" id="sd-view-class" data-sd-view="class">
                <div class="sd-class-banner" id="sdClassBanner">
                    <div class="sd-class-banner-inner">
                        <div>
                            <h2 id="sdClassBannerTitle">Class</h2>
                            <p id="sdClassBannerSub">Professor</p>
                        </div>
                        <div class="sd-class-avatar" id="sdClassBannerAvatar">C</div>
                    </div>
                </div>
                <div class="sd-home-grid">
                    <div class="sd-home-main"><div id="sdClassStream"></div></div>
                    <div class="sd-home-side">
                        <div class="sd-panel">
                            <div class="sd-panel-head">Class info</div>
                            <div id="sdClassInfo"></div>
                            <div class="sd-quick-key">
                                <input type="text" id="sdClassQuickKey" maxlength="8" placeholder="Quick enter exam key" autocomplete="off">
                                <button type="button" id="sdClassQuickKeyBtn" aria-label="Submit exam key"><i class="ti ti-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="sd-modal-overlay" id="sdJoinModal">
    <div class="sd-modal" role="dialog" aria-labelledby="sdJoinTitle">
        <div class="sd-modal-head">
            <h2 id="sdJoinTitle">Join a class</h2>
            <button type="button" class="sd-modal-close" id="sdJoinClose" aria-label="Close"><i class="ti ti-x"></i></button>
        </div>
        <p class="sd-modal-sub">Enter the 6-character class code your professor shared.</p>
        <form id="sdJoinForm">
            <div class="sd-field">
                <label class="sd-field-label" for="sdJoinName">Your name</label>
                <input type="text" id="sdJoinName" readonly>
            </div>
            <div class="sd-field">
                <label class="sd-field-label" for="sdJoinCode">Class code</label>
                <input type="text" id="sdJoinCode" class="uppercase" maxlength="6" placeholder="e.g. XY12AB" autocomplete="off">
            </div>
            <button type="submit" class="sd-modal-submit" id="sdJoinSubmit" disabled>Join class</button>
        </form>
    </div>
</div>

<div class="sd-modal-overlay" id="sdResultModal">
    <div class="sd-modal sd-result-modal" role="dialog" aria-labelledby="sdResultTitle">
        <div class="sd-modal-head">
            <h2 id="sdResultTitle">Exam result</h2>
            <button type="button" class="sd-modal-close" id="sdResultClose" aria-label="Close"><i class="ti ti-x"></i></button>
        </div>
        <div id="sdResultBody" class="sd-result-detail"></div>
    </div>
</div>

<div class="sd-modal-overlay" id="sdCertModal">
    <div class="sd-modal sd-cert-modal" role="dialog" aria-labelledby="sdCertModalTitle" aria-describedby="sdCertModalMsg">
        <div class="sd-cert-modal-icon" aria-hidden="true"><i class="ti ti-download"></i></div>
        <h2 id="sdCertModalTitle" class="sd-cert-modal-title">Download unavailable</h2>
        <p id="sdCertModalMsg" class="sd-cert-modal-msg">Certificate download will be available in a future update.</p>
        <button type="button" class="sd-cert-modal-btn" id="sdCertModalOk">Got it</button>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.ExamGuardStudent = {
        user: @json($user->toAuthArray()),
        preferences: @json($user->preferencesWithDefaults()),
    };
</script>
<script src="/js/api-client.js?v=11"></script>
<script src="/js/auth-guard.js"></script>
<script src="/js/professor-dialog.js?v=1"></script>
<script src="/js/settings-shared.js?v=3"></script>
<script src="/js/student-settings.js?v=3"></script>
<script src="/js/student.js?v=27"></script>
<script src="/js/student-notifications.js?v=2"></script>
@endpush
