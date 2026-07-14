/* Campus Champions - generic CRUD modal handling */
(function () {
    'use strict';
    const cfg = window.CRUD_CONFIG;
    if (!cfg) return;

    const modal = document.getElementById('crudModal');
    const form = document.getElementById('crudForm');
    const deleteModal = document.getElementById('deleteModal');
    const titleEl = document.getElementById('crudModalTitle');
    const submitLabel = document.querySelector('[data-submit-label]');
    let deleteId = null;
    let lastFocus = null;

    // ---------- Modal helpers ----------
    function openModal(m) {
        lastFocus = document.activeElement;
        m.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        const focusable = m.querySelector('input:not([type=hidden]), select, textarea, button');
        if (focusable) setTimeout(() => focusable.focus(), 50);
        trapFocus(m);
    }
    function closeModal(m) {
        m.classList.add('hidden');
        document.body.style.overflow = '';
        if (lastFocus) lastFocus.focus();
    }
    window.Modal = { closeAll() { [modal, deleteModal].forEach(m => m && closeModal(m)); } };

    function trapFocus(m) {
        m.addEventListener('keydown', function (e) {
            if (e.key !== 'Tab') return;
            const items = m.querySelectorAll('input:not([type=hidden]), select, textarea, button, a[href]');
            if (!items.length) return;
            const first = items[0], last = items[items.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        });
    }

    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => { closeModal(modal); if (deleteModal) closeModal(deleteModal); });
    });
    [modal, deleteModal].forEach(m => {
        if (!m) return;
        m.addEventListener('click', e => { if (e.target === m) closeModal(m); });
    });

    // ---------- Error handling ----------
    function clearErrors() {
        form.querySelectorAll('[data-error]').forEach(el => { el.classList.add('hidden'); el.textContent = ''; });
        form.querySelectorAll('[data-field]').forEach(el => el.classList.remove('border-rose-400'));
    }
    function showErrors(errors) {
        Object.keys(errors || {}).forEach(field => {
            const err = form.querySelector('[data-error="' + field + '"]');
            const input = form.querySelector('[data-field="' + field + '"]');
            if (err) { err.textContent = errors[field]; err.classList.remove('hidden'); }
            if (input) input.classList.add('border-rose-400');
        });
    }

    function resetForm() {
        form.reset();
        form.querySelector('#crudId').value = '';
        clearErrors();
    }

    // ---------- Add ----------
    const addBtn = document.querySelector('[data-crud-add]');
    if (addBtn) addBtn.addEventListener('click', () => {
        resetForm();
        titleEl.textContent = 'Add ' + cfg.entity;
        submitLabel.textContent = 'Save';
        openModal(modal);
    });

    // ---------- Edit ----------
    document.querySelectorAll('[data-crud-edit]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.getAttribute('data-crud-edit');
            resetForm();
            titleEl.textContent = 'Edit ' + cfg.entity;
            submitLabel.textContent = 'Update';
            try {
                const res = await window.apiFetch(cfg.route + '/' + id + '/edit');
                const data = res.data || {};
                form.querySelector('#crudId').value = data.id || '';
                cfg.fields.forEach(f => {
                    const input = form.querySelector('[data-field="' + f + '"]');
                    if (!input) return;
                    const val = data[f];
                    if (input.multiple && Array.isArray(val)) {
                        // multi-select: mark matching options selected
                        const set = new Set(val.map(String));
                        Array.from(input.options).forEach(o => { o.selected = set.has(String(o.value)); });
                    } else if (val !== undefined && val !== null) {
                        input.value = val;
                        // sync color picker if present
                        const picker = document.getElementById('f_' + f + '_picker');
                        if (picker && /^#[0-9a-fA-F]{6}$/.test(val)) picker.value = val;
                    }
                });
                openModal(modal);
            } catch (err) {
                window.Toast.show(err.message || 'Failed to load record.', 'error');
            }
        });
    });

    // Color picker sync
    document.querySelectorAll('input[type=color]').forEach(picker => {
        const target = document.getElementById(picker.id.replace('_picker', ''));
        if (target) picker.addEventListener('input', () => { target.value = picker.value; });
    });

    // ---------- Submit (create/update) ----------
    form.addEventListener('submit', async e => {
        e.preventDefault();
        clearErrors();
        const id = form.querySelector('#crudId').value;
        const isEdit = id !== '';
        const url = isEdit ? cfg.route + '/' + id : cfg.route;

        const fd = new FormData(form);
        if (isEdit) fd.append('_method', 'PUT');

        const submitBtn = form.querySelector('button[type=submit]');
        submitBtn.disabled = true;

        try {
            const res = await window.apiFetch(url, { method: 'POST', body: fd });
            closeModal(modal);
            window.Toast.show(res.message || 'Saved.', 'success');
            setTimeout(() => window.location.reload(), 700);
        } catch (err) {
            if (err.data && err.data.errors) showErrors(err.data.errors);
            window.Toast.show(err.message || 'Failed to save.', 'error');
            submitBtn.disabled = false;
        }
    });

    // ---------- Delete ----------
    document.querySelectorAll('[data-crud-delete]').forEach(btn => {
        btn.addEventListener('click', () => {
            deleteId = btn.getAttribute('data-crud-delete');
            openModal(deleteModal);
        });
    });
    const confirmDelete = document.getElementById('confirmDelete');
    if (confirmDelete) confirmDelete.addEventListener('click', async () => {
        if (!deleteId) return;
        confirmDelete.disabled = true;
        const fd = new FormData();
        fd.append('_method', 'DELETE');
        try {
            const res = await window.apiFetch(cfg.route + '/' + deleteId, { method: 'POST', body: fd });
            closeModal(deleteModal);
            window.Toast.show(res.message || 'Deleted.', 'success');
            setTimeout(() => window.location.reload(), 700);
        } catch (err) {
            closeModal(deleteModal);
            window.Toast.show(err.message || 'Failed to delete.', 'error');
        } finally {
            confirmDelete.disabled = false;
            deleteId = null;
        }
    });
})();
