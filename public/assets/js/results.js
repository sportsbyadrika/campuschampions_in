/* Campus Champions - result entry grid */
(function () {
    'use strict';
    const POINTS = window.POINTS || {};
    const RESULT = window.RESULT;
    const form = document.getElementById('resultForm');
    if (!form || !RESULT) return;

    // ---------- Search + "entered only" filter ----------
    const search = document.getElementById('resultSearch');
    const enteredOnly = document.getElementById('enteredOnly');
    const rows = Array.from(form.querySelectorAll('.result-row'));
    const noMatch = document.getElementById('noMatchRow');
    const visibleCount = document.getElementById('visibleCount');

    function applyFilter() {
        const q = (search ? search.value : '').trim().toLowerCase();
        const onlyEntered = enteredOnly && enteredOnly.checked;
        let visible = 0;
        rows.forEach(row => {
            const matchesText = !q || (row.getAttribute('data-search') || '').indexOf(q) >= 0;
            const pos = row.querySelector('.result-pos');
            const entered = pos && pos.value !== '';
            const show = matchesText && (!onlyEntered || entered);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (noMatch) noMatch.style.display = visible === 0 ? '' : 'none';
        if (visibleCount) visibleCount.textContent = visible;
    }
    if (search) search.addEventListener('input', window.debounce ? window.debounce(applyFilter, 150) : applyFilter);
    if (enteredOnly) enteredOnly.addEventListener('change', applyFilter);

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
            if (enteredOnly && enteredOnly.checked) applyFilter();
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
