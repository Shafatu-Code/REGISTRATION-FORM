/* Student Registration - client-side behaviour.
 * Client checks give instant feedback only. register.php repeats EVERY rule,
 * because browser code can be bypassed (see README section 6).
 * Set CLIENT_VALIDATION to false to send raw input to the server and watch
 * the PHP validation work on its own (useful for the demonstration). */
(() => {
    'use strict';

    const CLIENT_VALIDATION = true;

    const form = document.getElementById('registration-form');
    const alertBox = document.getElementById('form-alert');
    const submitBtn = document.getElementById('submit-btn');
    const tokenInput = document.getElementById('csrf_token');
    const dobInput = document.getElementById('date_of_birth');

    const FIELDS = ['full_name', 'email', 'phone', 'matric_number', 'date_of_birth',
                    'department', 'level', 'gender', 'password', 'confirm_password',
                    'confirm_accuracy'];

    const todayISO = () => new Date(Date.now() - new Date().getTimezoneOffset() * 60000)
        .toISOString().slice(0, 10);
    dobInput.max = todayISO();

    /* ---------- CSRF token (created by PHP, kept in the PHP session) ---------- */
    async function loadToken() {
        try {
            const res = await fetch('register.php?token=1', {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            tokenInput.value = data.token || '';
        } catch (err) {
            showAlert('Cannot reach the server. Make sure Apache and MySQL are running in XAMPP.', false);
        }
    }

    /* ---------- UI helpers ---------- */
    function showAlert(message, ok) {
        alertBox.textContent = message;
        alertBox.className = 'alert ' + (ok ? 'alert-success' : 'alert-error');
        alertBox.hidden = false;
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function clearErrors() {
        alertBox.hidden = true;
        form.querySelectorAll('[data-error-for]').forEach((p) => { p.textContent = ''; p.hidden = true; });
        form.querySelectorAll('[aria-invalid]').forEach((el) => el.removeAttribute('aria-invalid'));
    }

    function showErrors(errors) {
        let first = null;
        FIELDS.forEach((name) => {
            if (!errors[name]) { return; }
            const p = form.querySelector('[data-error-for="' + name + '"]');
            p.textContent = errors[name];
            p.hidden = false;
            const inputs = form.querySelectorAll('[name="' + name + '"]');
            inputs.forEach((el) => el.setAttribute('aria-invalid', 'true'));
            if (!first) { first = inputs[0]; }
        });
        if (first) { first.focus(); }
    }

    /* ---------- Client-side validation (mirrors register.php) ---------- */
    function validate(v) {
        const err = {};
        const name = v.full_name.replace(/\s+/g, ' ').trim();

        if (!name) err.full_name = 'Full name is required.';
        else if (name.length < 2 || name.length > 100) err.full_name = 'Full name must be between 2 and 100 characters.';
        else if (!/^\p{L}[\p{L} '.\-]*$/u.test(name)) err.full_name = 'Use letters, spaces, hyphens, apostrophes or dots only.';

        const email = v.email.trim();
        if (!email) err.email = 'Email address is required.';
        else if (email.length > 150 || !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) err.email = 'Enter a valid email address, e.g. student@example.com.';

        const phone = v.phone.replace(/[\s\-().]/g, '');
        if (!v.phone.trim()) err.phone = 'Phone number is required.';
        else if (!/^(?:\+237|00237|237)?6\d{8}$/.test(phone)) err.phone = 'Enter a valid phone number: 9 digits starting with 6, e.g. 6XXXXXXXX.';

        const matric = v.matric_number.trim().toUpperCase();
        const mm = matric.match(/^[A-Z]{2,4}(20\d{2})\d{3}$/);
        if (!matric) err.matric_number = 'Matriculation number is required.';
        else if (!mm) err.matric_number = 'Format: 2-4 letters, a 4-digit year, then 3 digits, e.g. SE2026001.';
        else if (Number(mm[1]) > new Date().getFullYear()) err.matric_number = 'The year in the matriculation number cannot be in the future.';

        const dob = v.date_of_birth.trim();
        if (!dob) err.date_of_birth = 'Date of birth is required.';
        else {
            const d = new Date(dob + 'T00:00:00Z');
            if (isNaN(d) || d.toISOString().slice(0, 10) !== dob) err.date_of_birth = 'Enter a valid date (DD / MM / YYYY).';
            else if (dob > todayISO()) err.date_of_birth = 'Date of birth cannot be in the future.';
            else if (dob < '1900-01-01') err.date_of_birth = 'Date of birth is not realistic. Check the year.';
        }

        if (!v.department) err.department = 'Select a department.';
        if (!v.level) err.level = 'Select a level.';
        if (!v.gender) err.gender = 'Select a gender option.';

        if (!v.password) err.password = 'Password is required.';
        else if (v.password.length < 8) err.password = 'Password must be at least 8 characters.';
        else if (v.password.length > 72) err.password = 'Password must not exceed 72 characters.';
        else if (!/\p{L}/u.test(v.password) || !/\d/.test(v.password)) err.password = 'Password must contain at least one letter and one number.';

        if (!v.confirm_password) err.confirm_password = 'Please confirm your password.';
        else if (v.password !== v.confirm_password) err.confirm_password = 'Passwords do not match.';

        if (!v.confirm_accuracy) err.confirm_accuracy = 'You must confirm that the information is accurate.';
        return err;
    }

    function readValues() {
        const data = new FormData(form);
        return {
            full_name: String(data.get('full_name') || ''),
            email: String(data.get('email') || ''),
            phone: String(data.get('phone') || ''),
            matric_number: String(data.get('matric_number') || ''),
            date_of_birth: String(data.get('date_of_birth') || ''),
            department: String(data.get('department') || ''),
            level: String(data.get('level') || ''),
            gender: String(data.get('gender') || ''),
            password: String(data.get('password') || ''),
            confirm_password: String(data.get('confirm_password') || ''),
            confirm_accuracy: data.get('confirm_accuracy') === '1'
        };
    }

    /* ---------- Submit (HTTP POST to register.php) ---------- */
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();

        if (CLIENT_VALIDATION) {
            const errors = validate(readValues());
            if (Object.keys(errors).length) {
                showErrors(errors);
                showAlert('Please correct the highlighted fields and try again.', false);
                return;
            }
        }

        submitBtn.disabled = true;
        try {
            const res = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();

            if (data.success) {
                form.reset();
                showAlert(data.message, true);
            } else {
                showErrors(data.errors || {});
                showAlert(data.message, false);
            }
            if (data.success || res.status === 403) { await loadToken(); }
        } catch (err) {
            showAlert('Sorry, the server could not be reached. Please try again later.', false);
        } finally {
            submitBtn.disabled = false;
        }
    });

    // clear a field's error as soon as the user edits it
    form.addEventListener('input', (e) => {
        const name = e.target.name;
        const p = form.querySelector('[data-error-for="' + name + '"]');
        if (p) { p.textContent = ''; p.hidden = true; }
        form.querySelectorAll('[name="' + name + '"]').forEach((el) => el.removeAttribute('aria-invalid'));
    });

    loadToken();
})();
