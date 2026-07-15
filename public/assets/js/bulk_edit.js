/* Campus Champions - course/division bulk edit, per-row AJAX auto-save */
(function () {
    'use strict';

    const cfg = window.BULKEDIT;
    const table = document.getElementById('bulkEditTable');
    if (!cfg || !cfg.base || !table) return;

    const rowUrl = id => cfg.base + '/row/' + encodeURIComponent(id);

    // Status indicator states rendered into the per-row [data-status] span.
    const STATES = {
        idle:   { cls: 'text-slate-300', icon: 'fa-circle',              title: 'No changes' },
        dirty:  { cls: 'text-amber-500', icon: 'fa-circle',              title: 'Unsaved changes' },
        saving: { cls: 'text-primary',   icon: 'fa-spinner fa-spin',     title: 'Saving…' },
        saved:  { cls: 'text-green-500', icon: 'fa-circle-check',        title: 'Saved' },
        error:  { cls: 'text-red-500',   icon: 'fa-circle-exclamation',  title: 'Failed to save — click save to retry' }
    };

    function setState(row, state, message) {
        row.dataset.state = state;
        const span = row.querySelector('[data-status]');
        if (span) {
            const s = STATES[state] || STATES.idle;
            span.className = 'be-status text-xs ' + s.cls;
            span.innerHTML = '<i class="fa-solid ' + s.icon + '"></i>';
            span.title = message || s.title;
        }
    }

    // Gather the editable [data-field] values of a row into a FormData payload.
    function collect(row) {
        const fd = new FormData();
        row.querySelectorAll('[data-field]').forEach(el => {
            fd.append(el.getAttribute('data-field'), el.value);
        });
        return fd;
    }

    function clearFieldErrors(row) {
        row.querySelectorAll('[data-field].is-invalid').forEach(el => {
            el.classList.remove('is-invalid', 'ring-1', 'ring-red-400');
        });
    }

    function markFieldErrors(row, errors) {
        if (!errors) return;
        Object.keys(errors).forEach(field => {
            const el = row.querySelector('[data-field="' + field + '"]');
            if (el) el.classList.add('is-invalid', 'ring-1', 'ring-red-400');
        });
    }

    // Save one row. Returns a promise that always resolves (true = saved).
    function save(row) {
        const id = row.getAttribute('data-id');
        if (!id) return Promise.resolve(false);
        // Guard against overlapping saves for the same row.
        if (row.dataset.saving === '1') {
            row.dataset.pending = '1';
            return Promise.resolve(false);
        }
        row.dataset.saving = '1';
        clearFieldErrors(row);
        setState(row, 'saving');

        return window.apiFetch(rowUrl(id), { method: 'POST', body: collect(row) })
            .then(res => {
                setState(row, 'saved', res && res.message ? res.message : 'Saved');
                return true;
            })
            .catch(err => {
                const data = err && err.data ? err.data : {};
                markFieldErrors(row, data.errors);
                setState(row, 'error', (err && err.message) || 'Failed to save');
                if (window.Toast) {
                    window.Toast.show((err && err.message) || 'Failed to save row.', 'error');
                }
                return false;
            })
            .then(ok => {
                row.dataset.saving = '';
                // A change came in while we were saving — save again.
                if (row.dataset.pending === '1') {
                    row.dataset.pending = '';
                    return save(row);
                }
                return ok;
            });
    }

    // Debounced auto-save per row (text a touch slower than selects).
    const savers = new WeakMap();
    function scheduledSave(row, wait) {
        let fn = savers.get(row);
        if (!fn) {
            fn = window.debounce(() => save(row), wait);
            savers.set(row, fn);
        }
        fn();
    }

    function onEdit(row, wait) {
        if (row.dataset.saving !== '1') setState(row, 'dirty');
        scheduledSave(row, wait);
    }

    Array.prototype.forEach.call(table.querySelectorAll('tr[data-id]'), row => {
        row.querySelectorAll('input[data-field]').forEach(el => {
            el.addEventListener('input', () => onEdit(row, 800));
            el.addEventListener('change', () => onEdit(row, 800));
        });
        row.querySelectorAll('select[data-field]').forEach(el => {
            el.addEventListener('change', () => onEdit(row, 300));
        });
        const btn = row.querySelector('.be-save');
        if (btn) btn.addEventListener('click', () => save(row));
    });

    // Force-save every row that has pending or failed changes, sequentially.
    const saveAll = document.getElementById('beSaveAll');
    if (saveAll) {
        saveAll.addEventListener('click', async () => {
            const rows = Array.prototype.filter.call(
                table.querySelectorAll('tr[data-id]'),
                r => r.dataset.state === 'dirty' || r.dataset.state === 'error'
            );
            if (!rows.length) {
                if (window.Toast) window.Toast.show('Nothing to save.', 'info');
                return;
            }
            saveAll.disabled = true;
            let ok = 0, fail = 0;
            for (const row of rows) {
                const done = await save(row);
                done ? ok++ : fail++;
            }
            saveAll.disabled = false;
            if (window.Toast) {
                const msg = 'Saved ' + ok + ' row(s)' + (fail ? ', ' + fail + ' failed' : '') + '.';
                window.Toast.show(msg, fail ? 'error' : 'success');
            }
        });
    }
})();
