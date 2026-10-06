@extends('layouts.app')

@section('title', 'Cetak QR Laptop')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between no-print">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Cetak QR Laptop</h1>
                <p class="mt-1 text-sm text-slate-500">Dipilih {{ $entries->count() }} laptop · Generate {{ $generatedAt->translatedFormat('d M Y H:i') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase">
                <a href="{{ route('admin.laptops.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-slate-600 hover:bg-slate-50">
                    <i class="fas fa-arrow-left text-blue-500"></i> Kembali
                </a>
                <button type="button" id="qr-print" class="inline-flex items-center gap-2 rounded-lg border border-blue-500 bg-blue-500 px-3 py-2 text-white hover:bg-blue-600">
                    <i class="fas fa-print"></i> Cetak Semua
                </button>
            </div>
        </div>

        <div class="qr-layout">
            {{-- Panel pengaturan --}}
            <aside class="qr-settings no-print" aria-label="Pengaturan cetak">
                <h2 class="qr-settings-title"><i class="fas fa-sliders-h"></i> Pengaturan Cetak</h2>

                <div class="qr-field">
                    <label for="s-paper">Ukuran kertas</label>
                    <select id="s-paper">
                        <option value="A4">A4 (210 × 297 mm)</option>
                        <option value="F4">F4 (215 × 330 mm)</option>
                        <option value="A5">A5 (148 × 210 mm)</option>
                        <option value="Letter">Letter (216 × 279 mm)</option>
                        <option value="custom">Custom…</option>
                    </select>
                </div>
                <div class="qr-row" id="custom-paper" hidden>
                    <div class="qr-field"><label for="s-paper-w">Lebar (mm)</label><input type="number" id="s-paper-w" min="50" step="1"></div>
                    <div class="qr-field"><label for="s-paper-h">Tinggi (mm)</label><input type="number" id="s-paper-h" min="50" step="1"></div>
                </div>

                <div class="qr-field">
                    <label>Orientasi</label>
                    <div class="qr-seg" id="s-orient">
                        <button type="button" data-v="portrait">Portrait</button>
                        <button type="button" data-v="landscape">Landscape</button>
                    </div>
                </div>

                <div class="qr-field">
                    <label for="s-preset">Preset ukuran label</label>
                    <select id="s-preset">
                        <option value="5x3.5">5 × 3.5 cm</option>
                        <option value="4x3">4 × 3 cm</option>
                        <option value="6x4">6 × 4 cm</option>
                        <option value="7x5">7 × 5 cm</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                <div class="qr-row">
                    <div class="qr-field"><label for="s-lw">Lebar label (cm)</label><input type="number" id="s-lw" min="1.5" step="0.1"></div>
                    <div class="qr-field"><label for="s-lh">Tinggi label (cm)</label><input type="number" id="s-lh" min="1.5" step="0.1"></div>
                </div>

                <div class="qr-field">
                    <label>Mode kolom</label>
                    <div class="qr-seg" id="s-mode">
                        <button type="button" data-v="auto">Otomatis</button>
                        <button type="button" data-v="manual">Manual</button>
                    </div>
                </div>
                <div class="qr-field" id="cols-wrap" hidden>
                    <label for="s-cols">Jumlah kolom</label>
                    <input type="number" id="s-cols" min="1" max="12" step="1">
                </div>

                <div class="qr-row">
                    <div class="qr-field"><label for="s-margin">Margin (mm)</label><input type="number" id="s-margin" min="0" max="40" step="1"></div>
                    <div class="qr-field"><label for="s-gap">Jarak label (mm)</label><input type="number" id="s-gap" min="0" max="20" step="0.5"></div>
                </div>

                <div class="qr-field">
                    <label for="s-qr">Ukuran QR: <span id="s-qr-val"></span> cm</label>
                    <input type="range" id="s-qr" min="1" max="6" step="0.1">
                </div>

                <div class="qr-field">
                    <label>Isi label</label>
                    <div class="qr-checks">
                        <label><input type="checkbox" id="s-show-code"> Kode laptop</label>
                        <label><input type="checkbox" id="s-show-name"> Nama pemilik</label>
                        <label><input type="checkbox" id="s-show-nis"> NIS</label>
                        <label><input type="checkbox" id="s-show-border"> Border label</label>
                    </div>
                </div>

                <div class="qr-summary" id="qr-summary" aria-live="polite"></div>
                <div class="qr-warn" id="qr-warn" hidden></div>
                <button type="button" id="qr-reset" class="qr-reset">Reset pengaturan</button>
            </aside>

            {{-- Preview / hasil cetak --}}
            <section class="qr-preview-wrap" id="qr-preview-wrap">
                <div id="qr-sheets"></div>
            </section>
        </div>

        {{-- Sumber label (di-clone oleh JS ke halaman) --}}
        <div id="qr-source" hidden>
            @foreach($entries as $entry)
                <div class="qr-label">
                    <div class="qr-meta qr-top">
                        <p class="qr-code">{{ $entry['laptop']->code }}</p>
                        @if($entry['laptop']->owner)
                            <p class="qr-owner"><strong>{{ \Illuminate\Support\Str::limit($entry['laptop']->owner->name, 40) }}</strong></p>
                        @endif
                    </div>
                    <div class="qr-box">{!! $entry['qrSvg'] !!}</div>
                    <div class="qr-meta qr-bottom">
                        @if($entry['laptop']->owner)
                            <p class="qr-nis"><strong>{{ \Illuminate\Support\Str::limit($entry['laptop']->owner->student_number, 10) }}</strong></p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .qr-layout { display: grid; grid-template-columns: 300px 1fr; gap: 1.25rem; align-items: start; }
        @media (max-width: 991px) { .qr-layout { grid-template-columns: 1fr; } }

        /* Panel pengaturan */
        .qr-settings { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1rem; box-shadow: 0 1px 2px rgba(15,23,42,.05); position: sticky; top: 1rem; max-height: calc(100vh - 2rem); overflow-y: auto; }
        .qr-settings-title { font-size: .95rem; font-weight: 700; color: #1e293b; margin: 0 0 .9rem; }
        .qr-settings-title i { color: #3b82f6; margin-right: .35rem; }
        .qr-field { margin-bottom: .75rem; flex: 1; min-width: 0; }
        .qr-field > label { display: block; font-size: .72rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .03em; margin-bottom: .25rem; }
        .qr-field select, .qr-field input[type=number] { width: 100%; border: 1px solid #cbd5e1; border-radius: .5rem; padding: .4rem .55rem; font-size: .85rem; background: #fff; color: #1e293b; }
        .qr-field select:focus, .qr-field input:focus { outline: 2px solid #93c5fd; border-color: #3b82f6; }
        .qr-field input[type=range] { width: 100%; accent-color: #3b82f6; }
        .qr-row { display: flex; gap: .6rem; }
        .qr-seg { display: flex; border: 1px solid #cbd5e1; border-radius: .5rem; overflow: hidden; }
        .qr-seg button { flex: 1; padding: .4rem; font-size: .8rem; background: #fff; color: #475569; border: 0; transition: background .15s, color .15s; }
        .qr-seg button + button { border-left: 1px solid #cbd5e1; }
        .qr-seg button.active { background: #3b82f6; color: #fff; font-weight: 600; }
        .qr-checks { display: grid; grid-template-columns: 1fr 1fr; gap: .3rem .5rem; }
        .qr-checks label { font-size: .8rem; color: #334155; margin: 0; text-transform: none; letter-spacing: 0; font-weight: 500; }
        .qr-summary { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; border-radius: .6rem; padding: .6rem .7rem; font-size: .8rem; line-height: 1.5; }
        .qr-warn { margin-top: .5rem; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; border-radius: .6rem; padding: .5rem .7rem; font-size: .78rem; }
        .qr-reset { margin-top: .6rem; width: 100%; background: transparent; border: 1px dashed #cbd5e1; color: #64748b; border-radius: .5rem; padding: .35rem; font-size: .78rem; }
        .qr-reset:hover { background: #f8fafc; }

        /* Preview */
        .qr-preview-wrap { background: #e2e8f0; border-radius: 1rem; padding: 1rem; overflow: hidden; min-width: 0; }
        #qr-sheets { zoom: var(--zoom, 1); display: flex; flex-direction: column; align-items: center; gap: 16px; }

        /* Halaman kertas */
        .qr-page { width: var(--paper-w); height: var(--paper-h); padding: var(--margin); background: #fff; box-sizing: border-box; overflow: hidden; box-shadow: 0 4px 16px rgba(15,23,42,.18); flex-shrink: 0; position: relative; }
        .qr-grid { display: grid; grid-template-columns: repeat(var(--cols), var(--label-w)); grid-auto-rows: var(--label-h); gap: var(--gap); justify-content: start; align-content: start; }

        /* Label */
        .qr-label { width: var(--label-w); height: var(--label-h); box-sizing: border-box; padding: 0.5cqmin; display: flex; flex-direction: column; justify-content: space-between; align-items: center; text-align: center; background: #fff; overflow: hidden; container-type: size; break-inside: avoid; page-break-inside: avoid; }
        .qr-label > * { width: 100%; }
        .qr-label.has-border { border: .2mm solid #94a3b8; }
        .qr-meta { line-height: 1.2; flex-shrink: 0; }
        .qr-meta p { margin: 0; }
        .qr-code { font-size: 7cqw; font-weight: 700; letter-spacing: .3em; color: #1e293b; }
        .qr-owner { font-size: 7cqw; color: #475569; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .qr-nis { font-size: 6cqw; color: #475569; }
        .qr-box { flex: 1; min-height: 0; display: flex; align-items: center; justify-content: center; }
        .qr-box svg { width: var(--qr); height: var(--qr); max-width: 100%; max-height: 100%; }
        .qr-label.hide-code .qr-code, .qr-label.hide-name .qr-owner, .qr-label.hide-nis .qr-bottom { display: none; }

        @page { margin: 0; }
        @media print {
            @page { margin: 0; }
            html, body { background: #fff !important; margin: 0 !important; padding: 0 !important; height: auto !important; overflow: visible !important; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print, .main-sidebar, .main-header, .main-footer, .control-sidebar, .content-header, .preloader, .navbar { display: none !important; }
            .wrapper, .content-wrapper, .content, .container-fluid { margin: 0 !important; padding: 0 !important; min-height: 0 !important; background: #fff !important; box-shadow: none !important; width: auto !important; max-width: none !important; }
            .qr-layout { display: block; }
            .qr-preview-wrap { background: #fff; padding: 0; border-radius: 0; overflow: visible; }
            #qr-sheets { zoom: 1 !important; display: block; }
            .qr-page { box-shadow: none; break-after: page; page-break-after: always; height: calc(var(--paper-h) - 0.6mm); }
            .qr-page:last-child { break-after: auto; page-break-after: auto; }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            const PAPERS = { A4: [210, 297], F4: [215, 330], A5: [148, 210], Letter: [216, 279] };
            const KEY = 'qr-bulk-settings-v1';
            const DEFAULTS = {
                paper: 'A4', paperW: 210, paperH: 297, orient: 'portrait',
                preset: '5x3.5', lw: 5, lh: 3.5,
                mode: 'auto', cols: 4, margin: 10, gap: 2,
                qr: 2, code: true, name: true, nis: true, border: true,
            };
            let s = Object.assign({}, DEFAULTS);
            try { Object.assign(s, JSON.parse(localStorage.getItem(KEY) || '{}')); } catch (e) {}

            const $ = (id) => document.getElementById(id);
            const root = document.documentElement;
            const sheets = $('qr-sheets');
            const items = Array.from(document.querySelectorAll('#qr-source > .qr-label'));
            const pageStyle = document.createElement('style');
            document.head.appendChild(pageStyle);

            const num = (v, d) => { v = parseFloat(v); return isFinite(v) && v > 0 ? v : d; };

            function paperSize() {
                let [w, h] = s.paper === 'custom' ? [num(s.paperW, 210), num(s.paperH, 297)] : PAPERS[s.paper];
                if (s.orient === 'landscape') [w, h] = [Math.max(w, h), Math.min(w, h)];
                else [w, h] = [Math.min(w, h), Math.max(w, h)];
                return [w, h];
            }

            function compute() {
                const [pw, ph] = paperSize();
                const margin = Math.max(0, num(s.margin, 0) || 0);
                const gap = Math.max(0, parseFloat(s.gap) || 0);
                const uw = pw - 2 * margin, uh = ph - 2 * margin;
                let lw = num(s.lw, 5) * 10, lh = num(s.lh, 3.5) * 10;
                let cols;
                let warn = '';

                if (s.mode === 'manual') {
                    cols = Math.max(1, Math.floor(num(s.cols, 1)));
                    lw = (uw - gap * (cols - 1)) / cols;
                    if (lw < 15) warn = 'Kolom terlalu banyak, label menjadi terlalu kecil.';
                } else {
                    cols = Math.floor((uw + gap) / (lw + gap));
                }
                let rows = Math.floor((uh + gap) / (lh + gap));

                if (cols < 1 || rows < 1 || lw > uw + 0.01 || lh > uh + 0.01) {
                    warn = 'Ukuran label lebih besar dari area cetak. Perkecil label/margin atau pilih kertas lebih besar.';
                    cols = Math.max(1, cols); rows = Math.max(1, rows);
                    if (lw > uw) lw = uw;
                    if (lh > uh) lh = uh;
                }
                return { pw, ph, margin, gap, lw, lh, cols, rows, perPage: cols * rows, warn };
            }

            function render() {
                const c = compute();
                root.style.setProperty('--paper-w', c.pw + 'mm');
                root.style.setProperty('--paper-h', c.ph + 'mm');
                root.style.setProperty('--margin', c.margin + 'mm');
                root.style.setProperty('--gap', c.gap + 'mm');
                root.style.setProperty('--label-w', c.lw + 'mm');
                root.style.setProperty('--label-h', c.lh + 'mm');
                root.style.setProperty('--cols', c.cols);
                root.style.setProperty('--qr', num(s.qr, 2) + 'cm');

                const orientName = c.pw > c.ph ? 'landscape' : 'portrait';
                pageStyle.textContent = '@page { size: ' + c.pw + 'mm ' + c.ph + 'mm; margin: 0; }';

                sheets.textContent = '';
                const totalPages = Math.max(1, Math.ceil(items.length / c.perPage));
                for (let p = 0; p < totalPages; p++) {
                    const page = document.createElement('div');
                    page.className = 'qr-page';
                    const grid = document.createElement('div');
                    grid.className = 'qr-grid';
                    items.slice(p * c.perPage, (p + 1) * c.perPage).forEach((el) => {
                        const n = el.cloneNode(true);
                        if (s.border) n.classList.add('has-border');
                        if (!s.code) n.classList.add('hide-code');
                        if (!s.name) n.classList.add('hide-name');
                        if (!s.nis) n.classList.add('hide-nis');
                        grid.appendChild(n);
                    });
                    page.appendChild(grid);
                    sheets.appendChild(page);
                }

                $('qr-summary').innerHTML =
                    '<strong>' + c.cols + ' kolom × ' + c.rows + ' baris</strong> = ' + c.perPage + ' label/halaman<br>' +
                    'Total ' + items.length + ' label · <strong>' + totalPages + ' halaman</strong><br>' +
                    'Kertas ' + (s.paper === 'custom' ? 'Custom' : s.paper) + ' ' + orientName + ' (' + c.pw + '×' + c.ph + ' mm) · label ' +
                    (c.lw / 10).toFixed(1) + '×' + (c.lh / 10).toFixed(1) + ' cm';
                const w = $('qr-warn');
                w.hidden = !c.warn;
                w.textContent = c.warn;

                fitPreview(c.pw);
                try { localStorage.setItem(KEY, JSON.stringify(s)); } catch (e) {}
            }

            function fitPreview(paperMm) {
                const wrap = $('qr-preview-wrap');
                const avail = wrap.clientWidth - 32;
                const paperPx = paperMm * 96 / 25.4;
                sheets.style.setProperty('--zoom', Math.min(1, avail / paperPx).toFixed(3));
            }

            function syncInputs() {
                $('s-paper').value = s.paper;
                $('s-paper-w').value = s.paperW; $('s-paper-h').value = s.paperH;
                $('custom-paper').hidden = s.paper !== 'custom';
                $('s-preset').value = s.preset;
                $('s-lw').value = s.lw; $('s-lh').value = s.lh;
                $('s-cols').value = s.cols;
                $('cols-wrap').hidden = s.mode !== 'manual';
                $('s-lw').disabled = s.mode === 'manual';
                $('s-margin').value = s.margin; $('s-gap').value = s.gap;
                $('s-qr').value = s.qr; $('s-qr-val').textContent = num(s.qr, 2).toFixed(1);
                $('s-show-code').checked = s.code; $('s-show-name').checked = s.name;
                $('s-show-nis').checked = s.nis; $('s-show-border').checked = s.border;
                document.querySelectorAll('#s-orient button').forEach(b => b.classList.toggle('active', b.dataset.v === s.orient));
                document.querySelectorAll('#s-mode button').forEach(b => b.classList.toggle('active', b.dataset.v === s.mode));
            }

            function bind(id, key, parse) {
                $(id).addEventListener('input', (e) => {
                    s[key] = parse ? parse(e.target) : e.target.value;
                    if (key === 'lw' || key === 'lh') s.preset = 'custom';
                    if (key === 'paper' && s.paper !== 'custom') { /* keep custom values */ }
                    syncInputs(); render();
                });
            }
            bind('s-paper', 'paper');
            bind('s-paper-w', 'paperW'); bind('s-paper-h', 'paperH');
            bind('s-lw', 'lw'); bind('s-lh', 'lh');
            bind('s-cols', 'cols'); bind('s-margin', 'margin'); bind('s-gap', 'gap');
            bind('s-qr', 'qr');
            bind('s-show-code', 'code', e => e.checked); bind('s-show-name', 'name', e => e.checked);
            bind('s-show-nis', 'nis', e => e.checked); bind('s-show-border', 'border', e => e.checked);

            $('s-preset').addEventListener('change', (e) => {
                s.preset = e.target.value;
                if (s.preset !== 'custom') {
                    const [w, h] = s.preset.split('x').map(parseFloat);
                    s.lw = w; s.lh = h;
                }
                syncInputs(); render();
            });
            document.querySelectorAll('#s-orient button').forEach(b => b.addEventListener('click', () => { s.orient = b.dataset.v; syncInputs(); render(); }));
            document.querySelectorAll('#s-mode button').forEach(b => b.addEventListener('click', () => { s.mode = b.dataset.v; syncInputs(); render(); }));
            $('qr-reset').addEventListener('click', () => { s = Object.assign({}, DEFAULTS); syncInputs(); render(); });
            $('qr-print').addEventListener('click', () => window.print());
            window.addEventListener('resize', () => fitPreview(compute().pw));

            syncInputs();
            render();
        })();
    </script>
@endpush
