@extends('layouts.app')

@section('title', 'Rekam Voice Work Log')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('logs.index') }}" class="inline-flex items-center gap-2 text-xs font-medium text-slate-400 hover:text-white transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Timeline
        </a>
    </div>

    <!-- Record Card Header -->
    <div class="glass-panel p-6 sm:p-8 rounded-3xl space-y-6 text-center relative overflow-hidden">
        <div class="space-y-2 max-w-lg mx-auto">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Rekam Voice Work Log</h1>
            <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                Ceritakan apa yang kamu kerjakan dalam bahasa Indonesia atau Inggris (masalah, sebelum, sesudah, dampak, dsb). Gemini AI akan otomatis mengubahnya menjadi dokumentasi terstruktur!
            </p>
        </div>

        <!-- Recording Indicator & Timer -->
        <div class="py-6 space-y-4">
            <!-- Timer Display -->
            <div id="timer" class="font-mono text-4xl sm:text-5xl font-extrabold text-slate-400 tracking-wider">
                00:00
            </div>
            <div id="statusBadge" class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-800 text-slate-400 text-xs font-medium">
                <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                Siap Merekam
            </div>
        </div>

        <!-- Record / Stop Button -->
        <div class="flex flex-col items-center justify-center gap-4">
            <button type="button" id="recordBtn" class="w-24 h-24 rounded-full bg-gradient-to-tr from-rose-600 to-red-500 hover:from-rose-500 hover:to-red-400 text-white flex items-center justify-center shadow-xl shadow-red-600/30 hover:scale-105 transition-all duration-200 focus:outline-none">
                <svg id="micIcon" class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/><path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>
                <svg id="stopIcon" class="w-10 h-10 hidden" fill="currentColor" viewBox="0 0 24 24"><path d="M6 6h12v12H6z"/></svg>
            </button>
            <p id="recordHint" class="text-xs text-slate-400 font-medium">Klik tombol merah di atas untuk mulai merekam</p>
        </div>

        <!-- Audio Preview & Upload Form -->
        <div id="previewArea" class="hidden space-y-4 pt-6 border-t border-slate-800/80 text-left">
            <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-2">
                <span>🎧</span> Preview Hasil Rekaman
            </h3>
            
            <audio id="audioPlayback" controls class="w-full h-10 rounded-xl bg-slate-900 border border-slate-700"></audio>

            <form id="uploadForm" action="{{ route('logs.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 pt-2">
                @csrf
                <input type="file" id="audioFileInput" name="audio" class="hidden" accept="audio/*">

                <div class="flex items-center gap-3">
                    <button type="button" id="reRecordBtn" class="flex-1 py-3 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs border border-slate-700 transition-all">
                        🔄 Rekam Ulang
                    </button>
                    <button type="submit" id="submitBtn" class="flex-1 py-3 px-4 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition-all">
                        🚀 Process & Extract with Gemini AI
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tips Card -->
    <div class="glass-panel p-5 rounded-2xl space-y-2 text-xs text-slate-400">
        <div class="font-bold text-slate-200 flex items-center gap-2">
            💡 Tips Pengucapan Voice Note:
        </div>
        <ul class="list-disc list-inside space-y-1 text-slate-300">
            <li>Sebutkan nama **Project** dan **Module** (misal: "Project e-commerce, module checkout").</li>
            <li>Jelaskan **Permasalahan** & **Kondisi Sebelum** ("Sebelumnya tombol bayar error 500 saat diskon").</li>
            <li>Jelaskan **Aksi / Yang Dikerjakan** ("Saya memperbaiki validasi voucher dan menambah error logging").</li>
            <li>Sebutkan **Kondisi Setelah** & **Impact** ("Sekarang pembayaran lancar dan respon 20% lebih cepat").</li>
        </ul>
    </div>
</div>

<!-- Fullscreen Processing Overlay / Loading State -->
<div id="loadingOverlay" class="fixed inset-0 z-50 bg-slate-950/90 backdrop-blur-md hidden flex flex-col items-center justify-center p-6 text-center space-y-6">
    <div class="relative w-24 h-24 flex items-center justify-center">
        <!-- Glowing animated outer ring -->
        <div class="absolute inset-0 rounded-full border-4 border-indigo-500/20 border-t-indigo-500 animate-spin"></div>
        <div class="absolute inset-2 rounded-full border-4 border-purple-500/20 border-b-purple-500 animate-spin" style="animation-direction: reverse; animation-duration: 1.5s;"></div>
        <!-- Gemini Icon -->
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center shadow-lg shadow-purple-500/40">
            <svg class="w-6 h-6 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
        </div>
    </div>
    
    <div class="space-y-2 max-w-sm">
        <h3 class="text-xl font-extrabold text-white">Gemini AI sedang Menganalisis...</h3>
        <p class="text-xs text-slate-400">Audio sedang diunggah, ditranskripkan, dan diekstrak menjadi dokumentasi perubahan (Before & After).</p>
    </div>

    <div class="flex items-center gap-2 px-4 py-2 rounded-full bg-slate-900 border border-slate-800 text-xs font-mono text-indigo-400">
        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-ping"></span>
        Processing Voice Note...
    </div>
