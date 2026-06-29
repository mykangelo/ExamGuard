(function () {
  'use strict';

  const POLL_MS = 4000;
  const HEARTBEAT_LIVE_WINDOW_MS = 18 * 1000;
  const mount = document.getElementById('pgSidebarLiveWidget');
  if (!mount) return;

  let timer = null;
  let running = false;

  function esc(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function parseIso(iso) {
    const t = iso ? Date.parse(iso) : NaN;
    return Number.isFinite(t) ? t : 0;
  }

  function pickMostRecentExam(sessions) {
    const byExam = new Map();
    sessions.forEach((s) => {
      const key = String(s.examId ?? '');
      if (!key) return;
      const existing = byExam.get(key);
      const started = parseIso(s.startedAt);
      const last = parseIso(s.lastHeartbeatAt);
      const score = Math.max(started, last);
      if (!existing) {
        byExam.set(key, { examId: s.examId, examTitle: s.examTitle, className: s.className, score, count: 1 });
        return;
      }
      existing.count += 1;
      if (score > existing.score) {
        existing.score = score;
        existing.examTitle = s.examTitle;
        existing.className = s.className;
      }
    });

    const list = Array.from(byExam.values()).sort((a, b) => b.score - a.score);
    return { primary: list[0] ?? null, extraCount: Math.max(0, list.length - 1) };
  }

  function hide() {
    mount.classList.add('hidden');
    mount.innerHTML = '';
  }

  function show({ primary, extraCount }) {
    if (!primary) return hide();
    mount.classList.remove('hidden');

    const extra = extraCount > 0
      ? `<button type="button" class="pg-live-widget-more" data-live-widget-more>+ ${extraCount} more active</button>`
      : '';

    mount.innerHTML = `
      <div class="pg-live-widget-card">
        <div class="pg-live-widget-head">
          <span class="pg-live-widget-dot" aria-hidden="true"></span>
          <span class="pg-live-widget-label">LIVE NOW</span>
        </div>
        <div class="pg-live-widget-title" title="${esc(primary.examTitle)}">${esc(primary.examTitle)}</div>
        <div class="pg-live-widget-sub">${esc(primary.className || 'Class')}</div>
        <div class="pg-live-widget-meta">${primary.count} student${primary.count === 1 ? '' : 's'} active</div>
        <button type="button" class="pg-live-widget-btn" data-live-widget-monitor>Monitor</button>
      </div>
      ${extra}
    `;

    const go = () => window.ExamGuardWorkspace?.switchView?.('live-sessions');
    mount.querySelector('[data-live-widget-monitor]')?.addEventListener('click', go);
    mount.querySelector('[data-live-widget-more]')?.addEventListener('click', go);
  }

  async function refresh() {
    if (!running) return;
    try {
      const payload = await window.ExamGuardApi.liveSessions();
      const now = Date.now();
      const sessions = (payload.sessions || []).filter((s) => {
        const last = parseIso(s.lastHeartbeatAt);
        // Treat "live now" as recent heartbeats only (otherwise the widget can stick around after exit).
        return s.status === 'in_progress' && last && (now - last) <= HEARTBEAT_LIVE_WINDOW_MS;
      });
      if (!sessions.length) {
        hide();
        return;
      }
      show(pickMostRecentExam(sessions));
    } catch (_) {
      // Fail closed (don't show stale "live" badge)
      hide();
    }
  }

  function start() {
    if (running) return;
    running = true;
    refresh();
    timer = setInterval(refresh, POLL_MS);
  }

  function stop() {
    running = false;
    if (timer) clearInterval(timer);
    timer = null;
    hide();
  }

  start();
  window.ExamGuardSidebarLiveWidget = { start, stop, refresh };
})();

