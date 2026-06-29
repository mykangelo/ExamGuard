@php
    $prefs = $user->preferencesWithDefaults();
@endphp

<div id="settingsView" class="pg-settings">
    <div class="pg-settings-layout">
        <header class="pg-settings-page-head">
            <h1>Settings</h1>
            <p class="pg-settings-page-sub">Manage your profile, password, and preferences.</p>
        </header>

        <div id="settingsAlert" class="pg-settings-alert hidden" role="status" aria-live="polite"></div>

        <div class="sd-settings-shell">
            <nav class="sd-settings-nav" aria-label="Settings sections">
                <button type="button" class="sd-settings-nav-link active" data-pg-settings-section="profile">
                    <i class="ti ti-user"></i><span>Profile</span>
                </button>
                <button type="button" class="sd-settings-nav-link" data-pg-settings-section="password">
                    <i class="ti ti-lock"></i><span>Password</span>
                </button>
                <button type="button" class="sd-settings-nav-link" data-pg-settings-section="notifications">
                    <i class="ti ti-bell"></i><span>Notifications</span>
                </button>
                <button type="button" class="sd-settings-nav-link" data-pg-settings-section="workspace">
                    <i class="ti ti-adjustments"></i><span>Workspace</span>
                </button>
                <button type="button" class="sd-settings-nav-link sd-settings-nav-link-danger" data-pg-settings-section="danger">
                    <i class="ti ti-alert-triangle"></i><span>Danger zone</span>
                </button>
            </nav>

            <div class="sd-settings-content">
                <div class="pg-settings-grid sd-settings-grid-single" id="pgSettingsGrid">
                    <section class="pg-settings-card pg-settings-card-compact sd-settings-section" id="settingsProfileCard" data-pg-settings-section="profile">
                        <div class="pg-settings-card-head">
                            <i class="ti ti-user"></i>
                            <h3>Personal info</h3>
                        </div>
                        <form id="settingsProfileForm" class="pg-settings-form" novalidate>
                            <div class="pg-settings-form-error hidden" data-form-error role="alert"></div>

                            <div class="pg-settings-avatar-row">
                                <button type="button" class="pg-settings-avatar-btn" id="settingsAvatarBtn" aria-label="Upload profile photo">
                                    @if($user->avatar_path)
                                        <img src="{{ asset('storage/'.$user->avatar_path) }}" alt="">
                                    @else
                                        {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                                    @endif
                                </button>
                                <input type="file" id="settingsAvatarInput" accept="image/*" hidden>
                                <div class="pg-settings-avatar-hint">
                                    <strong>Profile photo</strong>
                                    Click to upload a new photo. JPG or PNG, max 2 MB.
                                </div>
                            </div>

                            <label class="pg-settings-field" data-field="name">
                                <span>Full name</span>
                                <input type="text" id="settingsName" name="name" value="{{ $user->name }}" autocomplete="name" required>
                                <span class="pg-settings-field-error hidden" data-field-error role="alert"></span>
                            </label>

                            <div class="pg-settings-email-row">
                                <label class="pg-settings-field" data-field="email">
                                    <span>Email address</span>
                                    <div class="pg-settings-email-wrap">
                                        <input type="email" id="settingsEmail" name="email" value="{{ $user->email }}" autocomplete="email" required>
                                        @if($user->hasVerifiedEmail())
                                            <span class="pg-settings-badge pg-settings-badge-ok"><i class="ti ti-circle-check"></i> Verified</span>
                                        @else
                                            <span class="pg-settings-badge pg-settings-badge-warn">Unverified</span>
                                        @endif
                                    </div>
                                    <span class="pg-settings-field-error hidden" data-field-error role="alert"></span>
                                </label>
                            </div>

                            <label class="pg-settings-field" data-field="department">
                                <span>Department</span>
                                <input type="text" id="settingsDepartment" name="department" value="{{ $prefs['department'] ?? '' }}" placeholder="e.g. Engineering" autocomplete="organization">
                            </label>

                            <dl class="pg-settings-account-grid">
                                <div class="pg-settings-account-item">
                                    <dt>Role</dt>
                                    <dd>{{ ucfirst($user->role) }}</dd>
                                </div>
                                <div class="pg-settings-account-item">
                                    <dt>Member since</dt>
                                    <dd id="settingsMemberSince">{{ $user->created_at?->format('M j, Y') }}</dd>
                                </div>
                                <div class="pg-settings-account-item">
                                    <dt>Email status</dt>
                                    <dd id="settingsVerifiedStatus">
                                        @if($user->hasVerifiedEmail())
                                            <span class="pg-settings-badge pg-settings-badge-ok">Verified</span>
                                        @else
                                            <span class="pg-settings-badge pg-settings-badge-warn">Unverified</span>
                                        @endif
                                    </dd>
                                </div>
                            </dl>

                            <button type="submit" class="pg-settings-btn" id="settingsProfileBtn">Save profile</button>
                        </form>
                    </section>

                    <section class="pg-settings-card sd-settings-section hidden" id="settingsPasswordCard" data-pg-settings-section="password">
                        <div class="pg-settings-card-head">
                            <i class="ti ti-lock"></i>
                            <h3>Password</h3>
                        </div>
                        <form id="settingsPasswordForm" class="pg-settings-form" novalidate>
                            <div class="pg-settings-form-error hidden" data-form-error role="alert"></div>

                            <label class="pg-settings-field" data-field="current_password">
                                <span>Current password</span>
                                <div class="pg-settings-pw-field">
                                    <input type="password" id="settingsCurrentPassword" name="current_password" autocomplete="current-password" required>
                                    <button type="button" class="pg-settings-pw-toggle" data-pw-toggle aria-label="Show password"><i class="ti ti-eye" aria-hidden="true"></i></button>
                                </div>
                                <span class="pg-settings-field-error hidden" data-field-error role="alert"></span>
                            </label>

                            <label class="pg-settings-field" data-field="password">
                                <span>New password</span>
                                <div class="pg-settings-pw-field">
                                    <input type="password" id="settingsNewPassword" name="password" autocomplete="new-password" minlength="8" required>
                                    <button type="button" class="pg-settings-pw-toggle" data-pw-toggle aria-label="Show password"><i class="ti ti-eye" aria-hidden="true"></i></button>
                                </div>
                                <div class="pg-settings-pw-strength" id="settingsPwStrength">
                                    <div class="pg-settings-pw-strength-bar"><div class="pg-settings-pw-strength-fill"></div></div>
                                    <span class="pg-settings-pw-strength-label">Enter a password</span>
                                </div>
                                <span class="pg-settings-field-error hidden" data-field-error role="alert"></span>
                            </label>

                            <label class="pg-settings-field" data-field="password_confirmation">
                                <span>Confirm new password</span>
                                <div class="pg-settings-pw-field">
                                    <input type="password" id="settingsConfirmPassword" name="password_confirmation" autocomplete="new-password" minlength="8" required>
                                    <button type="button" class="pg-settings-pw-toggle" data-pw-toggle aria-label="Show password"><i class="ti ti-eye" aria-hidden="true"></i></button>
                                </div>
                                <span class="pg-settings-field-error hidden" data-field-error role="alert"></span>
                            </label>

                            <button type="submit" class="pg-settings-btn" id="settingsPasswordBtn">Update password</button>
                        </form>
                    </section>
                    <section class="pg-settings-card sd-settings-section" id="settingsNotificationsCard" data-pg-settings-section="notifications">
                        <div class="pg-settings-card-head">
                            <i class="ti ti-bell"></i>
                            <h3>Notifications</h3>
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
                                    <strong>Violation recorded</strong>
                                    <small>When a student triggers a violation during a live session.</small>
                                </span>
                                <input type="checkbox" id="settingsEmailViolations" name="emailViolations" @checked($prefs['emailViolations'])>
                                <span class="pg-settings-toggle-ui" aria-hidden="true"></span>
                            </label>
                            <label class="pg-settings-toggle">
                                <span>
                                    <strong>Exam reminder</strong>
                                    <small>Remind you 30 minutes before a scheduled exam opens.</small>
                                </span>
                                <input type="checkbox" id="settingsEmailExamReminder" name="emailExamReminder" @checked($prefs['emailExamReminder'] ?? true)>
                                <span class="pg-settings-toggle-ui" aria-hidden="true"></span>
                            </label>
                            <p class="pg-settings-note">Notifications are sent via email and shown in-app.</p>
                            <button type="submit" class="pg-settings-btn" id="settingsNotificationsBtn">Save notifications</button>
                        </form>
                    </section>

                    <section class="pg-settings-card sd-settings-section hidden" id="settingsWorkspaceCard" data-pg-settings-section="workspace">
                        <div class="pg-settings-card-head">
                            <i class="ti ti-adjustments"></i>
                            <h3>Workspace defaults</h3>
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

                    <section class="pg-settings-card pg-settings-card-danger sd-settings-section hidden" id="settingsDangerCard" data-pg-settings-section="danger">
                        <div class="pg-settings-card-head">
                            <i class="ti ti-alert-triangle" style="color:#f87171"></i>
                            <h3>Danger zone</h3>
                        </div>
                        <p class="pg-settings-danger-copy">These actions affect your account security. Proceed with caution.</p>
                        <div class="pg-settings-danger-actions">
                            <button type="button" class="pg-settings-btn pg-settings-btn-outline" id="settingsLogoutAll">Log out of all devices</button>
                            <button type="button" class="pg-settings-btn pg-settings-btn-danger" id="settingsDeleteAccount">Delete account</button>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
