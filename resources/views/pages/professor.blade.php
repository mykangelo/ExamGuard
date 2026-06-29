@extends('layouts.app')

@section('title', 'Professor Dashboard — ExamGuard')

@section('body_attrs')
data-role="professor" class="eg-shell-body"
@endsection

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3/dist/tabler-icons.min.css">

@php
    $examList = collect($exams ?? []);
    $userInitials = strtoupper(substr($user->name ?? 'U', 0, 1));
    $pendingNotifications = $pendingNotifications ?? 0;
    $allAttempts = $examList->flatMap(fn ($e) => $e->attempts);
    $allViolations = $allAttempts->filter(fn ($a) => ($a->warning_count ?? 0) > 0);
    $totalResponses = $allAttempts->count();
    $totalCompleted = $allAttempts->whereNotNull('submitted_at')->count();
    $completionRate = $totalResponses > 0 ? round(($totalCompleted / $totalResponses) * 100) : 0;
    $inProgressCount = max(0, $totalResponses - $totalCompleted);
    $recentSubmissions = $examList->flatMap(function ($exam) {
        return $exam->attempts
            ->whereNotNull('submitted_at')
            ->map(function ($attempt) use ($exam) {
                $attempt->setRelation('exam', $exam);
                return $attempt;
            });
    })->sortByDesc('submitted_at')->take(6);
    $examPerformance = $examList->map(function ($exam) {
        $sub = $exam->attempts->whereNotNull('submitted_at');
        return [
            'exam'       => $exam,
            'responses'  => $exam->attempts->count(),
            'completed'  => $sub->count(),
            'violations' => $exam->attempts->sum('warning_count'),
        ];
    })->sortByDesc('completed')->values();
    $formatAttemptTime = function ($attempt) {
        if (!$attempt->started_at) {
            return '—';
        }
        $end = $attempt->submitted_at ?? now();
        $totalSeconds = (int) $attempt->started_at->diffInSeconds($end);
        if ($totalSeconds < 60) {
            return $totalSeconds . 's';
        }
        $minutes = intdiv($totalSeconds, 60);
        $seconds = $totalSeconds % 60;

        return $seconds > 0 ? ($minutes . 'm ' . $seconds . 's') : ($minutes . 'm');
    };
    $violationRecords = $examList->flatMap(function ($exam) {
        return $exam->attempts
            ->filter(fn ($a) => ($a->warning_count ?? 0) > 0)
            ->map(function ($attempt) use ($exam) {
                $attempt->setRelation('exam', $exam);
                return $attempt;
            });
    })->sort(function ($a, $b) {
        $warningsCompare = ($b->warning_count ?? 0) <=> ($a->warning_count ?? 0);
        if ($warningsCompare !== 0) {
            return $warningsCompare;
        }

        return ($b->submitted_at?->timestamp ?? 0) <=> ($a->submitted_at?->timestamp ?? 0);
    })->values();
    $totalWarningCount = $violationRecords->sum(fn ($a) => $a->warning_count ?? 0);
    $examsWithViolations = $violationRecords->pluck('exam.id')->unique()->count();
@endphp

<style>
*, *::before, *::after { box-sizing: border-box; }
html, body {
    margin: 0; padding: 0;
    background: #0f1e3d; color: #fff;
    font-family: 'Plus Jakarta Sans', ui-sans-serif, sans-serif;
    overflow: hidden; height: 100vh;
}
.pg-layout { display: flex; height: 100vh; overflow: hidden; }

/* ── Sidebar nav (left) ───────────────────────────── */
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

.pg-sidebar-nav { flex: 1; padding: 8px 0; overflow-y: auto; }
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

