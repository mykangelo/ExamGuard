@php
    $embedded = $embedded ?? false;
@endphp

<div class="{{ $embedded ? 'pg-create-exam pg-create-doc' : 'space-y-8' }}" id="createExamRoot">
    @unless($embedded)
        <div>
            <div class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-300">Professor Interface</div>
            <h1 class="mt-2 text-4xl font-bold">Create a new exam.</h1>
            <p class="mt-3 text-slate-300">Enter exam details, add questions, and save when ready.</p>
        </div>

        <section class="eg-panel space-y-4">
            <h3 class="text-xl font-semibold">Exam details</h3>
            <input id="examTitleInput" class="eg-input" placeholder="Midterm Examination">
            <textarea id="instructionsInput" class="eg-input min-h-28" placeholder="Enter instructions for students"></textarea>
            <div class="grid gap-4 md:grid-cols-3">
                <input id="timeLimitInput" class="eg-input" type="number" min="1" placeholder="Time limit (minutes)">
                <select id="warningLimitInput" class="eg-input">
                    <option value="3">3 warnings</option>
                    <option value="5">5 warnings</option>
                </select>
                <select id="examClassInput" class="eg-input"><option value="">Save without assigning</option></select>
            </div>
        </section>

        <section class="eg-panel space-y-4">
            <h3 class="text-xl font-semibold">Add question</h3>
            <textarea id="questionInput" class="eg-input min-h-24" placeholder="Enter your question"></textarea>
            <div class="grid gap-4 md:grid-cols-2">
                <input id="choiceA" class="eg-input" placeholder="Choice A">
                <input id="choiceB" class="eg-input" placeholder="Choice B">
                <input id="choiceC" class="eg-input" placeholder="Choice C">
                <input id="choiceD" class="eg-input" placeholder="Choice D">
            </div>
            <select id="correctAnswerInput" class="eg-input">
                <option value="">Select correct answer</option>
                <option value="0">Choice A</option>
                <option value="1">Choice B</option>
                <option value="2">Choice C</option>
                <option value="3">Choice D</option>
            </select>
            <textarea id="explanationInput" class="eg-input min-h-24" placeholder="Explain why the answer is correct"></textarea>
            <div class="pg-create-actions">
                <button id="addQuestionBtn" class="eg-btn-primary" type="button">Add question</button>
                <button id="clearExamBtn" class="eg-btn-secondary" type="button">Clear form</button>
            </div>
        </section>

        <section class="eg-panel">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-xl font-semibold">Current exam preview</h3>
                <span id="questionCount" class="eg-badge-warning">No questions added yet.</span>
            </div>
            <div id="questionPreview" class="space-y-4"></div>
            <button id="saveExamBtn" class="eg-btn-primary mt-6" type="button">Save exam</button>
        </section>

        <section class="eg-panel">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-semibold">Saved Exams</h2>
                <button id="deleteAllExamsBtn" class="eg-btn-secondary" type="button">Delete All Exams</button>
            </div>
            <div id="savedExams" class="space-y-4"></div>
        </section>
    @else
        {{-- Phase 1: Exam setup --}}
        <div id="createSetupPhase" class="pg-create-phase pg-create-phase-setup">
            <div class="pg-create-setup-screen">
                <div class="pg-create-setup-card">
                    <div class="pg-create-setup-form">
                        <label class="pg-create-setup-field-wrap" data-field="title">
                            <span class="pg-create-setup-label">Exam title</span>
                            <input id="examTitleInput" class="pg-create-setup-field" placeholder="Midterm Examination" autocomplete="off">
                            <span class="pg-create-field-error hidden" data-field-error></span>
                        </label>
                        <label class="pg-create-setup-field-wrap" data-field="instructions">
                            <span class="pg-create-setup-label">Instructions</span>
                            <textarea id="instructionsInput" class="pg-create-setup-field pg-create-setup-notes" placeholder="Instructions for students" rows="2"></textarea>
                            <span class="pg-create-field-error hidden" data-field-error></span>
                        </label>
                        <div class="pg-create-setup-row pg-create-setup-row-thirds">
                            <label class="pg-create-setup-field-wrap" data-field="timeLimit">
                                <span class="pg-create-setup-label">Time limit</span>
                                <div class="pg-create-setup-control-wrap">
                                    <input id="timeLimitInput" class="pg-create-setup-control" type="number" min="1" placeholder="60" inputmode="numeric">
                                    <span class="pg-create-setup-suffix" aria-hidden="true">min</span>
                                </div>
                                <span class="pg-create-field-error hidden" data-field-error></span>
                            </label>
                            <label class="pg-create-setup-field-wrap">
                                <span class="pg-create-setup-label">Warnings</span>
                                <div class="pg-create-setup-select-wrap">
                                    <select id="warningLimitInput" class="pg-create-setup-control pg-create-setup-select" aria-label="Warning limit">
                                        <option value="3">3 warnings</option>
                                        <option value="5">5 warnings</option>
                                    </select>
                                    <i class="ti ti-chevron-down" aria-hidden="true"></i>
                                </div>
                            </label>
                            <label class="pg-create-setup-field-wrap">
                                <span class="pg-create-setup-label">Class</span>
                                <div class="pg-create-setup-select-wrap">
                                    <select id="examClassInput" class="pg-create-setup-control pg-create-setup-select" aria-label="Assign to class">
                                        <option value="">None</option>
                                    </select>
                                    <i class="ti ti-chevron-down" aria-hidden="true"></i>
                                </div>
                            </label>
                        </div>
                        <div class="pg-create-setup-schedule">
                            <div class="pg-create-schedule-head">
                                <span class="pg-create-setup-label">Availability</span>
                                <span class="pg-create-schedule-badge">Optional</span>
                            </div>
                            <div class="pg-create-schedule-grid">
                                <div class="pg-create-schedule-line" data-field="opensAt">
                                    <span class="pg-create-schedule-tag"><i class="ti ti-door-enter" aria-hidden="true"></i> Opens</span>
                                    <div class="pg-create-schedule-control-wrap">
                                        <input id="examOpensDateInput" class="pg-create-setup-control pg-create-schedule-date" type="date" aria-label="Opens date">
                                        <i class="ti ti-calendar" aria-hidden="true"></i>
                                    </div>
                                    <div class="pg-create-schedule-control-wrap">
                                        <input id="examOpensTimeInput" class="pg-create-setup-control pg-create-schedule-time" type="time" step="300" aria-label="Opens time" disabled>
                                        <i class="ti ti-clock" aria-hidden="true"></i>
                                    </div>
                                    <span class="pg-create-field-error hidden" data-field-error></span>
                                </div>
                                <div class="pg-create-schedule-line" data-field="closesAt">
                                    <span class="pg-create-schedule-tag"><i class="ti ti-door-exit" aria-hidden="true"></i> Closes</span>
                                    <div class="pg-create-schedule-control-wrap">
                                        <input id="examClosesDateInput" class="pg-create-setup-control pg-create-schedule-date" type="date" aria-label="Closes date">
                                        <i class="ti ti-calendar" aria-hidden="true"></i>
                                    </div>
                                    <div class="pg-create-schedule-control-wrap">
                                        <input id="examClosesTimeInput" class="pg-create-setup-control pg-create-schedule-time" type="time" step="300" aria-label="Closes time" disabled>
                                        <i class="ti ti-clock" aria-hidden="true"></i>
                                    </div>
                                    <span class="pg-create-field-error hidden" data-field-error></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pg-create-setup-footer">
                    <p id="setupPhaseHint" class="pg-create-setup-hint">Enter a title, instructions, and time limit to continue.</p>
                    <button type="button" id="continueToBuilderBtn" class="pg-create-btn pg-create-btn-primary pg-create-setup-continue">
                        Create <i class="ti ti-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Phase 2: Quiz builder --}}
        <div id="createBuilderPhase" class="pg-create-phase pg-create-phase-builder hidden">
            <aside class="pg-create-sidebar">
                <div class="pg-create-setup-actions">
                    <button type="button" id="editSetupBtn" class="pg-create-edit-setup">
                        <i class="ti ti-pencil"></i> Edit setup
                    </button>
                </div>

                <div class="pg-create-qlist">
                    <div class="pg-create-qlist-head">
                        <span>Questions</span>
                        <span id="questionCount" class="pg-create-qlist-count">0</span>
                    </div>
                    <ul id="questionStack" class="pg-create-qlist-items">
                        <li class="pg-create-qlist-empty">No questions yet</li>
                    </ul>
                    <button type="button" id="newQuestionBtn" class="pg-create-qlist-add">
                        <i class="ti ti-plus"></i> New question
                    </button>
                </div>

                <div class="pg-create-sidebar-foot">
                    <p id="createSaveHint" class="pg-create-save-hint">Add at least one question to save or publish.</p>
                    <div class="pg-create-sidebar-actions">
                        <button type="button" id="saveDraftBtn" class="pg-create-btn pg-create-btn-secondary pg-create-btn-save">
                            <i class="ti ti-device-floppy"></i> Save draft
                        </button>
                        <button type="button" id="publishExamBtn" class="pg-create-btn pg-create-btn-primary pg-create-btn-save">
                            <i class="ti ti-send"></i> Publish
                        </button>
                    </div>
                </div>
            </aside>

            <div class="pg-quiz-panel">
                <div class="pg-quiz-toolbar" role="toolbar" aria-label="Quiz editor">
                    <h2 id="quizToolbarTitle" class="pg-quiz-toolbar-title">Untitled exam</h2>
                    <span id="quizToolbarCount" class="pg-quiz-toolbar-count">0 questions</span>
                </div>
                <div class="pg-quiz-scroll">
                    <article class="pg-quiz-sheet" id="examPaper">
                        <div id="docQuestions" class="pg-quiz-body"></div>
                        <footer class="pg-quiz-draft" id="docDraft" aria-label="New question draft"></footer>
                        <button type="button" id="addQuestionBtn" class="pg-quiz-add-btn">
                            <i class="ti ti-plus"></i> Add question
                        </button>
                    </article>
                </div>
            </div>
        </div>
        {{-- Phase 3: Published success --}}
        <div id="createSuccessPhase" class="pg-create-phase pg-create-phase-success hidden">
            <div class="pg-create-success-screen">
                <div class="pg-create-success-badge" aria-hidden="true">
                    <span class="pg-create-success-ring"></span>
                    <span class="pg-create-success-check"><i class="ti ti-check"></i></span>
                </div>
                <h2 class="pg-create-success-title">Congratulations!</h2>
                <p class="pg-create-success-lead" id="publishSuccessLead">Your exam is published and ready for students.</p>
                <div class="pg-create-success-card">
                    <p class="pg-create-success-exam" id="publishSuccessTitle">Untitled exam</p>
                    <p class="pg-create-success-meta" id="publishSuccessMeta">0 questions</p>
                    <div class="pg-create-success-key hidden" id="publishSuccessKeyWrap">
                        <span class="pg-create-success-key-label">Exam key for students</span>
                        <div class="pg-create-success-key-row">
                            <code id="publishSuccessKey">--------</code>
                            <button type="button" id="copyPublishKeyBtn" class="pg-create-success-key-copy" aria-label="Copy exam key">
                                <i class="ti ti-copy"></i> Copy
                            </button>
                        </div>
                        <p class="pg-create-success-key-hint">Students can enter this key on their dashboard to access the exam.</p>
                    </div>
                </div>
                <div class="pg-create-success-actions">
                    <button type="button" id="viewPublishedExamsBtn" class="pg-create-btn pg-create-btn-primary">
                        View exams <i class="ti ti-arrow-right"></i>
                    </button>
                    <button type="button" id="createAnotherExamBtn" class="pg-create-btn pg-create-btn-ghost">
                        Create another
                    </button>
                </div>
            </div>
        </div>

        <div id="publishOverlay" class="pg-create-publish-overlay hidden" aria-live="polite" aria-busy="true">
            <div class="pg-create-publish-panel">
                <div class="pg-create-publish-spinner" aria-hidden="true">
                    <span class="pg-create-publish-ring"></span>
                    <span class="pg-create-publish-ring pg-create-publish-ring-delay"></span>
                    <i class="ti ti-send"></i>
                </div>
                <p class="pg-create-publish-label" id="publishOverlayLabel">Publishing your exam…</p>
            </div>
        </div>
    @endunless
</div>
