/* Campus Champions - certificate generation */
(function () {
    'use strict';
    const CERT = window.CERT;
    const form = document.getElementById('certForm');
    if (!form || !CERT) return;

    // Select-all toggle
    const selectAll = document.getElementById('selectAll');
    if (selectAll) selectAll.addEventListener('change', () => {
        form.querySelectorAll('.cert-check').forEach(cb => { cb.checked = selectAll.checked; });
    });

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const checked = form.querySelectorAll('.cert-check:checked');
        if (!checked.length) { window.Toast.show('Select at least one contestant.', 'warning'); return; }

        const btn = form.querySelector('button[type=submit]');
        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';
        try {
            const res = await window.apiFetch(CERT.generate, { method: 'POST', body: new FormData(form) });
            window.Toast.show(res.message || 'Generated.', 'success');
            setTimeout(() => location.reload(), 900);
        } catch (err) {
            window.Toast.show(err.message || 'Failed to generate.', 'error');
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });
})();