.pg-sidebar-footer {
    padding: 12px 16px 16px;
    border-top: 0.5px solid rgba(255,255,255,0.08);
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.pg-sidebar-footer button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    background: none;
    border: none;
    cursor: pointer;
    padding: 6px 0;
    font-family: inherit;
    color: rgba(255,255,255,0.55);
    text-align: left;
}
.pg-sidebar-footer button:hover { color: #fff; }
.pg-sidebar-footer i { font-size: 15px; }

/* ── Sidebar live widget ─────────────────────────── */
.pg-sidebar-live-widget {
    padding: 0 16px;
    margin: 18px 0 8px;
    position: relative;
    z-index: 2;
    pointer-events: auto;
}
.pg-sidebar-live-widget.hidden { display: none !important; }
.pg-live-widget-card {
    width: 100%;
    background: rgba(22, 36, 68, 0.75);
    border: 0.5px solid rgba(255,255,255,0.10);
    border-radius: 12px;
    padding: 12px 12px 10px;
    pointer-events: auto;
}
.pg-live-widget-head { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.pg-live-widget-label {
    font-size: 10px;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.45);
    font-weight: 700;
}
.pg-live-widget-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 0 0 rgba(34,197,94,0.55);
    animation: pgPulseGreen 1.5s ease-out infinite;
}
@keyframes pgPulseGreen {
    0% { box-shadow: 0 0 0 0 rgba(34,197,94,0.55); opacity: 1; }
    70% { box-shadow: 0 0 0 10px rgba(34,197,94,0); opacity: 1; }
    100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); opacity: 1; }
}
.pg-live-widget-title {
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pg-live-widget-sub {
    margin-top: 2px;
    font-size: 11px;
    color: rgba(255,255,255,0.42);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pg-live-widget-meta { margin-top: 8px; font-size: 11px; color: rgba(255,255,255,0.62); }
.pg-live-widget-btn {
    margin-top: 10px;
    width: 100%;
    height: 32px;
    border: none;
    border-radius: 10px;
    background: #3b82f6;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.12s;
    pointer-events: auto;
}
.pg-live-widget-btn:hover { background: #2563eb; }
.pg-live-widget-more {
    margin: 8px 2px 0;
    width: calc(100% - 4px);
    background: none;
    border: none;
    color: rgba(255,255,255,0.40);
    font-size: 11px;
    cursor: pointer;
    text-align: left;
    padding: 0;
}
.pg-live-widget-more:hover { color: rgba(255,255,255,0.70); text-decoration: underline; }

/* ── Main workspace ───────────────────────────────── */
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
    gap: 12px;
    padding: 12px 14px;
    border-bottom: 0.5px solid rgba(255,255,255,0.08);
    flex-shrink: 0;
}
.pg-notify-head span {
    font-size: 13px;
    font-weight: 600;
    color: #fff;
}
.pg-notify-mark-all {
    background: none;
    border: none;
    padding: 0;
    font-size: 11px;
    color: #3b82f6;
    cursor: pointer;
    font-family: inherit;
}
.pg-notify-mark-all:hover { color: #93c5fd; }
.pg-notify-mark-all:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}
.pg-notify-list {
    overflow-y: auto;
    flex: 1;
    min-height: 0;
}
.pg-notify-item {
    display: flex;
    gap: 10px;
    width: 100%;
    padding: 12px 14px;
    background: none;
    border: none;
    border-bottom: 0.5px solid rgba(255,255,255,0.06);
    text-align: left;
    cursor: pointer;
    font-family: inherit;
    transition: background 0.12s;
}
.pg-notify-item:last-child { border-bottom: none; }
.pg-notify-item:hover { background: rgba(255,255,255,0.05); }
.pg-notify-item.unread { background: rgba(59,130,246,0.08); }
.pg-notify-item.unread:hover { background: rgba(59,130,246,0.12); }
.pg-notify-item-icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 15px;
}
.pg-notify-item-icon.is-submission {
    background: rgba(34,197,94,0.15);
    color: #22c55e;
}
.pg-notify-item-icon.is-violation {
    background: rgba(245,158,11,0.15);
    color: #f59e0b;
}
.pg-notify-item-body { min-width: 0; flex: 1; }
.pg-notify-item-title {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #fff;
    margin-bottom: 2px;
}
.pg-notify-item-message {
    display: block;
    font-size: 11px;
    color: rgba(255,255,255,0.55);
    line-height: 1.4;
}
.pg-notify-item-time {
    display: block;
    margin-top: 4px;
    font-size: 10px;
    color: rgba(255,255,255,0.32);
}
.pg-notify-empty,
.pg-notify-loading {
    padding: 28px 16px;
    text-align: center;
    font-size: 12px;
    color: rgba(255,255,255,0.42);
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
.pg-floating-nav {
    position: relative;
    z-index: 2;
    display: inline-flex;
    align-items: center; gap: 4px;
    width: auto; max-width: 100%;
    margin: 0 auto 28px;
    padding: 6px;
    background: rgba(22, 36, 68, 0.94);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 0.5px solid rgba(255,255,255,0.12);
    border-radius: 50px;
    box-shadow:
        0 8px 28px rgba(0, 0, 0, 0.32),
        0 0 0 1px rgba(255, 255, 255, 0.04);
    overflow-x: auto;
    scrollbar-width: none;
    flex-shrink: 0;
}
.pg-floating-nav::-webkit-scrollbar { display: none; }
.pg-floating-nav .pg-nav-link {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    width: auto; flex-shrink: 0;
    padding: 10px 18px;
    font-size: 13px; font-weight: 500;
    color: rgba(255,255,255,0.58);
    text-decoration: none; background: none; border: none;
    border-radius: 50px; cursor: pointer; font-family: inherit;
    white-space: nowrap;
    transition: background 0.18s, color 0.18s, box-shadow 0.18s, transform 0.18s;
}
.pg-floating-nav .pg-nav-link i { font-size: 16px; }
.pg-floating-nav .pg-nav-link:hover {
    color: #fff;
    background: rgba(255,255,255,0.08);
}
.pg-floating-nav .pg-nav-link.active {
    color: #fff;
    background: #3b82f6;
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.38);
}
.pg-floating-nav .pg-nav-link.active:hover { background: #2563eb; }

.pg-avatar {
    width: 32px; height: 32px; border-radius: 50%;
    background: #3b82f6; color: #fff;
    font-size: 12px; font-weight: 600;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}

.pg-nav-label {
    font-size: 10px; font-weight: 500;
    letter-spacing: 2px; text-transform: uppercase;
    color: rgba(255,255,255,0.30);
    padding: 8px 20px 6px;
}
.pg-topbar-left { display: flex; align-items: center; gap: 10px; min-width: 0; flex: 1; }
.pg-topbar-title { font-size: 15px; font-weight: 600; color: #fff; }
.pg-pill {
    display: inline-flex; padding: 2px 8px; border-radius: 50px;
    font-size: 11px; font-weight: 500;
}
.pg-pill-active,
.pg-pill-published { background: rgba(34,197,94,0.15); color: #22c55e; }
.pg-pill-scheduled { background: rgba(59,130,246,0.15); color: #60a5fa; }
.pg-pill-closed { background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.45); }
.pg-pill-draft  { background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.5); }

.pg-detail-tabs { display: flex; gap: 2px; flex-shrink: 0; }
.pg-detail-tabs.hidden { display: none; }
.pg-detail-tab {
    padding: 5px 12px; font-size: 12px;
    color: rgba(255,255,255,0.50);
    background: none; border: none; border-radius: 6px;
    cursor: pointer; font-family: inherit;
    transition: color 0.12s, background 0.12s;
}
.pg-detail-tab:hover { color: #fff; background: rgba(255,255,255,0.05); }
.pg-detail-tab.active { color: #fff; background: rgba(59,130,246,0.15); }

.pg-topbar-btn {
    display: flex; align-items: center; gap: 5px;
    padding: 5px 10px;
    background: rgba(255,255,255,0.05);
    border: 0.5px solid rgba(255,255,255,0.10);
    border-radius: 6px; color: rgba(255,255,255,0.65);
    font-size: 12px; text-decoration: none; font-family: inherit;
    transition: color 0.12s, border-color 0.12s;
}
.pg-topbar-btn:hover { color: #fff; border-color: rgba(255,255,255,0.25); }
.pg-topbar-btn.hidden { display: none; }
.pg-back-btn {
    display: none; align-items: center; gap: 4px;
    padding: 5px 10px; background: none; border: none;
    color: rgba(255,255,255,0.50); font-size: 12px;
    cursor: pointer; font-family: inherit;
    transition: color 0.12s;
}
.pg-back-btn.visible { display: flex; }
.pg-back-btn:hover { color: #fff; }

.pg-body {
    flex: 1; overflow-y: auto; padding: 20px;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.08) transparent;
}

.pg-view { display: none !important; }
.pg-view.active { display: block; }

@keyframes pgTabEnter {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
.pg-view.active.pg-view-enter,
#examsListView.pg-view-enter,
#examsDetailView.pg-view-enter {
    animation: pgTabEnter 0.38s cubic-bezier(0.22, 1, 0.36, 1) both;
}
@media (prefers-reduced-motion: reduce) {
    .pg-view.active.pg-view-enter,
    #examsListView.pg-view-enter,
    #examsDetailView.pg-view-enter {
        animation: none;
    }
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
.pg-dropdown a, .pg-dropdown button {
    display: flex; align-items: center; gap: 8px;
    width: 100%; padding: 8px 14px;
    font-size: 12px; color: rgba(255,255,255,0.8);
    text-decoration: none; background: none; border: none;
    cursor: pointer; text-align: left; font-family: inherit;
}
.pg-dropdown a:hover, .pg-dropdown button:hover { background: rgba(255,255,255,0.06); color: #fff; }
.pg-dropdown .divider { height: 0.5px; background: rgba(255,255,255,0.08); margin: 4px 0; }
.pg-dropdown .danger { color: #f87171; }

/* Shared components */
.pg-stats-row {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 10px; margin-bottom: 20px;
}
@@media (max-width: 900px) { .pg-stats-row { grid-template-columns: repeat(2, 1fr); } }
.pg-stat-card {
    background: #162444; border: 0.5px solid rgba(255,255,255,0.07);
    border-radius: 10px; padding: 14px 16px;
}
.pg-stat-card .num { font-size: 22px; font-weight: 600; color: #fff; line-height: 1; }
.pg-stat-card .lbl { font-size: 11px; color: rgba(255,255,255,0.42); margin-top: 4px; }

.pg-section-head {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; margin-bottom: 12px;
}
.pg-section-head-left { display: flex; align-items: baseline; gap: 10px; min-width: 0; }
.pg-section-head h3 { font-size: 13px; font-weight: 600; color: #fff; margin: 0; }
.pg-section-head span { font-size: 11px; color: rgba(255,255,255,0.40); }
.pg-workspace-cta {
    display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0;
    padding: 8px 16px;
    background: #3b82f6; color: #fff;
    font-size: 13px; font-weight: 500; text-decoration: none;
    border-radius: 50px; transition: background 0.15s;
    border: none; cursor: pointer; font-family: inherit;
}
.pg-workspace-cta:hover { background: #2563eb; }
.pg-workspace-cta i { font-size: 15px; }

.pg-table-wrap {
    background: #162444; border: 0.5px solid rgba(255,255,255,0.07);
    border-radius: 10px; overflow: hidden;
}
.pg-table { width: 100%; border-collapse: collapse; }
.pg-table th {
    font-size: 10px; font-weight: 500; letter-spacing: 1.2px;
    text-transform: uppercase; color: rgba(255,255,255,0.38);
    padding: 10px 14px; text-align: left;
    border-bottom: 0.5px solid rgba(255,255,255,0.07);
    background: rgba(255,255,255,0.02);
}
.pg-table th:not(:first-child) { text-align: right; }
.pg-table td {
    font-size: 13px; color: rgba(255,255,255,0.75);
    padding: 11px 14px;
    border-bottom: 0.5px solid rgba(255,255,255,0.05);
}
.pg-table td:not(:first-child) { text-align: right; }
.pg-table tr:last-child td { border-bottom: none; }
.pg-table tr:hover td { background: rgba(255,255,255,0.02); }
.pg-table .name { color: #fff; font-weight: 500; }
.pg-table .warn { color: #f59e0b; }
.pg-table .ok { color: #22c55e; }
.pg-table-empty { padding: 32px 16px; text-align: center; font-size: 12px; color: rgba(255,255,255,0.35); }

.pg-table tbody tr.pg-exam-row:hover td { background: rgba(255,255,255,0.04); }
.pg-table .exam-cell {
    display: flex; align-items: center; gap: 10px;
    text-align: left;
}
.pg-table td.exam-col { text-align: left !important; }
.pg-exam-cell-title { font-size: 13px; font-weight: 500; color: #fff; display: block; line-height: 1.3; }
.pg-exam-cell-meta { font-size: 11px; color: rgba(255,255,255,0.38); margin-top: 1px; display: block; }

.pg-detail { display: none; }
.pg-detail.active { display: block; }
.pg-section { display: none; }
.pg-section.active { display: block; }

.pg-meta-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px;
}
.pg-meta-card {
    background: #162444; border: 0.5px solid rgba(255,255,255,0.07);
    border-radius: 10px; padding: 14px 16px;
}
.pg-meta-card .lbl {
    font-size: 10px; text-transform: uppercase; letter-spacing: 1px;
    color: rgba(255,255,255,0.38); margin-bottom: 4px;
}
.pg-meta-card .val { font-size: 13px; color: rgba(255,255,255,0.80); line-height: 1.5; }

.pg-empty-state {
    text-align: center; padding: 60px 24px;
    color: rgba(255,255,255,0.40);
}
.pg-empty-state i { font-size: 48px; color: rgba(59,130,246,0.22); margin-bottom: 12px; display: block; }
.pg-empty-state h3 { font-size: 16px; font-weight: 600; color: #fff; margin: 0 0 6px; }
.pg-empty-state p { font-size: 13px; margin: 0 0 16px; }
.pg-empty-cta {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 22px;
    background: #3b82f6; color: #fff; font-size: 13px; font-weight: 500;
    border-radius: 50px; text-decoration: none;
    border: none; cursor: pointer; font-family: inherit;
}
.pg-empty-cta:hover { background: #2563eb; }

.pg-settings-card {
    background: #162444; border: 0.5px solid rgba(255,255,255,0.07);
    border-radius: 10px; padding: 14px 16px;
    display: flex; flex-direction: column; min-height: 0;
}
.pg-settings-card-wide { grid-column: 1 / -1; }
.pg-settings-card-muted { background: rgba(255,255,255,0.02); }
.pg-settings-card h3 { font-size: 13px; font-weight: 600; margin: 0; }

#view-settings.active,
#view-profile.active,
#view-help.active {
    display: flex !important; flex-direction: column;
    justify-content: center; align-items: center;
    flex: 1; width: 100%; min-height: 0; overflow-y: auto;
}
.pg-body:has(#view-settings.active),
.pg-body:has(#view-profile.active),
.pg-body:has(#view-help.active) {
    display: flex; flex-direction: column;
    justify-content: center; align-items: center;
    padding: 32px 40px; min-height: 0;
}
.pg-settings {
    width: 100%; max-width: 960px;
    margin: 0 auto;
    padding: 16px 8px 32px;
}
.pg-settings-layout { display: flex; flex-direction: column; gap: 16px; }
.pg-settings-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    align-items: stretch;
}
.pg-settings-hero {
    display: flex; flex-direction: column; align-items: center; text-align: center;
    gap: 4px; margin-bottom: 4px;
}
.pg-settings-hero h2 {
    margin: 0; font-size: 18px; font-weight: 600; color: #fff;
}
.pg-settings-hero p {
    margin: 0; font-size: 12px; color: rgba(255,255,255,0.42); line-height: 1.4;
}
.pg-settings-alert {
    padding: 8px 12px; border-radius: 8px; font-size: 12px; line-height: 1.4;
    display: flex; align-items: flex-start; gap: 8px;
}
.pg-settings-alert.hidden { display: none; }
.pg-settings-alert i { font-size: 15px; flex-shrink: 0; margin-top: 1px; }
.pg-settings-alert.is-success {
    background: rgba(34,197,94,0.12); color: #86efac;
    border: 0.5px solid rgba(34,197,94,0.25);
}
.pg-settings-alert.is-error {
    background: rgba(239,68,68,0.12); color: #fca5a5;
    border: 0.5px solid rgba(239,68,68,0.25);
}

/* ── Dialogs & toasts ────────────────────────────── */
.pg-dialog-root {
    position: fixed; inset: 0; z-index: 200;
    display: flex; align-items: center; justify-content: center;
    padding: 24px;
}
.pg-dialog-root.hidden { display: none !important; }
.pg-dialog-backdrop {
    position: absolute; inset: 0;
    background: rgba(8, 15, 30, 0.72);
    backdrop-filter: blur(4px);
}
.pg-dialog {
    position: relative; width: 100%; max-width: 400px;
    background: #162444;
    border: 0.5px solid rgba(255,255,255,0.12);
    border-radius: 14px; padding: 22px 22px 18px;
    box-shadow: 0 24px 48px rgba(0,0,0,0.45);
    animation: pgDialogIn 0.22s cubic-bezier(0.22, 1, 0.36, 1) both;
}
@keyframes pgDialogIn {
    from { opacity: 0; transform: translateY(10px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.pg-dialog-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 14px; font-size: 20px;
}
.pg-dialog-icon.is-danger {
    background: rgba(239,68,68,0.14); color: #f87171;
}
.pg-dialog-icon.is-warning {
    background: rgba(245,158,11,0.14); color: #fbbf24;
}
.pg-dialog-icon.is-info {
    background: rgba(59,130,246,0.14); color: #93c5fd;
}
.pg-dialog-icon.is-success {
    background: rgba(34,197,94,0.14); color: #4ade80;
}
.pg-dialog-title {
    margin: 0 0 8px; font-size: 16px; font-weight: 600; color: #fff;
}
.pg-dialog-message {
    margin: 0 0 20px; font-size: 13px; line-height: 1.55;
    color: rgba(255,255,255,0.58);
}
.pg-dialog-message strong { color: rgba(255,255,255,0.88); font-weight: 600; }
.pg-dialog-actions {
    display: flex; justify-content: flex-end; gap: 8px;
}
.pg-dialog-btn {
    padding: 8px 16px; border-radius: 8px; border: none;
    font-size: 12px; font-weight: 600; font-family: inherit;
    cursor: pointer; transition: background 0.12s, color 0.12s;
}
.pg-dialog-btn.hidden { display: none; }
.pg-dialog-btn-cancel {
    background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.75);
    border: 0.5px solid rgba(255,255,255,0.10);
}
.pg-dialog-btn-cancel:hover { background: rgba(255,255,255,0.10); color: #fff; }
.pg-dialog-btn-confirm {
    background: #3b82f6; color: #fff;
}
.pg-dialog-btn-confirm:hover { background: #2563eb; }
.pg-dialog-btn-confirm.is-danger { background: #dc2626; }
.pg-dialog-btn-confirm.is-danger:hover { background: #b91c1c; }
.pg-toast-root {
    position: fixed; right: 20px; bottom: 20px; z-index: 210;
    display: flex; flex-direction: column; gap: 8px; pointer-events: none;
}
.pg-toast {
    display: flex; align-items: center; gap: 10px;
    min-width: 240px; max-width: 360px; padding: 12px 14px;
    background: #162444; border: 0.5px solid rgba(255,255,255,0.12);
    border-radius: 10px; box-shadow: 0 12px 32px rgba(0,0,0,0.35);
    font-size: 12px; color: rgba(255,255,255,0.88);
    animation: pgToastIn 0.28s cubic-bezier(0.22, 1, 0.36, 1) both;
    pointer-events: auto;
}
.pg-toast i { font-size: 16px; flex-shrink: 0; }
.pg-toast.is-success i { color: #4ade80; }
.pg-toast.is-error i { color: #f87171; }
.pg-toast.is-info i { color: #93c5fd; }
@keyframes pgToastIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
@media (prefers-reduced-motion: reduce) {
    .pg-dialog, .pg-toast { animation: none; }
}

.pg-settings-form-error {
    display: flex; align-items: flex-start; gap: 6px;
    padding: 8px 10px; border-radius: 7px; font-size: 11px; line-height: 1.4;
    background: rgba(239,68,68,0.10); color: #fca5a5;
    border: 0.5px solid rgba(239,68,68,0.22);
}
.pg-settings-form-error.hidden { display: none; }
.pg-settings-form-error i { font-size: 13px; flex-shrink: 0; margin-top: 1px; }
.pg-settings-card.has-errors {
    border-color: rgba(239,68,68,0.28);
    box-shadow: 0 0 0 1px rgba(239,68,68,0.08);
}
.pg-settings-card.is-shake {
    animation: pgSettingsShake 0.42s cubic-bezier(0.36, 0.07, 0.19, 0.97);
}
@keyframes pgSettingsShake {
    10%, 90% { transform: translateX(-1px); }
    20%, 80% { transform: translateX(2px); }
    30%, 50%, 70% { transform: translateX(-3px); }
    40%, 60% { transform: translateX(3px); }
}
.pg-settings-field.is-error > span:first-child { color: #fca5a5; }
.pg-settings-field.is-error input,
.pg-settings-field.is-error select {
    border-color: rgba(248,113,113,0.65);
    background: rgba(239,68,68,0.06);
}
.pg-settings-field.is-error input:focus,
.pg-settings-field.is-error select:focus {
    border-color: rgba(248,113,113,0.85);
    box-shadow: 0 0 0 2px rgba(239,68,68,0.12);
}
.pg-settings-field-error {
    display: flex; align-items: flex-start; gap: 4px;
    font-size: 11px; color: #fca5a5; line-height: 1.35;
}
.pg-settings-field-error.hidden { display: none; }
.pg-settings-field-error i { font-size: 12px; flex-shrink: 0; margin-top: 1px; }
.pg-settings-card-head {
    display: flex; align-items: center; gap: 8px; margin-bottom: 10px;
}
.pg-settings-card-head i {
    font-size: 15px; color: #3b82f6; flex-shrink: 0;
}
.pg-settings-form {
    display: flex; flex-direction: column; gap: 10px; flex: 1;
}
.pg-settings-form .pg-settings-btn { margin-top: auto; }
.pg-settings-field {
    display: flex; flex-direction: column; gap: 4px;
}
.pg-settings-field span {
    font-size: 10px; font-weight: 600; letter-spacing: 0.08em;
    text-transform: uppercase; color: rgba(255,255,255,0.35);
}
.pg-settings-field input,
.pg-settings-field select {
    width: 100%; background: rgba(255,255,255,0.03);
    border: 0.5px solid rgba(255,255,255,0.10); border-radius: 7px;
    padding: 8px 10px; color: #fff; font-size: 12px;
    font-family: inherit; outline: none; color-scheme: dark;
    transition: border-color 0.12s, background 0.12s;
}
.pg-settings-field input:focus,
.pg-settings-field select:focus {
    border-color: rgba(59,130,246,0.45); background: rgba(255,255,255,0.05);
}
.pg-settings-field input::placeholder { color: rgba(255,255,255,0.28); }
.pg-settings-hint {
    margin: -2px 0 0; font-size: 11px; color: rgba(255,255,255,0.38); line-height: 1.35;
}
.pg-settings-row {
    display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px;
}
.pg-settings-row-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.pg-settings-btn {
    align-self: flex-start;
    display: inline-flex; align-items: center; justify-content: center;
    padding: 7px 14px; border: none; border-radius: 50px;
    background: #3b82f6; color: #fff; font-size: 12px; font-weight: 500;
    font-family: inherit; cursor: pointer; transition: background 0.15s, opacity 0.15s;
}
.pg-settings-btn:hover:not(:disabled) { background: #2563eb; }
.pg-settings-btn:disabled { opacity: 0.55; cursor: not-allowed; }
.pg-settings-form-toggles { gap: 8px; }
.pg-settings-toggle {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: 8px 0; border-bottom: 0.5px solid rgba(255,255,255,0.06);
    cursor: pointer;
}
.pg-settings-toggle:last-of-type { border-bottom: none; }
.pg-settings-toggle strong {
    display: block; font-size: 12px; font-weight: 500; color: #fff; margin-bottom: 1px;
}
.pg-settings-toggle small {
    display: block; font-size: 11px; color: rgba(255,255,255,0.40); line-height: 1.35;
}
.pg-settings-toggle input {
    position: absolute; opacity: 0; width: 0; height: 0;
}
.pg-settings-toggle-ui {
    width: 36px; height: 20px; border-radius: 999px; flex-shrink: 0;
    background: rgba(255,255,255,0.12); position: relative;
    transition: background 0.18s;
}
.pg-settings-toggle-ui::after {
    content: ''; position: absolute; top: 2px; left: 2px;
    width: 16px; height: 16px; border-radius: 50%; background: #fff;
    transition: transform 0.18s;
}
.pg-settings-toggle input:checked + .pg-settings-toggle-ui {
    background: #3b82f6;
}
.pg-settings-toggle input:checked + .pg-settings-toggle-ui::after {
    transform: translateX(16px);
}
.pg-settings-meta {
    display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin: 0;
}
.pg-settings-meta div {
    display: flex; flex-direction: column; align-items: flex-start; gap: 4px;
    padding: 10px 12px; border-radius: 8px;
    background: rgba(255,255,255,0.03);
    border: 0.5px solid rgba(255,255,255,0.06);
}
.pg-settings-meta dt {
    font-size: 10px; font-weight: 600; letter-spacing: 0.08em;
    text-transform: uppercase; color: rgba(255,255,255,0.35);
}
.pg-settings-meta dd {
    margin: 0; font-size: 12px; color: #fff; font-weight: 500;
}
.pg-settings-badge {
    display: inline-flex; padding: 2px 8px; border-radius: 50px;
    font-size: 10px; font-weight: 600;
}
.pg-settings-badge-ok { background: rgba(34,197,94,0.15); color: #22c55e; }
.pg-settings-badge-warn { background: rgba(245,158,11,0.15); color: #f59e0b; }

.pg-help-links { display: flex; flex-direction: column; gap: 8px; }
.pg-help-link {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; border-radius: 8px;
    background: rgba(255,255,255,0.03);
    border: 0.5px solid rgba(255,255,255,0.06);
    text-decoration: none; color: inherit;
    transition: background 0.12s, border-color 0.12s;
}
.pg-help-link:hover {
    background: rgba(255,255,255,0.06);
    border-color: rgba(255,255,255,0.12);
}
.pg-help-link-icon {
    width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: rgba(59,130,246,0.15); color: #93c5fd; font-size: 16px;
}
.pg-help-link-body { flex: 1; min-width: 0; }
.pg-help-link-body strong {
    display: block; font-size: 12px; font-weight: 600; color: #fff; margin-bottom: 2px;
}
.pg-help-link-body small {
    display: block; font-size: 11px; color: rgba(255,255,255,0.42); line-height: 1.35;
}
.pg-help-link-arrow { font-size: 14px; color: rgba(255,255,255,0.28); flex-shrink: 0; }
.pg-help-steps {
    list-style: none; margin: 0; padding: 0;
    display: flex; flex-direction: column; gap: 8px;
}
.pg-help-step-btn {
    display: flex; align-items: center; gap: 10px;
    width: 100%; padding: 10px 12px;
    background: rgba(255,255,255,0.03);
    border: 0.5px solid rgba(255,255,255,0.06);
    border-radius: 8px; cursor: pointer;
    font-family: inherit; text-align: left; color: inherit;
    transition: background 0.12s, border-color 0.12s;
}
.pg-help-step-btn:hover {
    background: rgba(59,130,246,0.10);
    border-color: rgba(59,130,246,0.25);
}
.pg-help-step-num {
    width: 22px; height: 22px; border-radius: 6px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: rgba(59,130,246,0.18); color: #93c5fd;
    font-size: 11px; font-weight: 700;
}
.pg-help-step-text {
    flex: 1; min-width: 0;
    font-size: 12px; color: rgba(255,255,255,0.82); line-height: 1.35;
}
.pg-help-step-btn > .ti-chevron-right {
    font-size: 14px; color: rgba(255,255,255,0.28); flex-shrink: 0;
}
.pg-help-faq { display: flex; flex-direction: column; gap: 8px; }
.pg-help-faq-item {
    border-radius: 8px;
    background: rgba(255,255,255,0.03);
    border: 0.5px solid rgba(255,255,255,0.06);
    overflow: hidden;
}
.pg-help-faq-item summary {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: 11px 12px; cursor: pointer;
    font-size: 12px; font-weight: 600; color: #fff; list-style: none;
}
.pg-help-faq-item summary::-webkit-details-marker { display: none; }
.pg-help-faq-chevron {
    font-size: 14px; color: rgba(255,255,255,0.35); flex-shrink: 0;
    transition: transform 0.18s;
}
.pg-help-faq-item[open] .pg-help-faq-chevron { transform: rotate(180deg); }
.pg-help-faq-item p {
    margin: 0; padding: 0 12px 12px;
    font-size: 11px; line-height: 1.5; color: rgba(255,255,255,0.52);
}
.pg-help-contact p {
    margin: 0 0 14px;
    font-size: 12px; line-height: 1.5; color: rgba(255,255,255,0.48);
}
.pg-help-contact-actions {
    display: flex; flex-wrap: wrap; align-items: center; gap: 12px;
}
.pg-help-contact-actions .pg-settings-btn {
    display: inline-flex; align-items: center; gap: 6px;
}
.pg-help-contact-actions .pg-settings-btn i { font-size: 14px; }
.pg-help-contact-email {
    font-size: 12px; color: #93c5fd; text-decoration: none;
}
.pg-help-contact-email:hover { color: #bfdbfe; text-decoration: underline; }


#examsListView.hidden, #examsDetailView.hidden { display: none !important; }

/* ── Exams tab workspace ─────────────────────────── */
#view-exams { --exams-gap: 12px; --exams-content-width: 1160px; --exams-table-height: min(500px, calc(100vh - 320px)); }

#view-exams .pg-workspace-header {
    max-width: var(--exams-content-width);
}

.pg-body:has(#view-exams.active),
.pg-body:has(#view-overall-results.active),
.pg-body:has(#view-classes.active) {
    display: flex; flex-direction: column;
    padding: 16px 24px; min-height: 0;
}
.pg-body:has(#view-exams.active),
.pg-body:has(#view-overall-results.active) {
    padding: 16px 24px 0;
    overflow: hidden;
}
#view-exams.active,
#view-overall-results.active,
#view-classes.active {
    display: flex !important; flex-direction: column;
    flex: 1; width: 100%; min-height: 0;
}

#view-exams #examsListView:not(.hidden),
#view-exams #examsDetailView:not(.hidden) {
    flex: 1; display: flex; flex-direction: column;
    justify-content: flex-start; align-items: center;
    width: 100%; gap: 16px;
    padding-top: 96px; padding-bottom: 48px; min-height: 0;
    overflow: hidden;
}
#view-classes #classesView {
    flex: 1; display: flex; flex-direction: column;
    justify-content: center; align-items: center;
    width: 100%; gap: 0;
}

#view-exams #examsListView .pg-workspace-header,
#view-exams #examsDetailView .pg-workspace-header {
    margin: 0 auto;
    flex-shrink: 0;
}

.pg-filter-label {
    font-size: 10px; color: rgba(255,255,255,0.38);
    white-space: nowrap; flex-shrink: 0;
}

.pg-workspace-header {
    width: 100%; max-width: 780px;
    margin: -56px auto 28px;
    display: flex; flex-direction: column; align-items: center;
    flex-shrink: 0;
}
.pg-workspace-brand {
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.pg-workspace-brand-link {
    display: inline-flex; align-items: center; justify-content: center;
    gap: 20px; text-decoration: none;
}
.pg-workspace-brand-link img {
    height: 88px; width: auto; object-fit: contain;
    filter: drop-shadow(0 0 14px rgba(59, 130, 246, 0.55)) drop-shadow(0 0 32px rgba(59, 130, 246, 0.3));
    animation: pg-logo-glow 3.5s ease-in-out infinite;
}
.pg-workspace-brand-link span {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 34px; font-weight: 600;
    color: rgba(255,255,255,0.88); letter-spacing: -0.5px;
    text-shadow: 0 0 24px rgba(59, 130, 246, 0.35);
}
@keyframes pg-logo-glow {
    0%, 100% {
        filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.45)) drop-shadow(0 0 24px rgba(59, 130, 246, 0.22));
    }
    50% {
        filter: drop-shadow(0 0 18px rgba(59, 130, 246, 0.65)) drop-shadow(0 0 40px rgba(96, 165, 250, 0.38));
    }
}
@media (prefers-reduced-motion: reduce) {
    .pg-sidebar-brand img,
    .pg-workspace-brand-link img { animation: none; }
}
.pg-workspace-header .pg-floating-nav {
    margin: 18px auto 0;
}

#view-create-exam #createExamRoot {
    flex: 1; min-height: 0; overflow: hidden;
}
#view-create-exam #createExamView > .pg-workspace-header {
    display: none;
    width: 100%;
    max-width: 780px;
    margin: 0 auto 14px;
    flex-shrink: 0;
}
#view-create-exam #createExamView.pg-create-setup-mode {
    overflow-y: auto;
    overflow-x: hidden;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.08) transparent;
    justify-content: center;
    align-items: center;
    padding: 48px 0 36px;
    box-sizing: border-box;
}
#view-create-exam #createExamView.pg-create-setup-mode #createExamRoot {
    flex: 0 0 auto;
    width: 100%;
    max-width: 620px;
    overflow: visible;
}
#view-create-exam #createExamView.pg-create-setup-mode .pg-create-phase-setup {
    flex: 0 0 auto;
}
#view-create-exam #createExamView.pg-create-setup-mode > .pg-workspace-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding-top: 0;
    margin-bottom: 0;
    width: 100%;
    max-width: 620px;
}
#view-create-exam #createExamView.pg-create-setup-mode .pg-workspace-header .pg-floating-nav {
    margin-top: 12px;
    margin-bottom: 20px;
    gap: 10px;
    padding: 8px 14px;
}
#view-create-exam #createExamView.pg-create-setup-mode .pg-floating-nav .pg-nav-link {
    padding: 10px 20px;
}
#view-create-exam #createExamView.pg-create-setup-mode .pg-workspace-brand-link img {
    height: 72px;
}
#view-create-exam #createExamView.pg-create-setup-mode .pg-workspace-brand-link span {
    font-size: 28px;
}
#view-create-exam #createExamView.pg-create-setup-mode .pg-create-setup-screen {
    flex: 0 1 auto;
    justify-content: flex-start;
    width: 100%;
    max-width: 620px;
    padding: 0 20px 20px;
    box-sizing: border-box;
}

#view-exams .pg-table-wrap {
    width: 100%; max-width: var(--exams-content-width); margin: 0 auto;
    flex: 0 0 auto;
    overflow: visible;
    display: flex; flex-direction: column;
}
#view-exams .pg-exams-table-scroll {
    max-height: var(--exams-table-height);
    overflow-x: auto; overflow-y: auto;
    overscroll-behavior: contain;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.12) transparent;
}
#view-exams .pg-exams-table-scroll.pg-exams-table-scroll--menu-open {
    overflow: visible;
}
#view-exams .pg-table-wrap.pg-exams-table-wrap--menu-open {
    overflow: visible;
    z-index: 40;
}
#view-exams .pg-table-wrap:has(.pg-exam-no-results.visible) .pg-exams-table-scroll {
    min-height: 220px;
}
#view-exams .pg-exams-toolbar {
    display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
    padding: 8px 14px;
    border-bottom: 0.5px solid rgba(255,255,255,0.06);
    background: #162444;
    flex-shrink: 0;
}
#view-exams .pg-filter-input {
    height: 28px; padding: 0 6px;
    background: transparent; border: none;
    color: rgba(255,255,255,0.75); font-size: 11px;
    font-family: inherit; outline: none;
}
#view-exams .pg-filter-date { width: 110px; color-scheme: dark; cursor: pointer; color: rgba(255,255,255,0.60); }
#view-exams .pg-filter-search {
    flex: 1; min-width: 120px; max-width: 280px;
}
#view-exams .pg-filter-search::placeholder { color: rgba(255,255,255,0.30); }
#view-exams .pg-filter-select {
    height: 28px; padding: 0 6px;
    background: transparent; border: none;
    color: rgba(255,255,255,0.60); font-size: 11px;
    font-family: inherit; outline: none; cursor: pointer; color-scheme: dark;
}
#view-exams .pg-filter-select option { background: #162444; color: #fff; }
#view-exams .pg-filter-sep {
    width: 1px; height: 16px; flex-shrink: 0;
    background: rgba(255,255,255,0.10); margin: 0 2px;
}
#view-exams .pg-filter-dash { font-size: 10px; color: rgba(255,255,255,0.25); flex-shrink: 0; }
#view-exams .pg-filter-reset {
    background: none; border: none; padding: 0 6px;
    font-size: 10px; color: rgba(255,255,255,0.35);
    cursor: pointer; font-family: inherit;
    opacity: 0; pointer-events: none; transition: opacity 0.12s, color 0.12s;
}
#view-exams .pg-filter-reset.visible { opacity: 1; pointer-events: auto; }
#view-exams .pg-filter-reset:hover { color: rgba(255,255,255,0.70); }
#view-exams .pg-filter-spacer { flex: 1; min-width: 8px; }
#view-exams .pg-workspace-cta {
    padding: 6px 14px; font-size: 12px; gap: 4px; flex-shrink: 0;
}
#view-exams .pg-workspace-cta i { font-size: 13px; }

#view-exams .pg-table-wrap { border-radius: 8px; }
#view-exams .pg-table { table-layout: fixed; }
#view-exams .pg-table th,
#view-exams .pg-table td {
    padding: 12px 14px; font-size: 13px;
    text-align: center !important;
}
#view-exams .pg-table th {
    font-size: 10px; letter-spacing: 1px; padding: 10px 14px;
    position: sticky; top: 0; z-index: 2;
    background: #162444;
}
#view-exams .pg-table th:nth-child(1) { width: 20%; text-align: left !important; }
#view-exams .pg-table th:nth-child(2) { width: 8%; }
#view-exams .pg-table th:nth-child(3) { width: 10%; }
#view-exams .pg-table th:nth-child(4) { width: 9%; }
#view-exams .pg-table th:nth-child(5) { width: 9%; }
#view-exams .pg-table th:nth-child(6) { width: 7%; }
#view-exams .pg-table th:nth-child(7) { width: 10%; }
#view-exams .pg-table th:nth-child(8) { width: 19%; }
#view-exams .pg-table th:nth-child(9) { width: 48px; }
#view-exams .pg-table td.exam-col { text-align: left !important; }
#view-exams .pg-exam-title-btn {
    background: none; border: none; padding: 0; margin: 0;
    font: inherit; color: #fff; font-size: 13px; font-weight: 500;
    cursor: pointer; text-align: left; max-width: 100%;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
