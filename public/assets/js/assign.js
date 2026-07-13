/* Campus Champions - event user assignment */
(function () {
    'use strict';
    const A = window.ASSIGN;
    if (!A) return;

    const form = document.getElementById('assignForm');
    if (form) form.addEventListener('submit', async e => {
        e.preventDefault();
        const fd = new FormData(form);
        if (!fd.get('user_id')) { window.Toast.show('Select a user.', 'warning'); return; }
        try {
            const res = await window.apiFetch(A.base + '/assign', { method: 'POST', body: fd });
            window.Toast.show(res.message || 'Assigned.', 'success');
            setTimeout(() => location.reload(), 500);
        } catch (err) { window.Toast.show(err.message || 'Failed.', 'error'); }
    });

    document.querySelectorAll('[data-unassign]').forEach(btn => btn.addEventListener('click', async () => {
        if (!confirm('Remove this assignment?')) return;
        const id = btn.dataset.unassign;
        const fd = new FormData(); fd.append('_method', 'DELETE');
        try {
            const res = await window.apiFetch(A.base + '/assign/' + id, { method: 'POST', body: fd });
            window.Toast.show(res.message || 'Removed.', 'success');
            setTimeout(() => location.reload(), 500);
        } catch (err) { window.Toast.show(err.message || 'Failed.', 'error'); }
    }));
})();
