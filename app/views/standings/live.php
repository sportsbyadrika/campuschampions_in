<?php
/** @var int $meetId @var string $meetTitle @var string $institution */
$dataUrl = url('standings/live-data/' . $meetId);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($meetTitle) ?> — Live Standings</title>
    <style>
        :root { --gold:#fbbf24; --silver:#cbd5e1; --bronze:#d97706; --ink:#e5edf7; --muted:#93a4bd; --line:rgba(255,255,255,.12); }
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
            display: flex; align-items: center; gap: 1.5vw;
            background: rgba(255,255,255,.05); border: 1px solid var(--line);
            border-radius: 16px; padding: 1.2vh 1.5vw; backdrop-filter: blur(6px);
        }
        .logo {
            width: 6.4vh; height: 6.4vh; min-width: 6.4vh; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #2563EB, #1e40af);
            font-weight: 800; font-size: 2.6vh; color: #fff; letter-spacing: .5px;
            box-shadow: 0 6px 18px rgba(37,99,235,.4);
        }
        .banner .titles { flex: 1; text-align: center; }
        .banner .meet { font-size: 3.2vh; font-weight: 800; letter-spacing: .4px; }
        .banner .inst { font-size: 1.9vh; color: var(--muted); margin-top: .3vh; }
        .clock { text-align: right; min-width: 15vw; }
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
        .left-house { flex: 1; }
        .left-cd { flex: 1; }

        /* ---- House standings ---- */
        .house { display: flex; align-items: center; gap: .7vw; margin-bottom: 1.3vh; }
        .house .rank { width: 2.2vh; text-align: center; font-weight: 800; color: var(--muted); font-size: 1.7vh; }
        .house .nm { width: 7.5vw; display: flex; align-items: center; gap: .4vw; font-weight: 700; font-size: 1.8vh; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .house .swatch { width: 1.5vh; height: 1.5vh; border-radius: 50%; border: 1px solid rgba(255,255,255,.3); flex: none; }
        .house .barwrap { flex: 1; height: 1.5vh; background: rgba(255,255,255,.09); border-radius: 999px; overflow: hidden; }
        .house .bar { height: 100%; border-radius: 999px; transition: width .6s ease; }
        .house .medals { font-size: 1.5vh; color: var(--muted); white-space: nowrap; }
        .house .pts { width: 5.5vw; text-align: right; font-weight: 800; font-size: 1.9vh; color: #fff; }

        /* ---- Tables ---- */
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .8vh .7vw; font-size: 1.6vh; border-bottom: 1px solid rgba(255,255,255,.07); }
        th { color: var(--muted); font-weight: 600; text-transform: uppercase; font-size: 1.25vh; letter-spacing: .05em; position: sticky; top: 0; background: rgba(15,23,42,.85); }
        td.c, th.c { text-align: center; }
        td.r, th.r { text-align: right; }
        .cd-table td.pts { font-weight: 800; color: #fff; }

        /* ---- Winners scroller ---- */
        .scroller { position: relative; height: 100%; overflow: hidden; }
        .track { will-change: transform; }
        .wtable th { background: rgba(15,23,42,.92); }
        .wtable td { vertical-align: top; }
        .wtable .evt { font-weight: 700; font-size: 1.7vh; color: #fff; }
        .wtable .evt small { display: block; color: var(--muted); font-weight: 400; font-size: 1.25vh; margin-top: .2vh; }
        .win { margin-bottom: .7vh; }
        .win:last-child { margin-bottom: 0; }
        .win .wn { font-weight: 700; font-size: 1.65vh; color: #fff; }
        .win .wm { font-size: 1.25vh; color: var(--muted); }
        .dash { color: rgba(255,255,255,.25); }
        .pos-first  td.pos { color: var(--gold); }
        .badge { display: inline-block; min-width: 3.2vh; text-align: center; }
        .loading { text-align: center; color: var(--muted); padding: 4vh; font-size: 2vh; }
    </style>
</head>
<body>
<div class="board">
    <!-- Banner -->
    <div class="banner">
        <div class="logo" id="logo"><?= e(strtoupper(mb_substr($institution ?: 'C', 0, 1))) ?></div>
        <div class="titles">
            <div class="meet" id="meetTitle"><?= e($meetTitle) ?></div>
            <div class="inst" id="instName"><?= e($institution) ?></div>
        </div>
        <div class="clock">
            <div class="time" id="clockTime">--:--:--</div>
            <div class="date" id="clockDate">&nbsp;</div>
        </div>
    </div>

    <!-- Two columns 1:3 -->
    <div class="grid">
        <div class="col">
            <div class="panel left-house">
                <div class="head"><span class="dot">🏆</span> House Standings</div>
                <div class="body" id="housePanel"><div class="loading">Loading…</div></div>
            </div>
            <div class="panel left-cd">
                <div class="head"><span class="dot">🎓</span> By Course / Division</div>
                <div class="body" id="cdPanel"><div class="loading">Loading…</div></div>
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
    var REFRESH_MS = 60000;   // one minute
    var SPEED = 28;           // px per second

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
        var max = 0; houses.forEach(function (h) { max = Math.max(max, h.points); });
        var rank = ['🥇','🥈','🥉'];
        return houses.map(function (h, i) {
            var pct = max > 0 ? (h.points / max) * 100 : 0;
            return '<div class="house">'
                + '<div class="rank">' + (rank[i] || (i+1)) + '</div>'
                + '<div class="nm"><span class="swatch" style="background:' + esc(h.color) + '"></span>' + esc(h.name) + '</div>'
                + '<div class="barwrap"><div class="bar" style="width:' + pct + '%;background:' + esc(h.color) + '"></div></div>'
                + '<div class="medals">🥇' + h.golds + ' 🥈' + h.silvers + ' 🥉' + h.bronzes + '</div>'
                + '<div class="pts">' + num(h.points) + '</div>'
                + '</div>';
        }).join('');
    }

    function renderCd(cds) {
        if (!cds.length) return '<div class="loading">No results yet.</div>';
        var rows = cds.map(function (c) {
            return '<tr><td>' + esc(c.label) + '</td><td class="c">' + c.golds + '</td><td class="c">' + c.silvers + '</td><td class="c">' + c.bronzes + '</td><td class="r pts">' + num(c.points) + '</td></tr>';
        }).join('');
        return '<table class="cd-table"><thead><tr><th>Course / Division</th><th class="c">🥇</th><th class="c">🥈</th><th class="c">🥉</th><th class="r">Pts</th></tr></thead><tbody>' + rows + '</tbody></table>';
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
                + '<td class="evt">' + esc(ev.label) + '<small>' + esc(ev.sub) + '</small></td>'
                + '<td>' + cell(ev.first) + '</td>'
                + '<td>' + cell(ev.second) + '</td>'
                + '<td>' + cell(ev.third) + '</td>'
                + '</tr>';
        }).join('');
        return '<table class="wtable"><thead><tr><th style="width:28%">Event Instance</th><th>🥇 First</th><th>🥈 Second</th><th>🥉 Third</th></tr></thead><tbody>' + rows + '</tbody></table>';
    }

    // ---------- Seamless auto-scroll ----------
    var track = document.getElementById('track');
    var scroller = document.getElementById('scroller');
    var offset = 0, copyH = 0, last = null;

    function buildWinners(events) {
        var html = winnersTable(events);
        // Two identical copies stacked for a seamless loop
        track.innerHTML = '<div class="copy">' + html + '</div><div class="copy" aria-hidden="true">' + html + '</div>';
        measure();
    }
    function measure() {
        var copy = track.querySelector('.copy');
        copyH = copy ? copy.offsetHeight : 0;
        if (offset > copyH && copyH > 0) offset = offset % copyH;
    }
    function frame(ts) {
        if (last == null) last = ts;
        var dt = (ts - last) / 1000; last = ts;
        // Only scroll when content overflows the visible area
        if (copyH > scroller.clientHeight + 4) {
            offset += SPEED * dt;
            if (offset >= copyH) offset -= copyH;
        } else {
            offset = 0;
        }
        track.style.transform = 'translateY(' + (-offset) + 'px)';
        requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
    window.addEventListener('resize', measure);

    // ---------- Data load ----------
    function apply(data) {
        if (data.meet && data.meet.title) document.getElementById('meetTitle').textContent = data.meet.title;
        if (data.institution != null) {
            document.getElementById('instName').textContent = data.institution;
            document.getElementById('logo').textContent = (data.institution || 'C').charAt(0).toUpperCase();
        }
        document.getElementById('housePanel').innerHTML = renderHouses(data.houses || []);
        document.getElementById('cdPanel').innerHTML = renderCd(data.courseDivisions || []);
        buildWinners(data.events || []);
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