#view-exams .pg-exam-title-btn:hover { color: #93c5fd; }
#view-exams .pg-exam-class-none { color: rgba(255,255,255,0.35); font-style: italic; }
#view-exams .pg-responses-cell { font-variant-numeric: tabular-nums; }
.pg-row-menu { position: relative; display: inline-flex; }
.pg-row-menu-btn {
    width: 28px; height: 28px; border-radius: 6px;
    border: none; background: transparent; color: rgba(255,255,255,0.45);
    cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
}
.pg-row-menu-btn:hover { background: rgba(255,255,255,0.06); color: #fff; }
.pg-row-menu-panel {
    display: none; position: absolute; right: 0; top: calc(100% + 4px);
    min-width: 168px; z-index: 350;
    background: #162444; border: 0.5px solid rgba(255,255,255,0.12);
    border-radius: 8px; padding: 4px; box-shadow: 0 8px 24px rgba(0,0,0,0.35);
}
.pg-row-menu-panel.open-up {
    top: auto;
    bottom: calc(100% + 4px);
}
.pg-row-menu-panel.open { display: block; }
#view-exams .pg-table td:last-child { overflow: visible; position: relative; z-index: 1; }
#view-exams .pg-table tr:has(.pg-row-menu-panel.open) { position: relative; z-index: 30; }
#view-exams .pg-table tr:has(.pg-row-menu-panel.open) td { overflow: visible; }
.pg-row-menu-panel button {
    display: flex; align-items: center; gap: 8px; width: 100%;
    padding: 8px 10px; border: none; border-radius: 6px;
    background: transparent; color: rgba(255,255,255,0.82);
    font-size: 12px; font-family: inherit; cursor: pointer; text-align: left;
}
.pg-row-menu-panel button:hover { background: rgba(255,255,255,0.06); color: #fff; }
.pg-row-menu-panel button.danger { color: #f87171; }
.pg-row-menu-panel button.danger:hover { background: rgba(248,113,113,0.10); }
.pg-row-menu-panel button:disabled { opacity: 0.35; pointer-events: none; }
.pg-row-menu-divider { height: 0.5px; background: rgba(255,255,255,0.08); margin: 4px 0; }
#view-exams .pg-exam-cell-title {
    font-size: 13px; line-height: 1.3;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
#view-exams .pg-pill { padding: 2px 8px; font-size: 10px; }
#view-exams .pg-detail .pg-table .name {
    color: #fff; font-weight: 500;
}
#view-exams .pg-detail .pg-table .warn { color: #f59e0b; }
#view-exams .pg-detail .pg-table .ok { color: #22c55e; }
#view-exams .pg-detail .pg-table { table-layout: auto; }
#view-exams .pg-detail .pg-section { width: 100%; }
#view-exams .pg-detail .pg-section.active { display: block; }

#view-exams .pg-detail-back {
    display: inline-flex; align-items: center; gap: 3px;
    background: none; border: none; padding: 0 4px;
    font-size: 11px; color: rgba(255,255,255,0.50);
    cursor: pointer; font-family: inherit; flex-shrink: 0;
    transition: color 0.12s;
}
#view-exams .pg-detail-back:hover { color: #fff; }
#view-exams .pg-detail-back i { font-size: 13px; }
#view-exams .pg-detail-exam {
    display: inline-flex; align-items: center; gap: 8px; min-width: 0;
}
#view-exams .pg-detail-exam .pg-exam-cell-title {
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    max-width: 180px;
}

#view-exams .pg-table-empty { padding: 20px 12px; font-size: 12px; text-align: center; }
#view-exams .pg-table td.pg-table-empty { border-bottom: none; }
#view-exams .pg-empty-state {
    flex: 0 0 auto;
    display: flex; flex-direction: column;
    justify-content: center; align-items: center;
    width: 100%; max-width: var(--exams-content-width);
    margin: 0 auto; padding: 52px 32px;
    background: #162444;
    border: 0.5px solid rgba(255,255,255,0.07);
    border-radius: 10px;
    box-sizing: border-box;
    text-align: center;
    color: rgba(255,255,255,0.40);
}
#view-exams .pg-empty-state i { font-size: 40px; margin-bottom: 12px; color: rgba(59,130,246,0.22); }
#view-exams .pg-empty-state h3 { font-size: 16px; font-weight: 600; color: #fff; margin: 0 0 8px; }
#view-exams .pg-empty-state p { font-size: 13px; margin: 0; color: rgba(255,255,255,0.45); line-height: 1.5; }
#view-exams .pg-empty-state p strong { color: rgba(255,255,255,0.75); font-weight: 600; }
#view-exams .pg-empty-cta { padding: 9px 20px; font-size: 13px; }
#view-exams .pg-exam-no-results {
    display: none; padding: 36px 16px; font-size: 12px;
    text-align: center; color: rgba(255,255,255,0.42);
}
#view-exams .pg-exam-no-results.visible { display: block; }
#view-exams .pg-section-head { margin-bottom: 8px; justify-content: center; }
#view-exams .pg-section-head h3 { font-size: 13px; }
#view-exams .pg-section-head span { font-size: 11px; }
#view-exams .pg-detail { width: 100%; max-width: var(--exams-content-width); }

