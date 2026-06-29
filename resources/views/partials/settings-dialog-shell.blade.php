<style>
.pg-dialog-root {
    position: fixed; inset: 0; z-index: 300;
    display: flex; align-items: center; justify-content: center; padding: 24px;
}
.pg-dialog-root.hidden { display: none !important; }
.pg-dialog-backdrop { position: absolute; inset: 0; background: rgba(8, 15, 30, 0.72); backdrop-filter: blur(4px); }
.pg-dialog {
    position: relative; width: 100%; max-width: 400px; background: #162444;
    border: 0.5px solid rgba(255,255,255,0.12); border-radius: 14px;
    padding: 22px 22px 18px; box-shadow: 0 24px 48px rgba(0,0,0,0.45);
}
.pg-dialog-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 14px; font-size: 20px;
}
.pg-dialog-icon.is-danger { background: rgba(239,68,68,0.14); color: #f87171; }
.pg-dialog-icon.is-warning { background: rgba(245,158,11,0.14); color: #fbbf24; }
.pg-dialog-icon.is-info { background: rgba(59,130,246,0.14); color: #93c5fd; }
.pg-dialog-title { margin: 0 0 8px; font-size: 16px; font-weight: 600; color: #fff; }
.pg-dialog-message { margin: 0 0 20px; font-size: 13px; line-height: 1.55; color: rgba(255,255,255,0.58); }
.pg-dialog-actions { display: flex; justify-content: flex-end; gap: 8px; }
.pg-dialog-btn {
    padding: 8px 16px; border-radius: 8px; border: none;
    font-size: 12px; font-weight: 600; font-family: inherit; cursor: pointer;
}
.pg-dialog-btn.hidden { display: none; }
.pg-dialog-btn-cancel {
    background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.75);
    border: 0.5px solid rgba(255,255,255,0.10);
}
.pg-dialog-btn-confirm { background: #3b82f6; color: #fff; }
.pg-dialog-btn-confirm.is-danger { background: #dc2626; }
.pg-toast-root {
    position: fixed; right: 20px; bottom: 20px; z-index: 310;
    display: flex; flex-direction: column; gap: 8px; pointer-events: none;
}
.pg-toast {
    display: flex; align-items: center; gap: 10px;
    min-width: 240px; max-width: 360px; padding: 12px 14px;
    background: #162444; border: 0.5px solid rgba(255,255,255,0.12);
    border-radius: 10px; box-shadow: 0 12px 32px rgba(0,0,0,0.35);
    font-size: 12px; color: rgba(255,255,255,0.88); pointer-events: auto;
}
.pg-toast i { font-size: 16px; flex-shrink: 0; }
.pg-toast.is-success i { color: #4ade80; }
.pg-toast.is-error i { color: #f87171; }
.pg-toast.is-info i { color: #93c5fd; }
</style>

<div id="pgDialogRoot" class="pg-dialog-root hidden" aria-hidden="true">
    <div class="pg-dialog-backdrop" data-dialog-dismiss></div>
    <div class="pg-dialog" role="dialog" aria-modal="true" aria-labelledby="pgDialogTitle">
        <div id="pgDialogIcon" class="pg-dialog-icon is-info" aria-hidden="true"><i class="ti ti-info-circle"></i></div>
        <h3 id="pgDialogTitle" class="pg-dialog-title"></h3>
        <p id="pgDialogMessage" class="pg-dialog-message"></p>
        <div class="pg-dialog-actions">
            <button type="button" id="pgDialogCancel" class="pg-dialog-btn pg-dialog-btn-cancel">Cancel</button>
            <button type="button" id="pgDialogConfirm" class="pg-dialog-btn pg-dialog-btn-confirm">OK</button>
        </div>
    </div>
</div>
<div id="pgToastRoot" class="pg-toast-root" aria-live="polite"></div>
