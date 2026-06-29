@php
    $prefs = $user->preferencesWithDefaults();
    $initials = strtoupper(substr($user->name ?? 'U', 0, 1));
@endphp

<div id="profileView" class="pg-settings">
    <div class="pg-settings-layout">
        <header class="pg-settings-page-head">
            <h1>Profile</h1>
            <p class="pg-settings-page-sub">Your name, email, password, and account details.</p>
        </header>

        <div id="profileAlert" class="pg-settings-alert hidden" role="status" aria-live="polite"></div>

        <div class="pg-settings-grid">
            <section class="pg-settings-card pg-settings-card-compact" id="settingsProfileCard">
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
                                {{ $initials }}
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

            <section class="pg-settings-card" id="settingsPasswordCard">
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

            <section class="pg-settings-card pg-settings-card-danger pg-settings-card-wide" id="settingsDangerCard">
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