/* ── Overall results dashboard ───────────────────── */
#view-overall-results {
    --overall-list-max-h: min(340px, calc(100vh - 420px));
}
#view-overall-results #overallResultsView {
    flex: 1; display: flex; flex-direction: column; align-items: center;
    justify-content: flex-start; width: 100%;
    padding: 48px 24px 48px; min-height: 0;
    overflow: hidden;
}
#view-overall-results .pg-empty-state {
    flex: 1; display: flex; flex-direction: column;
    justify-content: center; align-items: center;
    padding: 40px 24px; max-width: 900px; margin: 0 auto; width: 100%;
}
.pg-overall {
    width: 100%; max-width: 900px;
    display: flex; flex-direction: column; gap: 18px;
    flex: 1 1 auto; min-height: 0; max-height: 100%;
    margin-top: 0; overflow: hidden;
}
.pg-overall-hero { text-align: center; flex-shrink: 0; }
.pg-overall-hero h2 {
    font-size: 26px; font-weight: 700; color: #fff; margin: 0 0 8px;
}
.pg-overall-hero p {
    font-size: 13px; color: rgba(255,255,255,0.42); margin: 0;
}
.pg-overall-stats {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
    flex-shrink: 0;
}
@@media (max-width: 768px) { .pg-overall-stats { grid-template-columns: 1fr; } }
.pg-overall-stats .pg-stat-card { padding: 18px 20px; }
.pg-overall-stats .num { font-size: 26px; }
.pg-overall-stats .lbl { font-size: 12px; margin-top: 6px; }
.pg-overall-stats .num.warn { color: #f59e0b; }
.pg-overall-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 14px;
    flex: 1 1 auto; min-height: 0; align-items: stretch;
}
@@media (max-width: 900px) { .pg-overall-grid { grid-template-columns: 1fr; } }
.pg-overall-block {
    background: #162444; border: 0.5px solid rgba(255,255,255,0.07);
    border-radius: 10px; padding: 18px 20px; min-width: 0;
    display: flex; flex-direction: column; min-height: 0;
    max-height: 100%; overflow: hidden;
}
.pg-overall-block-head {
    display: flex; align-items: baseline; justify-content: space-between;
    gap: 8px; margin-bottom: 14px; flex-shrink: 0;
}
.pg-overall-block-head h3 {
    font-size: 14px; font-weight: 600; color: #fff; margin: 0;
}
.pg-overall-block-head span {
    font-size: 11px; color: rgba(255,255,255,0.38);
}
.pg-overall-exam-list,
.pg-overall-activity-list {
    display: flex; flex-direction: column;
    gap: 14px;
    flex: 1 1 auto; min-height: 0;
    max-height: var(--overall-list-max-h);
    overflow-x: hidden; overflow-y: auto;
    overscroll-behavior: contain;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.12) transparent;
    padding-right: 2px;
}
.pg-overall-activity-list { gap: 12px; }
.pg-overall-exam-row { display: flex; flex-direction: column; gap: 8px; flex-shrink: 0; }
.pg-overall-exam-top {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
.pg-overall-exam-top .exam-cell {
    display: flex; align-items: center; gap: 10px; min-width: 0; flex: 1;
}
.pg-overall-exam-info { min-width: 0; flex: 1; }
.pg-overall-exam-top .pg-exam-cell-title { font-size: 14px; }
.pg-overall-exam-meta {
    display: block; font-size: 11px; color: rgba(255,255,255,0.38); margin-top: 3px;
}
.pg-overall-exam-pct {
    font-size: 13px; font-weight: 600; color: #fff; flex-shrink: 0;
}
.pg-overall-bar {
    height: 5px; border-radius: 99px;
    background: rgba(255,255,255,0.08); overflow: hidden;
}
.pg-overall-bar-fill {
    height: 100%; border-radius: 99px;
    background: linear-gradient(90deg, #3b82f6, #22c55e);
    min-width: 2px; transition: width 0.2s;
}
.pg-overall-empty {
    padding: 24px 8px; text-align: center;
    font-size: 13px; color: rgba(255,255,255,0.35);
    flex: 1 1 auto; min-height: 120px;
    display: flex; align-items: center; justify-content: center;
}
.pg-overall-activity-row {
    display: flex; align-items: center; gap: 12px; flex-shrink: 0;
}
.pg-overall-activity-info { flex: 1; min-width: 0; }
.pg-overall-activity-name {
    display: block; font-size: 14px; font-weight: 500; color: #fff;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.pg-overall-activity-meta {
    display: block; font-size: 11px; color: rgba(255,255,255,0.38); margin-top: 3px;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}

/* ── Students with violations ────────────────────── */
.pg-body:has(#view-violations.active) {
    display: flex; flex-direction: column;
    padding: 16px 24px; min-height: 0;
}
#view-violations.active {
    display: flex !important; flex-direction: column;
    flex: 1; width: 100%; min-height: 0;
}
#view-violations #violationsView {
    flex: 1; display: flex; flex-direction: column;
    align-items: center !important;
    justify-content: flex-start !important;
    width: 100%;
    padding: 72px 24px 16px !important;
}
.pg-violations {
    width: 100%; max-width: 780px;
    display: flex; flex-direction: column; gap: 16px;
    margin-top: 80px;
}
.pg-violations-hero { text-align: center; padding-top: 0; margin-top: 0; }
.pg-violations-hero h2 {
    font-size: 26px; font-weight: 700; color: #fff; margin: 0 0 8px;
}
.pg-violations-hero p {
    font-size: 13px; color: rgba(255,255,255,0.42); margin: 0;
}
.pg-violations-stats {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
}
@@media (max-width: 768px) { .pg-violations-stats { grid-template-columns: 1fr; } }
.pg-violations-stats .pg-stat-card { padding: 16px 18px; }
.pg-violations-stats .num { font-size: 24px; }
.pg-violations-stats .num.warn { color: #f59e0b; }
.pg-violations-stats .lbl { font-size: 12px; margin-top: 5px; }
#view-violations .pg-table-wrap { border-radius: 8px; width: 100%; }
#view-violations .pg-violations-toolbar {
    display: flex; align-items: center; gap: 6px;
    padding: 8px 14px;
    border-bottom: 0.5px solid rgba(255,255,255,0.06);
    background: rgba(255,255,255,0.015);
}
#view-violations .pg-filter-input {
    height: 28px; padding: 0 6px;
    background: transparent; border: none;
    color: rgba(255,255,255,0.75); font-size: 11px;
    font-family: inherit; outline: none;
}
#view-violations .pg-filter-search {
    flex: 1; min-width: 100px; max-width: 200px;
}
#view-violations .pg-filter-search::placeholder { color: rgba(255,255,255,0.30); }
#view-violations .pg-filter-select {
    height: 28px; padding: 0 6px;
    background: transparent; border: none;
    color: rgba(255,255,255,0.60); font-size: 11px;
    font-family: inherit; outline: none; cursor: pointer; color-scheme: dark;
}
#view-violations .pg-filter-select option { background: #162444; color: #fff; }
#view-violations .pg-filter-sep {
    width: 1px; height: 16px; flex-shrink: 0;
    background: rgba(255,255,255,0.10); margin: 0 2px;
}
#view-violations .pg-filter-reset {
    background: none; border: none; padding: 0 6px;
    font-size: 10px; color: rgba(255,255,255,0.35);
    cursor: pointer; font-family: inherit;
    opacity: 0; pointer-events: none; transition: opacity 0.12s, color 0.12s;
}
#view-violations .pg-filter-reset.visible { opacity: 1; pointer-events: auto; }
#view-violations .pg-filter-reset:hover { color: rgba(255,255,255,0.70); }
#view-violations .pg-filter-spacer { flex: 1; min-width: 8px; }
#view-violations .pg-violations-count {
    font-size: 11px; color: rgba(255,255,255,0.38); flex-shrink: 0;
}
#view-violations .pg-table { table-layout: fixed; }
#view-violations .pg-table th,
#view-violations .pg-table td {
    padding: 10px 12px; font-size: 13px;
    text-align: center !important;
}
#view-violations .pg-table th {
    font-size: 10px; letter-spacing: 1px; padding: 8px 12px;
}
#view-violations .pg-table th:nth-child(1),
#view-violations .pg-table th:nth-child(2) { width: 28%; }
#view-violations .pg-table th:nth-child(3) { width: 14%; }
#view-violations .pg-table th:nth-child(4),
#view-violations .pg-table th:nth-child(5) { width: 15%; }
#view-violations .exam-cell {
    display: inline-flex; align-items: center; justify-content: center;
    gap: 8px; max-width: 100%;
}
#view-violations .pg-exam-cell-title {
    font-size: 13px; line-height: 1.3;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
#view-violations .pg-table .warn { color: #f59e0b; font-weight: 600; }
#view-violations .pg-violation-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 24px; padding: 2px 8px; border-radius: 50px;
    font-size: 12px; font-weight: 600;
    background: rgba(245,158,11,0.15); color: #f59e0b;
}
#view-violations .pg-violation-badge.high {
    background: rgba(239,68,68,0.15); color: #f87171;
}
#view-violations .pg-table-empty { padding: 20px 12px; font-size: 12px; text-align: center; }
#view-violations .pg-table td.pg-table-empty { border-bottom: none; }
#view-violations .pg-violation-row {
    cursor: pointer; transition: background 0.12s;
}
#view-violations .pg-violation-row:hover { background: rgba(255,255,255,0.03); }
#view-violations .pg-violation-row.is-selected { background: rgba(59,130,246,0.08); }
#view-violations .pg-violations-no-results {
    display: none; padding: 20px 12px; font-size: 12px;
    text-align: center; color: rgba(255,255,255,0.35);
}
#view-violations .pg-violations-no-results.visible { display: block; }

/* ── Severity badges & violation detail ─────────── */
.pg-severity-pill {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 22px; padding: 2px 8px; border-radius: 999px;
    font-size: 11px; font-weight: 600; text-transform: capitalize;
}
.pg-severity-pill.pg-severity-minor { background: rgba(234,179,8,0.18); color: #facc15; }
.pg-severity-pill.pg-severity-moderate { background: rgba(249,115,22,0.18); color: #fb923c; }
.pg-severity-pill.pg-severity-critical { background: rgba(239,68,68,0.18); color: #f87171; }
.pg-severity-none { font-size: 12px; color: rgba(255,255,255,0.35); }
.pg-severity-summary { display: flex; flex-wrap: wrap; gap: 6px; }
.pg-violation-student-btn {
    background: none; border: none; padding: 0; color: inherit;
    font: inherit; cursor: pointer; text-align: left;
}
.pg-violation-student-btn:hover { color: #93c5fd; }
.pg-violations-detail {
    background: #162444; border: 0.5px solid rgba(255,255,255,0.07);
    border-radius: 10px; overflow: hidden;
}
.pg-violations-detail.hidden { display: none !important; }
.pg-violations-detail-head {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
    padding: 14px 16px; border-bottom: 0.5px solid rgba(255,255,255,0.06);
    background: rgba(255,255,255,0.02);
}
.pg-violations-detail-head h3 {
    margin: 0; font-size: 14px; font-weight: 600; color: #fff;
}
.pg-violations-detail-meta {
    margin: 4px 0 0; font-size: 12px; color: rgba(255,255,255,0.45);
}
.pg-violations-detail-close {
    background: rgba(255,255,255,0.06); border: none; color: #fff;
    width: 30px; height: 30px; border-radius: 8px; cursor: pointer; flex-shrink: 0;
}
.pg-violations-detail-body {
    max-height: 320px; overflow-y: auto; padding: 14px 16px 16px;
}
.pg-violation-event {
    padding: 12px; margin-bottom: 10px; border-radius: 10px;
    background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);
}
.pg-violation-event-head {
    display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
    margin-bottom: 8px; font-size: 11px; color: rgba(255,255,255,0.55);
}
.pg-violation-event-type { text-transform: capitalize; }
.pg-violation-event p { margin: 0 0 10px; font-size: 13px; line-height: 1.45; }
.pg-violation-snapshot {
    width: 100%; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08);
    background: #0b1428;
}

/* ── Proctoring ───────────────────────────────── */
.pg-body:has(#view-live-sessions.active) {
    display: flex; flex-direction: column;
    padding: 16px 24px; min-height: 0;
}
#view-live-sessions.active {
    display: flex !important; flex-direction: column;
    flex: 1; width: 100%; min-height: 0;
}
#view-live-sessions #proctoringView {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: flex-start;
    width: 100%; padding: 72px 24px 16px;
}
.pg-proctoring {
    width: 100%; max-width: 780px;
    display: flex; flex-direction: column; gap: 16px;
    margin-top: 80px;
}
.pg-proctoring-hero { text-align: center; padding-top: 0; }
.pg-proctoring-hero h2 {
    font-size: 26px; font-weight: 700; color: #fff; margin: 0 0 8px;
}
.pg-proctoring-hero p {
    font-size: 13px; color: rgba(255,255,255,0.42); margin: 0;
}
.pg-proctoring-stats {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
}
@@media (max-width: 768px) { .pg-proctoring-stats { grid-template-columns: 1fr; } }
.pg-proctoring-stats .pg-stat-card { padding: 16px 18px; }
.pg-proctoring-stats .num { font-size: 24px; }
.pg-proctoring-stats .num.warn { color: #f59e0b; }
.pg-proctoring-stats .lbl { font-size: 12px; margin-top: 5px; }
#view-live-sessions .pg-table-wrap { border-radius: 8px; width: 100%; }
#view-live-sessions .pg-proctoring-toolbar {
    display: flex; align-items: center; gap: 6px;
    padding: 8px 14px;
    border-bottom: 0.5px solid rgba(255,255,255,0.06);
    background: rgba(255,255,255,0.015);
}
#view-live-sessions .pg-filter-input {
    height: 28px; padding: 0 6px;
    background: transparent; border: none;
    color: rgba(255,255,255,0.75); font-size: 11px;
    font-family: inherit; outline: none;
}
#view-live-sessions .pg-filter-search {
    flex: 1; min-width: 100px; max-width: 200px;
}
#view-live-sessions .pg-filter-search::placeholder { color: rgba(255,255,255,0.30); }
#view-live-sessions .pg-filter-select {
    height: 28px; padding: 0 6px;
    background: transparent; border: none;
    color: rgba(255,255,255,0.60); font-size: 11px;
    font-family: inherit; outline: none; cursor: pointer; color-scheme: dark;
}
#view-live-sessions .pg-filter-select option { background: #162444; color: #fff; }
#view-live-sessions .pg-filter-sep {
    width: 1px; height: 16px; flex-shrink: 0;
    background: rgba(255,255,255,0.10); margin: 0 2px;
}
#view-live-sessions .pg-filter-reset {
    background: none; border: none; padding: 0 6px;
    font-size: 10px; color: rgba(255,255,255,0.35);
    cursor: pointer; font-family: inherit;
    opacity: 0; pointer-events: none; transition: opacity 0.12s, color 0.12s;
}
#view-live-sessions .pg-filter-reset.visible { opacity: 1; pointer-events: auto; }
#view-live-sessions .pg-filter-reset:hover { color: rgba(255,255,255,0.70); }
#view-live-sessions .pg-filter-spacer { flex: 1; min-width: 8px; }
#view-live-sessions .pg-proctoring-count {
    font-size: 11px; color: rgba(255,255,255,0.38); flex-shrink: 0;
}
#view-live-sessions .pg-table { table-layout: fixed; }
#view-live-sessions .pg-table th,
#view-live-sessions .pg-table td {
    padding: 10px 12px; font-size: 13px;
    text-align: center !important;
}
#view-live-sessions .pg-table th {
    font-size: 10px; letter-spacing: 1px; padding: 8px 12px;
}
#view-live-sessions .pg-table th:nth-child(1),
#view-live-sessions .pg-table th:nth-child(2) { width: 22%; }
#view-live-sessions .pg-table th:nth-child(3) { width: 14%; }
#view-live-sessions .pg-table th:nth-child(4),
#view-live-sessions .pg-table th:nth-child(5),
#view-live-sessions .pg-table th:nth-child(6) { width: 14%; }
#view-live-sessions .exam-cell {
    display: inline-flex; align-items: center; justify-content: center;
    gap: 8px; max-width: 100%;
}
#view-live-sessions .pg-exam-cell-title {
    font-size: 13px; line-height: 1.3;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
#view-live-sessions .pg-proctoring-row {
    cursor: pointer; transition: background 0.12s;
}
#view-live-sessions .pg-proctoring-row:hover { background: rgba(255,255,255,0.03); }
#view-live-sessions .pg-proctoring-row.is-selected { background: rgba(59,130,246,0.08); }
#view-live-sessions .pg-proctoring-row.has-alert td:first-child {
    box-shadow: inset 3px 0 0 #f87171;
}
#view-live-sessions .pg-proctoring-student-btn {
    background: none; border: none; padding: 0; color: inherit;
    font: inherit; cursor: pointer;
}
#view-live-sessions .pg-proctoring-student-btn:hover { color: #93c5fd; }
#view-live-sessions .pg-proctoring-status {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600;
}
#view-live-sessions .pg-proctoring-status.is-active { background: rgba(34,197,94,0.15); color: #4ade80; }
#view-live-sessions .pg-proctoring-status.is-disconnected { background: rgba(249,115,22,0.15); color: #fb923c; }
#view-live-sessions .pg-proctoring-alert-flag {
    display: inline-flex; align-items: center; gap: 4px;
    margin-left: 6px; font-size: 10px; font-weight: 600; color: #f87171;
}
#view-live-sessions .pg-warn-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 3px 10px; border-radius: 999px;
    font-size: 11px; font-weight: 700;
    border: 0.5px solid rgba(255,255,255,0.10);
    background: rgba(245,158,11,0.15);
    color: #fbbf24;
}
#view-live-sessions .pg-warn-badge i { font-size: 13px; }
#view-live-sessions .pg-warn-badge.is-low {
    background: rgba(245,158,11,0.15);
    color: #fbbf24;
}
#view-live-sessions .pg-warn-badge.is-near {
    /* orange-ish escalation using same amber token, higher intensity */
    background: rgba(245,158,11,0.25);
    border-color: rgba(245,158,11,0.35);
    color: #fdba74;
}
#view-live-sessions .pg-warn-badge.is-max {
    background: rgba(239,68,68,0.15);
    border-color: rgba(239,68,68,0.25);
    color: #fca5a5;
    animation: pgWarnPulse 0.9s ease-in-out infinite;
}
#view-live-sessions .pg-proctoring-row.is-warn-near { background: rgba(245,158,11,0.06); }
#view-live-sessions .pg-proctoring-row.is-warn-max { background: rgba(239,68,68,0.08); }
#view-live-sessions .pg-max-flag { display: inline-flex; margin-left: 6px; color: #fca5a5; }
@keyframes pgWarnPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.03); }
}
#view-live-sessions .pg-table-empty { padding: 20px 12px; font-size: 12px; text-align: center; }
#view-live-sessions .pg-table td.pg-table-empty { border-bottom: none; }
#view-live-sessions .pg-proctoring-no-results {
    display: none; padding: 20px 12px; font-size: 12px;
    text-align: center; color: rgba(255,255,255,0.35);
}
#view-live-sessions .pg-proctoring-no-results.visible { display: block; }
.pg-proctoring-alert-banner {
    display: none; padding: 10px 14px; border-radius: 10px;
    background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.25);
    color: #fecaca; font-size: 13px;
}
.pg-proctoring-alert-banner.visible { display: block; animation: pg-live-flash 0.4s ease; }
.pg-proctoring-detail {
    background: #162444; border: 0.5px solid rgba(255,255,255,0.07);
    border-radius: 10px; overflow: hidden;
}
.pg-proctoring-detail.hidden { display: none !important; }
.pg-proctoring-detail-head {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
    padding: 14px 16px; border-bottom: 0.5px solid rgba(255,255,255,0.06);
    background: rgba(255,255,255,0.02);
}
.pg-proctoring-detail-head h3 {
    margin: 0; font-size: 14px; font-weight: 600; color: #fff;
}
.pg-proctoring-detail-meta {
    margin: 4px 0 0; font-size: 12px; color: rgba(255,255,255,0.45);
}
.pg-proctoring-detail-close {
    background: rgba(255,255,255,0.06); border: none; color: #fff;
    width: 30px; height: 30px; border-radius: 8px; cursor: pointer; flex-shrink: 0;
}
.pg-proctoring-detail-body {
    max-height: 320px; overflow-y: auto; padding: 14px 16px 16px;
}
@keyframes pg-live-flash {
    0% { transform: scale(0.98); opacity: 0.5; }
    100% { transform: scale(1); opacity: 1; }
}

