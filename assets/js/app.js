/**
 * AI Study Assistant - Core Frontend JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initSidebarToggle();
    initPasswordStrength();
    setupGlobalFetchCsrf();
});

/**
 * Toast Notification System
 * @param {string} message 
 * @param {string} type 'success' | 'error' | 'warning' | 'info'
 */
function showToast(message, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'custom-toast';

    const icons = {
        success: 'bi-check-circle-fill text-success',
        error: 'bi-x-circle-fill text-danger',
        warning: 'bi-exclamation-triangle-fill text-warning',
        info: 'bi-info-circle-fill text-primary'
    };

    const iconClass = icons[type] || icons.info;

    toast.innerHTML = `
        <i class="bi ${iconClass} fs-4"></i>
        <div class="flex-grow-1">
            <div class="fw-semibold text-capitalize">${type === 'error' ? 'Error' : type}</div>
            <div class="small text-muted">${message}</div>
        </div>
        <button type="button" class="btn-close ms-2" aria-label="Close" style="font-size: 0.75rem;"></button>
    `;

    const closeBtn = toast.querySelector('.btn-close');
    closeBtn.addEventListener('click', () => {
        toast.remove();
    });

    container.appendChild(toast);

    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }
    }, 4500);
}

/**
 * Theme Preference Management (Light / Dark Mode)
 */
function initTheme() {
    const savedTheme = localStorage.getItem('app_theme') || document.documentElement.getAttribute('data-bs-theme') || 'light';
    applyTheme(savedTheme);

    const themeToggles = document.querySelectorAll('.theme-toggle-btn');
    themeToggles.forEach(btn => {
        btn.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
            const nextTheme = current === 'dark' ? 'light' : 'dark';
            applyTheme(nextTheme);
            localStorage.setItem('app_theme', nextTheme);

            // If user is logged in, optionally notify backend
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) {
                fetch('api/profile.php?action=update_theme', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfMeta.content },
                    body: JSON.stringify({ theme: nextTheme })
                }).catch(() => {});
            }
        });
    });
}

function applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    const icons = document.querySelectorAll('.theme-toggle-icon');
    icons.forEach(icon => {
        if (theme === 'dark') {
            icon.className = 'bi bi-sun-fill theme-toggle-icon text-warning';
        } else {
            icon.className = 'bi bi-moon-stars-fill theme-toggle-icon';
        }
    });
}

/**
 * Sidebar Mobile Responsive Toggle
 */
function initSidebarToggle() {
    const sidebar = document.querySelector('.app-sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle-btn');
    const backdrop = document.querySelector('.sidebar-backdrop');

    if (!sidebar) return;

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            if (backdrop) backdrop.classList.toggle('show');
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', () => {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
        });
    }
}

/**
 * Password Strength Meter
 */
function initPasswordStrength() {
    const passwordInput = document.getElementById('password');
    const meterBar = document.getElementById('password-strength-bar');
    const meterText = document.getElementById('password-strength-text');

    if (!passwordInput || !meterBar) return;

    passwordInput.addEventListener('input', () => {
        const val = passwordInput.value;
        let score = 0;

        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const levels = [
            { width: '0%', color: '#E2E8F0', text: '' },
            { width: '25%', color: '#EF4444', text: 'Weak (needs numbers & symbols)' },
            { width: '50%', color: '#F59E0B', text: 'Fair (add uppercase & special chars)' },
            { width: '75%', color: '#3B82F6', text: 'Good (almost strong)' },
            { width: '100%', color: '#16A34A', text: 'Strong password' }
        ];

        const current = val.length === 0 ? levels[0] : levels[score];
        meterBar.style.width = current.width;
        meterBar.style.backgroundColor = current.color;
        if (meterText) {
            meterText.textContent = current.text;
            meterText.style.color = current.color;
        }
    });
}

/**
 * Automatically inject CSRF token header into standard Fetch requests
 */
function setupGlobalFetchCsrf() {
    const originalFetch = window.fetch;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (csrfToken) {
        window.fetch = function(url, options = {}) {
            options.headers = options.headers || {};
            if (options.headers instanceof Headers) {
                if (!options.headers.has('X-CSRF-Token')) {
                    options.headers.append('X-CSRF-Token', csrfToken);
                }
            } else if (typeof options.headers === 'object') {
                if (!options.headers['X-CSRF-Token']) {
                    options.headers['X-CSRF-Token'] = csrfToken;
                }
            }
            return originalFetch(url, options);
        };
    }
}