</div>

@endsection

@section('scripts')
<script>
    let mediaRecorder = null;
    let audioChunks = [];
    let isRecording = false;
    let timerInterval = null;
    let secondsElapsed = 0;
    let recordedBlob = null;

    const recordBtn = document.getElementById('recordBtn');
    const micIcon = document.getElementById('micIcon');
    const stopIcon = document.getElementById('stopIcon');
    const statusBadge = document.getElementById('statusBadge');
    const timerDisplay = document.getElementById('timer');
    const recordHint = document.getElementById('recordHint');
    const previewArea = document.getElementById('previewArea');
    const audioPlayback = document.getElementById('audioPlayback');
    const audioFileInput = document.getElementById('audioFileInput');
    const reRecordBtn = document.getElementById('reRecordBtn');
    const uploadForm = document.getElementById('uploadForm');
    const loadingOverlay = document.getElementById('loadingOverlay');

    function updateTimer() {
        secondsElapsed++;
        const mins = String(Math.floor(secondsElapsed / 60)).padStart(2, '0');
        const secs = String(secondsElapsed % 60).padStart(2, '0');
        timerDisplay.textContent = `${mins}:${secs}`;
    }

    recordBtn.addEventListener('click', async () => {
        if (!isRecording) {
            // Start Recording
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];

                mediaRecorder.ondataavailable = (e) => {
                    if (e.data.size > 0) {
                        audioChunks.push(e.data);
                    }
                };

                mediaRecorder.onstop = () => {
                    recordedBlob = new Blob(audioChunks, { type: mediaRecorder.mimeType || 'audio/webm' });
                    const audioUrl = URL.createObjectURL(recordedBlob);
                    audioPlayback.src = audioUrl;
                    previewArea.classList.remove('hidden');

                    // Set file into file input via DataTransfer
                    const file = new File([recordedBlob], `voice_log_${Date.now()}.webm`, { type: recordedBlob.type });
                    const container = new DataTransfer();
                    container.items.add(file);
                    audioFileInput.files = container.files;
                };

                mediaRecorder.start();
                isRecording = true;

                // UI Updates
                micIcon.classList.add('hidden');
                stopIcon.classList.remove('hidden');
                recordBtn.classList.add('pulse-ring', 'from-red-700', 'to-rose-600');
                statusBadge.className = 'inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-500/20 text-red-300 border border-red-500/30 text-xs font-semibold';
                statusBadge.innerHTML = '<span class="w-2 h-2 rounded-full bg-red-500 animate-ping"></span> Merekam Audio...';
                timerDisplay.classList.remove('text-slate-400');
                timerDisplay.classList.add('text-red-400');
                recordHint.textContent = 'Klik tombol untuk stop perekaman';

                secondsElapsed = 0;
                timerDisplay.textContent = '00:00';
                timerInterval = setInterval(updateTimer, 1000);

            } catch (err) {
                alert('Gagal mengakses mikrofon! Pastikan izin mikrofon diberikan di browser Anda. Detail: ' + err.message);
            }
        } else {
            // Stop Recording
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
                mediaRecorder.stream.getTracks().forEach(track => track.stop());
            }

            isRecording = false;
            clearInterval(timerInterval);

            // UI Updates
            micIcon.classList.remove('hidden');
            stopIcon.classList.add('hidden');
            recordBtn.classList.remove('pulse-ring', 'from-red-700', 'to-rose-600');
            statusBadge.className = 'inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-semibold';
            statusBadge.innerHTML = '✓ Perekaman Selesai';
            timerDisplay.classList.remove('text-red-400');
            timerDisplay.classList.add('text-slate-300');
            recordHint.textContent = 'Dengarkan hasil rekaman atau klik rekam ulang';
        }
    });

    reRecordBtn.addEventListener('click', () => {
        previewArea.classList.add('hidden');
        audioPlayback.src = '';
        secondsElapsed = 0;
        timerDisplay.textContent = '00:00';
        statusBadge.className = 'inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-800 text-slate-400 text-xs font-medium';
        statusBadge.innerHTML = '<span class="w-2 h-2 rounded-full bg-slate-500"></span> Siap Merekam';
        recordHint.textContent = 'Klik tombol merah di atas untuk mulai merekam';
    });

    uploadForm.addEventListener('submit', (e) => {
        if (!audioFileInput.files || audioFileInput.files.length === 0) {
            e.preventDefault();
            alert('Belum ada rekaman audio untuk dikirim!');
            return;
        }

        // Show loading overlay
        loadingOverlay.classList.remove('hidden');
    });
</script>
@endsection
