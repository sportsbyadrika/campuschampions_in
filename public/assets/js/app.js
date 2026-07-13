/* Campus Champions - shared front-end behaviour */
(function () {
    'use strict';

    // ---------------- Toasts ----------------
    const Toast = {
        container() {
            let c = document.getElementById('toastContainer');
            if (!c) {
                c = document.createElement('div');
                c.id = 'toastContainer';
                c.className = 'fixed top-4 right-4 z-50 space-y-2';
                document.body.appendChild(c);
            }
            return c;
        },
        show(message, type) {
            type = type || 'info';
            const icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', warning: 'fa-triangle-exclamation', info: 'fa-circle-info' };
            const el = document.createElement('div');
            el.className = 'toast toast-' + type;
            el.setAttribute('role', 'alert');
            el.innerHTML = '<i class="fa-solid ' + (icons[type] || icons.info) + ' mt-0.5"></i>' +
                '<div class="flex-1 text-sm">' + escapeHtml(message) + '</div>' +
                '<button class="opacity-80 hover:opacity-100" aria-label="Dismiss"><i class="fa-solid fa-xmark"></i></button>';
            el.querySelector('button').addEventListener('click', () => el.remove());
            this.container().appendChild(el);
            setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 4500);
        }
    };
    window.Toast = Toast;

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    // Server-flashed messages
    if (window.__flash && Array.isArray(window.__flash)) {
        window.__flash.forEach(f => Toast.show(f.message, f.type));
    }

    // ---------------- CSRF helper ----------------
    window.csrfToken = function () {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    };

    // Fetch wrapper that injects CSRF + JSON headers
    window.apiFetch = function (url, options) {
        options = options || {};
        options.headers = Object.assign({
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': window.csrfToken(),
            'Accept': 'application/json'
        }, options.headers || {});
        return fetch(url, options).then(r => r.json().catch(() => ({})).then(data => {
            if (!r.ok) { throw Object.assign(new Error(data.message || 'Request failed'), { data, status: r.status }); }
            return data;
        }));
    };

    // ---------------- Dropdowns ----------------
    document.addEventListener('click', function (e) {
        const toggle = e.target.closest('[data-dropdown-toggle]');
        const openMenus = document.querySelectorAll('[data-dropdown-menu]:not(.hidden)');

        if (toggle) {
            e.preventDefault();
            const menu = toggle.closest('[data-dropdown]').querySelector('[data-dropdown-menu]');
            openMenus.forEach(m => { if (m !== menu) m.classList.add('hidden'); });
            menu.classList.toggle('hidden');
            return;
        }
        // Click outside closes all
        if (!e.target.closest('[data-dropdown-menu]')) {
            openMenus.forEach(m => m.classList.add('hidden'));
        }
    });

    // ESC closes dropdowns + modals
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('[data-dropdown-menu]:not(.hidden)').forEach(m => m.classList.add('hidden'));
            if (window.Modal) window.Modal.closeAll();
        }
    });

    // ---------------- Mobile menu ----------------
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const mobileNav = document.getElementById('mobileNav');
    if (mobileBtn && mobileNav) {
        mobileBtn.addEventListener('click', () => mobileNav.classList.toggle('hidden'));
    }

    // ---------------- Debounce util ----------------
    window.debounce = function (fn, wait) {
        let t;
        return function () {
            const args = arguments, ctx = this;
            clearTimeout(t);
            t = setTimeout(() => fn.apply(ctx, args), wait || 300);
        };
    };
})();
