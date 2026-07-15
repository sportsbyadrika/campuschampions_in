<?php
/** @var int $meetId @var string $meetTitle @var string $institution
 *  @var string $meetLogo @var string $institutionLogo @var string $bannerImage @var int $scrollSpeed */
$dataUrl = url('standings/live-data/' . $meetId);
$meetLogo        = $meetLogo ?? '';
$institutionLogo = $institutionLogo ?? '';
$bannerImage     = $bannerImage ?? '';
$scrollSpeed     = (int) ($scrollSpeed ?? 28);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($meetTitle) ?> — Live Standings</title>
    <style>
        :root {
            --gold:#fbbf24; --silver:#cbd5e1; --bronze:#d97706;
            --ink:#e5edf7; --muted:#93a4bd; --line:rgba(255,255,255,.12);
            --pts:#fbbf24;      /* house total points accent */
            --evt:#38bdf8;      /* event instance emphasis */
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body {
            font-family: 'Segoe UI', system-ui, Arial, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1200px 620px at 18% -12%, rgba(37,99,235,.28), transparent 60%),
                radial-gradient(1000px 520px at 105% -5%, rgba(16,185,129,.16), transparent 55%),
                linear-gradient(160deg, #0b1220 0%, #0f172a 42%, #0b1220 100%);
            overflow: hidden;
        }
        .board { display: flex; flex-direction: column; height: 100vh; padding: 1.2vh 1.2vw; gap: 1.2vh; }

        /* ---- Banner ---- */
        .banner {
            display: grid; grid-template-columns: 1fr 2fr 1fr; align-items: center; gap: 1.5vw;
            background: rgba(255,255,255,.05); border: 1px solid var(--line);
            border-radius: 16px; padding: 1.1vh 1.5vw; backdrop-filter: blur(6px);
        }
        .brand-left { display: flex; align-items: center; gap: 1vw; }
        .logo {
            width: 7vh; height: 7vh; min-width: 7vh; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #2563EB, #1e40af);
            font-weight: 800; font-size: 2.8vh; color: #fff; letter-spacing: .5px;
            box-shadow: 0 6px 18px rgba(37,99,235,.4);
        }
        .inst-logo { height: 8vh; max-width: 14vw; object-fit: contain; border-radius: 10px; }
        .brand-center { text-align: center; display: flex; flex-direction: column; align-items: center; gap: .4vh; }
        .meet-logo { height: 9vh; max-width: 100%; object-fit: contain; }
        .banner .meet { font-size: 3.2vh; font-weight: 800; letter-spacing: .4px; }
        .banner .inst { font-size: 1.9vh; color: var(--muted); }
        .clock { text-align: right; }
        .clock .time { font-size: 3.2vh; font-weight: 800; font-variant-numeric: tabular-nums; letter-spacing: 1px; color: #fff; }
        .clock .date { font-size: 1.7vh; color: var(--muted); }

        /* ---- Layout ---- */
        .grid { flex: 1; display: grid; grid-template-columns: 1fr 3fr; gap: 1.2vw; min-height: 0; }
        .col { display: flex; flex-direction: column; gap: 1.2vh; min-height: 0; }
        .panel {
            background: rgba(255,255,255,.055); border: 1px solid var(--line); border-radius: 16px;
            display: flex; flex-direction: column; min-height: 0; overflow: hidden; backdrop-filter: blur(6px);
            box-shadow: 0 10px 30px rgba(0,0,0,.25);
        }
        .panel > .head { padding: 1.1vh 1.2vw; border-bottom: 1px solid var(--line); font-size: 2vh; font-weight: 700; color: #fff; display: flex; align-items: center; gap: .5vw; }
        .panel > .head .dot { color: var(--gold); }
        .panel > .body { padding: 1vh 1.2vw; overflow: hidden; flex: 1; min-height: 0; }
        .col > .panel { flex: 1; }              /* left column: two panels at 50/50 height */

        /* ---- House standings (enlarged) ---- */
        .house { display: flex; align-items: center; gap: .8vw; margin-bottom: 1.7vh; }
        .house .rank { width: 2.6vh; text-align: center; font-weight: 800; color: var(--muted); font-size: 2.1vh; }
        .house .nm { width: 8vw; display: flex; align-items: center; gap: .5vw; font-weight: 700; font-size: 2.2vh; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .house .swatch { width: 1.8vh; height: 1.8vh; border-radius: 50%; border: 1px solid rgba(255,255,255,.3); flex: none; }
        .house .barwrap { flex: 1; height: 1.8vh; background: rgba(255,255,255,.09); border-radius: 999px; overflow: hidden; }
        .house .bar { height: 100%; border-radius: 999px; transition: width .6s ease; }
        .house .medals { font-size: 1.9vh; color: var(--muted); white-space: nowrap; display: flex; gap: .7vw; }
        .house .medals .mc { display: inline-flex; align-items: center; gap: .2vw; }
        .house .pts { width: 6vw; text-align: right; font-weight: 800; font-size: 2.4vh; color: var(--pts); }

        /* ---- Tables ---- */
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .8vh .7vw; font-size: 1.6vh; border-bottom: 1px solid rgba(255,255,255,.07); }
        th { color: var(--muted); font-weight: 600; text-transform: uppercase; font-size: 1.25vh; letter-spacing: .05em; position: sticky; top: 0; background: rgba(15,23,42,.85); }
        td.c, th.c { text-align: center; }
        td.r, th.r { text-align: right; }

        /* ---- Category pivot ---- */
        .cat-table td.hname { font-weight: 700; color: #fff; white-space: nowrap; }
        .cat-table td.hname .swatch { display: inline-block; width: 1.4vh; height: 1.4vh; border-radius: 50%; margin-right: .4vw; vertical-align: middle; border: 1px solid rgba(255,255,255,.3); }
        .cat-table td.num { text-align: center; font-variant-numeric: tabular-nums; }
        .cat-table td.total { text-align: right; font-weight: 800; color: var(--pts); }
        .cat-table th.total { text-align: right; }

        /* ---- Winners scroller ---- */
        .scroller { position: relative; height: 100%; overflow: hidden; }
        .track { will-change: transform; }
        .wtable th { background: rgba(15,23,42,.92); }
        .wtable td { vertical-align: top; }
        .wtable tbody tr:nth-child(even) { background: rgba(255,255,255,.04); }   /* zebra striping */
        .wtable td.evtcell { border-left: .35vw solid var(--evt); }
        .wtable .evt { display: block; font-weight: 700; font-size: 1.55vh; color: var(--evt); letter-spacing: .2px; }
        .wtable .evsub { display: block; color: var(--muted); font-weight: 400; font-size: 1.3vh; margin-top: .35vh; }
        .win { margin-bottom: .7vh; }
        .win:last-child { margin-bottom: 0; }
        .win .wn { font-weight: 700; font-size: 1.65vh; color: #fff; }
        .win .wm { font-size: 1.25vh; color: var(--muted); }
        .dash { color: rgba(255,255,255,.25); }
        .badge { display: inline-block; min-width: 3.2vh; text-align: center; }
        .loading { text-align: center; color: var(--muted); padding: 4vh; font-size: 2vh; }
    </style>
</head>
<body>
<div class="board">
    <!-- Banner -->
    <div class="banner">
        <div class="brand-left">
            <?php if ($institutionLogo): ?>
                <img class="inst-logo" src="<?= e($institutionLogo) ?>" alt="<?= e($institution) ?>">
            <?php else: ?>
                <div class="logo"><?= e(strtoupper(mb_substr($institution ?: 'C', 0, 1))) ?></div>
            <?php endif; ?>
        </div>
        <div class="brand-center">
            <?php if ($meetLogo): ?>
                <img class="meet-logo" src="<?= e($meetLogo) ?>" alt="<?= e($meetTitle) ?>">
            <?php else: ?>
                <div class="meet" id="meetTitle"><?= e($meetTitle) ?></div>
                <div class="inst" id="instName"><?= e($institution) ?></div>
            <?php endif; ?>
        </div>
        <div class="clock">
            <div class="time" id="clockTime">--:--:--</div>
            <div class="date" id="clockDate">&nbsp;</div>
        </div>
    </div>

    <!-- Two columns 1:3 -->
    <div class="grid">
        <div class="col">
            <div class="panel">
                <div class="head"><span class="dot">🏆</span> House Standings</div>
                <div class="body" id="housePanel"><div class="loading">Loading…</div></div>
            </div>
            <div class="panel">
                <div class="head"><span class="dot">📚</span> House × Category Group — Points</div>
                <div class="body">
                    <div class="scroller" id="catScroller"><div class="track" id="catTrack"><div class="loading">Loading…</div></div></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="panel" style="flex:1">
                <div class="head"><span class="dot">🥇</span> Prize Winners by Event</div>
                <div class="body">
                    <div class="scroller" id="scroller">
                        <div class="track" id="track"><div class="loading">Loading…</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';
    var DATA_URL = <?= json_encode($dataUrl, JSON_UNESCAPED_SLASHES) ?>;
    var REFRESH_MS = 30000;   // 30 seconds
    var WIN_SPEED = <?= max(5, min(200, $scrollSpeed)) ?>;   // px/s for Prize Winners (from meet settings)
    var CAT_SPEED = 24;       // px/s for the category pivot

    function esc(s) { var d = document.createElement('div'); d.textContent = (s == null ? '' : String(s)); return d.innerHTML; }
    function num(v) { v = Number(v) || 0; return (v % 1 === 0) ? String(v) : v.toFixed(1); }

    // ---------- Clock ----------
    var days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    var mons = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    function tick() {
        var n = new Date();
        var hh = String(n.getHours()).padStart(2,'0'), mm = String(n.getMinutes()).padStart(2,'0'), ss = String(n.getSeconds()).padStart(2,'0');
        document.getElementById('clockTime').textContent = hh + ':' + mm + ':' + ss;
        document.getElementById('clockDate').textContent = days[n.getDay()] + ', ' + String(n.getDate()).padStart(2,'0') + ' ' + mons[n.getMonth()] + ' ' + n.getFullYear();
    }
    setInterval(tick, 1000); tick();

    // ---------- Renderers ----------
    function renderHouses(houses) {
        if (!houses.length) return '<div class="loading">No results yet.</div>';
        houses = houses.slice(0, 5); // show top 5 houses only
        var max = 0; houses.forEach(function (h) { max = Math.max(max, h.points); });
        var rank = ['🥇','🥈','🥉'];
        return houses.map(function (h, i) {
            var pct = max > 0 ? (h.points / max) * 100 : 0;
            return '<div class="house">'
                + '<div class="rank">' + (rank[i] || (i+1)) + '</div>'
                + '<div class="nm"><span class="swatch" style="background:' + esc(h.color) + '"></span>' + esc(h.name) + '</div>'
                + '<div class="barwrap"><div class="bar" style="width:' + pct + '%;background:' + esc(h.color) + '"></div></div>'
                + '<div class="medals"><span class="mc">🥇' + h.golds + '</span><span class="mc">🥈' + h.silvers + '</span><span class="mc">🥉' + h.bronzes + '</span></div>'
                + '<div class="pts">' + num(h.points) + '</div>'
                + '</div>';
        }).join('');
    }

    function renderCategoryPivot(pivot) {
        if (!pivot || !pivot.rows || !pivot.rows.length) return '<div class="loading">No results yet.</div>';
        var cats = pivot.categories || [];
        var head = '<tr><th>House</th>' + cats.map(function (c) { return '<th class="c">' + esc(c) + '</th>'; }).join('') + '<th class="total">Total</th></tr>';
        var body = pivot.rows.map(function (r) {
            var cells = (r.points || []).map(function (p) { return '<td class="num">' + (p ? num(p) : '<span class="dash">·</span>') + '</td>'; }).join('');
            return '<tr><td class="hname"><span class="swatch" style="background:' + esc(r.color) + '"></span>' + esc(r.name) + '</td>' + cells + '<td class="total">' + num(r.total) + '</td></tr>';
        }).join('');
        return '<table class="cat-table"><thead>' + head + '</thead><tbody>' + body + '</tbody></table>';
    }

    function cell(list) {
        if (!list || !list.length) return '<span class="dash">—</span>';
        return list.map(function (w) {
            return '<div class="win"><div class="wn">' + esc(w.name) + '</div>' + (w.meta ? '<div class="wm">' + esc(w.meta) + '</div>' : '') + '</div>';
        }).join('');
    }

    function winnersTable(events) {
        if (!events.length) return '<div class="loading">No prize winners yet.</div>';
        var rows = events.map(function (ev) {
            return '<tr>'
                + '<td class="evtcell"><span class="evt">' + esc(ev.label) + '</span><span class="evsub">' + esc(ev.sub) + '</span></td>'
                + '<td>' + cell(ev.first) + '</td>'
                + '<td>' + cell(ev.second) + '</td>'
                + '<td>' + cell(ev.third) + '</td>'
                + '</tr>';
        }).join('');
        return '<table class="wtable"><thead><tr><th style="width:28%">Event Instance</th><th>🥇 First</th><th>🥈 Second</th><th>🥉 Third</th></tr></thead><tbody>' + rows + '</tbody></table>';
    }

    // ---------- Seamless auto-scroll (reusable marquee) ----------
    // Shows a single copy; only adds a duplicate (for a seamless loop) when the
    // content actually overflows and needs to scroll. Each marquee has its own speed.
    function makeMarquee(scrollerEl, trackEl, getSpeed) {
        var offset = 0, copyH = 0, needScroll = false, html = '';
        function set(h) { html = h; layout(); }
        function layout() {
            trackEl.innerHTML = '<div class="copy">' + html + '</div>';
            var copy = trackEl.querySelector('.copy');
            copyH = copy ? copy.offsetHeight : 0;
            if (copyH > scrollerEl.clientHeight + 4) {
                trackEl.insertAdjacentHTML('beforeend', '<div class="copy" aria-hidden="true">' + html + '</div>');
                needScroll = true;
                if (copyH > 0 && offset >= copyH) offset = offset % copyH;
            } else {
                needScroll = false;
                offset = 0;
                trackEl.style.transform = 'translateY(0)';
            }
        }
        function step(dt) {
            if (needScroll && copyH > 0) {
                offset += getSpeed() * dt;
                if (offset >= copyH) offset -= copyH;
                trackEl.style.transform = 'translateY(' + (-offset) + 'px)';
            }
        }
        function has() { return html !== ''; }
        return { set: set, layout: layout, step: step, has: has };
    }

    var winMarquee = makeMarquee(document.getElementById('scroller'), document.getElementById('track'), function () { return WIN_SPEED; });
    var catMarquee = makeMarquee(document.getElementById('catScroller'), document.getElementById('catTrack'), function () { return CAT_SPEED; });
    var marquees = [winMarquee, catMarquee];

    var last = null;
    function frame(ts) {
        if (last == null) last = ts;
        var dt = (ts - last) / 1000; last = ts;
        marquees.forEach(function (m) { m.step(dt); });
        requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
    window.addEventListener('resize', function () { marquees.forEach(function (m) { if (m.has()) m.layout(); }); });

    // ---------- Data load ----------
    function apply(data) {
        if (data.meet) {
            if (data.meet.title) { var mt = document.getElementById('meetTitle'); if (mt) mt.textContent = data.meet.title; }
            if (data.meet.scrollSpeed) WIN_SPEED = Number(data.meet.scrollSpeed) || WIN_SPEED;
        }
        if (data.institution != null) { var inm = document.getElementById('instName'); if (inm) inm.textContent = data.institution; }
        document.getElementById('housePanel').innerHTML = renderHouses(data.houses || []);
        catMarquee.set(renderCategoryPivot(data.categoryPivot));
        winMarquee.set(winnersTable(data.events || []));
    }
    function load() {
        fetch(DATA_URL, { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .then(apply)
            .catch(function () { /* keep showing last data + scrolling */ });
    }
    load();
    setInterval(load, REFRESH_MS);
})();
</script>
</body>
</html>
