/**
 * Reusable password creation UX: a live strength meter plus a requirement
 * checklist that ticks off as you type, added under any password field
 * marked data-password-strength — so every "create/change a password"
 * screen gives the same real-time feedback instead of a bare input with
 * no signal until a validation error comes back after submitting.
 *
 * Deliberately non-invasive: it does NOT move, wrap, or restyle the
 * input itself (several of these forms already have their own
 * Bootstrap .input-group markup with a working show/hide button) — it
 * only appends the meter/checklist right after the input's containing
 * field wrapper.
 */
(function () {
    const RULES = [
        { test: (v) => v.length >= 8, label: 'At least 8 characters' },
        { test: (v) => /[a-z]/.test(v), label: 'A lowercase letter' },
        { test: (v) => /[A-Z]/.test(v), label: 'An uppercase letter' },
        { test: (v) => /[0-9]/.test(v), label: 'A number' },
        { test: (v) => /[^A-Za-z0-9]/.test(v), label: 'A symbol (e.g. ! @ # $)' },
    ];

    function scoreOf(value) {
        return RULES.filter((r) => r.test(value)).length;
    }

    function strengthLabel(score) {
        if (score <= 1) return { text: 'Very weak', tone: 'rg-pw-weak' };
        if (score === 2) return { text: 'Weak', tone: 'rg-pw-weak' };
        if (score === 3) return { text: 'Okay', tone: 'rg-pw-okay' };
        if (score === 4) return { text: 'Strong', tone: 'rg-pw-strong' };
        return { text: 'Very strong', tone: 'rg-pw-strong' };
    }

    function build(input) {
        // Anchor after the field's own wrapper (e.g. the .mb-3 div holding
        // the label + input-group), not after the bare <input>, so this
        // renders below the whole field instead of splitting it apart.
        const anchor = input.closest('.mb-3, .mb-4, .form-group') || input.parentElement;

        const meterWrap = document.createElement('div');
        meterWrap.className = 'rg-pw-meter-wrap mt-2';
        meterWrap.innerHTML = `
            <div class="rg-pw-meter" role="progressbar" aria-label="Password requirements" aria-valuemin="0" aria-valuemax="${RULES.length}" aria-valuenow="0"><div class="rg-pw-meter-fill"></div></div>
            <span class="rg-pw-meter-label"></span>
        `;

        const checklist = document.createElement('ul');
        checklist.className = 'rg-pw-checklist';
        checklist.innerHTML = RULES.map((r, i) => `
            <li data-rule="${i}"><i class="bi bi-circle"></i><span>${r.label}</span></li>
        `).join('');

        anchor.insertAdjacentElement('afterend', checklist);
        anchor.insertAdjacentElement('afterend', meterWrap);

        const fill = meterWrap.querySelector('.rg-pw-meter-fill');
        const label = meterWrap.querySelector('.rg-pw-meter-label');
        const meter = meterWrap.querySelector('.rg-pw-meter');

        function refresh() {
            const value = input.value;
            const score = scoreOf(value);
            const { text, tone } = strengthLabel(score);
            const valid = score === RULES.length;

            fill.className = 'rg-pw-meter-fill ' + tone;
            fill.style.width = value ? `${(score / RULES.length) * 100}%` : '0%';
            label.textContent = value ? `${text} · ${score}/${RULES.length} requirements` : `0/${RULES.length} requirements`;
            label.className = 'rg-pw-meter-label ' + (value ? tone : '');
            meter.setAttribute('aria-valuenow', String(score));
            input.setCustomValidity(valid || !value ? '' : 'Meet all five password requirements before continuing.');

            RULES.forEach((r, i) => {
                const li = checklist.querySelector(`li[data-rule="${i}"]`);
                const met = r.test(value);
                li.classList.toggle('rg-pw-met', met);
                li.querySelector('i').className = met ? 'bi bi-check-circle-fill' : 'bi bi-circle';
            });
        }

        input.addEventListener('input', refresh);
        refresh();

        const form = input.form;
        if (form && !form.dataset.passwordRulesBound) {
            form.dataset.passwordRulesBound = '1';
            form.addEventListener('submit', function (event) {
                const invalidStrengthInput = [...form.querySelectorAll('input[data-password-strength]')]
                    .find((passwordInput) => {
                        if (passwordInput.hasAttribute('data-password-optional') && !passwordInput.value) return false;
                        return scoreOf(passwordInput.value) !== RULES.length;
                    });
                const invalidConfirmation = [...form.querySelectorAll('input[data-password-confirm-of]')]
                    .find((confirmationInput) => {
                        const source = document.getElementById(confirmationInput.getAttribute('data-password-confirm-of'));
                        return source && confirmationInput.value !== source.value;
                    });

                if (invalidStrengthInput || invalidConfirmation) {
                    event.preventDefault();
                    (invalidStrengthInput || invalidConfirmation).reportValidity();
                    const submitButton = form.querySelector('[data-password-submit]');
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.removeAttribute('aria-busy');
                        submitButton.textContent = submitButton.dataset.submitText || 'Update Password';
                    }
                    delete form.dataset.rgSubmitting;
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.addEventListener('click', function () {
                const input = document.getElementById(button.getAttribute('aria-controls'));
                if (!input) return;

                const showing = input.type === 'password';
                input.type = showing ? 'text' : 'password';
                button.setAttribute('aria-pressed', showing ? 'true' : 'false');
                button.setAttribute('aria-label', `${showing ? 'Hide' : 'Show'} ${button.dataset.passwordLabel || 'password'}`);
                const icon = button.querySelector('i');
                if (icon) icon.className = showing ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        });

        document.querySelectorAll('input[data-password-strength]').forEach(build);

        // Live "passwords match" feedback next to a confirmation field, when
        // one exists on the page — separate from the strength meter above
        // since it depends on a second field.
        document.querySelectorAll('input[data-password-confirm-of]').forEach((confirmInput) => {
            const sourceId = confirmInput.getAttribute('data-password-confirm-of');
            const source = document.getElementById(sourceId);
            if (!source) return;

            const anchor = confirmInput.closest('.mb-3, .mb-4, .form-group') || confirmInput.parentElement;
            const matchEl = document.createElement('div');
            matchEl.className = 'rg-pw-match mt-1';
            anchor.insertAdjacentElement('afterend', matchEl);

            function refreshMatch() {
                if (!confirmInput.value) {
                    matchEl.textContent = '';
                    confirmInput.setCustomValidity(source.value ? 'Enter your password again to confirm it.' : '');
                    return;
                }
                const matches = confirmInput.value === source.value;
                matchEl.textContent = matches ? 'Passwords match' : "Passwords don't match yet";
                matchEl.className = 'rg-pw-match mt-1 ' + (matches ? 'rg-pw-strong' : 'rg-pw-weak');
                confirmInput.setCustomValidity(matches ? '' : 'The password confirmation does not match.');
            }

            confirmInput.addEventListener('input', refreshMatch);
            source.addEventListener('input', refreshMatch);
            refreshMatch();
        });
    });
})();
