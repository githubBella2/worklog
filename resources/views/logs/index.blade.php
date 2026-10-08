@extends('layouts.app')

@section('title', 'Timeline - Voice Work Log')

@section('content')
    <div class="space-y-6">

        <!-- Page Header & Action Bar -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-800">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Timeline Work Log</h1>
                <p class="text-sm text-slate-400 mt-1">Dokumentasi perubahan teknik berbasis voice note dari pengembang.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('logs.export') }}"
                    class="px-3.5 py-2 rounded-xl text-xs font-semibold text-emerald-300 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/30 transition-all flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Export 1 Minggu (.md)
                </a>
                <a href="{{ route('logs.record') }}"
                    class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 transition-all flex items-center gap-2 shadow-lg shadow-indigo-600/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Tambah Log Baru
                </a>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="glass-panel p-4 rounded-2xl">
            <form method="GET" action="{{ route('logs.index') }}"
                class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
                <!-- Project Filter -->
                <div>
                    <label for="project" class="block text-xs font-medium text-slate-300 mb-1.5">Project</label>
                    <select name="project" id="project" onchange="this.form.submit()"
                        class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Semua Project</option>
                        @foreach ($projects as $p)
                            <option value="{{ $p }}" {{ ($filters['project'] ?? '') == $p ? 'selected' : '' }}>
                                {{ $p }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Type Filter -->
                <div>
                    <label for="type" class="block text-xs font-medium text-slate-300 mb-1.5">Kategori / Type</label>
                    <select name="type" id="type" onchange="this.form.submit()"
                        class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Semua Type</option>
                        <option value="feature" {{ ($filters['type'] ?? '') == 'feature' ? 'selected' : '' }}>Feature
                        </option>
                        <option value="bug_fix" {{ ($filters['type'] ?? '') == 'bug_fix' ? 'selected' : '' }}>Bug Fix
                        </option>
                        <option value="improvement" {{ ($filters['type'] ?? '') == 'improvement' ? 'selected' : '' }}>
                            Improvement</option>
                        <option value="refactor" {{ ($filters['type'] ?? '') == 'refactor' ? 'selected' : '' }}>Refactor
                        </option>
                        <option value="other" {{ ($filters['type'] ?? '') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-xs font-medium text-slate-300 mb-1.5">Status</label>
                    <select name="status" id="status" onchange="this.form.submit()"
                        class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-xs rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Semua Status</option>
                        <option value="completed" {{ ($filters['status'] ?? '') == 'completed' ? 'selected' : '' }}>
                            Completed</option>
                        <option value="in_progress" {{ ($filters['status'] ?? '') == 'in_progress' ? 'selected' : '' }}>In
                            Progress</option>
                        <option value="blocked" {{ ($filters['status'] ?? '') == 'blocked' ? 'selected' : '' }}>Blocked
                        </option>
                    </select>
                </div>

                <!-- Filter Reset / Submit -->
                <div class="flex items-center gap-2">
                    @if (!empty($filters['project']) || !empty($filters['type']) || !empty($filters['status']))
                        <a href="{{ route('logs.index') }}"
                            class="w-full text-center px-3 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium rounded-xl border border-slate-700 transition-all">
                            Reset Filter
                        </a>
                    @else
                        <button type="submit"
                            class="w-full px-3 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium rounded-xl border border-slate-700 transition-all">
                            Terapkan
                        </button>
                    @endif
                </div>
            </form>
        </div>

        <!-- Timeline Content -->
        @if ($groupedLogs->isEmpty())
            <div class="glass-panel p-12 text-center rounded-2xl space-y-4 my-8">
                <div
                    class="w-16 h-16 rounded-full bg-slate-800/80 border border-slate-700 text-slate-400 flex items-center justify-center mx-auto">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-white">Belum Ada Work Log</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Belum ada catatan pekerjaan suara yang tersimpan
                        atau cocok dengan filter Anda.</p>
                </div>
                <a href="{{ route('logs.record') }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Rekam Voice Note Pertama
                </a>
            </div>
        @else
            <div
                class="space-y-8 relative before:absolute before:inset-0 before:left-4 before:md:left-7 before:w-0.5 before:bg-slate-800/80">
                @foreach ($groupedLogs as $dateString => $logs)
                    <div class="space-y-4">
                        <!-- Date Group Header -->
                        <div
                            class="sticky top-20 z-10 flex items-center gap-3 bg-slate-950/90 backdrop-blur py-2 rounded-xl pr-4">
                            <div
                                class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-indigo-600/20 border border-indigo-500/40 text-indigo-400 flex items-center justify-center font-bold text-xs shrink-0 shadow-sm">
                                📅
                            </div>
                            <h2 class="text-sm font-bold text-indigo-300 tracking-wide uppercase">
                                @if ($dateString === 'Tanpa Tanggal')
                                    Tanpa Tanggal
                                @else
                                    {{ \Carbon\Carbon::parse($dateString)->isoFormat('dddd, D MMMM YYYY') }}
                                @endif
                                <span class="ml-2 text-xs font-normal text-slate-500">({{ $logs->count() }} log)</span>
                            </h2>
                        </div>

                        <!-- Log Cards for this date -->
                        <div class="pl-8 md:pl-12 space-y-4">
                            @foreach ($logs as $log)
                                <div
                                    class="glass-panel p-5 rounded-2xl hover:border-indigo-500/40 transition-all duration-200 group">
                                    <div class="flex flex-col md:flex-row md:items-start justify-between gap-3">
                                        <!-- Title & Meta -->
                                        <div class="space-y-1.5 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <!-- Source Badge -->
                                                @if (($log->source ?? 'voice') === 'text')
                                                    <span
                                                        class="px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30"
                                                        title="Input dari Teks">
                                                        ⌨️ Text
                                                    </span>
                                                @else
                                                    <span
                                                        class="px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/30"
                                                        title="Input dari Voice Note">
                                                        🎙️ Voice
                                                    </span>
                                                @endif

                                                <!-- Project & Module Badges -->
                                                @if ($log->project)
                                                    <span
                                                        class="px-2.5 py-0.5 rounded-lg text-xs font-mono font-medium bg-slate-800 text-slate-300 border border-slate-700">
                                                        📁
                                                        {{ $log->project }}{{ $log->module ? ' / ' . $log->module : '' }}
                                                    </span>
                                                @endif

                                                <!-- Type Badge -->
                                                @php
                                                    $typeColors = [
                                                        'feature' => 'bg-blue-500/10 text-blue-400 border-blue-500/30',
                                                        'bug_fix' => 'bg-rose-500/10 text-rose-400 border-rose-500/30',
                                                        'improvement' =>
                                                            'bg-purple-500/10 text-purple-400 border-purple-500/30',
                                                        'refactor' =>
                                                            'bg-amber-500/10 text-amber-400 border-amber-500/30',
                                                        'other' => 'bg-slate-500/10 text-slate-400 border-slate-500/30',
                                                    ];
                                                    $typeLabels = [
                                                        'feature' => 'Feature',
                                                        'bug_fix' => 'Bug Fix',
                                                        'improvement' => 'Improvement',
                                                        'refactor' => 'Refactor',
                                                        'other' => 'Other',
                                                    ];
                                                @endphp
                                                <span
                                                    class="px-2.5 py-0.5 rounded-lg text-xs font-semibold border {{ $typeColors[$log->type] ?? $typeColors['other'] }}">
                                                    {{ $typeLabels[$log->type] ?? ucfirst($log->type) }}
                                                </span>

                                                <!-- Status Badge -->
                                                @php
                                                    $statusColors = [
                                                        'completed' =>
                                                            'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                                                        'in_progress' =>
                                                            'bg-blue-500/10 text-blue-400 border-blue-500/30',
                                                        'blocked' => 'bg-red-500/10 text-red-400 border-red-500/30',
                                                    ];
                                                    $statusLabels = [
                                                        'completed' => '✓ Completed',
                                                        'in_progress' => '⏳ In Progress',
                                                        'blocked' => '🚫 Blocked',
                                                    ];
                                                @endphp
                                                <span
                                                    class="px-2.5 py-0.5 rounded-lg text-xs font-semibold border {{ $statusColors[$log->status] ?? $statusColors['completed'] }}">
                                                    {{ $statusLabels[$log->status] ?? ucfirst($log->status) }}
                                                </span>
                                            </div>

                                            <h3
                                                class="text-base font-bold text-white group-hover:text-indigo-300 transition-colors">
                                                <a href="{{ route('logs.show', $log->id_work_log) }}">
                                                    {{ $log->title }}
                                                </a>
                                            </h3>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="flex items-center gap-2 self-start shrink-0">
                                            <a href="{{ route('logs.show', $log->id_work_log) }}"
                                                class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-300 bg-slate-800 hover:bg-slate-700 hover:text-white transition-all border border-slate-700">
                                                Lihat Detail
                                            </a>
                                            <a href="{{ route('logs.edit', $log->id_work_log) }}"
                                                class="px-3 py-1.5 rounded-lg text-xs font-medium text-indigo-300 bg-indigo-500/10 hover:bg-indigo-500/20 transition-all border border-indigo-500/30">
                                                Edit
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Summary: BEFORE -> AFTER (teks + thumbnail dalam satu kotak) -->
                                    @php
                                        $thumbBefore = $log->images->firstWhere('kind', 'before');
                                        $thumbAfter = $log->images->firstWhere('kind', 'after');
                                    @endphp
                                    <div
                                        class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3 pt-3 border-t border-slate-800/80">
                                        <!-- BEFORE -->
                                        <div class="p-3 rounded-xl bg-red-950/20 border border-red-900/30 text-xs space-y-2">
                                            <div class="font-bold text-red-400 flex items-center gap-1.5">
                                                <span>❌</span> BEFORE
                                                @if (($log->before_images_count ?? 0) > 0)
                                                    <span class="ml-auto font-normal text-slate-400">🖼️ {{ $log->before_images_count }}</span>
                                                @endif
                                            </div>
                                            @if ($log->before)
                                                <p class="text-slate-300 line-clamp-2">{{ $log->before }}</p>
                                            @elseif (!$thumbBefore)
                                                <p class="text-slate-500 italic">Tidak ada deskripsi kondisi sebelum.</p>
                                            @endif
                                            @if ($thumbBefore)
                                                <a href="{{ route('logs.show', $log) }}"
                                                    class="block aspect-video rounded-lg overflow-hidden border border-slate-700 hover:border-indigo-400 transition-all">
                                                    <img src="{{ $thumbBefore->url }}" alt="" loading="lazy"
                                                        class="w-full h-full object-cover">
                                                </a>
                                            @endif
                                        </div>

                                        <!-- AFTER -->
                                        <div class="p-3 rounded-xl bg-emerald-950/20 border border-emerald-900/30 text-xs space-y-2">
                                            <div class="font-bold text-emerald-400 flex items-center gap-1.5">
                                                <span>✅</span> AFTER
                                                @if (($log->after_images_count ?? 0) > 0)
                                                    <span class="ml-auto font-normal text-slate-400">🖼️ {{ $log->after_images_count }}</span>
                                                @endif
                                            </div>
                                            @if ($log->after)
                                                <p class="text-slate-300 line-clamp-2">{{ $log->after }}</p>
                                            @elseif (!$thumbAfter)
                                                <p class="text-slate-500 italic">Tidak ada deskripsi kondisi sesudah.</p>
                                            @endif
                                            @if ($thumbAfter)
                                                <a href="{{ route('logs.show', $log) }}"
                                                    class="block aspect-video rounded-lg overflow-hidden border border-slate-700 hover:border-indigo-400 transition-all">
                                                    <img src="{{ $thumbAfter->url }}" alt="" loading="lazy"
                                                        class="w-full h-full object-cover">
                                                </a>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Actions List snippet if present -->
                                    @if (!empty($log->actions) && is_array($log->actions))
                                        <div class="mt-3 flex items-center gap-2 text-xs text-slate-400">
                                            <span class="font-medium text-slate-500 shrink-0">Aksi
                                                ({{ count($log->actions) }}):</span>
                                            <span class="truncate italic text-slate-300">
                                                {{ implode(' • ', array_slice($log->actions, 0, 2)) }}{{ count($log->actions) > 2 ? ' ...' : '' }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
@endsection