/* ── Classes tab ─────────────────────────────────── */
#view-classes .pg-table-wrap {
    width: 100%; max-width: 780px; margin: 0 auto;
    border-radius: 8px;
}
#view-classes .pg-classes-toolbar {
    display: flex; flex-direction: column; gap: 8px;
    padding: 8px 14px;
    border-bottom: 0.5px solid rgba(255,255,255,0.06);
    background: rgba(255,255,255,0.015);
}
#view-classes .pg-classes-toolbar-row {
    display: flex; align-items: center; gap: 8px;
}
#view-classes .pg-classes-toolbar-tools {
    display: flex; flex-wrap: wrap; align-items: stretch; gap: 8px;
}
#view-classes .pg-classes-tool-group {
    display: flex; flex-wrap: wrap; align-items: center; gap: 6px;
    padding: 6px 8px;
    background: rgba(255,255,255,0.03);
    border: 0.5px solid rgba(255,255,255,0.07);
    border-radius: 8px;
}
#view-classes .pg-classes-tool-label {
    flex-shrink: 0;
    font-size: 10px; font-weight: 600; letter-spacing: 0.06em;
    text-transform: uppercase; color: rgba(255,255,255,0.32);
    padding-right: 2px;
}
#view-classes .pg-filter-input,
#view-classes .pg-classes-inline-input,
#view-classes .pg-classes-inline-select {
    height: 28px; padding: 0 8px;
    background: rgba(255,255,255,0.04);
    border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 6px;
    color: rgba(255,255,255,0.75); font-size: 11px;
    font-family: inherit; outline: none;
}
#view-classes .pg-classes-inline-input { width: 108px; }
#view-classes .pg-classes-inline-select {
    min-width: 108px; max-width: 140px;
    cursor: pointer; color-scheme: dark;
}
#view-classes .pg-classes-inline-input:focus,
#view-classes .pg-classes-inline-select:focus {
    border-color: rgba(59,130,246,0.35); color: #fff;
}
#view-classes .pg-classes-inline-input::placeholder { color: rgba(255,255,255,0.28); }
#view-classes .pg-filter-search {
    flex: 1; min-width: 120px;
    background: transparent; border: none; border-radius: 0;
    padding: 0 6px;
}
#view-classes .pg-filter-search::placeholder { color: rgba(255,255,255,0.30); }
#view-classes .pg-filter-sep {
    width: 1px; height: 16px; flex-shrink: 0;
    background: rgba(255,255,255,0.10); margin: 0 2px;
}
#view-classes .pg-filter-spacer { flex: 1; min-width: 8px; }
#view-classes .pg-classes-count {
    font-size: 11px; color: rgba(255,255,255,0.38);
    font-variant-numeric: tabular-nums; flex-shrink: 0;
}
.pg-classes-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 4px;
    height: 28px; padding: 0 12px; border-radius: 6px; border: none;
    background: #3b82f6; color: #fff;
    font-size: 11px; font-weight: 500; font-family: inherit;
    cursor: pointer; transition: background 0.15s; flex-shrink: 0;
}
.pg-classes-btn:hover:not(:disabled) { background: #2563eb; }
.pg-classes-btn:disabled { opacity: 0.38; cursor: not-allowed; }
.pg-classes-btn-secondary {
    background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.75);
}
.pg-classes-btn-secondary:hover:not(:disabled) { background: rgba(255,255,255,0.12); color: #fff; }
.pg-classes-btn-danger {
    background: rgba(239,68,68,0.15); color: #fca5a5;
    width: 28px; padding: 0;
}
.pg-classes-btn-icon {
    width: 28px; padding: 0;
}
.pg-classes-btn-danger:hover:not(:disabled) { background: rgba(239,68,68,0.25); color: #fff; }
#view-classes .pg-table {
    table-layout: fixed;
}
#view-classes .pg-table th,
#view-classes .pg-table td {
    padding: 10px 12px; font-size: 13px;
    text-align: center !important;
}
#view-classes .pg-table th {
    font-size: 10px; letter-spacing: 1px; padding: 8px 12px;
}
#view-classes .pg-table th:nth-child(1) { width: 34%; }
#view-classes .pg-table th:nth-child(2) { width: 16%; }
#view-classes .pg-table th:nth-child(3),
#view-classes .pg-table th:nth-child(4) { width: 14%; }
#view-classes .pg-table th:nth-child(5) { width: 22%; }
#view-classes .exam-cell {
    display: inline-flex; align-items: center; justify-content: center;
    gap: 8px; max-width: 100%;
}
#view-classes .pg-table tbody tr:last-child td { border-bottom: none; }
.pg-class-code {
    display: inline-flex; align-items: center; gap: 6px;
    font-family: ui-monospace, monospace; font-size: 12px;
    color: #93c5fd; letter-spacing: 0.06em;
}
.pg-class-meta {
    font-size: 11px; color: rgba(255,255,255,0.42); line-height: 1.4;
}
.pg-class-actions {
    display: flex; align-items: center; justify-content: center; gap: 4px;
}
#view-classes .pg-classes-no-results {
    display: none; padding: 20px 12px; font-size: 12px;
    text-align: center; color: rgba(255,255,255,0.35);
}
#view-classes .pg-classes-no-results.visible { display: block; }
.pg-classes-loading {
    width: 100%; max-width: 780px; margin: 0 auto;
    padding: 20px 12px; font-size: 12px; text-align: center;
    color: rgba(255,255,255,0.38);
}
#view-classes .pg-classes-empty-row td {
    text-align: center; padding: 20px 12px !important;
    color: rgba(255,255,255,0.35); font-size: 12px;
}

/* ── Create exam (document builder) ──────────────── */
@keyframes pgCreateFadeUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes pgCreateItemIn {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes pgCreateSaveGlow {
    0%, 100% { box-shadow: 0 0 0 0 rgba(59,130,246,0); }
    50% { box-shadow: 0 0 0 5px rgba(59,130,246,0.15); }
}
@keyframes pgCreateSpin {
    to { transform: rotate(360deg); }
}
@keyframes pgCreatePublishPulse {
    0%, 100% { transform: scale(1); opacity: 0.55; }
    50% { transform: scale(1.08); opacity: 1; }
}
@keyframes pgCreateSuccessPop {
    0% { opacity: 0; transform: scale(0.6); }
    60% { transform: scale(1.08); }
    100% { opacity: 1; transform: scale(1); }
}
@keyframes pgCreateSuccessRing {
    0% { transform: scale(0.85); opacity: 0.4; }
    100% { transform: scale(1.35); opacity: 0; }
}

.pg-body.pg-quiz-maker-mode {
    display: flex; flex-direction: column;
    padding: 10px 16px; min-height: 0; overflow: hidden;
}
#view-create-exam.active {
    display: flex !important; flex-direction: column;
    flex: 1; width: 100%; min-height: 0; overflow: hidden;
}
#view-create-exam #createExamView {
    flex: 1; width: 100%; min-height: 0; overflow: hidden;
    display: flex; flex-direction: column;
}
#view-create-exam.active #createExamRoot.pg-create-enter .pg-create-phase-setup,
#view-create-exam.active #createExamRoot.pg-create-enter .pg-create-sidebar,
#view-create-exam.active #createExamRoot.pg-create-enter .pg-quiz-panel {
    animation: pgCreateFadeUp 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
}
#view-create-exam.active #createExamRoot.pg-create-enter .pg-create-sidebar { animation-delay: 0.04s; }
#view-create-exam.active #createExamRoot.pg-create-enter .pg-quiz-panel { animation-delay: 0.08s; }

.pg-create-doc {
    flex: 1; min-height: 0;
    display: flex; flex-direction: column;
    overflow: hidden;
    position: relative;
}
.pg-create-phase { flex: 1; min-height: 0; display: flex; flex-direction: column; }
.pg-create-phase.hidden { display: none !important; }
.pg-create-phase-builder {
    display: grid;
    grid-template-columns: 252px minmax(0, 1fr);
    gap: 16px;
    min-height: 0;
    overflow: hidden;
}

