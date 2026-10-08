<script>
(function () {
    const MAX_FILES = 10;
    const MAX_BYTES = 5 * 1024 * 1024;
    const ALLOWED = ['image/png', 'image/jpeg', 'image/webp'];
    const zones = [];
    let active = null;

    function visible(el) { return el.offsetParent !== null; }

    function setup(root) {
        const input = root.querySelector('[data-input]');
        const grid = root.querySelector('[data-previews]');
        const pickBtn = root.querySelector('[data-pick]');
        let files = [];

        function sync() {
            const dt = new DataTransfer();
            files.forEach(f => dt.items.add(f));
            input.files = dt.files;

            grid.innerHTML = '';
            files.forEach((file, i) => {
                const wrap = document.createElement('div');
                wrap.className = 'relative aspect-video rounded-lg overflow-hidden border border-slate-700 bg-slate-900';
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.className = 'w-full h-full object-cover';
                const del = document.createElement('button');
                del.type = 'button';
                del.textContent = '✕';
                del.title = 'Hapus';
                del.className = 'absolute top-1 right-1 w-5 h-5 rounded-full bg-black/70 text-white text-[10px] leading-5 hover:bg-red-600';
                del.addEventListener('click', e => { e.stopPropagation(); files.splice(i, 1); sync(); });
                wrap.append(img, del);
                grid.append(wrap);
            });
        }

        function add(list) {
            for (const f of list) {
                if (!ALLOWED.includes(f.type)) { alert('Format tidak didukung: ' + f.name + '. Gunakan jpg, png, atau webp.'); continue; }
                if (f.size > MAX_BYTES) { alert(f.name + ' lebih dari 5 MB.'); continue; }
                if (files.length >= MAX_FILES) { alert('Maksimal ' + MAX_FILES + ' gambar per kategori.'); break; }
                files.push(f);
            }
            sync();
        }

        pickBtn.addEventListener('click', e => { e.stopPropagation(); input.click(); });
        input.addEventListener('change', () => {
            const picked = Array.from(input.files);
            files = files.slice(); // keep earlier ones
            input.value = '';
            add(picked);
        });

        ['click', 'focus', 'mouseenter'].forEach(ev => root.addEventListener(ev, () => { active = zone; }));
        ['dragenter', 'dragover'].forEach(ev => root.addEventListener(ev, e => { e.preventDefault(); root.classList.add('ring-2', 'ring-indigo-500/50'); }));
        ['dragleave', 'drop'].forEach(ev => root.addEventListener(ev, e => { e.preventDefault(); root.classList.remove('ring-2', 'ring-indigo-500/50'); }));
        root.addEventListener('drop', e => add(Array.from(e.dataTransfer.files)));

        const zone = { root, add };
        zones.push(zone);
    }

    document.querySelectorAll('[data-uploader]').forEach(setup);

    // Paste screenshot from clipboard -> active (or first visible) zone
    document.addEventListener('paste', e => {
        const imgs = Array.from(e.clipboardData ? e.clipboardData.files : []).filter(f => f.type.startsWith('image/'));
        if (!imgs.length) return;
        const target = (active && visible(active.root)) ? active : zones.find(z => visible(z.root));
        if (!target) return;
        e.preventDefault();
        const named = imgs.map((f, i) => new File([f], 'screenshot-' + Date.now() + '-' + i + '.' + (f.type.split('/')[1] || 'png'), { type: f.type }));
        target.add(named);
    });
})();
</script>
