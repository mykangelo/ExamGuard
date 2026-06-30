<style>
/* Settings page — shared student & professor */
.pg-settings {
    width: 100%;
    max-width: 920px;
    margin: 0 auto;
    padding: 0 12px 32px;
}
.pg-settings-layout { display: flex; flex-direction: column; gap: 14px; }
.pg-settings-page-head { margin-bottom: 4px; text-align: left; }
.pg-settings-page-head h1,
.pg-settings-page-head h2 {
    margin: 0;
    font-size: 28px;
    font-weight: 700;
    color: #fff;
    line-height: 1.2;
}
.pg-settings-page-sub {
    margin: 6px 0 0;
    font-size: 14px;
    color: rgba(255,255,255,0.45);
    line-height: 1.45;
}
.pg-settings-stack { display: flex; flex-direction: column; gap: 14px; }
.pg-settings-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    align-items: start;
}
.pg-settings-card-wide { grid-column: 1 / -1; }
.pg-settings-card {
    background: #162444;
    border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    min-height: 0;
}
.pg-settings-card-compact { padding: 12px 14px; }
.pg-settings-card-compact .pg-settings-form { gap: 10px; }
.pg-settings-card-compact .pg-settings-avatar-row { gap: 12px; margin-bottom: 2px; }
.pg-settings-card-compact .pg-settings-avatar-btn { width: 56px; height: 56px; font-size: 20px; }
.pg-settings-card-compact .pg-settings-account-grid { padding: 10px; }

/* ── Student settings sub-nav (settings tab only) ── */
#sdSettingsView .sd-settings-shell,
#settingsView .sd-settings-shell {
    display: grid;
    grid-template-columns: 240px minmax(0, 1fr);
    gap: 16px;
    align-items: start;
}
#sdSettingsView .sd-settings-nav,
#settingsView .sd-settings-nav {
    position: sticky;
    top: 18px;
    align-self: start;
    padding: 6px 10px;
    border-right: 0.5px solid rgba(255,255,255,0.10);
}
#sdSettingsView .sd-settings-nav-link,
#settingsView .sd-settings-nav-link {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 10px;
    background: transparent;
    border: none;
    cursor: pointer;
    text-align: left;
    color: rgba(255,255,255,0.68);
    font-family: inherit;
    font-size: 13px;
    border-radius: 12px;
    transition: background 0.12s, color 0.12s;
}
#sdSettingsView .sd-settings-nav-link i,
#settingsView .sd-settings-nav-link i {
    font-size: 16px;
    width: 18px;
    display: inline-flex;
    justify-content: center;
    color: rgba(255,255,255,0.55);
}
#sdSettingsView .sd-settings-nav-link:hover,
#settingsView .sd-settings-nav-link:hover { background: rgba(255,255,255,0.05); color: #fff; }
#sdSettingsView .sd-settings-nav-link.active,
#settingsView .sd-settings-nav-link.active {
    background: rgba(255,255,255,0.06);
    color: #fff;
}
#sdSettingsView .sd-settings-nav-link.active i,
#settingsView .sd-settings-nav-link.active i { color: rgba(255,255,255,0.82); }
#sdSettingsView .sd-settings-nav-link-danger i,
#settingsView .sd-settings-nav-link-danger i { color: #f87171; }

#sdSettingsView .sd-settings-content,
#settingsView .sd-settings-content { min-width: 0; }
#sdSettingsView .sd-settings-section.hidden,
#settingsView .sd-settings-section.hidden { display: none !important; }
#sdSettingsView .pg-settings-grid.sd-settings-grid-single,
#settingsView .pg-settings-grid.sd-settings-grid-single { grid-template-columns: 1fr; }