/* ── Phase 1: setup screen ── */
.pg-create-phase-setup {
    flex: 1; min-height: 0; overflow: hidden;
}
.pg-create-setup-screen {
    flex: 0 1 auto;
    display: flex; flex-direction: column; align-items: stretch;
    max-width: 620px; width: 100%; margin: 0 auto;
    padding: 0 0 12px;
    animation: pgCreateFadeUp 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
}
.pg-create-setup-card {
    width: 100%;
    padding: 14px 14px 12px;
    background: rgba(255,255,255,0.025);
    border: 0.5px solid rgba(255,255,255,0.10);
    border-radius: 10px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
}
.pg-create-setup-form {
    width: 100%; display: flex; flex-direction: column; gap: 10px;
}
.pg-create-setup-field-wrap {
    display: flex; flex-direction: column; gap: 4px;
}
.pg-create-setup-row {
    display: grid; gap: 10px;
}
.pg-create-setup-row-thirds {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}
.pg-create-setup-options {
    display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
}
.pg-create-setup-control {
    width: 100%; box-sizing: border-box;
    background: rgba(8, 16, 32, 0.55);
    border: 0.5px solid rgba(255,255,255,0.12);
    border-radius: 7px; padding: 7px 10px;
    min-height: 34px;
    color: #fff; font-size: 12px; font-family: inherit;
    outline: none; color-scheme: dark;
    transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
}
.pg-create-setup-control::placeholder { color: rgba(255,255,255,0.28); }
.pg-create-setup-control:hover {
    border-color: rgba(255,255,255,0.18);
    background: rgba(8, 16, 32, 0.68);
}
.pg-create-setup-control:focus {
    border-color: rgba(59,130,246,0.55);
    background: rgba(8, 16, 32, 0.72);
    box-shadow: 0 0 0 2px rgba(59,130,246,0.12);
}
.pg-create-setup-control-wrap {
    position: relative; display: flex; align-items: center;
}
.pg-create-setup-control-wrap .pg-create-setup-control {
    padding-right: 40px;
    -moz-appearance: textfield;
}
.pg-create-setup-control-wrap .pg-create-setup-control::-webkit-outer-spin-button,
.pg-create-setup-control-wrap .pg-create-setup-control::-webkit-inner-spin-button {
    -webkit-appearance: none; margin: 0;
}
.pg-create-setup-suffix {
    position: absolute; right: 12px;
    font-size: 11px; font-weight: 500; letter-spacing: 0.04em;
    color: rgba(255,255,255,0.38); pointer-events: none;
}
.pg-create-setup-select-wrap {
    position: relative; display: flex; align-items: center;
}
.pg-create-setup-select {
    appearance: none; -webkit-appearance: none;
    padding-right: 34px; cursor: pointer;
}
.pg-create-setup-select-wrap i {
    position: absolute; right: 11px; top: 50%;
    transform: translateY(-50%);
    font-size: 14px; color: rgba(255,255,255,0.40);
    pointer-events: none;
}
.pg-create-setup-select option {
    background: #162444; color: #fff;
}
.pg-create-setup-hint {
    margin: 0; width: 100%;
    font-size: 11px; color: rgba(255,255,255,0.35);
    line-height: 1.45; text-align: center;
}
.pg-create-setup-hint.ready { color: rgba(34,197,94,0.85); }
.pg-create-setup-footer {
    display: flex; flex-direction: column; align-items: center;
    gap: 12px; width: 100%; margin-top: 14px;
}
.pg-create-setup-schedule {
    display: flex; flex-direction: column; gap: 6px;
    padding-top: 2px;
}
.pg-create-schedule-head {
    display: flex; align-items: center; justify-content: space-between; gap: 8px;
}
.pg-create-schedule-badge {
    font-size: 9px; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase;
    color: rgba(255,255,255,0.34); padding: 2px 7px; border-radius: 50px;
    background: rgba(255,255,255,0.04); border: 0.5px solid rgba(255,255,255,0.08);
}
.pg-create-schedule-grid {
    display: flex; flex-direction: column; gap: 8px;
    padding: 8px;
    background: rgba(8, 16, 32, 0.32);
    border: 0.5px solid rgba(255,255,255,0.07);
    border-radius: 8px;
}
.pg-create-schedule-line {
    display: grid;
    grid-template-columns: 62px minmax(0, 1fr) minmax(0, 0.82fr);
    gap: 8px; align-items: center;
}
.pg-create-schedule-line .pg-create-field-error {
    grid-column: 1 / -1;
}
.pg-create-schedule-tag {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11px; font-weight: 600; color: rgba(255,255,255,0.72);
    white-space: nowrap;
}
.pg-create-schedule-tag i { font-size: 12px; color: #60a5fa; }
.pg-create-schedule-control-wrap {
    position: relative; display: flex; align-items: center; min-width: 0;
}
.pg-create-schedule-control-wrap .pg-create-setup-control {
    padding-right: 30px; min-height: 32px;
}
.pg-create-schedule-control-wrap i {
    position: absolute; right: 9px; top: 50%; transform: translateY(-50%);
    font-size: 13px; color: rgba(255,255,255,0.34); pointer-events: none;
}
.pg-create-schedule-date,
.pg-create-schedule-time {
    color-scheme: dark;
    cursor: pointer;
}
.pg-create-schedule-time:disabled {
    opacity: 0.42; cursor: not-allowed;
}
.pg-create-schedule-date::-webkit-calendar-picker-indicator,
.pg-create-schedule-time::-webkit-calendar-picker-indicator {
    opacity: 0; position: absolute; right: 0; width: 32px; height: 100%; cursor: pointer;
}
@media (max-width: 640px) {
    .pg-create-setup-row-thirds { grid-template-columns: 1fr; }
    .pg-create-schedule-line {
        grid-template-columns: 1fr 1fr;
    }
    .pg-create-schedule-tag { grid-column: 1 / -1; }
}
.pg-exam-schedule {
    display: block; margin-top: 4px;
    font-size: 10px; color: rgba(255,255,255,0.38); line-height: 1.35;
}
.pg-exam-schedule i { font-size: 10px; margin-right: 2px; vertical-align: -1px; }
.pg-schedule-dialog-fields {
    display: flex; flex-direction: column; gap: 12px; margin-top: 12px; text-align: left;
}
.pg-schedule-dialog-fields label {
    display: flex; flex-direction: column; gap: 6px;
    font-size: 12px; color: rgba(255,255,255,0.72);
}
.pg-schedule-dialog-fields input {
    height: 36px; padding: 0 10px;
    background: rgba(8, 16, 32, 0.55);
    border: 0.5px solid rgba(255,255,255,0.12);
    border-radius: 8px; color: #fff; font-size: 13px;
    font-family: inherit; color-scheme: dark;
}
.pg-schedule-dialog-hint {
    margin: 0; font-size: 11px; color: rgba(255,255,255,0.42); line-height: 1.4;
}
.pg-create-field-error {
    font-size: 11px; color: #fca5a5; line-height: 1.35; margin: 0;
}
.pg-create-field-error.hidden { display: none; }
.pg-create-setup-field-wrap.is-error .pg-create-setup-field,
.pg-create-setup-field-wrap.is-error input,
.pg-create-setup-field-wrap.is-error textarea,
.pg-create-setup-field-wrap.is-error .pg-create-setup-control,
.pg-create-schedule-line.is-error .pg-create-setup-control,
.pg-create-schedule-row.is-error .pg-create-setup-control {
    border-color: rgba(248,113,113,0.85);
    box-shadow: 0 0 0 2px rgba(239,68,68,0.10);
}
.pg-quiz-q-head.is-error .pg-quiz-q-text,
.pg-quiz-opt.is-error .pg-quiz-opt-text,
.pg-quiz-explanation.is-error .pg-quiz-explanation-text {
    box-shadow: inset 0 -1px 0 rgba(248,113,113,0.85);
}
.pg-quiz-options.is-error {
    outline: 1px solid rgba(248,113,113,0.35);
    border-radius: 8px;
    padding: 4px;
    margin: -4px;
}
.pg-create-setup-continue {
    width: 100%; max-width: 220px; flex-shrink: 0;
    min-width: 0; padding: 0 18px;
    height: 38px;
}
.pg-create-setup-continue i { font-size: 13px; }

/* ── Phase 2: edit setup in sidebar ── */
.pg-create-setup-actions {
    flex-shrink: 0;
    padding-bottom: 12px;
    border-bottom: 0.5px solid rgba(255,255,255,0.08);
}
.pg-create-edit-setup {
    display: inline-flex; align-items: center; gap: 5px;
    background: none; border: none; padding: 4px 0;
    font-size: 11px; font-family: inherit; cursor: pointer;
    color: rgba(59,130,246,0.85);
    transition: color 0.12s;
}
.pg-create-edit-setup:hover { color: #93c5fd; }
.pg-create-edit-setup i { font-size: 12px; }

/* ── Left sidebar ── */
.pg-create-sidebar {
    display: flex; flex-direction: column; gap: 20px;
    min-height: 0; overflow: hidden;
    padding: 4px 2px 0 0;
}

/* Exam setup — borderless, compact */
.pg-create-setup {
    flex-shrink: 0;
    display: flex; flex-direction: column; gap: 6px;
}
.pg-create-setup-label {
    font-size: 10px; font-weight: 600; letter-spacing: 0.1em;
    text-transform: uppercase; color: rgba(255,255,255,0.32);
    margin: 0;
}
.pg-create-setup-field {
    width: 100%; background: transparent;
    border: none; border-bottom: 0.5px solid rgba(255,255,255,0.10);
    border-radius: 0; padding: 5px 0;
    color: #fff; font-size: 13px; font-family: inherit; outline: none;
    transition: border-color 0.15s;
}
.pg-create-setup-field:focus {
    border-bottom-color: rgba(59,130,246,0.50);
}
.pg-create-setup-field::placeholder { color: rgba(255,255,255,0.28); }
.pg-create-setup-notes {
    resize: none; line-height: 1.35; min-height: 34px;
    font-size: 12px; color: rgba(255,255,255,0.75);
}
.pg-create-setup-inline {
    display: flex; align-items: center; gap: 8px;
    margin-top: 2px;
}
.pg-create-setup-inline input,
.pg-create-setup-inline select {
    flex: 1; min-width: 0;
    background: transparent; border: none;
    border-bottom: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 0; padding: 5px 0;
    color: rgba(255,255,255,0.60); font-size: 11px;
    font-family: inherit; outline: none; color-scheme: dark;
}
.pg-create-setup-inline input { max-width: 48px; flex: 0 0 48px; }
.pg-create-setup-inline input:focus,
.pg-create-setup-inline select:focus {
    border-bottom-color: rgba(59,130,246,0.40);
    color: #fff;
}
.pg-create-setup-inline input::placeholder { color: rgba(255,255,255,0.25); }

/* Questions — list style */
.pg-create-qlist {
    flex: 1; min-height: 0;
    display: flex; flex-direction: column; gap: 6px;
    overflow: hidden;
}
.pg-create-qlist-head {
    display: flex; align-items: baseline; justify-content: space-between;
    flex-shrink: 0; padding-bottom: 6px;
    border-bottom: 0.5px solid rgba(255,255,255,0.08);
}
.pg-create-qlist-head > span:first-child {
    font-size: 10px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.1em; color: rgba(255,255,255,0.32);
}
.pg-create-qlist-count {
    font-size: 11px; font-weight: 500; color: rgba(255,255,255,0.35);
    font-variant-numeric: tabular-nums;
}
.pg-create-qlist-count.has-items { color: #93c5fd; }
.pg-create-qlist-items {
    flex: 1; min-height: 0; overflow-y: auto;
    list-style: none; margin: 0; padding: 0;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.08) transparent;
}
.pg-create-qlist-empty {
    padding: 12px 0; font-size: 11px; color: rgba(255,255,255,0.28);
    line-height: 1.4;
}
.pg-create-qlist-row {
    display: flex; align-items: baseline; gap: 8px;
    padding: 9px 0; cursor: pointer;
    border-bottom: 0.5px solid rgba(255,255,255,0.06);
    transition: color 0.12s;
}
.pg-create-qlist-row:hover { color: #fff; }
.pg-create-qlist-row.active {
    color: #fff;
    border-bottom-color: rgba(59,130,246,0.25);
}
.pg-create-qlist-row.active .pg-create-qlist-num { color: #93c5fd; }
.pg-create-qlist-num {
    flex-shrink: 0; width: 1.4em;
    font-size: 11px; font-weight: 600;
    color: rgba(255,255,255,0.35);
    font-variant-numeric: tabular-nums;
}
.pg-create-qlist-preview {
    flex: 1; min-width: 0;
    font-size: 12px; color: rgba(255,255,255,0.62);
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    line-height: 1.35;
}
.pg-create-qlist-row:hover .pg-create-qlist-preview,
.pg-create-qlist-row.active .pg-create-qlist-preview { color: rgba(255,255,255,0.88); }
.pg-create-qlist-add {
    flex-shrink: 0; display: inline-flex; align-items: center; gap: 4px;
    background: none; border: none; padding: 6px 0;
    font-size: 11px; font-family: inherit; cursor: pointer;
    color: rgba(255,255,255,0.38);
    transition: color 0.12s;
}
.pg-create-qlist-add:hover { color: #93c5fd; }
.pg-create-qlist-add i { font-size: 13px; }

.pg-create-sidebar-foot {
    flex-shrink: 0; display: flex; flex-direction: column; gap: 8px;
    padding-top: 4px;
    border-top: 0.5px solid rgba(255,255,255,0.06);
}
.pg-create-sidebar-actions {
    display: grid; grid-template-columns: 1fr 1fr; gap: 8px;
}
.pg-create-btn-secondary {
    background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.82); width: 100%;
}
.pg-create-btn-secondary:hover:not(:disabled) { background: rgba(255,255,255,0.12); color: #fff; }
.pg-create-btn-secondary:disabled { opacity: 0.38; cursor: not-allowed; }
.pg-create-btn-secondary.ready:not(:disabled) {
    border: 0.5px solid rgba(255,255,255,0.12);
}
.pg-create-save-hint {
    font-size: 11px; color: rgba(255,255,255,0.35); margin: 0; line-height: 1.4;
}
.pg-create-save-hint.ready { color: rgba(34,197,94,0.85); }
.pg-create-save-hint.is-error { color: #fca5a5; }
.pg-create-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    padding: 9px 16px; border-radius: 50px;
    font-size: 12px; font-weight: 500; font-family: inherit;
    cursor: pointer; border: none;
    transition: background 0.2s, opacity 0.2s, box-shadow 0.2s;
}
.pg-create-btn-primary { background: #3b82f6; color: #fff; width: 100%; }
.pg-create-setup-footer .pg-create-btn-primary {
    width: 100%;
    max-width: 220px;
}
.pg-create-btn-primary:hover:not(:disabled) { background: #2563eb; }
.pg-create-btn-primary:disabled { opacity: 0.38; cursor: not-allowed; }
.pg-create-btn-primary.ready:not(:disabled) { animation: pgCreateSaveGlow 2.5s ease-in-out infinite; }
.pg-create-btn-save.is-publishing,
.pg-create-btn-save.is-saving {
    pointer-events: none; opacity: 1 !important;
}
.pg-create-btn-save.is-publishing { background: #2563eb; }
.pg-create-btn-save.is-saving { background: rgba(255,255,255,0.14); color: #fff; }
.pg-create-btn-save.is-publishing i,
.pg-create-btn-save.is-saving i { animation: pgCreateSpin 0.75s linear infinite; }

/* ── Publish loading overlay ── */
.pg-create-publish-overlay {
    position: absolute; inset: 0; z-index: 40;
    display: flex; align-items: center; justify-content: center;
    background: rgba(15, 30, 61, 0.72);
    backdrop-filter: blur(6px);
    animation: pgCreateFadeUp 0.25s ease both;
}
.pg-create-publish-overlay.hidden { display: none !important; }
.pg-create-publish-panel {
    display: flex; flex-direction: column; align-items: center; gap: 16px;
    padding: 28px 32px;
    background: #162444; border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 14px; box-shadow: 0 16px 48px rgba(0,0,0,0.35);
}
.pg-create-publish-spinner {
    position: relative; width: 56px; height: 56px;
    display: flex; align-items: center; justify-content: center;
}
.pg-create-publish-spinner > i {
    font-size: 22px; color: #93c5fd;
    animation: pgCreatePublishPulse 1.2s ease-in-out infinite;
}
.pg-create-publish-ring {
    position: absolute; inset: 0;
    border: 2px solid transparent;
    border-top-color: #3b82f6; border-right-color: rgba(59,130,246,0.35);
    border-radius: 50%;
    animation: pgCreateSpin 0.9s linear infinite;
}
.pg-create-publish-ring-delay {
    inset: 6px;
    border-top-color: rgba(147,197,253,0.85);
    border-right-color: transparent;
    animation-duration: 1.4s;
    animation-direction: reverse;
}
.pg-create-publish-label {
    margin: 0; font-size: 13px; font-weight: 500; color: rgba(255,255,255,0.82);
}

/* ── Publish success screen ── */
.pg-create-phase-success {
    align-items: center; justify-content: center;
}
.pg-create-success-screen {
    width: 100%; max-width: 420px; margin: 0 auto;
    padding: 32px 20px 40px;
    display: flex; flex-direction: column; align-items: center; text-align: center;
}
.pg-create-success-screen.pg-create-success-enter,
.pg-create-success-enter .pg-create-success-badge,
.pg-create-success-enter .pg-create-success-title,
.pg-create-success-enter .pg-create-success-lead,
.pg-create-success-enter .pg-create-success-card,
.pg-create-success-enter .pg-create-success-actions {
    animation: pgCreateFadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
}
.pg-create-success-enter .pg-create-success-badge { animation-name: pgCreateSuccessPop; animation-duration: 0.55s; }
.pg-create-success-enter .pg-create-success-title { animation-delay: 0.08s; }
.pg-create-success-enter .pg-create-success-lead { animation-delay: 0.14s; }
.pg-create-success-enter .pg-create-success-card { animation-delay: 0.2s; }
.pg-create-success-enter .pg-create-success-actions { animation-delay: 0.28s; }
.pg-create-success-badge {
    position: relative; width: 72px; height: 72px; margin-bottom: 20px;
    display: flex; align-items: center; justify-content: center;
}
.pg-create-success-ring {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid rgba(34,197,94,0.45);
    animation: pgCreateSuccessRing 1.8s ease-out infinite;
}
.pg-create-success-check {
    width: 56px; height: 56px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(145deg, #22c55e, #16a34a);
    box-shadow: 0 8px 24px rgba(34,197,94,0.35);
    color: #fff; font-size: 28px;
}
.pg-create-success-title {
    margin: 0 0 8px; font-size: 24px; font-weight: 600; color: #fff;
}
.pg-create-success-lead {
    margin: 0 0 20px; font-size: 13px; color: rgba(255,255,255,0.52); line-height: 1.5;
}
.pg-create-success-card {
    width: 100%; padding: 14px 16px; margin-bottom: 24px;
    background: rgba(255,255,255,0.04);
    border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 10px;
}
.pg-create-success-exam {
    margin: 0 0 4px; font-size: 15px; font-weight: 600; color: #fff;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.pg-create-success-meta {
    margin: 0; font-size: 12px; color: rgba(255,255,255,0.45);
}
.pg-create-success-key {
    margin-top: 14px; padding-top: 14px;
    border-top: 0.5px solid rgba(255,255,255,0.08);
}
.pg-create-success-key.hidden { display: none; }
.pg-create-success-key-label {
    display: block; margin-bottom: 8px;
    font-size: 10px; font-weight: 600; letter-spacing: 0.08em;
    text-transform: uppercase; color: rgba(255,255,255,0.38);
}
.pg-create-success-key-row {
    display: flex; align-items: center; gap: 8px;
}
.pg-create-success-key code {
    flex: 1; padding: 8px 10px;
    background: rgba(59,130,246,0.12);
    border: 0.5px solid rgba(59,130,246,0.25);
    border-radius: 8px;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 14px; font-weight: 600; letter-spacing: 0.12em;
    color: #93c5fd;
}
.pg-create-success-key-copy {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 8px 10px; border-radius: 8px; border: none;
    background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.82);
    font-size: 11px; font-weight: 600; font-family: inherit; cursor: pointer;
}
.pg-create-success-key-copy:hover { background: rgba(255,255,255,0.10); color: #fff; }
.pg-create-success-key-hint {
    margin: 8px 0 0; font-size: 11px; line-height: 1.45; color: rgba(255,255,255,0.40);
}
.pg-exam-key {
    display: inline-flex; align-items: center; gap: 4px;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 11px; font-weight: 600; letter-spacing: 0.08em; color: #93c5fd;
}
.pg-exam-key-copy {
    width: 22px; height: 22px; padding: 0; border: none; border-radius: 5px;
    background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.45);
    cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
}
.pg-exam-key-copy:hover { background: rgba(59,130,246,0.15); color: #93c5fd; }
.pg-exam-key-copy i { font-size: 12px; }
.pg-exam-key-none { color: rgba(255,255,255,0.28); font-size: 11px; }
.pg-create-success-actions {
    width: 100%; display: flex; flex-direction: column; gap: 10px;
}
.pg-create-btn-ghost {
    background: transparent; color: rgba(255,255,255,0.55); width: 100%;
}
.pg-create-btn-ghost:hover { color: #fff; background: rgba(255,255,255,0.06); }

/* ── Right panel: preformatted editable quiz ── */
.pg-quiz-panel {
    min-height: 0; overflow: hidden;
    display: flex; flex-direction: column;
    background: #162444; border-radius: 10px;
    border: 0.5px solid rgba(255,255,255,0.07);
}
.pg-quiz-toolbar {
    flex-shrink: 0; display: flex; align-items: center; justify-content: space-between; gap: 16px;
    padding: 10px 16px;
    border-bottom: 0.5px solid rgba(255,255,255,0.06);
    background: rgba(255,255,255,0.02);
}
.pg-quiz-toolbar-title {
    margin: 0; min-width: 0; flex: 1;
    font-size: 14px; font-weight: 600; color: #fff;
    letter-spacing: -0.01em; line-height: 1.3;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.pg-quiz-toolbar-count {
    flex-shrink: 0;
    font-size: 11px; font-weight: 500; color: rgba(255,255,255,0.40);
    font-variant-numeric: tabular-nums;
}
.pg-quiz-toolbar-count.has-items { color: #93c5fd; }
.pg-quiz-tool {
    display: inline-flex; align-items: center; justify-content: center; gap: 5px;
    padding: 6px 12px; border-radius: 50px;
    background: rgba(255,255,255,0.06); border: 0.5px solid rgba(255,255,255,0.08);
    cursor: pointer; color: rgba(255,255,255,0.70);
    font-size: 11px; font-family: inherit;
    transition: background 0.12s, color 0.12s, border-color 0.12s;
}
.pg-quiz-tool:hover { background: rgba(59,130,246,0.15); color: #fff; border-color: rgba(59,130,246,0.30); }
.pg-quiz-tool i { font-size: 14px; }

.pg-quiz-add-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    align-self: center; margin-top: 4px;
    padding: 10px 20px; border-radius: 50px;
    background: #3b82f6; border: none;
    cursor: pointer; color: #fff;
    font-size: 12px; font-weight: 500; font-family: inherit;
    transition: background 0.15s;
}
.pg-quiz-add-btn:hover { background: #2563eb; }
.pg-quiz-add-btn i { font-size: 14px; }

.pg-quiz-scroll {
    flex: 1; min-height: 0; overflow-y: auto;
    padding: 16px 18px 20px;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.08) transparent;
}

.pg-quiz-sheet {
    max-width: 720px; margin: 0 auto;
    display: flex; flex-direction: column; gap: 16px;
    font-family: 'Plus Jakarta Sans', ui-sans-serif, sans-serif;
    font-size: 13px; line-height: 1.5; color: rgba(255,255,255,0.85);
}

.pg-quiz-header {
    padding: 18px 20px;
    background: rgba(255,255,255,0.03);
    border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 10px;
    display: flex; flex-direction: column; gap: 8px;
}
.pg-quiz-field-label {
    font-size: 10px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.07em; color: rgba(255,255,255,0.35);
}
.pg-quiz-title {
    font-size: 20px; font-weight: 600; color: #fff;
    margin: 0; outline: none; line-height: 1.3;
    letter-spacing: -0.02em;
}
.pg-quiz-title:empty::before,
.pg-quiz-instructions:empty::before,
.pg-quiz-q-text:empty::before,
.pg-quiz-opt-text:empty::before,
.pg-quiz-explanation-text:empty::before,
[data-draft]:empty::before {
    content: attr(data-placeholder);
    color: rgba(255,255,255,0.28); pointer-events: none;
}
.pg-quiz-instructions {
    font-size: 13px; color: rgba(255,255,255,0.65);
    outline: none; min-height: 2.5em; line-height: 1.55;
}
.pg-quiz-meta {
    display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px;
}
.pg-quiz-meta:empty { display: none; }
.pg-quiz-meta::before {
    content: none;
}
.pg-quiz-meta {
    font-size: 11px; color: rgba(255,255,255,0.40);
}
#docMetaMirror:not(:empty) {
    display: inline-flex; align-items: center; gap: 6px;
}
#docMetaMirror:not(:empty)::before {
    content: '';
    display: inline-block; width: 4px; height: 4px; border-radius: 50%;
    background: rgba(59,130,246,0.50);
}

.pg-quiz-section-head { padding: 0 2px; }
.pg-quiz-section-label {
    font-size: 10px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.08em; color: rgba(255,255,255,0.35);
}

.pg-quiz-body { display: flex; flex-direction: column; gap: 10px; }
.pg-quiz-q {
    animation: pgCreateItemIn 0.28s ease both;
    padding: 16px 18px;
    background: rgba(255,255,255,0.03);
    border: 0.5px solid rgba(255,255,255,0.07);
    border-radius: 10px;
    transition: border-color 0.15s, background 0.15s;
}
.pg-quiz-q.active {
    border-color: rgba(59,130,246,0.35);
    background: rgba(59,130,246,0.08);
    box-shadow: 0 0 0 1px rgba(59,130,246,0.12);
}
.pg-quiz-q-head {
    display: flex; align-items: flex-start; gap: 10px; margin-bottom: 12px;
}
.pg-quiz-q-num {
    flex-shrink: 0; min-width: 26px; height: 26px;
    border-radius: 7px; font-size: 12px; font-weight: 600;
    background: rgba(59,130,246,0.18); color: #93c5fd;
    display: flex; align-items: center; justify-content: center;
}
.pg-quiz-q-text {
    flex: 1; outline: none; font-size: 14px; font-weight: 600;
    color: #fff; line-height: 1.45; min-height: 1.4em;
}
.pg-quiz-q-remove {
    flex-shrink: 0; background: none; border: none; cursor: pointer;
    color: rgba(255,255,255,0.25); padding: 4px; border-radius: 6px;
    transition: color 0.12s, background 0.12s;
}
.pg-quiz-q-remove:hover { color: #f87171; background: rgba(239,68,68,0.12); }

.pg-quiz-options {
    list-style: none; margin: 0 0 12px; padding: 0;
    display: flex; flex-direction: column; gap: 6px;
}
.pg-quiz-opt {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 10px; border-radius: 8px;
    background: rgba(255,255,255,0.02);
    border: 0.5px solid rgba(255,255,255,0.05);
    transition: background 0.12s, border-color 0.12s;
}
.pg-quiz-opt.correct {
    background: rgba(34,197,94,0.08);
    border-color: rgba(34,197,94,0.25);
}
.pg-quiz-opt.correct .pg-quiz-opt-text { color: #86efac; font-weight: 500; }
.pg-quiz-opt.correct .pg-quiz-opt-letter {
    background: rgba(34,197,94,0.25); color: #86efac;
    border-color: rgba(34,197,94,0.40);
}
.pg-quiz-opt-mark {
    flex-shrink: 0; cursor: pointer; display: flex; align-items: center;
}
.pg-quiz-opt-mark input { position: absolute; opacity: 0; pointer-events: none; }
.pg-quiz-opt-letter {
    width: 26px; height: 26px; border-radius: 6px;
    font-size: 11px; font-weight: 700;
    background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.50);
    border: 0.5px solid rgba(255,255,255,0.10);
    display: flex; align-items: center; justify-content: center;
    transition: background 0.15s, color 0.15s, border-color 0.15s;
}
.pg-quiz-opt-mark input:checked + .pg-quiz-opt-letter,
.pg-quiz-opt.correct .pg-quiz-opt-letter {
    background: rgba(34,197,94,0.25); color: #86efac;
    border-color: rgba(34,197,94,0.40);
}
.pg-quiz-opt-text {
    flex: 1; outline: none; font-size: 13px;
    color: rgba(255,255,255,0.75); line-height: 1.4; min-height: 1.3em;
}

.pg-quiz-explanation {
    display: flex; flex-direction: column; gap: 4px;
    padding: 10px 12px; border-radius: 8px;
    background: rgba(255,255,255,0.02);
    border-left: 2px solid rgba(255,255,255,0.12);
}
.pg-quiz-explanation-label {
    font-size: 10px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.06em; color: rgba(255,255,255,0.35);
}
.pg-quiz-explanation-text {
    outline: none; font-size: 12px; color: rgba(255,255,255,0.55);
    line-height: 1.45; min-height: 1.3em;
}

.pg-quiz-draft {
    padding: 16px 18px;
    border: 0.5px dashed rgba(255,255,255,0.12);
    border-radius: 10px;
    background: rgba(255,255,255,0.015);
}
.pg-quiz-draft-label {
    font-size: 10px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.07em; color: rgba(255,255,255,0.35); margin: 0 0 12px;
}
.pg-quiz-draft-block .pg-quiz-q-num {
    background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.40);
}

@@media (max-width: 1100px) {
    .pg-create-phase-builder { grid-template-columns: 228px minmax(0, 1fr); }
}

@@media (max-width: 900px) {
    .pg-create-phase-builder {
        grid-template-columns: 1fr;
        grid-template-rows: auto minmax(0, 1fr);
    }
    .pg-create-sidebar { max-height: 36vh; }
    .pg-create-qlist { min-height: 64px; }
    .pg-create-setup-row-thirds { grid-template-columns: 1fr; }
}

@@media (max-width: 768px) {
    .pg-body.pg-quiz-maker-mode { padding: 8px 10px; }
    #view-create-exam #createExamView.pg-create-setup-mode {
        padding: 28px 0 24px;
    }
    #view-create-exam #createExamView.pg-create-setup-mode > .pg-workspace-header {
        margin: 0 auto;
        padding-top: 0;
    }
    #view-create-exam #createExamView.pg-create-setup-mode .pg-workspace-header .pg-floating-nav {
        margin-top: 8px;
        margin-bottom: 14px;
        gap: 8px;
        padding: 7px 10px;
    }
    #view-create-exam #createExamView.pg-create-setup-mode .pg-floating-nav .pg-nav-link {
        padding: 9px 14px;
    }
    #view-create-exam #createExamView.pg-create-setup-mode .pg-workspace-brand-link img {
        height: 56px;
    }
    #view-create-exam #createExamView.pg-create-setup-mode .pg-workspace-brand-link span {
        font-size: 22px;
    }
    #view-create-exam #createExamView.pg-create-setup-mode .pg-create-setup-screen {
        padding: 0 12px 12px;
    }
    .pg-quiz-scroll { padding: 12px 10px 16px; }
    .pg-quiz-q { padding: 14px; }
    .pg-sidebar { width: 64px; }
    .pg-sidebar-brand img { height: 52px; }
    .pg-sidebar .pg-nav-link span, .pg-nav-label { display: none; }
    .pg-sidebar .pg-nav-link { justify-content: center; padding: 10px 0; border-left: none; border-right: 3px solid transparent; }
    .pg-sidebar .pg-nav-link.active { border-right-color: #3b82f6; }
    .pg-floating-actions { top: 12px; right: 12px; gap: 8px; }
    .pg-floating-notify { width: 40px; height: 40px; }
    .pg-floating-notify i { font-size: 17px; }
    .pg-floating-profile .pg-avatar { width: 32px; height: 32px; font-size: 12px; }
    .pg-workspace-header { margin: -40px auto 20px; }
    #view-overall-results { --overall-list-max-h: min(280px, calc(100vh - 380px)); }
    #view-overall-results #overallResultsView { padding: 32px 16px 44px; }
    #view-exams { --exams-table-height: min(420px, calc(100vh - 280px)); }
    #view-exams #examsListView:not(.hidden),
    #view-exams #examsDetailView:not(.hidden) { padding-top: 64px; padding-bottom: 44px; }
    .pg-workspace-brand-link img { height: 64px; }
    .pg-workspace-brand-link span { font-size: 26px; }
    .pg-workspace-header .pg-floating-nav { margin-top: 14px; }
    .pg-floating-nav { margin-bottom: 20px; padding: 5px; }
    .pg-floating-nav .pg-nav-link { padding: 9px 14px; font-size: 12px; }
    .pg-floating-nav .pg-nav-link span { display: none; }
    .pg-floating-nav .pg-nav-link i { font-size: 18px; }
    .pg-settings-grid { grid-template-columns: 1fr; }
    .pg-settings-row,
    .pg-settings-row-3,
    .pg-settings-meta { grid-template-columns: 1fr; }
    .pg-body:has(#view-settings.active),
    .pg-body:has(#view-profile.active),
    .pg-body:has(#view-help.active) { padding: 20px 16px; }
    .pg-settings { padding: 12px 4px 24px; }
    .pg-settings-grid { gap: 12px; }
    .pg-detail-tabs { display: none !important; }
}
</style>
@include('partials.settings-shared-styles')

<div class="pg-layout">

    {{-- LEFT: Sidebar (analytics + account) --}}
    <aside class="pg-sidebar">
        <div class="pg-sidebar-top">
            <a href="/" class="pg-sidebar-brand">
                <img src="/images/logo.png" alt="ExamGuard">
            </a>
        </div>

        <nav class="pg-sidebar-nav">
            <p class="pg-nav-label">Workspace</p>
            <button type="button" class="pg-nav-link active" data-view="workspace">
                <i class="ti ti-home"></i><span>Workspace</span>
            </button>

            <p class="pg-nav-label">Analytics</p>
            <button type="button" class="pg-nav-link" data-view="live-sessions">
                <i class="ti ti-broadcast"></i><span>Proctoring</span>
            </button>
            <button type="button" class="pg-nav-link" data-view="overall-results">
                <i class="ti ti-chart-bar"></i><span>Overall results</span>
            </button>
            <button type="button" class="pg-nav-link" data-view="violations">
                <i class="ti ti-alert-triangle"></i><span>Students with violations</span>
            </button>

            <p class="pg-nav-label">Account</p>
            <button type="button" class="pg-nav-link" data-view="settings" data-open-settings-section="notifications">
                <i class="ti ti-settings"></i><span>Settings</span>
            </button>
        </nav>

        <div id="pgSidebarLiveWidget" class="pg-sidebar-live-widget hidden" aria-live="polite"></div>

        <div class="pg-sidebar-footer" aria-label="Sidebar footer">
            <button type="button" data-view="help">
                <i class="ti ti-help-circle"></i><span>Help &amp; Support</span>
            </button>
        </div>
    </aside>

    {{-- RIGHT: Workspace --}}
    <div class="pg-main">
        <div class="pg-floating-actions">
            <div class="pg-floating-notify" id="topbarNotifyToggle" role="button" tabindex="0" aria-label="Notifications" aria-haspopup="true">
                <i class="ti ti-bell" aria-hidden="true"></i>
                <span class="pg-notify-badge hidden" id="notifyBadge" aria-hidden="true">0</span>
                <div class="pg-notify-panel" id="topbarNotifyPanel">
                    <div class="pg-notify-head">
                        <span>Notifications</span>
                        <button type="button" class="pg-notify-mark-all" id="notifyMarkAllBtn" disabled>Mark all read</button>
                    </div>
                    <div class="pg-notify-list" id="notifyList">
                        <div class="pg-notify-loading">Loading notifications…</div>
                    </div>
                </div>
            </div>
            <div class="pg-floating-profile" id="topbarProfileToggle" role="button" tabindex="0" aria-label="Profile menu" aria-haspopup="true">
                <div class="pg-avatar">{{ $userInitials }}</div>
                <div class="pg-dropdown" id="topbarProfileDropdown">
                    <button type="button" data-switch-view="profile"><i class="ti ti-user"></i> View profile</button>
                    <div class="divider"></div>
                    <button type="button" class="danger" data-logout><i class="ti ti-logout"></i> Log out</button>
                </div>
            </div>
        </div>

        <div class="pg-body">

            {{-- VIEW: Exams --}}
            <div class="pg-view active" id="view-exams" data-view="exams">

                {{-- Exam list --}}
                <div id="examsListView">
                    @include('partials.workspace-header')
                    @if($examList->isEmpty())
                        <div class="pg-empty-state">
                            <i class="ti ti-file-off"></i>
                            <h3>No exams yet</h3>
                            <p>Select <strong>Create exam</strong> above to build your first assessment.</p>
                        </div>
                    @else
                        <div class="pg-table-wrap">
                            <div class="pg-exams-toolbar">
                                <input type="search" id="examSearch" class="pg-filter-input pg-filter-search" placeholder="Search exams" autocomplete="off" aria-label="Search exams">
                                <span class="pg-filter-sep"></span>
                                <span class="pg-filter-label">Updated</span>
                                <input type="date" id="examDateFrom" class="pg-filter-input pg-filter-date" aria-label="Updated from">
                                <span class="pg-filter-dash">–</span>
                                <input type="date" id="examDateTo" class="pg-filter-input pg-filter-date" aria-label="Updated to">
                                <span class="pg-filter-sep"></span>
                                <select id="examStatusFilter" class="pg-filter-select" aria-label="Filter by status">
                                    <option value="">All statuses</option>
                                    <option value="draft">Draft</option>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="active">Active</option>
                                    <option value="closed">Closed</option>
                                </select>
                                <span class="pg-filter-sep"></span>
                                <select id="examSort" class="pg-filter-select" aria-label="Sort exams">
                                    <option value="updated-desc">Updated (newest)</option>
                                    <option value="updated-asc">Updated (oldest)</option>
                                    <option value="created-desc">Created (newest)</option>
                                    <option value="created-asc">Created (oldest)</option>
                                    <option value="name-asc">Name (A–Z)</option>
                                    <option value="name-desc">Name (Z–A)</option>
                                    <option value="status-asc">Status (A–Z)</option>
                                    <option value="status-desc">Status (Z–A)</option>
                                </select>
                                <button type="button" id="examFilterClear" class="pg-filter-reset" aria-label="Clear filters">Reset</button>
                            </div>
                            <div class="pg-exams-table-scroll">
                            <table class="pg-table" id="examsTable">
                                <thead>
                                    <tr>
                                        <th>Exam</th>
                                        <th>Questions</th>
                                        <th>Class</th>
                                        <th>Exam key</th>
                                        <th>Responses</th>
                                        <th>Limit</th>
                                        <th>Status</th>
                                        <th>Updated</th>
                                        <th aria-label="Actions"></th>
                                    </tr>
                                </thead>
                                <tbody id="examsTableBody">
                                @foreach($examList as $index => $exam)
                                    @php
                                        $displayStatus = $exam->displayStatus();
                                        $questionCount = $exam->questions_count ?? $exam->questions->count();
                                        $classroom = $exam->assignments->first()?->classroom;
                                        $className = $classroom?->name;
                                        $totalStudents = $classroom ? $classroom->enrollments->count() : 0;
                                        $submitted = $exam->attempts->whereNotNull('submitted_at')->count();
                                        $statusLabel = ucfirst($displayStatus);
                                    @endphp
                                    <tr class="pg-exam-row"
                                        data-exam-id="{{ $exam->id }}"
                                        data-title="{{ $exam->title }}"
                                        data-status="{{ $displayStatus }}"
                                        data-sort-index="{{ $index }}"
                                        data-updated="{{ $exam->updated_at->format('Y-m-d') }}"
                                        data-created="{{ $exam->created_at->format('Y-m-d') }}"
                                        @if($exam->opens_at) data-opens-at="{{ $exam->opens_at->toIso8601String() }}" @endif
                                        @if($exam->closes_at) data-closes-at="{{ $exam->closes_at->toIso8601String() }}" @endif
                                        @if($exam->exam_key) data-exam-key="{{ $exam->exam_key }}" @endif>
                                        <td class="exam-col">
                                            <button type="button" class="pg-exam-title-btn" title="{{ $exam->title }}">{{ $exam->title }}</button>
                                        </td>
                                        <td>{{ $questionCount }}</td>
                                        <td>
                                            @if($className)
                                                <span title="{{ $className }}">{{ $className }}</span>
                                            @else
                                                <span class="pg-exam-class-none">None</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($exam->exam_key)
                                                <span class="pg-exam-key">
                                                    <span>{{ $exam->exam_key }}</span>
                                                    <button type="button" class="pg-exam-key-copy" data-exam-key-copy="{{ $exam->exam_key }}" aria-label="Copy exam key" title="Copy exam key">
                                                        <i class="ti ti-copy"></i>
                                                    </button>
                                                </span>
                                            @else
                                                <span class="pg-exam-key-none">—</span>
                                            @endif
                                        </td>
                                        <td class="pg-responses-cell">{{ $submitted }} / {{ $totalStudents }}</td>
                                        <td>{{ $exam->time_limit }}m</td>
                                        <td>
                                            <span class="pg-pill pg-pill-{{ $displayStatus }}">{{ $statusLabel }}</span>
                                            @if($displayStatus === 'scheduled' && $exam->opens_at)
                                                <span class="pg-exam-schedule"><i class="ti ti-calendar-time"></i>Opens {{ $exam->opens_at->format('M d, g:i A') }}</span>
                                            @elseif($exam->closes_at && in_array($displayStatus, ['active', 'scheduled']))
                                                <span class="pg-exam-schedule"><i class="ti ti-clock"></i>Closes {{ $exam->closes_at->format('M d, g:i A') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $exam->updated_at->format('M d, Y') }}</td>
                                        <td>
                                            <div class="pg-row-menu">
                                                <button type="button" class="pg-row-menu-btn" aria-label="Exam actions" aria-haspopup="true">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </button>
                                                <div class="pg-row-menu-panel" role="menu">
                                                    <button type="button" data-exam-action="view" role="menuitem"><i class="ti ti-eye"></i> View</button>
                                                    <button type="button" data-exam-action="edit" role="menuitem" @disabled($displayStatus !== 'draft')><i class="ti ti-edit"></i> Edit</button>
                                                    <button type="button" data-exam-action="duplicate" role="menuitem"><i class="ti ti-copy"></i> Duplicate</button>
                                                    <button type="button" data-exam-action="share" role="menuitem" @disabled(! $exam->exam_key)><i class="ti ti-link"></i> Copy exam key</button>
                                                    @if(in_array($displayStatus, ['active', 'scheduled']))
                                                        <button type="button" data-exam-action="schedule" role="menuitem"><i class="ti ti-calendar-time"></i> Schedule</button>
                                                        <div class="pg-row-menu-divider"></div>
                                                        <button type="button" data-exam-action="close" role="menuitem"><i class="ti ti-circle-x"></i> Close exam</button>
                                                    @endif
                                                    <div class="pg-row-menu-divider"></div>
                                                    <button type="button" data-exam-action="delete" class="danger" role="menuitem"><i class="ti ti-trash"></i> Delete</button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                            <div class="pg-table-empty pg-exam-no-results" id="examNoResults">No exams match the selected filters.</div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Exam detail (per exam) --}}
                <div id="examsDetailView" class="hidden">
                    @include('partials.workspace-header')
                    @foreach($examList as $exam)
                        @php
                            $attempts = $exam->attempts;
                            $detailStatus = $exam->displayStatus();
                            $detailClassroom = $exam->assignments->first()?->classroom;
                            $detailStudents = $detailClassroom ? $detailClassroom->enrollments->count() : 0;
                            $detailSubmitted = $attempts->whereNotNull('submitted_at')->count();
                        @endphp
                        <div class="pg-detail" id="detail-{{ $exam->id }}" data-exam-id="{{ $exam->id }}">
                            <div class="pg-table-wrap">
                                <div class="pg-exams-toolbar">
                                    <button type="button" class="pg-detail-back" aria-label="Back to exams">
                                        <i class="ti ti-arrow-left"></i> Back
                                    </button>
                                    <span class="pg-filter-sep"></span>
                                    <div class="pg-detail-exam">
                                        <span class="pg-exam-cell-title">{{ $exam->title }}</span>
                                        <span class="pg-pill pg-pill-{{ $detailStatus }}">{{ ucfirst($detailStatus) }}</span>
                                        @if($exam->exam_key)
                                            <span class="pg-exam-key">
                                                <span>{{ $exam->exam_key }}</span>
                                                <button type="button" class="pg-exam-key-copy" data-exam-key-copy="{{ $exam->exam_key }}" aria-label="Copy exam key" title="Copy exam key">
                                                    <i class="ti ti-copy"></i>
                                                </button>
                                            </span>
                                        @endif
                                        @if($exam->opens_at || $exam->closes_at)
                                            <span class="pg-filter-label">
                                                @if($exam->opens_at)Opens {{ $exam->opens_at->format('M d, g:i A') }}@endif
                                                @if($exam->opens_at && $exam->closes_at) · @endif
                                                @if($exam->closes_at)Closes {{ $exam->closes_at->format('M d, g:i A') }}@endif
                                            </span>
                                        @endif
                                        <span class="pg-filter-label">{{ $detailSubmitted }} / {{ $detailStudents }} submitted</span>
                                    </div>
                                    <span class="pg-filter-spacer"></span>
                                    @if($detailStatus === 'draft')
                                        <button type="button" class="pg-workspace-cta" data-switch-view="create-exam" data-exam-id="{{ $exam->id }}"><i class="ti ti-edit"></i> Edit</button>
                                    @endif
                                </div>
                                @include('partials.exam-attempt-table', [
                                    'attempts' => $attempts,
                                    'emptyMessage' => 'No students have taken this exam yet.',
                                ])
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- VIEW: Overall results --}}
            <div class="pg-view" id="view-overall-results" data-view="overall-results">
                <div id="overallResultsView">
                    @if($examList->isEmpty())
                        <div class="pg-empty-state">
                            <i class="ti ti-chart-bar"></i>
                            <h3>No results yet</h3>
                            <p>Create an exam and collect student responses to see results here.</p>
                        </div>
                    @else
                        <div class="pg-overall">
                            <div class="pg-overall-hero">
                                <h2>Overall results</h2>
                                <p>Performance snapshot across all {{ $examList->count() }} {{ $examList->count() === 1 ? 'exam' : 'exams' }}.</p>
                            </div>

                            <div class="pg-overall-stats">
                                <div class="pg-stat-card">
                                    <div class="num">{{ $totalResponses }}</div>
                                    <div class="lbl">Total responses</div>
                                </div>
                                <div class="pg-stat-card">
                                    <div class="num">{{ $completionRate }}%</div>
                                    <div class="lbl">Completion rate</div>
                                </div>
                                <div class="pg-stat-card">
                                    <div class="num {{ $allViolations->count() > 0 ? 'warn' : '' }}">{{ $allViolations->count() }}</div>
                                    <div class="lbl">Students flagged</div>
                                </div>
                            </div>

                            <div class="pg-overall-grid">
                                <div class="pg-overall-block">
                                    <div class="pg-overall-block-head">
                                        <h3>Performance by exam</h3>
                                        <span>{{ $inProgressCount }} in progress</span>
                                    </div>
                                    <div class="pg-overall-exam-list">
                                        @foreach($examPerformance as $row)
                                            @php
                                                $exam = $row['exam'];
                                                $completionPct = $row['responses'] > 0
                                                    ? round(($row['completed'] / $row['responses']) * 100)
                                                    : 0;
                                            @endphp
                                            <div class="pg-overall-exam-row">
                                                <div class="pg-overall-exam-top">
                                                    <div class="exam-cell">
                                                        <div class="pg-overall-exam-info">
                                                            <span class="pg-exam-cell-title">{{ $exam->title }}</span>
                                                            <span class="pg-overall-exam-meta">{{ $row['completed'] }}/{{ $row['responses'] }} completed · {{ $row['violations'] }} violations</span>
                                                        </div>
                                                    </div>
                                                    <span class="pg-overall-exam-pct">{{ $completionPct }}%</span>
                                                </div>
                                                <div class="pg-overall-bar" aria-hidden="true">
                                                    <div class="pg-overall-bar-fill" style="width: {{ $completionPct }}%"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="pg-overall-block">
                                    <div class="pg-overall-block-head">
                                        <h3>Recent submissions</h3>
                                        <span>Latest activity</span>
                                    </div>
                                    @if($recentSubmissions->isEmpty())
                                        <div class="pg-overall-empty">No submissions yet.</div>
                                    @else
                                        <div class="pg-overall-activity-list">
                                            @foreach($recentSubmissions as $attempt)
                                                @php
                                                    $studentName = $attempt->student->name ?? 'Unknown';
                                                @endphp
                                                <div class="pg-overall-activity-row">
                                                    <div class="pg-overall-activity-info">
                                                        <span class="pg-overall-activity-name">{{ $studentName }}</span>
                                                        <span class="pg-overall-activity-meta">{{ $attempt->exam->title ?? 'Exam' }} · {{ $attempt->submitted_at->format('M d, g:i A') }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- VIEW: Proctoring --}}
            <div class="pg-view" id="view-live-sessions" data-view="live-sessions">
                <div id="proctoringView">
                    <div class="pg-proctoring" id="proctoringContent">
                        <div class="pg-proctoring-hero">
                            <h2>Proctoring</h2>
                            <p>Monitor students currently taking exams in real time.</p>
                        </div>

                        <div class="pg-proctoring-stats">
                            <div class="pg-stat-card">
                                <div class="num" id="proctoringStatActive">0</div>
                                <div class="lbl">Active sessions</div>
                            </div>
                            <div class="pg-stat-card">
                                <div class="num" id="proctoringStatInProgress">0</div>
                                <div class="lbl">In progress</div>
                            </div>
                            <div class="pg-stat-card">
                                <div class="num warn" id="proctoringStatAlerts">0</div>
                                <div class="lbl">With violations</div>
                            </div>
                        </div>

                        <div class="pg-proctoring-alert-banner" id="liveSessionsAlert" role="status"></div>

                        <div class="pg-table-wrap">
                            <div class="pg-proctoring-toolbar">
                                <input type="search" id="proctoringSearch" class="pg-filter-input pg-filter-search" placeholder="Search" autocomplete="off" aria-label="Search students or exams">
                                <span class="pg-filter-sep"></span>
                                <select id="proctoringStatusFilter" class="pg-filter-select" aria-label="Filter by status">
                                    <option value="">All statuses</option>
                                    <option value="in_progress">In progress</option>
                                    <option value="disconnected">Disconnected</option>
                                </select>
                                <button type="button" id="proctoringFilterClear" class="pg-filter-reset" aria-label="Clear filters">Reset</button>
                                <span class="pg-filter-spacer"></span>
                                <span class="pg-proctoring-count" id="proctoringVisibleCount">0 sessions</span>
                            </div>
                            <table class="pg-table" id="proctoringTable">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Exam</th>
                                        <th>Status</th>
                                        <th>Elapsed</th>
                                        <th>Remaining</th>
                                        <th>Violations</th>
                                    </tr>
                                </thead>
                                <tbody id="proctoringTableBody">
                                    <tr class="pg-proctoring-empty-row">
                                        <td colspan="6" class="pg-table-empty">No students are taking an exam right now.</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="pg-proctoring-no-results" id="proctoringNoResults">No sessions match the selected filters.</div>
                        </div>

                        <div class="pg-proctoring-detail hidden" id="proctoringSessionDetail">
                            <div class="pg-proctoring-detail-head">
                                <div>
                                    <h3 id="liveSessionDetailTitle">Session log</h3>
                                    <p class="pg-proctoring-detail-meta" id="liveSessionDetailMeta"></p>
                                </div>
                                <button type="button" class="pg-proctoring-detail-close" id="liveSessionDetailClose" aria-label="Close session detail">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                            <div class="pg-proctoring-detail-body" id="liveSessionDetailEvents"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- VIEW: Students with violations --}}
            <div class="pg-view" id="view-violations" data-view="violations">
                <div id="violationsView">
                    <div class="pg-violations">
                        <div class="pg-violations-hero">
                            <h2>Students with violations</h2>
                            <p>Review flagged attempts with severity levels and captured snapshots.</p>
                        </div>

                        <div class="pg-violations-stats">
                            <div class="pg-stat-card">
                                <div class="num warn" id="violationsStatStudents">{{ $violationRecords->count() }}</div>
                                <div class="lbl">Students flagged</div>
                            </div>
                            <div class="pg-stat-card">
                                <div class="num warn" id="violationsStatTotal">{{ $totalWarningCount }}</div>
                                <div class="lbl">Total violations</div>
                            </div>
                            <div class="pg-stat-card">
                                <div class="num" id="violationsStatExams">{{ $examsWithViolations }}</div>
                                <div class="lbl">Exams affected</div>
                            </div>
                        </div>

                        <div class="pg-table-wrap">
                            <div class="pg-violations-toolbar">
                                <input type="search" id="violationSearch" class="pg-filter-input pg-filter-search" placeholder="Search" autocomplete="off" aria-label="Search students or exams">
                                <span class="pg-filter-sep"></span>
                                <select id="violationExamFilter" class="pg-filter-select" aria-label="Filter by exam">
                                    <option value="">All exams</option>
                                    @foreach($examList->filter(fn ($e) => $e->attempts->contains(fn ($a) => ($a->warning_count ?? 0) > 0)) as $exam)
                                        <option value="{{ $exam->id }}">{{ $exam->title }}</option>
                                    @endforeach
                                </select>
                                <select id="violationSeverityFilter" class="pg-filter-select" aria-label="Filter by severity">
                                    <option value="">All severities</option>
                                    <option value="minor">Minor</option>
                                    <option value="moderate">Moderate</option>
                                    <option value="critical">Critical</option>
                                </select>
                                <button type="button" id="violationFilterClear" class="pg-filter-reset" aria-label="Clear filters">Reset</button>
                                <span class="pg-filter-spacer"></span>
                                <span class="pg-violations-count" id="violationVisibleCount">{{ $violationRecords->count() }} records</span>
                            </div>
                            <table class="pg-table" id="violationsTable">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Exam</th>
                                        <th>Severity summary</th>
                                        <th>Total time</th>
                                        <th>Submitted</th>
                                    </tr>
                                </thead>
                                <tbody id="violationsTableBody">
                                    <tr class="pg-violations-empty-row">
                                        <td colspan="5" class="pg-table-empty">No violations recorded yet.</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="pg-violations-no-results" id="violationNoResults">No records match the selected filters.</div>
                        </div>

                        <div class="pg-violations-detail hidden" id="violationDetailPanel">
                            <div class="pg-violations-detail-head">
                                <div>
                                    <h3 id="violationDetailTitle">Violation log</h3>
                                    <p class="pg-violations-detail-meta" id="violationDetailMeta"></p>
                                </div>
                                <button type="button" class="pg-violations-detail-close" id="violationDetailClose" aria-label="Close violation detail">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                            <div class="pg-violations-detail-body" id="violationDetailEvents"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- VIEW: Classes --}}
            <div class="pg-view" id="view-classes" data-view="classes">
                <div id="classesView">
                    @include('partials.workspace-header')
                    <div class="pg-classes-loading" id="classesLoading">Loading classes…</div>
                    <div id="classesContent" class="pg-table-wrap hidden">
                        <div class="pg-classes-toolbar">
                            <div class="pg-classes-toolbar-row">
                                <input type="search" id="classSearch" class="pg-filter-input pg-filter-search" placeholder="Search classes" autocomplete="off" aria-label="Search classes">
                                <span class="pg-filter-spacer"></span>
                                <span class="pg-classes-count" id="classVisibleCount">0 classes</span>
                            </div>
                            <div class="pg-classes-toolbar-row pg-classes-toolbar-tools">
                                <form id="createClassForm" class="pg-classes-tool-group">
                                    <span class="pg-classes-tool-label">Create</span>
                                    <input id="classNameInput" class="pg-classes-inline-input" type="text" placeholder="Class name" autocomplete="off" required>
                                    <input id="classSubjectInput" class="pg-classes-inline-input" type="text" placeholder="Subject" autocomplete="off" required>
                                    <button type="submit" class="pg-classes-btn pg-classes-btn-icon" title="Create class"><i class="ti ti-plus"></i></button>
                                </form>
                                <form id="assignExamForm" class="pg-classes-tool-group">
                                    <span class="pg-classes-tool-label">Assign</span>
                                    <select id="assignExamSelect" class="pg-classes-inline-select" aria-label="Select exam" required>
                                        <option value="">Exam</option>
                                    </select>
                                    <select id="assignClassSelect" class="pg-classes-inline-select" aria-label="Select class" required>
                                        <option value="">Class</option>
                                    </select>
                                    <button type="submit" id="assignExamBtn" class="pg-classes-btn pg-classes-btn-secondary pg-classes-btn-icon" disabled title="Assign exam"><i class="ti ti-link"></i></button>
                                </form>
                            </div>
                        </div>
                        <table class="pg-table" id="classesTable">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th>Code</th>
                                    <th>Students</th>
                                    <th>Exams</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="classesTableBody"></tbody>
                        </table>
                        <div class="pg-classes-no-results" id="classNoResults">No classes match your search.</div>
                    </div>
                </div>
            </div>

            {{-- VIEW: Create exam --}}
            <div class="pg-view" id="view-create-exam" data-view="create-exam">
                <div id="createExamView">
                    @include('partials.workspace-header')
                    @include('partials.create-exam-form', ['embedded' => true])
                </div>
            </div>

            {{-- VIEW: Profile --}}
            <div class="pg-view" id="view-profile" data-view="profile">
                <div class="pg-settings" style="max-width: 920px; margin: 0 auto; padding: 32px 24px;">
                    <header class="pg-settings-page-head">
                        <h1>Profile moved</h1>
                        <p class="pg-settings-page-sub">Your profile is now inside Settings.</p>
                    </header>
                    <button type="button" class="pg-settings-btn" data-view="settings" data-open-settings-section="profile">Go to Settings</button>
                </div>
            </div>

            {{-- VIEW: Settings --}}
            <div class="pg-view" id="view-settings" data-view="settings">
                @include('partials.professor-settings')
            </div>

            {{-- VIEW: Help --}}
            <div class="pg-view" id="view-help" data-view="help">
                @include('partials.professor-help')
            </div>

        </div>
    </div>
