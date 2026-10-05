@extends('layouts.app')

@section('title', $workLog->title . ' - Detail Log')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Top Navigation & Action Controls -->
    <div class="flex items-center justify-between">
        <a href="{{ route('logs.index') }}" class="inline-flex items-center gap-2 text-xs font-medium text-slate-400 hover:text-white transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Timeline
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('logs.edit', $workLog->id_work_log) }}" class="px-3.5 py-2 rounded-xl text-xs font-semibold text-indigo-300 bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/30 transition-all flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Edit Log
            </a>
            <form action="{{ route('logs.destroy', $workLog->id_work_log) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus log pekerjaan ini?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-400 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 transition-all flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Hapus
                </button>
            </form>
        </div>
    </div>

    <!-- Main Detail Card -->
    <div class="glass-panel p-6 sm:p-8 rounded-3xl space-y-6">

        <!-- Header: Title, Project, Module, Date, Badges -->
        <div class="space-y-3 pb-6 border-b border-slate-800">
            <div class="flex flex-wrap items-center gap-2">
                @if(($workLog->source ?? 'voice') === 'text')
                    <span class="px-3 py-1 rounded-lg text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40" title="Input dari Teks">
                        ⌨️ Text
                    </span>
                @else
                    <span class="px-3 py-1 rounded-lg text-xs font-semibold bg-indigo-500/20 text-indigo-300 border border-indigo-500/40" title="Input dari Voice Note">
                        🎙️ Voice
                    </span>
                @endif

                @if($workLog->project)
                    <span class="px-3 py-1 rounded-lg text-xs font-mono font-medium bg-slate-800 text-slate-300 border border-slate-700">
                        📁 {{ $workLog->project }}{{ $workLog->module ? ' / ' . $workLog->module : '' }}
                    </span>
                @endif

                @php
                    $typeColors = [
                        'feature' => 'bg-blue-500/20 text-blue-300 border-blue-500/40',
                        'bug_fix' => 'bg-rose-500/20 text-rose-300 border-rose-500/40',
                        'improvement' => 'bg-purple-500/20 text-purple-300 border-purple-500/40',
                        'refactor' => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
                        'other' => 'bg-slate-500/20 text-slate-400 border-slate-500/40',
                    ];
                    $statusColors = [
                        'completed' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
                        'in_progress' => 'bg-blue-500/20 text-blue-300 border-blue-500/40',
                        'blocked' => 'bg-red-500/20 text-red-300 border-red-500/40',
                    ];
                @endphp

                <span class="px-3 py-1 rounded-lg text-xs font-semibold border {{ $typeColors[$workLog->type] ?? $typeColors['other'] }}">
                    {{ ucfirst($workLog->type) }}
                </span>

                <span class="px-3 py-1 rounded-lg text-xs font-semibold border {{ $statusColors[$workLog->status] ?? $statusColors['completed'] }}">
                    {{ ucfirst($workLog->status) }}
                </span>

                <span class="ml-auto text-xs text-slate-400 font-mono">
                    🗓️ {{ $workLog->logged_at ? $workLog->logged_at->format('d M Y') : 'Tanpa Tanggal' }}
                </span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-extrabold text-white leading-tight">
                {{ $workLog->title }}
            </h1>
        </div>

        <!-- Problem Section -->
        @if($workLog->problem)
            <div class="space-y-2">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                    <span>📌</span> Permasalahan / Background
                </h3>
                <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-800 text-sm text-slate-200 leading-relaxed">
                    {{ $workLog->problem }}
                </div>
            </div>
        @endif

        <!-- BEFORE & AFTER Comparison Blocks (As requested) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- BEFORE Block (Light Pink / Red Block) -->
            <div class="p-5 rounded-2xl bg-red-950/30 border-l-4 border-red-500 border-t border-r border-b border-red-900/40 space-y-2 shadow-sm">
                <div class="flex items-center gap-2 text-red-400 font-bold text-sm tracking-wide">
                    <span class="text-lg">❌</span> BEFORE (Kondisi Sebelum)
                </div>
                <p class="text-xs sm:text-sm text-red-100/90 leading-relaxed font-sans">
                    {{ $workLog->before ?: 'Tidak dicantumkan dalam catatan.' }}
                </p>
            </div>

            <!-- AFTER Block (Light Green Block) -->
            <div class="p-5 rounded-2xl bg-emerald-950/30 border-l-4 border-emerald-500 border-t border-r border-b border-emerald-900/40 space-y-2 shadow-sm">
                <div class="flex items-center gap-2 text-emerald-400 font-bold text-sm tracking-wide">
                    <span class="text-lg">✅</span> AFTER (Kondisi Sesudah)
                </div>
                <p class="text-xs sm:text-sm text-emerald-100/90 leading-relaxed font-sans">
                    {{ $workLog->after ?: 'Tidak dicantumkan dalam catatan.' }}
                </p>
            </div>
        </div>

        <!-- WHAT I DID (List of Actions) -->
        <div class="space-y-3 pt-2">
            <h3 class="text-xs font-bold text-indigo-400 uppercase tracking-wider flex items-center gap-2">
                <span>🛠️</span> WHAT I DID (Langkah Pengerjaan)
            </h3>
            @if(!empty($workLog->actions) && is_array($workLog->actions))
                <ul class="space-y-2">
                    @foreach($workLog->actions as $action)
                        <li class="flex items-start gap-3 p-3 rounded-xl bg-slate-900/70 border border-slate-800/80 text-sm text-slate-200">
                            <span class="text-indigo-400 font-bold mt-0.5">•</span>
                            <span class="flex-1">{{ $action }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-xs text-slate-500 italic p-3 rounded-xl bg-slate-900/50">Tidak ada rincian langkah pengerjaan.</p>
            @endif
        </div>

        <!-- IMPACT Section -->
        @if($workLog->impact)
            <div class="space-y-2 pt-2">
                <h3 class="text-xs font-bold text-purple-400 uppercase tracking-wider flex items-center gap-2">
                    <span>🚀</span> IMPACT (Dampak Perubahan)
                </h3>
                <div class="p-4 rounded-2xl bg-purple-950/20 border border-purple-900/40 text-sm text-purple-200 leading-relaxed">
                    {{ $workLog->impact }}
                </div>
            </div>
        @endif

        <!-- Testing, Technologies & Next Step Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
            <!-- Testing -->
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-2">
                <h4 class="text-xs font-bold text-amber-400 uppercase tracking-wider">🧪 Testing</h4>
                @if(!empty($workLog->testing) && is_array($workLog->testing))
                    <ul class="text-xs text-slate-300 space-y-1 list-disc list-inside">
                        @foreach($workLog->testing as $t)
                            <li>{{ $t }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-xs text-slate-500 italic">-</p>
                @endif
            </div>

            <!-- Technologies -->
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-2">
                <h4 class="text-xs font-bold text-sky-400 uppercase tracking-wider">💻 Teknologi</h4>
                @if(!empty($workLog->technologies) && is_array($workLog->technologies))
                    <div class="flex flex-wrap gap-1.5 pt-1">
                        @foreach($workLog->technologies as $tech)
                            <span class="px-2 py-0.5 rounded-md text-xs font-mono bg-sky-950/60 border border-sky-800/60 text-sky-300">
                                {{ $tech }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-500 italic">-</p>
                @endif
            </div>

            <!-- Next Step -->
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-2">
                <h4 class="text-xs font-bold text-emerald-400 uppercase tracking-wider">🎯 Langkah Selanjutnya</h4>
                <p class="text-xs text-slate-300 leading-relaxed">
                    {{ $workLog->next_step ?: '-' }}
                </p>
            </div>
        </div>

        <!-- Tags if present -->
        @if(!empty($workLog->tags) && is_array($workLog->tags))
            <div class="flex items-center gap-2 pt-2">
                <span class="text-xs font-semibold text-slate-400">Tags:</span>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($workLog->tags as $tag)
                        <span class="px-2.5 py-0.5 rounded-full text-xs bg-slate-800 text-slate-300 border border-slate-700">
                            #{{ $tag }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Audio Player -->
        @if($workLog->audio_path)
            <div class="p-4 rounded-2xl bg-indigo-950/20 border border-indigo-900/40 space-y-2">
                <div class="flex items-center gap-2 text-xs font-bold text-indigo-300">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path></svg>
                    Rekaman Audio Asli
                </div>
                <audio controls class="w-full h-10 rounded-xl">
                    <source src="{{ asset($workLog->audio_path) }}" type="audio/webm">
                    <source src="{{ asset($workLog->audio_path) }}" type="audio/mp3">
                    Browser Anda tidak mendukung player audio.
                </audio>
            </div>
        @endif

        <!-- Original Transcript / Text Input Collapsible -->
        @if($workLog->transcript)
            <details class="group rounded-2xl bg-slate-900/60 border border-slate-800/80 overflow-hidden">
                <summary class="p-4 text-xs font-bold text-slate-300 hover:text-white cursor-pointer flex items-center justify-between select-none">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        {{ ($workLog->source ?? 'voice') === 'text' ? 'Teks Asli Input Developer' : 'Transkrip Lengkap Voice Note (AI Gemini)' }}
                    </span>
                    <span class="text-indigo-400 group-open:rotate-180 transition-transform duration-200">▼</span>
                </summary>
                <div class="p-4 border-t border-slate-800/80 bg-slate-950/40 text-xs text-slate-300 font-mono whitespace-pre-wrap leading-relaxed">
                    {{ $workLog->transcript }}
                </div>
            </details>
        @endif

    </div>
</div>
@endsection
