<div id="lightbox" class="hidden fixed inset-0 z-[60] bg-black/90 flex flex-col items-center justify-center p-4">
    <button type="button" id="lbClose" class="absolute top-4 right-4 w-10 h-10 rounded-full bg-slate-800 text-white text-lg hover:bg-slate-700">✕</button>
    <button type="button" id="lbPrev" class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-slate-800 text-white text-lg hover:bg-slate-700">‹</button>
    <button type="button" id="lbNext" class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-slate-800 text-white text-lg hover:bg-slate-700">›</button>
    <img id="lbImg" src="" alt="" class="max-h-[80vh] max-w-full rounded-lg shadow-2xl">
    <div class="mt-3 text-center space-y-1">
        <div id="lbCaption" class="text-sm text-slate-200"></div>
        <a id="lbDownload" href="#" download class="inline-block text-xs text-indigo-300 hover:text-indigo-200 underline">⬇ Download gambar</a>
    </div>
</div>
<script>
(function () {
    const items = Array.from(document.querySelectorAll('[data-lightbox]'));
    if (!items.length) return;
    const box = document.getElementById('lightbox');
    const img = document.getElementById('lbImg');
    const cap = document.getElementById('lbCaption');
    const dl = document.getElementById('lbDownload');
    let idx = 0;

    function show(i) {
        idx = (i + items.length) % items.length;
        const el = items[idx];
        img.src = el.dataset.src;
        dl.href = el.dataset.src;
        cap.textContent = (el.dataset.group === 'before' ? 'BEFORE' : 'AFTER') + (el.dataset.caption ? ' — ' + el.dataset.caption : '') + '  (' + (idx + 1) + '/' + items.length + ')';
        box.classList.remove('hidden');
    }
    function close() { box.classList.add('hidden'); img.src = ''; }

    items.forEach((el, i) => el.addEventListener('click', () => show(i)));
    document.getElementById('lbClose').addEventListener('click', close);
    document.getElementById('lbPrev').addEventListener('click', () => show(idx - 1));
    document.getElementById('lbNext').addEventListener('click', () => show(idx + 1));
    box.addEventListener('click', e => { if (e.target === box) close(); });
    document.addEventListener('keydown', e => {
        if (box.classList.contains('hidden')) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowLeft') show(idx - 1);
        if (e.key === 'ArrowRight') show(idx + 1);
    });
})();
</script>
