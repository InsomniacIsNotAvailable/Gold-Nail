document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('appointmentForm');

    // Hide all error messages on load
    hideAllErrors();

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Reset previous errors and borders
        hideAllErrors();
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));

        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const phoneInput = document.getElementById('phone');
        const datetimeSelect = document.getElementById('appointment_datetime');
        const purposeSelect = document.getElementById('purpose');

        // Sanitize phone: remove all non-digits (spaces, dashes, etc.)
        const sanitizedPhone = (phoneInput.value || '').replace(/\D+/g, '');
        phoneInput.value = sanitizedPhone;

        let hasError = false;

        // Client-side validation
        if (!nameInput.value.trim()) {
            showError('appointment-name', 'Name is required.');
            nameInput.classList.add('input-error');
            hasError = true;
        }

        if (!emailInput.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
            showError('appointment-email', 'Enter a valid email address.');
            emailInput.classList.add('input-error');
            hasError = true;
        }

        if (!sanitizedPhone || !/^[0-9]{11}$/.test(sanitizedPhone)) {
            showError('appointment-phone', 'Enter an 11-digit phone number.');
            phoneInput.classList.add('input-error');
            hasError = true;
        }

        if (!datetimeSelect.value) {
            showError('appointment-datetime', 'Please select a date and time.');
            datetimeSelect.classList.add('input-error');
            hasError = true;
        }

        if (!purposeSelect.value) {
            showError('appointment-purpose', 'Please select a purpose.');
            purposeSelect.classList.add('input-error');
            hasError = true;
        }

        if (hasError) return;

        // Submit to backend
        const formData = new FormData(form);
        formData.set('phone', sanitizedPhone);

        try {
            const response = await fetch(form.action, { method: 'POST', body: formData });
            const result = await response.json();

            if (result.status === 'error') {
                const targetId = result.field || 'appointment-general';
                showError(targetId, result.message || 'There was a problem with your submission.');
            } else if (result.status === 'success') {
                // No alert() -> no "localhost says"
                showSuccess(result.message || 'Appointment booked.');
                await delay(1500); // wait 1.5s
                window.location.href = 'Homepage.php';
            } else {
                showError('appointment-general', 'Unexpected error. Please try again.');
            }
        } catch {
            showError('appointment-general', 'Unexpected error. Please try again.');
        }
    });

    function hideAllErrors() {
        document.querySelectorAll('.error-message').forEach(div => {
            div.classList.remove('is-visible');
            div.textContent = '';
        });
    }

    function showError(divId, message) {
        const errorDiv = document.getElementById(divId);
        if (!errorDiv) return;
        errorDiv.textContent = message;
        errorDiv.classList.add('is-visible');
        // Optional: ensure error bg (red) if it was used for success before
        errorDiv.style.backgroundColor = '#c0392b';
    }

    function showSuccess(message) {
        const general = document.getElementById('appointment-general');
        if (!general) return;
        general.textContent = message;
        general.classList.add('is-visible');
        // Simple green success styling without adding new CSS
        general.style.backgroundColor = '#27ae60';
    }

    const delay = (ms) => new Promise(res => setTimeout(res, ms));
});
