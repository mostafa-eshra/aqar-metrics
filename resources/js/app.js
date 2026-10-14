import { bootstrap } from '@tabler/core';

window.bootstrap = bootstrap;

const preferredTheme = () => localStorage.getItem('aqar-theme') ?? 'auto';

const resolveTheme = theme => theme === 'auto'
    ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
    : theme;

const applyTheme = theme => {
    document.documentElement.dataset.bsTheme = resolveTheme(theme);
    document.documentElement.dataset.themePreference = theme;
    localStorage.setItem('aqar-theme', theme);

    document.querySelectorAll('[data-theme-value]').forEach(button => {
        const isActive = button.dataset.themeValue === theme;
        button.classList.toggle('active', isActive);
        button.setAttribute('aria-pressed', String(isActive));
    });
};

document.addEventListener('DOMContentLoaded', () => {
    applyTheme(preferredTheme());

    document.querySelectorAll('[data-theme-value]').forEach(button => {
        button.addEventListener('click', () => applyTheme(button.dataset.themeValue));
    });

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (preferredTheme() === 'auto') {
            applyTheme('auto');
        }
    });

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
        bootstrap.Tooltip.getOrCreateInstance(element);
    });

    document.querySelectorAll('[data-password-toggle]').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);

            if (!input) {
                return;
            }

            const isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            button.setAttribute('aria-pressed', String(!isVisible));
        });
    });

    const modalElement = document.getElementById('confirm-action-modal');
    const messageElement = document.getElementById('confirm-action-message');
    const submitButton = document.getElementById('confirm-action-submit');
    let pendingForm = null;

    if (modalElement && messageElement && submitButton) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

        document.addEventListener('submit', event => {
            const form = event.target.closest('[data-confirm], .js-confirm-archive');

            if (!form || form.dataset.confirmed === 'true') {
                return;
            }

            event.preventDefault();
            pendingForm = form;
            messageElement.textContent = form.dataset.confirm ?? '';
            modal.show();
        });

        submitButton.addEventListener('click', () => {
            if (!pendingForm) {
                return;
            }

            pendingForm.dataset.confirmed = 'true';
            modal.hide();
            pendingForm.requestSubmit();
        });

        modalElement.addEventListener('hidden.bs.modal', () => {
            pendingForm = null;
        });
    }
});
