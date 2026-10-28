@section('title', $recordId ? 'Yorliq dizayni: '.$name : 'Yangi yorliq dizayni')

<div x-data="{ tab: 'design' }">
    @include('partials.lw-flash')

    <div class="tabs">
        <button type="button" :class="{ on: tab === 'design' }" @click="tab = 'design'">Dizayn</button>
        <button type="button" :class="{ on: tab === 'zpl' }" @click="tab = 'zpl'; window.dzPush && window.dzPush(); $wire.$refresh()">ZPL kodi</button>
        <button type="button" :class="{ on: tab === 'test' }" @click="tab = 'test'">Sinov chop etish</button>
    </div>

    {{-- The canvas editor is plain client-side JS; Livewire must not re-render it (wire:ignore). Saving / ZPL / test print go through Livewire. --}}
    <div x-show="tab === 'design'">
        <div wire:ignore class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
            <script type="application/json" id="dz-samples">@json($sampleData)</script>
            <div>
                <div class="mb-3 flex flex-wrap gap-1.5 [&_.btn]:px-2.5 [&_.btn]:py-1.5 [&_.btn]:text-[13px]">
                    <button type="button" class="btn gray" data-add="name">+ Nomi</button>
                    <button type="button" class="btn gray" data-add="price">+ Narx</button>
                    <button type="button" class="btn gray" data-add="barcode">+ Shtrix-kod</button>
                    <button type="button" class="btn gray" data-add="qr">+ QR</button>
                    <button type="button" class="btn gray" data-add="code">+ Kod</button>
                    <button type="button" class="btn gray" data-add="article">+ Artikul</button>
                    <button type="button" class="btn gray" data-add="unit">+ Birlik</button>
                    <button type="button" class="btn gray" data-add="static">+ Matn</button>
                    <button type="button" class="btn gray" data-add="line">+ Chiziq</button>
                    <button type="button" class="btn gray" data-add="box">+ Ramka</button>
                </div>
                <div class="bar !mb-2.5">
                    <label class="text-xs text-ink-muted">Namuna tovar
                        <select id="sample" class="mt-0.5 block">@foreach ($sampleData as $i => $s)<option value="{{ $i }}">{{ $s['name'] }}</option>@endforeach</select>
                    </label>
                    <label class="text-xs text-ink-muted">Masshtab
                        <select id="zoom" class="mt-0.5 block"><option value="5">5 px/mm</option><option value="8" selected>8 px/mm</option><option value="12">12 px/mm</option><option value="16">16 px/mm</option></select>
                    </label>
                    <label class="m-0 flex items-center gap-1.5 text-[13px]"><input type="checkbox" id="preview"> Chop etish ko‘rinishi (yorliq burilishi bilan)</label>
                </div>
                <div id="stage" class="min-h-[260px] overflow-auto rounded-lg bg-[#dfe5ee] p-6"><div id="frame" class="relative mx-auto"><div id="sheet" tabindex="0"></div></div></div>
                <p class="hint">Sudrab joylashtiring · burchakdagi kvadrat — o‘lcham · strelkalar 0,5 mm ga suradi (Shift — 2 mm) · Delete — o‘chirish. Shtrix-kod va QR ko‘rinishi taxminiy, printerda haqiqiysi chiqadi.</p>
            </div>

            <div>
                <div class="panel">
                    <h3>Yorliq</h3>
                    <label>Nomi <input id="dz-name" value="{{ $name }}" required></label>
                    <div class="grid grid-cols-2 gap-x-2.5">
                        <label>Kengligi, mm <input id="W" type="number" step="0.5" min="15" max="200" value="{{ $width_mm }}" required></label>
                        <label>Balandligi, mm <input id="H" type="number" step="0.5" min="10" max="300" value="{{ $height_mm }}" required></label>
                    </div>
                    <div class="grid grid-cols-2 gap-x-2.5">
                        <label>Yorliq burilishi
                            <select id="R">@foreach ([0, 90, 180, 270] as $r)<option value="{{ $r }}" @selected((int) $rotation === $r)>{{ $r }}°</option>@endforeach</select>
                        </label>
                        <label>Printer dpi
                            <select id="dz-dpi">@foreach ([203, 300, 600] as $d)<option value="{{ $d }}" @selected((int) $dpi === $d)>{{ $d }}</option>@endforeach</select>
                        </label>
                    </div>
                    <label>Shrift (ZPL) <input id="dz-font" value="{{ $font }}" required></label>
                    <span class="hint">«0» — printerning ichki shrifti. Kirill / o‘zbek harflari chiqmasa, printerga yuklangan shrift faylini yozing: E:ARIAL.TTF</span>
                </div>

                <div class="panel" id="props"></div>
            </div>
        </div>

        <div class="actions">
            <button type="button" class="btn" id="dz-save">Saqlash</button>
            <a class="btn gray" href="{{ route('admin.labels.index') }}">Ro‘yxat</a>
        </div>
    </div>

    <div x-show="tab === 'zpl'" x-cloak>
        @if ($zpl)
            <p class="hint">Hozirgi dizayn va birinchi namuna tovar uchun hosil bo‘lgan ZPL (saqlamasdan ham ko‘rinadi).</p>
            <pre class="overflow-auto whitespace-pre-wrap break-all rounded-lg bg-[#101828] p-3.5 text-[#d7e3f4]">{{ $zpl }}</pre>
        @else
            <p>Dizaynni ko‘rish uchun elementlar qo‘shing.</p>
        @endif
    </div>

    <div x-show="tab === 'test'" x-cloak>
        @if ($recordId)
            <form class="panel max-w-[480px]" wire:submit="test">
                <p class="hint">Saqlangan dizaynni tanlangan printerga tanlangan tovar bilan yuboradi.</p>
                <label>Printer
                    <select wire:model="printer_id" required>
                        <option value="">— tanlang —</option>
                        @foreach ($printers as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->address }})</option>@endforeach
                    </select>
                </label>
                <label>Tovar
                    <select wire:model="item_id" required>
                        <option value="">— tanlang —</option>
                        @foreach ($samples as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach
                    </select>
                </label>
                <button class="btn" @disabled($printers->isEmpty() || $samples->isEmpty())>Sinov yorlig‘ini chop etish</button>
                @if ($printers->isEmpty())<span class="hint">Avval «Printerlar» bo‘limida printer qo‘shing.</span>@endif
            </form>
        @else
            <p>Avval dizaynni saqlang.</p>
        @endif
    </div>
</div>

@script
<script>
(function () {
    var els = JSON.parse(JSON.stringify($wire.elements || []));
    var samples = JSON.parse(document.getElementById('dz-samples').textContent);
    var PX = 8, sel = -1, drag = null;

    var $ = function (id) { return document.getElementById(id); };
    var sheet = $('sheet'), frame = $('frame'), props = $('props');
    var num = function (v, d) { v = parseFloat(v); return isNaN(v) ? d : v; };
    var W = function () { return num($('W').value, 58); };
    var H = function () { return num($('H').value, 40); };
    var R = function () { return parseInt($('R').value, 10) || 0; };
    var round = function (v) { return Math.round(v * 100) / 100; };
    var esc = function (s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;'); };
    var SOURCES = { name: 'Nomi', price: 'Narx', code: 'Kod', article: 'Artikul', barcode: 'Shtrix-kod', unit: 'Birlik', static: 'Matn' };

    var DEFAULTS = {
        name:    { type: 'text', source: 'name', w: 40, h: 8, size: 3.5 },
        price:   { type: 'text', source: 'price', text: ' so‘m', w: 40, h: 9, size: 7, align: 'R' },
        code:    { type: 'text', source: 'code', w: 25, h: 4, size: 3 },
        article: { type: 'text', source: 'article', w: 25, h: 4, size: 3 },
        unit:    { type: 'text', source: 'unit', w: 15, h: 4, size: 3 },
        static:  { type: 'text', source: 'static', text: 'Matn', w: 25, h: 5, size: 3.5 },
        barcode: { type: 'barcode', source: 'barcode', symbology: 'code128', show_text: true, w: 40, h: 11, size: 2.5, align: 'C' },
        qr:      { type: 'qr', source: 'barcode', w: 14, h: 14, size: 3 },
        line:    { type: 'line', source: 'static', w: 40, h: 0.4, size: 3 },
        box:     { type: 'box', source: 'static', w: 30, h: 12, size: 3, border: 0.3 }
    };

    function sample() { return samples[parseInt($('sample').value, 10) || 0] || samples[0]; }

    function valueOf(e) {
        var s = sample(), suffix = e.text || '';
        switch (e.source) {
            case 'name': return s.name;
            case 'price': return String(Math.round(s.price)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + suffix;
            case 'code': return s.code;
            case 'article': return s.article;
            case 'barcode': return s.barcode;
            case 'unit': return s.unit;
            default: return suffix;
        }
    }

    // deterministic pseudo bars / QR modules from the data, only a preview of the real symbol
    function seeded(str) { var h = 7; for (var i = 0; i < str.length; i++) h = (h * 31 + str.charCodeAt(i)) >>> 0; return function () { h = (h * 1664525 + 1013904223) >>> 0; return h / 4294967296; }; }
    function barsSvg(data) {
        var rnd = seeded(data), x = 0, rects = '';
        for (var i = 0; i < 70; i++) { var w = 1 + Math.floor(rnd() * 3); if (i % 2 === 0) rects += '<rect x="' + x + '" y="0" width="' + w + '" height="100" fill="#000"/>'; x += w; }
        return '<svg viewBox="0 0 ' + x + ' 100" preserveAspectRatio="none">' + rects + '</svg>';
    }
    function qrSvg(data) {
        var rnd = seeded(data), n = 21, rects = '';
        for (var y = 0; y < n; y++) for (var x = 0; x < n; x++) {
            var fx = x < 7 ? x : x > 13 ? x - 14 : -1, fy = y < 7 ? y : y > 13 ? y - 14 : -1;
            var finder = fx >= 0 && fy >= 0 && !(x > 13 && y > 13) && !(x >= 7 && x <= 13 || y >= 7 && y <= 13);
            var on = finder ? (fx === 0 || fx === 6 || fy === 0 || fy === 6 || (fx >= 2 && fx <= 4 && fy >= 2 && fy <= 4)) : rnd() > 0.5;
            if (on) rects += '<rect x="' + x + '" y="' + y + '" width="1" height="1"/>';
        }
        return '<svg viewBox="0 0 ' + n + ' ' + n + '" preserveAspectRatio="none" shape-rendering="crispEdges">' + rects + '</svg>';
    }

    function content(e) {
        var al = { L: 'left', C: 'center', R: 'right' }[e.align] || 'left';
        if (e.type === 'text') return '<div class="txt" style="font-size:' + (e.size * PX) + 'px;text-align:' + al + '">' + esc(valueOf(e)) + '</div>';
        if (e.type === 'barcode') {
            var d = valueOf(e), t = e.show_text ? e.size * 1.2 : 0;
            return '<div style="height:calc(100% - ' + (t * PX) + 'px)">' + barsSvg(d) + '</div>'
                + (e.show_text ? '<div style="height:' + (t * PX) + 'px;font-size:' + (e.size * PX) + 'px;line-height:' + (t * PX) + 'px;text-align:center;overflow:hidden">' + esc(d) + '</div>' : '');
        }
        if (e.type === 'qr') return qrSvg(valueOf(e));
        if (e.type === 'line') return '<div style="width:100%;height:100%;background:#000"></div>';
        if (e.type === 'box') return '<div style="width:100%;height:100%;border:' + Math.max(1, e.border * PX) + 'px solid #000"></div>';
        return '';
    }

    function layout() {
        var w = W(), h = H(), r = R(), pv = $('preview').checked, swap = pv && (r === 90 || r === 270);
        sheet.style.width = (w * PX) + 'px'; sheet.style.height = (h * PX) + 'px';
        sheet.style.backgroundSize = (5 * PX) + 'px ' + (5 * PX) + 'px';
        frame.style.width = ((swap ? h : w) * PX) + 'px'; frame.style.height = ((swap ? w : h) * PX) + 'px';
        sheet.style.transform = pv ? { 0: '', 90: 'translate(' + (h * PX) + 'px,0) rotate(90deg)', 180: 'translate(' + (w * PX) + 'px,' + (h * PX) + 'px) rotate(180deg)', 270: 'translate(0,' + (w * PX) + 'px) rotate(270deg)' }[r] : '';
        document.getElementById('stage').classList.toggle('preview', pv);
    }

    function draw(keepProps) {
        layout();
        sheet.innerHTML = '';
        els.forEach(function (e, i) {
            var d = document.createElement('div');
            d.className = 'el' + (i === sel ? ' sel' : '');
            d.style.cssText = 'left:' + e.x * PX + 'px;top:' + e.y * PX + 'px;width:' + e.w * PX + 'px;height:' + e.h * PX + 'px;transform:rotate(' + (e.rotation || 0) + 'deg)';
            d.dataset.i = i;
            d.innerHTML = content(e) + '<span class="grip"></span>';
            sheet.appendChild(d);
        });
        keepProps === true || drawProps();
    }

    function field(label, html) { return '<label>' + label + html + '</label>'; }
    function drawProps() {
        var e = els[sel];
        if (!e) { props.innerHTML = '<h3>Element</h3><p class="hint">Elementni tanlang yoki yuqoridan qo‘shing.</p>'; return; }
        var h = '<h3>Element: ' + (e.type === 'text' || e.type === 'barcode' ? (SOURCES[e.source] || e.type) : e.type) + '</h3>';
        if (e.type === 'text' || e.type === 'barcode' || e.type === 'qr') {
            var opts = Object.keys(SOURCES).filter(function (k) { return e.type === 'text' || k === 'barcode' || k === 'code'; });
            h += field('Ma’lumot', '<select data-p="source">' + opts.map(function (k) { return '<option value="' + k + '"' + (e.source === k ? ' selected' : '') + '>' + SOURCES[k] + '</option>'; }).join('') + '</select>');
        }
        if (e.type === 'text') h += field(e.source === 'static' ? 'Matn' : 'Qo‘shimcha matn (oxirida)', '<input data-p="text" value="' + esc(e.text || '') + '">');
        h += '<div class="row4">' + [['x', 'X'], ['y', 'Y'], ['w', 'Eni'], ['h', 'Bo‘yi']].map(function (f) { return field(f[1], '<input data-p="' + f[0] + '" type="number" step="0.5" value="' + e[f[0]] + '">'); }).join('') + '</div>';
        if (e.type === 'text' || e.type === 'barcode') h += field('Matn balandligi, mm', '<input data-p="size" type="number" step="0.5" min="1" value="' + e.size + '">');
        if (e.type === 'text' || e.type === 'barcode') h += field('Tekislash', '<select data-p="align"><option value="L"' + (e.align === 'L' ? ' selected' : '') + '>Chapga</option><option value="C"' + (e.align === 'C' ? ' selected' : '') + '>Markazga</option><option value="R"' + (e.align === 'R' ? ' selected' : '') + '>O‘ngga</option></select>');
        if (e.type === 'barcode') h += field('Turi', '<select data-p="symbology"><option value="code128"' + (e.symbology === 'code128' ? ' selected' : '') + '>Code 128</option><option value="ean13"' + (e.symbology === 'ean13' ? ' selected' : '') + '>EAN-13</option></select>')
            + '<label><input type="checkbox" data-p="show_text"' + (e.show_text ? ' checked' : '') + '> Raqamni ostida ko‘rsatish</label>';
        if (e.type === 'box') h += field('Chiziq qalinligi, mm', '<input data-p="border" type="number" step="0.1" min="0.1" value="' + e.border + '">');
        h += field('Burilish', '<div class="seg"><button type="button" class="btn gray" data-rot="-90" title="Chapga 90°">⟲</button>'
            + '<select data-p="rotation">' + [0, 90, 180, 270].map(function (r) { return '<option value="' + r + '"' + (e.rotation === r ? ' selected' : '') + '>' + r + '°</option>'; }).join('') + '</select>'
            + '<button type="button" class="btn gray" data-rot="90" title="O‘ngga 90°">⟳</button></div>');
        h += '<div class="actions"><button type="button" class="btn gray" data-act="dup">Nusxa</button><button type="button" class="btn gray" data-act="front">Oldinga</button><button type="button" class="btn gray" data-act="back">Orqaga</button><button type="button" class="btn red" data-act="del">O‘chirish</button></div>';
        props.innerHTML = h;
    }

    function add(kind) {
        var base = JSON.parse(JSON.stringify(DEFAULTS[kind]));
        els.push(Object.assign({ type: 'text', source: 'static', text: '', x: 2, y: 2, w: 20, h: 6, size: 3, align: 'L', rotation: 0, symbology: 'code128', show_text: false, border: 0.3 }, base));
        sel = els.length - 1; draw();
    }

    // ---- events
    document.querySelectorAll('[data-add]').forEach(function (b) { b.addEventListener('click', function () { add(b.dataset.add); }); });
    ['W', 'H', 'R', 'sample', 'preview'].forEach(function (id) { $(id).addEventListener('input', draw); $(id).addEventListener('change', draw); });
    $('zoom').addEventListener('change', function () { PX = parseInt(this.value, 10); draw(); });

    props.addEventListener('input', function (ev) {
        var p = ev.target.dataset.p, e = els[sel]; if (!p || !e) return;
        var v = ev.target.type === 'checkbox' ? ev.target.checked : ev.target.value;
        e[p] = ['x', 'y', 'w', 'h', 'size', 'border'].indexOf(p) >= 0 ? num(v, e[p]) : p === 'rotation' ? parseInt(v, 10) : v;
        if (p === 'w' || p === 'h') { e[p] = Math.max(0.2, e[p]); }
        draw(true); // keep the panel (and the focus in it) while typing
    });
    props.addEventListener('change', function (ev) { if (ev.target.dataset.p === 'source' || ev.target.dataset.p === 'rotation' || ev.target.dataset.p === 'symbology' || ev.target.dataset.p === 'show_text' || ev.target.dataset.p === 'align') draw(); });
    props.addEventListener('click', function (ev) {
        var b = ev.target.closest('button'); if (!b || sel < 0) return;
        var e = els[sel];
        if (b.dataset.rot) { e.rotation = ((e.rotation || 0) + parseInt(b.dataset.rot, 10) + 360) % 360; draw(); }
        else if (b.dataset.act === 'dup') { var c = JSON.parse(JSON.stringify(e)); c.x += 2; c.y += 2; els.push(c); sel = els.length - 1; draw(); }
        else if (b.dataset.act === 'del') { els.splice(sel, 1); sel = -1; draw(); }
        else if (b.dataset.act === 'front' && sel < els.length - 1) { els.splice(sel + 1, 0, els.splice(sel, 1)[0]); sel++; draw(); }
        else if (b.dataset.act === 'back' && sel > 0) { els.splice(sel - 1, 0, els.splice(sel, 1)[0]); sel--; draw(); }
    });

    sheet.addEventListener('pointerdown', function (ev) {
        var t = ev.target.closest('.el');
        if (!t) { sel = -1; draw(); return; }
        var i = parseInt(t.dataset.i, 10), e = els[i];
        if (sel !== i) { sel = i; draw(); t = sheet.querySelector('[data-i="' + i + '"]'); }
        drag = { i: i, mode: ev.target.classList.contains('grip') ? 'size' : 'move', sx: ev.clientX, sy: ev.clientY, x: e.x, y: e.y, w: e.w, h: e.h };
        sheet.setPointerCapture(ev.pointerId); ev.preventDefault(); sheet.focus();
    });
    sheet.addEventListener('pointermove', function (ev) {
        if (!drag) return;
        var e = els[drag.i], dx = (ev.clientX - drag.sx) / PX, dy = (ev.clientY - drag.sy) / PX, snap = ev.altKey ? 0.1 : 0.5;
        var q = function (v) { return round(Math.round(v / snap) * snap); };
        if (drag.mode === 'move') { e.x = q(drag.x + dx); e.y = q(drag.y + dy); }
        else { // resize along the element's own (rotated) axes
            var a = (e.rotation || 0) * Math.PI / 180, lx = dx * Math.cos(a) + dy * Math.sin(a), ly = -dx * Math.sin(a) + dy * Math.cos(a);
            e.w = Math.max(0.5, q(drag.w + lx)); e.h = Math.max(0.5, q(drag.h + ly));
        }
        draw();
    });
    sheet.addEventListener('pointerup', function () { drag = null; });
    sheet.addEventListener('keydown', function (ev) {
        var e = els[sel]; if (!e) return;
        var s = ev.shiftKey ? 2 : 0.5;
        if (ev.key === 'ArrowLeft') e.x = round(e.x - s); else if (ev.key === 'ArrowRight') e.x = round(e.x + s);
        else if (ev.key === 'ArrowUp') e.y = round(e.y - s); else if (ev.key === 'ArrowDown') e.y = round(e.y + s);
        else if (ev.key === 'Delete') { els.splice(sel, 1); sel = -1; }
        else return;
        ev.preventDefault(); draw();
    });

    // Push the editor state into the Livewire component (no request yet); it is sent with the next action.
    function push() {
        $wire.elements = JSON.parse(JSON.stringify(els));
        $wire.name = $('dz-name').value;
        $wire.width_mm = $('W').value;
        $wire.height_mm = $('H').value;
        $wire.rotation = $('R').value;
        $wire.dpi = $('dz-dpi').value;
        $wire.font = $('dz-font').value;
    }
    window.dzPush = push; // the ZPL tab calls this before refreshing
    $('dz-save').addEventListener('click', function () { push(); $wire.save(); });
    draw();
})();
</script>
@endscript