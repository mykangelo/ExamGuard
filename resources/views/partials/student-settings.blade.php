@php
    $prefs = $user->preferencesWithDefaults();
    $initials = strtoupper(substr($user->name ?? 'U', 0, 1));
@endphp

<div id="sdSettingsView" class="pg-settings">
    <div class="pg-settings-layout">
        <header class="pg-settings-page-head">
            <h1>Settings</h1>
            <p class="pg-settings-page-sub">Manage your profile, password, and preferences.</p>
        </header>

        <div id="sdSettingsAlert" class="pg-settings-alert hidden" role="status" aria-live="polite"></div>

        <div class="sd-settings-shell">
            <nav class="sd-settings-nav" aria-label="Settings sections">
                <button type="button" class="sd-settings-nav-link active" data-sd-settings-section="profile">
                    <i class="ti ti-user"></i><span>Profile</span>
                </button>
                <button type="button" class="sd-settings-nav-link" data-sd-settings-section="password">
                    <i class="ti ti-lock"></i><span>Password</span>
                </button>
                <button type="button" class="sd-settings-nav-link" data-sd-settings-section="notifications">
                    <i class="ti ti-bell"></i><span>Notifications</span>
                </button>
                <button type="button" class="sd-settings-nav-link sd-settings-nav-link-danger" data-sd-settings-section="danger">
                    <i class="ti ti-alert-triangle"></i><span>Danger zone</span>
                </button>
            </nav>

            <div class="sd-settings-content">
                <div class="pg-settings-grid sd-settings-grid-single" id="sdSettingsGrid">
            <section class="pg-settings-card pg-settings-card-compact sd-settings-section" id="sdSettingsProfileCard" data-sd-settings-section="profile">
                <div class="pg-settings-card-head">
                    <i class="ti ti-user"></i>
                    <h3>Profile</h3>
                </div>
                <form id="sdSettingsProfileForm" class="pg-settings-form" novalidate>
                    <div class="pg-settings-form-error hidden" data-form-error role="alert"></div>

                    <div class="pg-settings-avatar-row">
                        <button type="button" class="pg-settings-avatar-btn" id="sdSettingsAvatarBtn" aria-label="Upload profile photo">
                            @if($user->avatar_path)
                                <img src="{{ asset('storage/'.$user->avatar_path) }}" alt="">
                            @else
                                {{ $initials }}
                            @endif
                        </button>
                        <input type="file" id="sdSettingsAvatarInput" accept="image/*" hidden>
                        <div class="pg-settings-avatar-hint">
                            <strong>Profile photo</strong>
                            Click to upload a new photo. JPG or PNG, max 2 MB.
                        </div>
                    </div>

                    <label class="pg-settings-field" data-field="name">
                        <span>Full name</span>
                        <input type="text" id="sdSettingsName" name="name" value="{{ $user->name }}" autocomplete="name" required>
                        <span class="pg-settings-field-error hidden" data-field-error role="alert"></span>
                    </label>

                    <div class="pg-settings-email-row">
                        <label class="pg-settings-field" data-field="email">
                            <span>Email</span>
                            <div class="pg-settings-email-wrap">
                                <input type="email" id="sdSettingsEmail" name="email" value="{{ $user->email }}" autocomplete="email" required>
                                @if($user->hasVerifiedEmail())
                                    <span class="pg-settings-badge pg-settings-badge-ok"><i class="ti ti-circle-check"></i> Verified</span>
                                @else
                                    <span class="pg-settings-badge pg-settings-badge-warn">Unverified</span>
                                @endif
                            </div>
                            <span class="pg-settings-field-error hidden" data-field-error role="alert"></span>
                        </label>
                    </div>

                    <div class="pg-settings-row">
                        <label class="pg-settings-field" data-field="department">
                            <span>Department</span>
                            <input type="text" id="sdSettingsDepartment" name="department" value="{{ $prefs['department'] ?? '' }}" placeholder="e.g. Computer Science" autocomplete="organization">
                        </label>
                        <label class="pg-settings-field" data-field="yearLevel">
                            <span>Year level</span>
                            <input type="text" id="sdSettingsYearLevel" name="yearLevel" value="{{ $prefs['yearLevel'] ?? '' }}" placeholder="e.g. 2nd year">
                        </label>
                    </div>

                    <label class="pg-settings-field" data-field="studentId">
                        <span>Student ID</span>
                        <input type="text" id="sdSettingsStudentId" name="studentId" value="{{ $prefs['studentId'] ?? '' }}" placeholder="Optional">
                    </label>

                    <dl class="pg-settings-account-grid">
                        <div class="pg-settings-account-item">
                            <dt>Role</dt>
                            <dd>Student</dd>
                        </div>
                        <div class="pg-settings-account-item">
                            <dt>Member since</dt>
                            <dd id="sdSettingsMemberSince">{{ $user->created_at?->format('M j, Y') }}</dd>
                        </div>
                        <div class="pg-settings-account-item">
                            <dt>Email status</dt>
                            <dd id="sdSettingsVerifiedStatus">
                                @if($user->hasVerifiedEmail())
                                    <span class="pg-settings-badge pg-settings-badge-ok">Verified</span>
                                @else
                                    <span class="pg-settings-badge pg-settings-badge-warn">Unverified</span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    <button type="submit" class="pg-settings-btn" id="sdSettingsProfileBtn">Save profile</button>
                </form>
            </section>

            <section class="pg-settings-card sd-settings-section hidden" id="sdSettingsPasswordCard" data-sd-settings-section="password">
                <div class="pg-settings-card-head">
                    <i class="ti ti-lock"></i>
                    <h3>Password</h3>
                </div>
                <form id="sdSettingsPasswordForm" class="pg-settings-form" novalidate>
                    <div class="pg-settings-form-error hidden" data-form-error role="alert"></div>

                    <label class="pg-settings-field" data-field="current_password">
                        <span>Current password</span>
                        <div class="pg-settings-pw-field">
                            <input type="password" id="sdSettingsCurrentPassword" name="current_password" autocomplete="current-password" required>
                            <button type="button" class="pg-settings-pw-toggle" data-pw-toggle aria-label="Show password"><i class="ti ti-eye" aria-hidden="true"></i></button>
                        </div>
                        <span class="pg-settings-field-error hidden" data-field-error role="alert"></span>
                    </label>

                    <label class="pg-settings-field" data-field="password">
                        <span>New password</span>
                        <div class="pg-settings-pw-field">
                            <input type="password" id="sdSettingsNewPassword" name="password" autocomplete="new-password" minlength="8" required>
                            <button type="button" class="pg-settings-pw-toggle" data-pw-toggle aria-label="Show password"><i class="ti ti-eye" aria-hidden="true"></i></button>
                        </div>
                        <div class="pg-settings-pw-strength" id="sdSettingsPwStrength">
                            <div class="pg-settings-pw-strength-bar"><div class="pg-settings-pw-strength-fill"></div></div>
                            <span class="pg-settings-pw-strength-label">Enter a password</span>
                        </div>
                        <span class="pg-settings-field-error hidden" data-field-error role="alert"></span>
                    </label>

                    <label class="pg-settings-field" data-field="password_confirmation">
                        <span>Confirm new password</span>
                        <div class="pg-settings-pw-field">
                            <input type="password" id="sdSettingsConfirmPassword" name="password_confirmation" autocomplete="new-password" minlength="8" required>
                            <button type="button" class="pg-settings-pw-toggle" data-pw-toggle aria-label="Show password"><i class="ti ti-eye" aria-hidden="true"></i></button>
                        </div>
                        <span class="pg-settings-field-error hidden" data-field-error role="alert"></span>
                    </label>

                    <button type="submit" class="pg-settings-btn" id="sdSettingsPasswordBtn">Update password</button>
                </form>
            </section>

            <section class="pg-settings-card sd-settings-section hidden" id="sdSettingsNotificationsCard" data-sd-settings-section="notifications">
                <div class="pg-settings-card-head">
                    <i class="ti ti-bell"></i>
                    <h3>Notifications</h3>
                </div>
                <form id="sdSettingsNotificationsForm" class="pg-settings-form pg-settings-form-toggles">
                    <div class="pg-settings-form-error hidden" data-form-error role="alert"></div>
                    <label class="pg-settings-toggle">
                        <span>
                            <strong>New exam assignments</strong>
                            <small>When a professor assigns an exam to your class.</small>
                        </span>
                        <input type="checkbox" id="sdSettingsEmailExamAssigned" name="emailExamAssigned" @checked($prefs['emailExamAssigned'] ?? true)>
                        <span class="pg-settings-toggle-ui" aria-hidden="true"></span>
                    </label>
                    <label class="pg-settings-toggle">
                        <span>
                            <strong>Class &amp; exam updates</strong>
                            <small>When a class or assigned exam is removed.</small>
                        </span>
                        <input type="checkbox" id="sdSettingsEmailClassUpdates" name="emailClassUpdates" @checked($prefs['emailClassUpdates'] ?? true)>
                        <span class="pg-settings-toggle-ui" aria-hidden="true"></span>
                    </label>
                    <label class="pg-settings-toggle">
                        <span>
                            <strong>Exam reminder</strong>
                            <small>Notify you 30 minutes before a scheduled exam starts.</small>
                        </span>
                        <input type="checkbox" id="sdSettingsEmailExamReminder" name="emailExamReminder" @checked($prefs['emailExamReminder'] ?? true)>
                        <span class="pg-settings-toggle-ui" aria-hidden="true"></span>
                    </label>
                    <label class="pg-settings-toggle">
                        <span>
                            <strong>Exam results available</strong>
                            <small>When your exam results are ready to view.</small>
                        </span>
                        <input type="checkbox" id="sdSettingsEmailExamResults" name="emailExamResults" @checked($prefs['emailExamResults'] ?? true)>
                        <span class="pg-settings-toggle-ui" aria-hidden="true"></span>
                    </label>
                    <p class="pg-settings-note">Notifications are sent via email and shown in-app.</p>
                    <button type="submit" class="pg-settings-btn" id="sdSettingsNotificationsBtn">Save notifications</button>
                </form>
            </section>

            <section class="pg-settings-card pg-settings-card-danger sd-settings-section hidden" id="sdSettingsDangerCard" data-sd-settings-section="danger">
                <div class="pg-settings-card-head">
                    <i class="ti ti-alert-triangle" style="color:#f87171"></i>
                    <h3>Danger zone</h3>
                </div>
                <p class="pg-settings-danger-copy">These actions affect your account security. Proceed with caution.</p>
                <div class="pg-settings-danger-actions">
                    <button type="button" class="pg-settings-btn pg-settings-btn-outline" id="sdSettingsLogoutAll">Log out of all devices</button>
                    <button type="button" class="pg-settings-btn pg-settings-btn-danger" id="sdSettingsDeleteAccount">Delete account</button>
                </div>
            </section>
                </div>
            </div>
        </div>
    </div>
</div>