</div>

<div id="pgDialogRoot" class="pg-dialog-root hidden" aria-hidden="true">
    <div class="pg-dialog-backdrop" data-dialog-dismiss></div>
    <div class="pg-dialog" role="dialog" aria-modal="true" aria-labelledby="pgDialogTitle" aria-describedby="pgDialogMessage">
        <div id="pgDialogIcon" class="pg-dialog-icon is-info" aria-hidden="true">
            <i class="ti ti-info-circle"></i>
        </div>
        <h3 id="pgDialogTitle" class="pg-dialog-title"></h3>
        <p id="pgDialogMessage" class="pg-dialog-message"></p>
        <div class="pg-dialog-actions">
            <button type="button" id="pgDialogCancel" class="pg-dialog-btn pg-dialog-btn-cancel">Cancel</button>
            <button type="button" id="pgDialogConfirm" class="pg-dialog-btn pg-dialog-btn-confirm">OK</button>
        </div>
    </div>
</div>
<div id="pgToastRoot" class="pg-toast-root" aria-live="polite"></div>
@endsection

@push('scripts')
<script>
    window.ExamGuardProfessor = {
        user: @json($user->toAuthArray()),
        preferences: @json($user->preferencesWithDefaults()),
    };
</script>
<script src="/js/api-client.js?v=11"></script>
<script src="/js/professor-dialog.js?v=1"></script>
<script src="/js/settings-shared.js?v=2"></script>
<script src="/js/professor-settings.js?v=5"></script>
<script src="/js/professor-notifications.js?v=2"></script>
<script src="/js/professor-sidebar-live-widget.js?v=1"></script>
<script src="/js/create-exam.js?v=18"></script>
<script src="/js/professor-classes.js?v=4"></script>
<script src="/js/professor-exams.js?v=9"></script>
<script src="/js/professor-proctoring.js?v=2"></script>
<script src="/js/professor-live-sessions.js?v=4"></script>
<script src="/js/professor.js?v=48"></script>
@endpush
