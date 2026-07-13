/* Campus Champions - event instance registrations */
(function () {
    'use strict';
    const REG = window.REG;
    if (!REG) return;

    const modal = document.getElementById('regModal');
    let lastFocus = null;
    function open(m) { lastFocus = document.activeElement; m.classList.remove('hidden'); document.body.style.overflow = 'hidden'; const f = m.querySelector('select'); if (f) setTimeout(() => f.focus(), 50); }
    function close(m) { m.classList.add('hidden'); document.body.style.overflow = ''; if (lastFocus) lastFocus.focus(); }
    window.Modal = { closeAll() { document.querySelectorAll('.modal-backdrop:not(.hidden)').forEach(close); } };

    document.querySelectorAll('[data-modal-close]').forEach(b => b.addEventListener('click', () => close(modal)));
    modal.addEventListener('click', e => { if (e.target === modal) close(modal); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') close(modal); });

    const addBtn = document.getElementById('addRegBtn');
    if (addBtn) addBtn.addEventListener('click', () => open(modal));

    document.getElementById('regForm').addEventListener('submit', async e => {
        e.preventDefault();
        const fd = new FormData(e.target);
        if (!fd.get('contestant_id')) { window.Toast.show('Please select a contestant.', 'warning'); return; }
        try {
            const res = await window.apiFetch(REG.base + '/registrations', { method: 'POST', body: fd });
            window.Toast.show(res.message || 'Registered.', 'success');
            setTimeout(() => location.reload(), 600);
        } catch (err) { window.Toast.show(err.message || 'Failed to register.', 'error'); }
    });

    // Status change
    document.querySelectorAll('[data-status]').forEach(sel => sel.addEventListener('change', async () => {
        const id = sel.dataset.status;
        const fd = new FormData(); fd.append('_method', 'PUT'); fd.append('status', sel.value);
        try {
            const res = await window.apiFetch(REG.base + '/registrations/' + id, { method: 'POST', body: fd });
            window.Toast.show(res.message || 'Updated.', 'success');
        } catch (err) { window.Toast.show(err.message || 'Failed.', 'error'); }
    }));

    // Remove
    document.querySelectorAll('[data-remove]').forEach(btn => btn.addEventListener('click', async () => {
        if (!confirm('Remove this registration?')) return;
        const id = btn.dataset.remove;
        const fd = new FormData(); fd.append('_method', 'DELETE');
        try {
            const res = await window.apiFetch(REG.base + '/registrations/' + id, { method: 'POST', body: fd });
            window.Toast.show(res.message || 'Removed.', 'success');
            setTimeout(() => location.reload(), 500);
        } catch (err) { window.Toast.show(err.message || 'Failed.', 'error'); }
    }));
})();
