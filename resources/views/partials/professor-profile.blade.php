<div id="profileView" class="pg-settings">
    <div class="pg-settings-layout">
        <header class="pg-settings-hero">
            <h2>Profile</h2>
            <p>Your name, email, password, and account details.</p>
        </header>

        <div id="profileAlert" class="pg-settings-alert hidden" role="status" aria-live="polite"></div>

        <div class="pg-settings-grid">
            <section class="pg-settings-card" id="settingsProfileCard">
                <div class="pg-settings-card-head">
                    <i class="ti ti-user"></i>
                    <div>
                        <h3>Personal info</h3>
                    </div>
                </div>
                <form id="settingsProfileForm" class="pg-settings-form" novalidate>
                    <div class="pg-settings-form-error hidden" data-form-error role="alert"></div>
                    <div class="pg-settings-row">
                        <label class="pg-settings-field" data-field="name">
                            <span>Full name</span>
                            <input type="text" id="settingsName" name="name" value="{{ $user->name }}" autocomplete="name" required>
                            <span class="pg-settings-field-error hidden" data-field-error role="alert"></span>
                        </label>
                        <label class="pg-settings-field" data-field="email">
                            <span>Email address</span>
                            <input type="email" id="settingsEmail" name="email" value="{{ $user->email }}" autocomplete="email" required>
                            <span class="pg-settings-field-error hidden" data-field-error role="alert"></span>
                        </label>
                    </div>
                    <p class="pg-settings-hint" id="settingsEmailHint">
                        @if($user->hasVerifiedEmail())
                            Your email is verified.
                        @else
                            Verify your email to unlock all features.
                        @endif
                    </p>
                    <button type="submit" class="pg-settings-btn" id="settingsProfileBtn">Save profile</button>
                </form>
            </section>

            <section class="pg-settings-card" id="settingsPasswordCard">
                <div class="pg-settings-card-head">
                    <i class="ti ti-lock"></i>
                    <div>
                        <h3>Password</h3>
                    </div>
                </div>
                <form id="settingsPasswordForm" class="pg-settings-form" novalidate>
                    <div class="pg-settings-form-error hidden" data-form-error role="alert"></div>
                    <div class="pg-settings-row pg-settings-row-3">
                        <label class="pg-settings-field" data-field="current_password">
                            <span>Current</span>
                            <input type="password" id="settingsCurrentPassword" name="current_password" autocomplete="current-password" required>
                            <span class="pg-settings-field-error hidden" data-field-error role="alert"></span>
                        </label>
                        <label class="pg-settings-field" data-field="password">
                            <span>New</span>
                            <input type="password" id="settingsNewPassword" name="password" autocomplete="new-password" minlength="8" required>
                            <span class="pg-settings-field-error hidden" data-field-error role="alert"></span>
                        </label>
                        <label class="pg-settings-field" data-field="password_confirmation">
                            <span>Confirm</span>
                            <input type="password" id="settingsConfirmPassword" name="password_confirmation" autocomplete="new-password" minlength="8" required>
                            <span class="pg-settings-field-error hidden" data-field-error role="alert"></span>
                        </label>
                    </div>
                    <button type="submit" class="pg-settings-btn" id="settingsPasswordBtn">Update password</button>
                </form>
            </section>

            <section class="pg-settings-card pg-settings-card-muted pg-settings-card-wide">
                <div class="pg-settings-card-head">
                    <i class="ti ti-id"></i>
                    <div>
                        <h3>Account</h3>
                    </div>
                </div>
                <dl class="pg-settings-meta">
                    <div>
                        <dt>Role</dt>
                        <dd>{{ ucfirst($user->role) }}</dd>
                    </div>
                    <div>
                        <dt>Member since</dt>
                        <dd id="settingsMemberSince">{{ $user->created_at?->format('M j, Y') }}</dd>
                    </div>
                    <div>
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
            </section>
        </div>
    </div>
</div>
