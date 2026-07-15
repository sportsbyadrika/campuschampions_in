/* Campus Champions - meet live-screen settings: image crop + save */
(function () {
    'use strict';
    var MEET = window.MEET;
    if (!MEET || !MEET.crop) return;

    var panel = document.querySelector('[data-panel="live"]');
    if (!panel) return;

    // Per-field pending state: { blob, remove, els... }
    var fields = {};

    // ---------- Scroll-speed slider ----------
    var speed = document.getElementById('scrollSpeed');
    var speedVal = document.getElementById('scrollSpeedVal');
    if (speed && speedVal) {
        speed.addEventListener('input', function () { speedVal.textContent = speed.value; });
    }

    // ---------- Crop modal machinery ----------
    var modal = document.getElementById('cropModal');
    var stage = document.getElementById('cropStage');
    var cropImg = document.getElementById('cropImg');
    var zoomInput = document.getElementById('cropZoom');
    var ratioLabel = document.getElementById('cropRatioLabel');
    var sess = null; // { field, cfg, natW, natH, stageW, stageH, base, zoom, tx, ty }

    function openModal() { modal.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
    function closeModal() { modal.classList.add('hidden'); document.body.style.overflow = ''; sess = null; }
    modal.querySelectorAll('[data-crop-close]').forEach(function (b) { b.addEventListener('click', closeModal); });
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

    function layoutStage(cfg) {
        // Fit the stage width to the available modal width, height from aspect.
        var avail = Math.min(560, (modal.querySelector('.modal-panel').clientWidth || 560) - 48);
        var w = Math.max(240, avail);
        var h = Math.round(w / cfg.aspect);
        stage.style.width = w + 'px';
        stage.style.height = h + 'px';
        return { w: w, h: h };
    }

    function clamp() {
        var ds = sess.base * sess.zoom;
        var iw = sess.natW * ds, ih = sess.natH * ds;
        // Image must always cover the stage.
        var minTx = sess.stageW - iw, minTy = sess.stageH - ih;
        if (sess.tx > 0) sess.tx = 0; if (sess.tx < minTx) sess.tx = minTx;
        if (sess.ty > 0) sess.ty = 0; if (sess.ty < minTy) sess.ty = minTy;
    }

    function render() {
        var ds = sess.base * sess.zoom;
        cropImg.style.width = (sess.natW * ds) + 'px';
        cropImg.style.height = (sess.natH * ds) + 'px';
        cropImg.style.transform = 'translate(' + sess.tx + 'px,' + sess.ty + 'px)';
    }

    function setZoom(z) {
        var cx = sess.stageW / 2, cy = sess.stageH / 2;
        var dsOld = sess.base * sess.zoom;
        var imgX = (cx - sess.tx) / dsOld, imgY = (cy - sess.ty) / dsOld;
        sess.zoom = z;
        var dsNew = sess.base * sess.zoom;
        sess.tx = cx - imgX * dsNew;
        sess.ty = cy - imgY * dsNew;
        clamp(); render();
    }

    zoomInput.addEventListener('input', function () { if (sess) setZoom(parseFloat(zoomInput.value)); });

    // Drag to pan
    var dragging = false, lastX = 0, lastY = 0;
    stage.addEventListener('pointerdown', function (e) {
        if (!sess) return;
        dragging = true; lastX = e.clientX; lastY = e.clientY;
        stage.setPointerCapture(e.pointerId);
    });
    stage.addEventListener('pointermove', function (e) {
        if (!dragging || !sess) return;
        sess.tx += e.clientX - lastX; sess.ty += e.clientY - lastY;
        lastX = e.clientX; lastY = e.clientY;
        clamp(); render();
    });
    function endDrag() { dragging = false; }
    stage.addEventListener('pointerup', endDrag);
    stage.addEventListener('pointercancel', endDrag);

    function startCrop(field, file) {
        var cfg = MEET.crop[field];
        if (!cfg) return;
        var reader = new FileReader();
        reader.onload = function () {
            var img = new Image();
            img.onload = function () {
                var s = layoutStage(cfg);
                var base = Math.max(s.w / img.naturalWidth, s.h / img.naturalHeight); // cover fit
                sess = {
                    field: field, cfg: cfg, natW: img.naturalWidth, natH: img.naturalHeight,
                    stageW: s.w, stageH: s.h, base: base, zoom: 1, tx: 0, ty: 0
                };
                // Center the image
                sess.tx = (s.w - img.naturalWidth * base) / 2;
                sess.ty = (s.h - img.naturalHeight * base) / 2;
                cropImg.src = img.src;
                zoomInput.value = '1';
                ratioLabel.textContent = cfg.label;
                clamp(); render(); openModal();
            };
            img.src = reader.result;
        };
        reader.readAsDataURL(file);
    }

    document.getElementById('cropApply').addEventListener('click', function () {
        if (!sess) return;
        var ds = sess.base * sess.zoom;
        var sx = (-sess.tx) / ds, sy = (-sess.ty) / ds;
        var sw = sess.stageW / ds, sh = sess.stageH / ds;
        var canvas = document.createElement('canvas');
        canvas.width = sess.cfg.outW; canvas.height = sess.cfg.outH;
        var ctx = canvas.getContext('2d');
        var imgEl = new Image();
        imgEl.onload = function () {
            ctx.drawImage(imgEl, sx, sy, sw, sh, 0, 0, sess.cfg.outW, sess.cfg.outH);
            var field = sess.field;
            canvas.toBlob(function (blob) {
                fields[field].blob = blob;
                fields[field].remove = false;
                showPreview(field, URL.createObjectURL(blob));
                closeModal();
            }, 'image/png');
        };
        imgEl.src = cropImg.src;
    });

    // ---------- Per-field wiring ----------
    function showPreview(field, url) {
        var f = fields[field];
        f.img.src = url; f.img.classList.remove('hidden');
        if (f.empty) f.empty.classList.add('hidden');
        if (f.removeBtn) f.removeBtn.classList.remove('hidden');
        f.err.classList.add('hidden'); f.err.textContent = '';
    }
    function clearPreview(field) {
        var f = fields[field];
        f.img.src = ''; f.img.classList.add('hidden');
        if (f.empty) f.empty.classList.remove('hidden');
        if (f.removeBtn) f.removeBtn.classList.add('hidden');
    }

    panel.querySelectorAll('[data-crop-field]').forEach(function (block) {
        var field = block.getAttribute('data-crop-field');
        var fileInput = block.querySelector('[data-file]');
        var f = {
            blob: null, remove: false,
            img: block.querySelector('[data-preview-img]'),
            empty: block.querySelector('[data-preview-empty]'),
            removeBtn: block.querySelector('[data-remove]'),
            err: block.querySelector('[data-error="' + field + '"]')
        };
        fields[field] = f;

        block.querySelector('[data-pick]').addEventListener('click', function () { fileInput.click(); });
        fileInput.addEventListener('change', function () {
            if (fileInput.files && fileInput.files[0]) startCrop(field, fileInput.files[0]);
            fileInput.value = ''; // allow re-picking the same file
        });
        if (f.removeBtn) {
            f.removeBtn.addEventListener('click', function () {
                f.blob = null; f.remove = true; clearPreview(field);
            });
        }
    });

    // ---------- Save ----------
    document.getElementById('saveLiveSettings').addEventListener('click', function () {
        var btn = this;
        var fd = new FormData();
        fd.append('winners_scroll_speed', speed ? speed.value : '28');
        Object.keys(fields).forEach(function (field) {
            var f = fields[field];
            if (f.blob) fd.append(field, f.blob, field + '.png');
            else if (f.remove) fd.append('remove_' + field, '1');
        });
        btn.disabled = true;
        window.apiFetch(MEET.base + '/live-settings', { method: 'POST', body: fd })
            .then(function (res) {
                if (res.paths) {
                    Object.keys(res.paths).forEach(function (field) {
                        if (!fields[field]) return;
                        fields[field].blob = null; fields[field].remove = false;
                        if (res.paths[field]) showPreview(field, res.paths[field]);
                        else clearPreview(field);
                    });
                }
                window.Toast.show(res.message || 'Saved.', 'success');
                btn.disabled = false;
            })
            .catch(function (err) {
                var errs = (err.data && err.data.errors) || {};
                Object.keys(errs).forEach(function (field) {
                    if (fields[field]) { fields[field].err.textContent = errs[field]; fields[field].err.classList.remove('hidden'); }
                });
                window.Toast.show(err.message || 'Failed to save.', 'error');
                btn.disabled = false;
            });
    });
})();