@media (max-width: 900px) {
    #sdSettingsView .sd-settings-shell,
    #settingsView .sd-settings-shell { grid-template-columns: 1fr; }
    #sdSettingsView .sd-settings-nav,
    #settingsView .sd-settings-nav {
        position: static;
        border-right: none;
        border-bottom: 0.5px solid rgba(255,255,255,0.10);
        padding: 0 0 10px;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }
    #sdSettingsView .sd-settings-nav-link,
    #settingsView .sd-settings-nav-link { padding: 10px 10px; }
}
.pg-settings-card-danger {
    border-color: rgba(239,68,68,0.22);
    background: rgba(239,68,68,0.04);
}
.pg-settings-card-head {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}
.pg-settings-card-head i { font-size: 16px; color: #3b82f6; flex-shrink: 0; }
.pg-settings-card-head h3 { font-size: 14px; font-weight: 600; margin: 0; color: #fff; }
.pg-settings-form { display: flex; flex-direction: column; gap: 12px; }
.pg-settings-form-toggles { gap: 4px; }
.pg-settings-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.pg-settings-field { display: flex; flex-direction: column; gap: 5px; }
.pg-settings-field > span:first-child {
    font-size: 10px; font-weight: 600; letter-spacing: 0.08em;
    text-transform: uppercase; color: rgba(255,255,255,0.38);
}
.pg-settings-field input,
.pg-settings-field select {
    width: 100%; background: rgba(255,255,255,0.03);
    border: 0.5px solid rgba(255,255,255,0.10); border-radius: 8px;
    padding: 9px 11px; color: #fff; font-size: 13px;
    font-family: inherit; outline: none; color-scheme: dark;
}
.pg-settings-field input:focus,
.pg-settings-field select:focus {
    border-color: rgba(59,130,246,0.45); background: rgba(255,255,255,0.05);
}
.pg-settings-field.is-error input,
.pg-settings-field.is-error select {
    border-color: rgba(248,113,113,0.65);
    background: rgba(239,68,68,0.06);
}
.pg-settings-field-error {
    display: flex; align-items: flex-start; gap: 4px;
    font-size: 11px; color: #fca5a5; line-height: 1.35;
}
.pg-settings-field-error.hidden { display: none; }
.pg-settings-form-error {
    display: flex; align-items: flex-start; gap: 6px;
    padding: 8px 10px; border-radius: 8px; font-size: 11px; line-height: 1.4;
    background: rgba(239,68,68,0.10); color: #fca5a5;
    border: 0.5px solid rgba(239,68,68,0.22);
}
.pg-settings-form-error.hidden { display: none; }
.pg-settings-card.has-errors { border-color: rgba(239,68,68,0.28); }
.pg-settings-card.is-shake { animation: pgSettingsShake 0.42s cubic-bezier(0.36, 0.07, 0.19, 0.97); }
@keyframes pgSettingsShake {
    10%, 90% { transform: translateX(-1px); }
    20%, 80% { transform: translateX(2px); }
    30%, 50%, 70% { transform: translateX(-3px); }
    40%, 60% { transform: translateX(3px); }
}
.pg-settings-btn {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 160px; width: fit-content; max-width: 100%;
    padding: 9px 18px; border: none; border-radius: 50px;
    background: #3b82f6; color: #fff; font-size: 13px; font-weight: 600;
    font-family: inherit; cursor: pointer; transition: background 0.15s, opacity 0.15s;
}
.pg-settings-btn:hover:not(:disabled) { background: #2563eb; }
.pg-settings-btn:disabled { opacity: 0.55; cursor: not-allowed; }
.pg-settings-btn-outline {
    background: transparent; color: rgba(255,255,255,0.82);
    border: 0.5px solid rgba(255,255,255,0.18);
}
.pg-settings-btn-outline:hover:not(:disabled) { background: rgba(255,255,255,0.06); color: #fff; }
.pg-settings-btn-danger { background: #dc2626; }
.pg-settings-btn-danger:hover:not(:disabled) { background: #b91c1c; }
.pg-settings-alert {
    padding: 8px 12px; border-radius: 8px; font-size: 12px; line-height: 1.4;
    display: flex; align-items: flex-start; gap: 8px;
}
.pg-settings-alert.hidden { display: none; }
.pg-settings-alert.is-success {
    background: rgba(34,197,94,0.12); color: #86efac;
    border: 0.5px solid rgba(34,197,94,0.25);
}
.pg-settings-alert.is-error {
    background: rgba(239,68,68,0.12); color: #fca5a5;
    border: 0.5px solid rgba(239,68,68,0.25);
}
.pg-settings-avatar-row {
    display: flex; align-items: center; gap: 14px; margin-bottom: 4px;
}
.pg-settings-avatar-btn {
    position: relative; width: 64px; height: 64px; border-radius: 50%;
    border: 2px solid rgba(59,130,246,0.35); background: #1d4ed8;
    color: #fff; font-size: 22px; font-weight: 700; cursor: pointer;
    overflow: hidden; padding: 0; flex-shrink: 0;
}
.pg-settings-avatar-btn img {
    width: 100%; height: 100%; object-fit: cover; display: block;
}
.pg-settings-avatar-btn:hover { border-color: #3b82f6; }
.pg-settings-avatar-hint { font-size: 12px; color: rgba(255,255,255,0.42); line-height: 1.45; }
.pg-settings-avatar-hint strong { display: block; color: rgba(255,255,255,0.78); font-weight: 600; margin-bottom: 2px; }
.pg-settings-email-row { display: flex; flex-direction: column; gap: 6px; }
.pg-settings-email-wrap { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.pg-settings-email-wrap input { flex: 1; min-width: 0; }
.pg-settings-badge {
    display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px;
    border-radius: 50px; font-size: 11px; font-weight: 600; white-space: nowrap;
}
.pg-settings-badge-ok { background: rgba(34,197,94,0.15); color: #22c55e; }
.pg-settings-badge-warn { background: rgba(245,158,11,0.15); color: #f59e0b; }
.pg-settings-account-grid {
    display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px;
    padding: 12px; border-radius: 8px; background: rgba(255,255,255,0.03);
    border: 0.5px solid rgba(255,255,255,0.06);
}
.pg-settings-account-item { display: flex; flex-direction: column; gap: 4px; }
.pg-settings-account-item dt {
    font-size: 10px; font-weight: 600; letter-spacing: 0.08em;
    text-transform: uppercase; color: rgba(255,255,255,0.35); margin: 0;
}
.pg-settings-account-item dd { margin: 0; font-size: 13px; color: #fff; font-weight: 500; }
.pg-settings-toggle {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: 11px 0; border-bottom: 0.5px solid rgba(255,255,255,0.06); cursor: pointer;
}
.pg-settings-toggle:last-of-type { border-bottom: none; }
.pg-settings-toggle strong { display: block; font-size: 13px; font-weight: 600; color: #fff; }
.pg-settings-toggle small { display: block; font-size: 11px; color: rgba(255,255,255,0.38); margin-top: 2px; line-height: 1.35; }
.pg-settings-toggle input { position: absolute; opacity: 0; width: 0; height: 0; }
.pg-settings-toggle-ui {
    width: 38px; height: 22px; border-radius: 50px; flex-shrink: 0;
    background: rgba(255,255,255,0.12); position: relative; transition: background 0.15s;
}
.pg-settings-toggle-ui::after {
    content: ''; position: absolute; top: 3px; left: 3px; width: 16px; height: 16px;
    border-radius: 50%; background: #fff; transition: transform 0.15s;
}
.pg-settings-toggle input:checked + .pg-settings-toggle-ui { background: #3b82f6; }
.pg-settings-toggle input:checked + .pg-settings-toggle-ui::after { transform: translateX(16px); }
.pg-settings-note {
    margin: 4px 0 0; font-size: 11px; color: rgba(255,255,255,0.38); line-height: 1.45;
}
.pg-settings-pw-field { position: relative; }
.pg-settings-pw-field input { padding-right: 38px; }
.pg-settings-pw-toggle {
    position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
    background: none; border: none; color: rgba(255,255,255,0.40); cursor: pointer;
    padding: 4px; font-size: 16px; line-height: 1; z-index: 2;
}
.pg-settings-pw-toggle:hover { color: rgba(255,255,255,0.75); }
.pg-settings-pw-strength {
    display: flex; align-items: center; gap: 8px; font-size: 11px; color: rgba(255,255,255,0.42);
}
.pg-settings-pw-strength-bar {
    flex: 1; height: 4px; border-radius: 50px; background: rgba(255,255,255,0.08); overflow: hidden;
}
.pg-settings-pw-strength-fill { height: 100%; width: 0; border-radius: 50px; transition: width 0.2s, background 0.2s; }
.pg-settings-pw-strength.is-weak .pg-settings-pw-strength-fill { width: 33%; background: #ef4444; }
.pg-settings-pw-strength.is-weak .pg-settings-pw-strength-label { color: #f87171; }
.pg-settings-pw-strength.is-fair .pg-settings-pw-strength-fill { width: 66%; background: #f59e0b; }
.pg-settings-pw-strength.is-fair .pg-settings-pw-strength-label { color: #fbbf24; }
.pg-settings-pw-strength.is-strong .pg-settings-pw-strength-fill { width: 100%; background: #22c55e; }
.pg-settings-pw-strength.is-strong .pg-settings-pw-strength-label { color: #4ade80; }
.pg-settings-danger-title { margin: 0 0 6px; font-size: 13px; font-weight: 600; color: #fca5a5; }
.pg-settings-danger-copy { margin: 0 0 12px; font-size: 12px; color: rgba(255,255,255,0.42); line-height: 1.45; }
.pg-settings-danger-actions { display: flex; flex-wrap: wrap; gap: 10px; }
#sd-view-settings.active,
#view-settings.active,
#view-profile.active {
    display: block !important;
    padding: 32px 24px 44px;
    flex: none;
    width: 100%;
    overflow-y: auto;
}

/* Extra top padding for Settings hub (professor + student) */
#sd-view-settings.active,
#view-settings.active {
    padding-top: 92px;
}
.pg-body:has(#view-settings.active),
.pg-body:has(#view-profile.active) {
    display: block !important;
    padding: 0 !important;
    justify-content: flex-start !important;
    align-items: stretch !important;
}
#view-settings.active,
#view-profile.active {
    display: block !important;
    flex-direction: column;
    justify-content: flex-start;
    align-items: stretch;
}
@media (max-width: 768px) {
    .pg-settings-grid { grid-template-columns: 1fr; }
    .pg-settings-row,
    .pg-settings-account-grid { grid-template-columns: 1fr; }
    #sd-view-settings.active,
    #view-settings.active,
    #view-profile.active { padding: 28px 16px 40px; }
}
</style>
