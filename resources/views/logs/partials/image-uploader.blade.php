{{-- Props: $kind ('before'|'after'), $form (optional form id for the file input) --}}
@php
    $isBefore = $kind === 'before';
    $theme = $isBefore
        ? ['border' => 'border-red-500/40', 'bg' => 'bg-red-950/20', 'text' => 'text-red-400', 'icon' => '❌', 'title' => 'Screenshot BEFORE']
        : ['border' => 'border-emerald-500/40', 'bg' => 'bg-emerald-950/20', 'text' => 'text-emerald-400', 'icon' => '✅', 'title' => 'Screenshot AFTER'];
@endphp
<div data-uploader tabindex="0"
     class="rounded-2xl border border-dashed {{ $theme['border'] }} {{ $theme['bg'] }} p-4 text-left space-y-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 transition-all">
    <div class="flex items-center justify-between gap-2">
        <div class="text-xs font-bold {{ $theme['text'] }}">
            {{ $theme['icon'] }} {{ $theme['title'] }}
            <span class="font-normal text-slate-500">(opsional, maks 10)</span>
        </div>
        <button type="button" data-pick class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-200 bg-slate-800 hover:bg-slate-700 border border-slate-700">
            + Pilih gambar
        </button>
    </div>
    <input type="file" data-input name="{{ $kind }}_images[]" multiple
           accept="image/png,image/jpeg,image/webp" class="hidden"
           @if(!empty($form)) form="{{ $form }}" @endif>
    <p class="text-[11px] text-slate-500 leading-relaxed">
        Drag &amp; drop gambar ke sini, atau klik kotak ini lalu tekan <kbd class="px-1 rounded bg-slate-800 border border-slate-700">Ctrl</kbd>+<kbd class="px-1 rounded bg-slate-800 border border-slate-700">V</kbd> untuk paste screenshot dari clipboard.
    </p>
    <div data-previews class="grid grid-cols-3 sm:grid-cols-5 gap-2"></div>
</div>
