@extends('layouts.app')

@section('title', 'Edit Work Log: ' . $workLog->title)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('logs.show', $workLog->id_work_log) }}" class="inline-flex items-center gap-2 text-xs font-medium text-slate-400 hover:text-white transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Batal & Kembali ke Detail
        </a>
    </div>

    <!-- Edit Form Card -->
    <div class="glass-panel p-6 sm:p-8 rounded-3xl space-y-6">
        <div class="border-b border-slate-800 pb-4">
            <h1 class="text-2xl font-extrabold text-white">Edit Work Log</h1>
            <p class="text-xs text-slate-400 mt-1">Sesuaikan atau lengkapi data hasil ekstraksi AI Gemini jika terdapat ketidaksesuaian.</p>
        </div>

        <form action="{{ route('logs.update', $workLog->id_work_log) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Title -->
            <div>
                <label for="title" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Judul Work Log *</label>
                <input type="text" name="title" id="title" required value="{{ old('title', $workLog->title) }}" class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-medium">
            </div>

            <!-- Project & Module -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="project" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Project</label>
                    <input type="text" name="project" id="project" value="{{ old('project', $workLog->project) }}" placeholder="misal: E-Commerce Web" class="w-full bg-slate-900 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label for="module" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Module</label>
                    <input type="text" name="module" id="module" value="{{ old('module', $workLog->module) }}" placeholder="misal: Payment Gateway" class="w-full bg-slate-900 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <!-- Type, Status & Logged At -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="type" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Kategori (Type) *</label>
                    <select name="type" id="type" required class="w-full bg-slate-900 border border-slate-700 text-slate-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="feature" {{ old('type', $workLog->type) == 'feature' ? 'selected' : '' }}>Feature</option>
                        <option value="bug_fix" {{ old('type', $workLog->type) == 'bug_fix' ? 'selected' : '' }}>Bug Fix</option>
                        <option value="improvement" {{ old('type', $workLog->type) == 'improvement' ? 'selected' : '' }}>Improvement</option>
                        <option value="refactor" {{ old('type', $workLog->type) == 'refactor' ? 'selected' : '' }}>Refactor</option>
                        <option value="other" {{ old('type', $workLog->type) == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Status *</label>
                    <select name="status" id="status" required class="w-full bg-slate-900 border border-slate-700 text-slate-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="completed" {{ old('status', $workLog->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="in_progress" {{ old('status', $workLog->status) == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="blocked" {{ old('status', $workLog->status) == 'blocked' ? 'selected' : '' }}>Blocked</option>
                    </select>
                </div>

                <div>
                    <label for="logged_at" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Tanggal Log *</label>
                    <input type="date" name="logged_at" id="logged_at" required value="{{ old('logged_at', $workLog->logged_at ? $workLog->logged_at->format('Y-m-d') : date('Y-m-d')) }}" class="w-full bg-slate-900 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <!-- Problem -->
            <div>
                <label for="problem" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Permasalahan / Background</label>
                <textarea name="problem" id="problem" rows="3" class="w-full bg-slate-900 border border-slate-700 text-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">{{ old('problem', $workLog->problem) }}</textarea>
            </div>

            <!-- BEFORE & AFTER -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- BEFORE -->
                <div class="p-4 rounded-2xl bg-red-950/20 border border-red-900/40 space-y-2">
                    <label for="before" class="block text-xs font-bold text-red-400 uppercase tracking-wider">❌ BEFORE (Kondisi Sebelum)</label>
                    <textarea name="before" id="before" rows="4" class="w-full bg-slate-900 border border-slate-700 text-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">{{ old('before', $workLog->before) }}</textarea>
                </div>

                <!-- AFTER -->
                <div class="p-4 rounded-2xl bg-emerald-950/20 border border-emerald-900/40 space-y-2">
                    <label for="after" class="block text-xs font-bold text-emerald-400 uppercase tracking-wider">✅ AFTER (Kondisi Sesudah)</label>
                    <textarea name="after" id="after" rows="4" class="w-full bg-slate-900 border border-slate-700 text-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">{{ old('after', $workLog->after) }}</textarea>
                </div>
            </div>

            <!-- WHAT I DID (Actions) -->
            <div>
                <label for="actions_raw" class="block text-xs font-bold text-indigo-400 uppercase tracking-wider mb-1">🛠️ WHAT I DID (Aksi / Pengerjaan - 1 item per baris)</label>
                <textarea name="actions_raw" id="actions_raw" rows="4" placeholder="Satu langkah per baris..." class="w-full bg-slate-900 border border-slate-700 text-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono">{{ old('actions_raw', is_array($workLog->actions) ? implode("\n", $workLog->actions) : '') }}</textarea>
            </div>

            <!-- IMPACT -->
            <div>
                <label for="impact" class="block text-xs font-bold text-purple-400 uppercase tracking-wider mb-2">🚀 IMPACT (Dampak ke User / Bisnis)</label>
                <textarea name="impact" id="impact" rows="3" class="w-full bg-slate-900 border border-slate-700 text-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">{{ old('impact', $workLog->impact) }}</textarea>
            </div>

            <!-- Testing & Technologies -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="testing_raw" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">🧪 Testing (1 item per baris)</label>
                    <textarea name="testing_raw" id="testing_raw" rows="3" class="w-full bg-slate-900 border border-slate-700 text-slate-200 rounded-xl p-3 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">{{ old('testing_raw', is_array($workLog->testing) ? implode("\n", $workLog->testing) : '') }}</textarea>
                </div>

                <div>
                    <label for="technologies_raw" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">💻 Teknologi (dipisah koma atau baris)</label>
                    <textarea name="technologies_raw" id="technologies_raw" rows="3" placeholder="PHP, Laravel, SQLite..." class="w-full bg-slate-900 border border-slate-700 text-slate-200 rounded-xl p-3 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">{{ old('technologies_raw', is_array($workLog->technologies) ? implode(", ", $workLog->technologies) : '') }}</textarea>
                </div>
            </div>

            <!-- Next Step & Tags -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="next_step" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">🎯 Langkah Selanjutnya</label>
                    <input type="text" name="next_step" id="next_step" value="{{ old('next_step', $workLog->next_step) }}" class="w-full bg-slate-900 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label for="tags_raw" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">🏷️ Tags (dipisah koma)</label>
                    <input type="text" name="tags_raw" id="tags_raw" value="{{ old('tags_raw', is_array($workLog->tags) ? implode(", ", $workLog->tags) : '') }}" placeholder="backend, api, hotfix" class="w-full bg-slate-900 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <!-- Transcript -->
            <div>
                <label for="transcript" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">📝 Transkrip Asli Audio</label>
                <textarea name="transcript" id="transcript" rows="5" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-xl p-3 text-xs font-mono focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">{{ old('transcript', $workLog->transcript) }}</textarea>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                <a href="{{ route('logs.show', $workLog->id_work_log) }}" class="px-5 py-2.5 rounded-xl text-xs font-semibold text-slate-300 bg-slate-800 hover:bg-slate-700 transition-all border border-slate-700">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 shadow-lg shadow-indigo-600/30 transition-all">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
