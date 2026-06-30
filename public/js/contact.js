(function () {
    'use strict';

    var form = document.getElementById('contactForm');
    if (!form) return;

    var nameInput = document.getElementById('contactName');
    var emailInput = document.getElementById('contactEmail');
    var subjectInput = document.getElementById('contactSubject');
    var messageInput = document.getElementById('contactMessage');
    var submitBtn = document.getElementById('contactSubmitBtn');
    var submitText = document.getElementById('contactSubmitText');
    var spinner = document.getElementById('contactSpinner');
    var errorBanner = document.getElementById('contactError');
    var successBanner = document.getElementById('contactSuccess');
    var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function showError(message) {
        if (!errorBanner) return;
        errorBanner.querySelector('span').textContent = message;
        errorBanner.classList.remove('hidden');
        if (successBanner) successBanner.classList.add('hidden');
    }

    function showSuccess(message) {
        if (!successBanner) return;
        successBanner.querySelector('span').textContent = message;
        successBanner.classList.remove('hidden');
        if (errorBanner) errorBanner.classList.add('hidden');
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (errorBanner) errorBanner.classList.add('hidden');
        if (successBanner) successBanner.classList.add('hidden');

        var name = nameInput.value.trim();
        var email = emailInput.value.trim();
        var subject = subjectInput.value.trim();
        var message = messageInput.value.trim();

        if (!name) return showError('Please enter your name.');
        if (!email || !emailRe.test(email)) return showError('Please enter a valid email address.');
        if (!subject) return showError('Please select a subject.');
        if (!message) return showError('Please enter a message.');

        submitBtn.disabled = true;
        if (submitText) submitText.textContent = 'Sending…';
        if (spinner) spinner.classList.remove('hidden');

        try {
            var hp = document.getElementById('hp_contact');
            var result = await ExamGuardApi.contact({
                name: name,
                email: email,
                subject: subject,
                message: message,
                website: hp ? hp.value : '',
            });
            showSuccess(result.message || 'Thank you! We received your message.');
            form.reset();
        } catch (err) {
            showError(err.message || 'Unable to send your message. Please try again.');
        } finally {
            submitBtn.disabled = false;
            if (submitText) submitText.textContent = 'Send message';
            if (spinner) spinner.classList.add('hidden');
        }
    });
})();
