<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Work Log {{ $from }} s/d {{ $to }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1e293b; background: #f1f5f9; margin: 0; line-height: 1.5; font-size: 14px; }
        .page { max-width: 900px; margin: 0 auto; padding: 24px 16px 64px; }
        .toolbar { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 24px; }
        .toolbar form { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
        .toolbar label { display: block; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 4px; }
        .toolbar input, .toolbar select { padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; }
        .btn { display: inline-block; padding: 8px 14px; border-radius: 8px; border: 0; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-green { background: #059669; color: #fff; }
        .btn-gray { background: #e2e8f0; color: #334155; }
        .actions { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }
        h1 { font-size: 24px; margin: 0 0 4px; }
        .meta { color: #64748b; font-size: 13px; margin-bottom: 24px; }
        .log { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .log h2 { font-size: 18px; margin: 0 0 6px; }
        .tags span { display: inline-block; font-size: 11px; padding: 2px 8px; border-radius: 999px; background: #eef2ff; color: #4338ca; margin-right: 4px; }
        h3 { font-size: 12px; text-transform: uppercase; letter-spacing: .05em; margin: 16px 0 6px; color: #475569; }
        .box { padding: 10px 12px; border-radius: 8px; border-left: 4px solid; white-space: pre-line; }
        .before { background: #fef2f2; border-color: #ef4444; }
        .after { background: #f0fdf4; border-color: #22c55e; }
        .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px; }
        figure { margin: 0; break-inside: avoid; page-break-inside: avoid; }
        figure img { width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; display: block; }
        figcaption { font-size: 11px; color: #64748b; margin-top: 3px; }
        ul { margin: 0; padding-left: 20px; }
        .empty { text-align: center; color: #64748b; padding: 48px 0; }
        .sign { margin-top: 32px; font-size: 13px; }
        @media print {
            body { background: #fff; font-size: 12px; }
            .no-print { display: none !important; }
            .page { max-width: none; padding: 0; }
            .log { border: 1px solid #cbd5e1; box-shadow: none; }
            @page { margin: 14mm; }
        }
    </style>
</head>
<body>
<div class="page">

    <div class="toolbar no-print">
        <form method="GET" action="{{ route('logs.report') }}">
            <div><label>Dari</label><input type="date" name="from" value="{{ $from }}"></div>
            <div><label>Sampai</label><input type="date" name="to" value="{{ $to }}"></div>
            <div>
                <label>Project</label>
                <select name="project">
                    <option value="">Semua project</option>
                    @foreach($projects as $p)
                        <option value="{{ $p }}" @selected($project === $p)>{{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div><label>Disusun oleh</label><input type="text" name="nama" value="{{ $preparedBy }}" placeholder="Nama kamu"></div>
            <button type="submit" class="btn btn-primary">Tampilkan</button>
        </form>
        <div class="actions">
            <button type="button" class="btn btn-green" onclick="window.print()">🖨️ Cetak / Simpan sebagai PDF</button>
            <a class="btn btn-gray" href="{{ route('logs.report.zip', request()->query()) }}">⬇ Download ZIP (laporan.md + screenshot)</a>
            <a class="btn btn-gray" href="{{ route('logs.index') }}">← Kembali</a>
        </div>
        <p style="font-size:12px;color:#64748b;margin:10px 0 0">Tips: di dialog cetak pilih "Save as PDF" dan aktifkan "Background graphics" supaya warna Before/After ikut tercetak.</p>
    </div>

    <h1>Laporan Aktivitas Kerja</h1>
    <div class="meta">
        Periode: {{ \Carbon\Carbon::parse($from)->format('d M Y') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
        @if($project) · Project: {{ $project }} @endif
        · Total: {{ $logs->count() }} pekerjaan
        @if($preparedBy) · Disusun oleh: {{ $preparedBy }} @endif
    </div>

    @forelse($logs as $log)
        @php
            $before = $log->images->where('kind', 'before');
            $after = $log->images->where('kind', 'after');
        @endphp
        <article class="log">
            <h2>{{ $loop->iteration }}. {{ $log->title }}</h2>
            <div class="meta" style="margin-bottom:8px">
                {{ $log->logged_at ? $log->logged_at->format('d M Y') : '-' }}
                @if($log->project) · {{ $log->project }}{{ $log->module ? ' / '.$log->module : '' }} @endif
                · {{ ucfirst(str_replace('_', ' ', $log->type)) }} · {{ ucfirst(str_replace('_', ' ', $log->status)) }}
            </div>

            @if($log->problem)
                <h3>Permasalahan</h3>
                <div>{{ $log->problem }}</div>
            @endif

            <h3>Before</h3>
            @if ($log->before)
                <div class="box before">{{ $log->before }}</div>
            @elseif ($before->isEmpty())
                <div class="box before">Tidak dicantumkan.</div>
            @endif
            @if($before->isNotEmpty())
                <div class="grid">
                    @foreach($before as $img)
                        <figure><img src="{{ $img->url }}" alt=""><figcaption>{{ $img->caption }}</figcaption></figure>
                    @endforeach
                </div>
            @endif

            @if(!empty($log->actions))
                <h3>Yang Dikerjakan</h3>
                <ul>@foreach($log->actions as $a)<li>{{ $a }}</li>@endforeach</ul>
            @endif

            <h3>After</h3>
            @if ($log->after)
                <div class="box after">{{ $log->after }}</div>
            @elseif ($after->isEmpty())
                <div class="box after">Tidak dicantumkan.</div>
            @endif
            @if($after->isNotEmpty())
                <div class="grid">
                    @foreach($after as $img)
                        <figure><img src="{{ $img->url }}" alt=""><figcaption>{{ $img->caption }}</figcaption></figure>
                    @endforeach
                </div>
            @endif

            @if($log->impact)
                <h3>Dampak</h3>
                <div>{{ $log->impact }}</div>
            @endif

            @if(!empty($log->testing))
                <h3>Testing</h3>
                <ul>@foreach($log->testing as $t)<li>{{ $t }}</li>@endforeach</ul>
            @endif

            @if(!empty($log->technologies))
                <h3>Teknologi</h3>
                <div class="tags">@foreach($log->technologies as $t)<span>{{ $t }}</span>@endforeach</div>
            @endif
        </article>
    @empty
        <div class="empty">Tidak ada work log pada periode ini.</div>
    @endforelse

</div>
</body>
</html>
