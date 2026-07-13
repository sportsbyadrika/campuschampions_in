/* Campus Champions - meet setup hub (disciplines, categories, events, instances, points) */
(function () {
    'use strict';
    const MEET = window.MEET;
    if (!MEET) return;

    // ---------- Tabs (persist active tab in the URL hash) ----------
    const tabs = document.querySelectorAll('.setup-tab');
    const panels = document.querySelectorAll('.setup-panel');
    function activate(name) {
        tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === name));
        panels.forEach(p => p.classList.toggle('hidden', p.dataset.panel !== name));
        if (history.replaceState) history.replaceState(null, '', '#' + name);
    }
    tabs.forEach(t => t.addEventListener('click', () => activate(t.dataset.tab)));
    activate((location.hash || '#points').slice(1) || 'points');

    // ---------- Modal helpers ----------
    let lastFocus = null;
    function openModal(m) {
        lastFocus = document.activeElement;
        m.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        const f = m.querySelector('input:not([type=hidden]), select, textarea, button');
        if (f) setTimeout(() => f.focus(), 50);
    }
    function closeModal(m) { m.classList.add('hidden'); document.body.style.overflow = ''; if (lastFocus) lastFocus.focus(); }
    window.Modal = { closeAll() { document.querySelectorAll('.modal-backdrop:not(.hidden)').forEach(closeModal); } };

    document.querySelectorAll('[data-modal-close]').forEach(b => b.addEventListener('click', e => {
        const m = e.target.closest('.modal-backdrop'); if (m) closeModal(m);
    }));
    document.querySelectorAll('.modal-backdrop').forEach(m => m.addEventListener('click', e => { if (e.target === m) closeModal(m); }));

    function modalFor(entity) { return document.getElementById('modal-' + entity); }
    function clearErrors(form) {
        form.querySelectorAll('[data-error]').forEach(el => { el.classList.add('hidden'); el.textContent = ''; });
    }
    function showErrors(form, errors) {
        Object.keys(errors || {}).forEach(f => {
            const el = form.querySelector('[data-error="' + f + '"]');
            if (el) { el.textContent = errors[f]; el.classList.remove('hidden'); }
        });
    }

    // ---------- Add ----------
    document.querySelectorAll('[data-add]').forEach(btn => btn.addEventListener('click', () => {
        const entity = btn.dataset.add;
        const modal = modalFor(entity);
        const form = modal.querySelector('form');
        form.reset();
        form.querySelector('[data-idfield]').value = '';
        clearErrors(form);
        modal.querySelector('[data-title]').textContent = 'Add';
        openModal(modal);
    }));

    // ---------- Edit ----------
    document.querySelectorAll('[data-edit]').forEach(btn => btn.addEventListener('click', () => {
        const entity = btn.dataset.edit;
        const rec = JSON.parse(btn.dataset.record || '{}');
        const modal = modalFor(entity);
        const form = modal.querySelector('form');
        form.reset();
        clearErrors(form);
        form.querySelector('[data-idfield]').value = rec.id || '';
        Object.keys(rec).forEach(k => {
            const input = form.querySelector('[data-field="' + k + '"]');
            if (input && rec[k] !== null && rec[k] !== undefined) input.value = rec[k];
        });
        modal.querySelector('[data-title]').textContent = 'Edit';
        openModal(modal);
    }));

    // ---------- Submit (create/update) ----------
    document.querySelectorAll('[data-form]').forEach(form => form.addEventListener('submit', async e => {
        e.preventDefault();
        clearErrors(form);
        const entity = form.dataset.form;
        const id = form.querySelector('[data-idfield]').value;
        const url = MEET.base + '/' + entity + (id ? '/' + id : '');
        const fd = new FormData(form);
        if (id) fd.append('_method', 'PUT');
        const btn = form.querySelector('button[type=submit]');
        btn.disabled = true;
        try {
            const res = await window.apiFetch(url, { method: 'POST', body: fd });
            window.Toast.show(res.message || 'Saved.', 'success');
            setTimeout(() => location.reload(), 600);
        } catch (err) {
            if (err.data && err.data.errors) showErrors(form, err.data.errors);
            window.Toast.show(err.message || 'Failed to save.', 'error');
            btn.disabled = false;
        }
    }));

    // ---------- Delete ----------
    let del = { entity: null, id: null };
    const delModal = document.getElementById('setupDelete');
    document.querySelectorAll('[data-del]').forEach(btn => btn.addEventListener('click', () => {
        del = { entity: btn.dataset.del, id: btn.dataset.id };
        openModal(delModal);
    }));
    document.getElementById('setupConfirmDelete').addEventListener('click', async () => {
        if (!del.id) return;
        const fd = new FormData(); fd.append('_method', 'DELETE');
        try {
            const res = await window.apiFetch(MEET.base + '/' + del.entity + '/' + del.id, { method: 'POST', body: fd });
            window.Toast.show(res.message || 'Deleted.', 'success');
            setTimeout(() => location.reload(), 600);
        } catch (err) {
            closeModal(delModal);
            window.Toast.show(err.message || 'Failed to delete.', 'error');
        }
    });

    // ---------- Points ----------
    const pointsForm = document.getElementById('pointsForm');
    if (pointsForm) pointsForm.addEventListener('submit', async e => {
        e.preventDefault();
        const fd = new FormData(pointsForm);
        try {
            const res = await window.apiFetch(MEET.base + '/points', { method: 'POST', body: fd });
            window.Toast.show(res.message || 'Saved.', 'success');
        } catch (err) {
            window.Toast.show(err.message || 'Failed to save points.', 'error');
        }
    });

    // ESC closes modals
    document.addEventListener('keydown', e => { if (e.key === 'Escape') window.Modal.closeAll(); });
})();
