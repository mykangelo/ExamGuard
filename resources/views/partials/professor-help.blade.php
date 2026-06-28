@php
    $helpFaqs = [
        [
            'How do I publish an exam?',
            'Finish the exam setup, add your questions, then click Publish in the quiz builder. Only published exams are visible to students.',
        ],
        [
            'How do students join my class?',
            'Create a class from the Classes tab, then share the join code with students. They enter it on their dashboard to enroll.',
        ],
        [
            'Where do I see violations?',
            'Open Students with violations in the sidebar, or check the notification bell when a student triggers a proctoring warning.',
        ],
        [
            'Can I edit a published exam?',
            'Published exams cannot be edited. Duplicate the exam or create a new version if you need changes after publishing.',
        ],
    ];
@endphp

<div id="helpView" class="pg-settings pg-help">
    <div class="pg-settings-layout">
        <header class="pg-settings-hero">
            <h2>Help & Support</h2>
            <p>Guides, quick answers, and ways to reach our team.</p>
        </header>

        <div class="pg-settings-grid">
            <section class="pg-settings-card">
                <div class="pg-settings-card-head">
                    <i class="ti ti-book"></i>
                    <div>
                        <h3>Guides & resources</h3>
                    </div>
                </div>
                <div class="pg-help-links">
                    <a href="/tour" class="pg-help-link" target="_blank" rel="noopener">
                        <span class="pg-help-link-icon"><i class="ti ti-route"></i></span>
                        <span class="pg-help-link-body">
                            <strong>Product tour</strong>
                            <small>Walk through ExamGuard features step by step.</small>
                        </span>
                        <i class="ti ti-external-link pg-help-link-arrow" aria-hidden="true"></i>
                    </a>
                    <a href="/faq" class="pg-help-link" target="_blank" rel="noopener">
                        <span class="pg-help-link-icon"><i class="ti ti-help"></i></span>
                        <span class="pg-help-link-body">
                            <strong>FAQ</strong>
                            <small>Answers about exams, monitoring, classes, and privacy.</small>
                        </span>
                        <i class="ti ti-external-link pg-help-link-arrow" aria-hidden="true"></i>
                    </a>
                    <a href="/contact" class="pg-help-link" target="_blank" rel="noopener">
                        <span class="pg-help-link-icon"><i class="ti ti-mail"></i></span>
                        <span class="pg-help-link-body">
                            <strong>Contact support</strong>
                            <small>Send a message to the ExamGuard team.</small>
                        </span>
                        <i class="ti ti-external-link pg-help-link-arrow" aria-hidden="true"></i>
                    </a>
                </div>
            </section>

            <section class="pg-settings-card">
                <div class="pg-settings-card-head">
                    <i class="ti ti-list-check"></i>
                    <div>
                        <h3>Professor workflow</h3>
                    </div>
                </div>
                <ol class="pg-help-steps">
                    <li>
                        <button type="button" class="pg-help-step-btn" data-switch-view="classes">
                            <span class="pg-help-step-num">1</span>
                            <span class="pg-help-step-text">Create a class and share the join code</span>
                            <i class="ti ti-chevron-right" aria-hidden="true"></i>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="pg-help-step-btn" data-switch-view="create-exam">
                            <span class="pg-help-step-num">2</span>
                            <span class="pg-help-step-text">Build an exam and add questions</span>
                            <i class="ti ti-chevron-right" aria-hidden="true"></i>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="pg-help-step-btn" data-switch-view="exams">
                            <span class="pg-help-step-num">3</span>
                            <span class="pg-help-step-text">Publish the exam and assign it to a class</span>
                            <i class="ti ti-chevron-right" aria-hidden="true"></i>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="pg-help-step-btn" data-switch-view="violations">
                            <span class="pg-help-step-num">4</span>
                            <span class="pg-help-step-text">Review submissions and violations</span>
                            <i class="ti ti-chevron-right" aria-hidden="true"></i>
                        </button>
                    </li>
                </ol>
            </section>

            <section class="pg-settings-card pg-settings-card-wide">
                <div class="pg-settings-card-head">
                    <i class="ti ti-message-circle"></i>
                    <div>
                        <h3>Common questions</h3>
                    </div>
                </div>
                <div class="pg-help-faq">
                    @foreach($helpFaqs as [$question, $answer])
                        <details class="pg-help-faq-item">
                            <summary>
                                <span>{{ $question }}</span>
                                <i class="ti ti-chevron-down pg-help-faq-chevron" aria-hidden="true"></i>
                            </summary>
                            <p>{{ $answer }}</p>
                        </details>
                    @endforeach
                </div>
            </section>

            <section class="pg-settings-card pg-settings-card-muted pg-settings-card-wide">
                <div class="pg-settings-card-head">
                    <i class="ti ti-headset"></i>
                    <div>
                        <h3>Need more help?</h3>
                    </div>
                </div>
                <div class="pg-help-contact">
                    <p>Our team typically responds within one business day. Include your institution, class size, and a short description of the issue.</p>
                    <div class="pg-help-contact-actions">
                        <a href="/contact" class="pg-settings-btn" target="_blank" rel="noopener">
                            Contact support <i class="ti ti-arrow-right"></i>
                        </a>
                        <a href="mailto:support@examguard.app" class="pg-help-contact-email">support@examguard.app</a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
