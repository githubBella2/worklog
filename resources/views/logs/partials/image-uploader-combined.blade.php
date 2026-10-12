{{-- Satu kartu upload untuk Before & After. Tiap gambar punya tombol pilihan Before/After.
     Props: $form (opsional, id form tujuan bila kartu ini berada di luar <form>) --}}
<div data-combined tabindex="0"
     class="rounded-2xl border border-dashed border-indigo-500/40 bg-indigo-950/10 p-4 text-left space-y-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 transition-all">
    <div class="flex items-center justify-between gap-2">
        <div class="text-xs font-bold text-indigo-300">
            🖼️ Bukti Screenshot
            <span class="font-normal text-slate-500">(opsional, maks 10 Before + 10 After)</span>
        </div>
        <button type="button" data-pick class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-200 bg-slate-800 hover:bg-slate-700 border border-slate-700">
            + Pilih gambar
        </button>
    </div>

    {{-- Input tersembunyi yang ikut terkirim ke server --}}
    <input type="file" data-input-before name="before_images[]" multiple class="hidden" @if(!empty($form)) form="{{ $form }}" @endif>
    <input type="file" data-input-after name="after_images[]" multiple class="hidden" @if(!empty($form)) form="{{ $form }}" @endif>
    {{-- Input pemilih file (tanpa name, tidak ikut terkirim) --}}
    <input type="file" data-input-pick multiple accept="image/png,image/jpeg,image/webp" class="hidden">

    <p class="text-[11px] text-slate-500 leading-relaxed">
        Drag &amp; drop gambar ke sini, atau klik kotak ini lalu tekan
        <kbd class="px-1 rounded bg-slate-800 border border-slate-700">Ctrl</kbd>+<kbd class="px-1 rounded bg-slate-800 border border-slate-700">V</kbd>
        untuk paste screenshot. Lalu tentukan tiap gambar sebagai <span class="text-red-300 font-semibold">Before</span> atau <span class="text-emerald-300 font-semibold">After</span>.
    </p>

    <div data-previews class="grid grid-cols-2 sm:grid-cols-4 gap-2"></div>
    <p data-summary class="text-[11px] text-slate-400"></p>
</div>
