/* Campus Champions - result entry grid */
(function () {
    'use strict';
    const POINTS = window.POINTS || {};
    const RESULT = window.RESULT;
    const form = document.getElementById('resultForm');
    if (!form || !RESULT) return;

    // Auto-fill points when a position is chosen (only if points field is empty)
    form.querySelectorAll('.result-pos').forEach(sel => {
        sel.addEventListener('change', () => {
            const row = sel.closest('tr');
            const pts = row.querySelector('.result-pts');
            if (sel.value && POINTS[sel.value] !== undefined) {
                if (pts.value === '' || pts.dataset.auto === '1') {
                    pts.value = POINTS[sel.value];
                    pts.dataset.auto = '1';
                }
            } else if (sel.value === '') {
                if (pts.dataset.auto === '1') { pts.value = ''; }
            }
        });
    });
    // Mark manual edits so auto-fill stops overwriting
    form.querySelectorAll('.result-pts').forEach(inp => {
        inp.addEventListener('input', () => { inp.dataset.auto = '0'; });
    });

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const btn = form.querySelector('button[type=submit]');
        btn.disabled = true;
        try {
            const res = await window.apiFetch(RESULT.save, { method: 'POST', body: new FormData(form) });
            window.Toast.show(res.message || 'Saved.', 'success');
            btn.disabled = false;
        } catch (err) {
            window.Toast.show(err.message || 'Failed to save results.', 'error');
            btn.disabled = false;
        }
    });
})();
