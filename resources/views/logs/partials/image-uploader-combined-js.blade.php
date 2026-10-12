<script>
(function () {
    const MAX_PER_KIND = 10;
    const MAX_BYTES = 5 * 1024 * 1024;
    const ALLOWED = ['image/png', 'image/jpeg', 'image/webp'];
    const zones = [];
    let active = null;

    function visible(el) { return el.offsetParent !== null; }

    function setup(root) {
        const inBefore = root.querySelector('[data-input-before]');
        const inAfter = root.querySelector('[data-input-after]');
        const inPick = root.querySelector('[data-input-pick]');
        const grid = root.querySelector('[data-previews]');
        const summary = root.querySelector('[data-summary]');
        const pickBtn = root.querySelector('[data-pick]');
        let items = []; // { file, kind: 'before' | 'after' }

        const count = kind => items.filter(i => i.kind === kind).length;

        function sync() {
            const dtB = new DataTransfer();
            const dtA = new DataTransfer();
            items.forEach(i => (i.kind === 'before' ? dtB : dtA).items.add(i.file));
            inBefore.files = dtB.files;
            inAfter.files = dtA.files;

            grid.innerHTML = '';
            items.forEach((item, idx) => {
                const isBefore = item.kind === 'before';
                const card = document.createElement('div');
                card.className = 'rounded-lg overflow-hidden border-2 bg-slate-900 ' + (isBefore ? 'border-red-500/60' : 'border-emerald-500/60');

                const thumb = document.createElement('div');
                thumb.className = 'relative aspect-video';
                const img = document.createElement('img');
                img.src = URL.createObjectURL(item.file);
                img.className = 'w-full h-full object-cover';
                const del = document.createElement('button');
                del.type = 'button';
                del.textContent = '✕';
                del.title = 'Hapus';
                del.className = 'absolute top-1 right-1 w-5 h-5 rounded-full bg-black/70 text-white text-[10px] leading-5 hover:bg-red-600';
                del.addEventListener('click', e => { e.stopPropagation(); items.splice(idx, 1); sync(); });
                thumb.append(img, del);

                const toggle = document.createElement('div');
                toggle.className = 'grid grid-cols-2 text-[10px] font-bold';
                [['before', 'Before', 'bg-red-600 text-white', 'bg-slate-800 text-slate-400 hover:text-white'],
                 ['after', 'After', 'bg-emerald-600 text-white', 'bg-slate-800 text-slate-400 hover:text-white']].forEach(([k, label, on, off]) => {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.textContent = label;
                    b.className = 'py-1 ' + (item.kind === k ? on : off);
                    b.addEventListener('click', e => {
                        e.stopPropagation();
                        if (item.kind === k) return;
                        if (count(k) >= MAX_PER_KIND) { alert('Maksimal ' + MAX_PER_KIND + ' gambar untuk ' + label + '.'); return; }
                        item.kind = k;
                        sync();
                    });
                    toggle.append(b);
                });

                card.append(thumb, toggle);
                grid.append(card);
            });

            summary.textContent = items.length
                ? items.length + ' gambar: ' + count('before') + ' Before, ' + count('after') + ' After.'
                : '';
        }

        function add(list) {
            for (const f of list) {
                if (!ALLOWED.includes(f.type)) { alert('Format tidak didukung: ' + f.name + '. Gunakan jpg, png, atau webp.'); continue; }
                if (f.size > MAX_BYTES) { alert(f.name + ' lebih dari 5 MB.'); continue; }
                // Gambar pertama = Before; berikutnya mengikuti pilihan gambar terakhir
                let kind = items.length ? items[items.length - 1].kind : 'before';
                if (count(kind) >= MAX_PER_KIND) kind = kind === 'before' ? 'after' : 'before';
                if (count(kind) >= MAX_PER_KIND) { alert('Batas gambar tercapai.'); break; }
                items.push({ file: f, kind });
            }
            sync();
        }

        pickBtn.addEventListener('click', e => { e.stopPropagation(); inPick.click(); });
        inPick.addEventListener('change', () => { const picked = Array.from(inPick.files); inPick.value = ''; add(picked); });

        ['click', 'focus', 'mouseenter'].forEach(ev => root.addEventListener(ev, () => { active = zone; }));
        ['dragenter', 'dragover'].forEach(ev => root.addEventListener(ev, e => { e.preventDefault(); root.classList.add('ring-2', 'ring-indigo-500/50'); }));
        ['dragleave', 'drop'].forEach(ev => root.addEventListener(ev, e => { e.preventDefault(); root.classList.remove('ring-2', 'ring-indigo-500/50'); }));
        root.addEventListener('drop', e => add(Array.from(e.dataTransfer.files)));

        const zone = { root, add };
        zones.push(zone);
    }

    document.querySelectorAll('[data-combined]').forEach(setup);

    // Paste screenshot dari clipboard -> kartu yang sedang aktif / terlihat
    document.addEventListener('paste', e => {
        const imgs = Array.from(e.clipboardData ? e.clipboardData.files : []).filter(f => f.type.startsWith('image/'));
        if (!imgs.length) return;
        const target = (active && visible(active.root)) ? active : zones.find(z => visible(z.root));
        if (!target) return;
        e.preventDefault();
        target.add(imgs.map((f, i) => new File([f], 'screenshot-' + Date.now() + '-' + i + '.' + (f.type.split('/')[1] || 'png'), { type: f.type })));
    });
})();
</script>
