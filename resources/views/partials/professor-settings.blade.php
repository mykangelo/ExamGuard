@php
    $prefs = $user->preferencesWithDefaults();
@endphp

<div id="settingsView" class="pg-settings">
    <div class="pg-settings-layout">
        <header class="pg-settings-hero">
            <h2>Settings</h2>
            <p>Notifications and workspace defaults for your account.</p>
        </header>

        <div id="settingsAlert" class="pg-settings-alert hidden" role="status" aria-live="polite"></div>

        <div class="pg-settings-grid">
            <section class="pg-settings-card" id="settingsNotificationsCard">
                <div class="pg-settings-card-head">
                    <i class="ti ti-bell"></i>
                    <div>
                        <h3>Notifications</h3>
                    </div>
                </div>
                <form id="settingsNotificationsForm" class="pg-settings-form pg-settings-form-toggles">
                    <div class="pg-settings-form-error hidden" data-form-error role="alert"></div>
                    <label class="pg-settings-toggle">
                        <span>
                            <strong>Exam submissions</strong>
                            <small>When a student submits an exam.</small>
                        </span>
                        <input type="checkbox" id="settingsEmailExamSubmitted" name="emailExamSubmitted" @checked($prefs['emailExamSubmitted'])>
                        <span class="pg-settings-toggle-ui" aria-hidden="true"></span>
                    </label>
                    <label class="pg-settings-toggle">
                        <span>
                            <strong>Proctoring violations</strong>
                            <small>When a student triggers a warning.</small>
                        </span>
                        <input type="checkbox" id="settingsEmailViolations" name="emailViolations" @checked($prefs['emailViolations'])>
                        <span class="pg-settings-toggle-ui" aria-hidden="true"></span>
                    </label>
                    <button type="submit" class="pg-settings-btn" id="settingsNotificationsBtn">Save notifications</button>
                </form>
            </section>

            <section class="pg-settings-card" id="settingsWorkspaceCard">
                <div class="pg-settings-card-head">
                    <i class="ti ti-adjustments"></i>
                    <div>
                        <h3>Workspace defaults</h3>
                    </div>
                </div>
                <form id="settingsWorkspaceForm" class="pg-settings-form">
                    <div class="pg-settings-form-error hidden" data-form-error role="alert"></div>
                    <div class="pg-settings-row">
                        <label class="pg-settings-field" data-field="defaultTimeLimit">
                            <span>Time limit (min)</span>
                            <input type="number" id="settingsDefaultTimeLimit" name="defaultTimeLimit" min="1" max="480" value="{{ $prefs['defaultTimeLimit'] }}">
                            <span class="pg-settings-field-error hidden" data-field-error role="alert"></span>
                        </label>
                        <label class="pg-settings-field" data-field="defaultWarningLimit">
                            <span>Warnings</span>
                            <select id="settingsDefaultWarningLimit" name="defaultWarningLimit">
                                <option value="3" @selected($prefs['defaultWarningLimit'] === 3)>3 warnings</option>
                                <option value="5" @selected($prefs['defaultWarningLimit'] === 5)>5 warnings</option>
                            </select>
                            <span class="pg-settings-field-error hidden" data-field-error role="alert"></span>
                        </label>
                    </div>
                    <button type="submit" class="pg-settings-btn" id="settingsWorkspaceBtn">Save defaults</button>
                </form>
            </section>
        </div>
    </div>
</div>
