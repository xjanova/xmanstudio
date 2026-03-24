@extends('layouts.admin')

@section('title', 'Aipray Recorder')
@section('page-title', 'Aipray - Audio Recorder')

@section('content')
{{-- Navigation Tabs --}}
<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex space-x-6 overflow-x-auto">
        <a href="{{ route('admin.aipray.dashboard') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Dashboard</a>
        <a href="{{ route('admin.aipray.dataset.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Dataset</a>
        <a href="{{ route('admin.aipray.record.index') }}" class="whitespace-nowrap border-b-2 border-yellow-500 pb-3 px-1 text-sm font-medium text-yellow-600">Record</a>
        <a href="{{ route('admin.aipray.training.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Training</a>
        <a href="{{ route('admin.aipray.models.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Models</a>
        <a href="{{ route('admin.aipray.evaluate.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Evaluate</a>
        <a href="{{ route('admin.aipray.chants.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Chants</a>
        <a href="{{ route('admin.aipray.donations.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Donations</a>
    </nav>
</div>

{{-- Header --}}
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-yellow-700 via-yellow-600 to-amber-500 p-8 mb-8 shadow-xl">
    <div class="absolute top-0 right-0 -mt-16 -mr-16 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
    <div class="relative">
        <h1 class="text-2xl md:text-3xl font-bold text-white">Audio Recorder</h1>
        <p class="text-yellow-100 text-lg">Record chant audio samples directly from your browser</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    {{-- Left: Chant & Line Selection --}}
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Select Chant</h3>

            <div class="space-y-4">
                <div>
                    <label for="chant-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Chant</label>
                    <select id="chant-select" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-yellow-500 focus:border-yellow-500 text-sm">
                        <option value="">-- Select a Chant --</option>
                        @foreach($chants as $chant)
                            <option value="{{ $chant->chant_id }}" data-lines="{{ json_encode($chant->lines ?? []) }}">
                                {{ $chant->title_th }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="line-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Line</label>
                    <select id="line-select" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-yellow-500 focus:border-yellow-500 text-sm" disabled>
                        <option value="">-- Select a line --</option>
                    </select>
                </div>

                <div id="line-preview" class="hidden mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl border border-yellow-200 dark:border-yellow-800">
                    <p class="text-xs font-medium text-yellow-700 dark:text-yellow-400 mb-1">Selected Line:</p>
                    <p id="line-text" class="text-lg font-medium text-gray-900 dark:text-white leading-relaxed"></p>
                </div>
            </div>
        </div>

        {{-- Recording Log --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Session Log</h3>
            <div id="recording-log" class="space-y-2 max-h-64 overflow-y-auto text-sm">
                <p class="text-gray-400 dark:text-gray-500 italic">No recordings yet in this session.</p>
            </div>
        </div>
    </div>

    {{-- Right: Recording Interface --}}
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-8">
            <div class="text-center">
                {{-- Status --}}
                <div id="recorder-status" class="mb-6">
                    <div class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <span class="w-2.5 h-2.5 bg-gray-400 rounded-full mr-2"></span>
                        Ready
                    </div>
                </div>

                {{-- Visualizer --}}
                <div class="mb-8 bg-gray-50 dark:bg-gray-900 rounded-xl p-4 h-32 flex items-center justify-center">
                    <canvas id="audio-visualizer" class="w-full h-full"></canvas>
                </div>

                {{-- Timer --}}
                <div id="recording-timer" class="text-4xl font-mono font-bold text-gray-900 dark:text-white mb-8">
                    00:00.0
                </div>

                {{-- Record Button --}}
                <div class="flex items-center justify-center gap-4">
                    <button id="btn-record" type="button"
                        class="w-20 h-20 rounded-full bg-red-600 hover:bg-red-700 text-white shadow-lg shadow-red-600/30 flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-red-300 disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled>
                        <svg id="icon-mic" class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                        </svg>
                        <svg id="icon-stop" class="w-10 h-10 hidden" fill="currentColor" viewBox="0 0 24 24">
                            <rect x="6" y="6" width="12" height="12" rx="2"/>
                        </svg>
                    </button>
                </div>

                <p id="record-hint" class="mt-4 text-sm text-gray-500 dark:text-gray-400">Select a chant and line to start recording.</p>

                {{-- Playback --}}
                <div id="playback-section" class="hidden mt-8 p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preview Recording</p>
                    <audio id="playback-audio" controls class="w-full mb-4"></audio>
                    <div class="flex justify-center gap-3">
                        <button id="btn-discard" type="button" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Discard
                        </button>
                        <button id="btn-save" type="button" class="inline-flex items-center px-6 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Save Recording
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chantSelect = document.getElementById('chant-select');
    const lineSelect = document.getElementById('line-select');
    const linePreview = document.getElementById('line-preview');
    const lineText = document.getElementById('line-text');
    const btnRecord = document.getElementById('btn-record');
    const btnSave = document.getElementById('btn-save');
    const btnDiscard = document.getElementById('btn-discard');
    const iconMic = document.getElementById('icon-mic');
    const iconStop = document.getElementById('icon-stop');
    const recorderStatus = document.getElementById('recorder-status');
    const timerDisplay = document.getElementById('recording-timer');
    const playbackSection = document.getElementById('playback-section');
    const playbackAudio = document.getElementById('playback-audio');
    const recordHint = document.getElementById('record-hint');
    const recordingLog = document.getElementById('recording-log');
    const canvas = document.getElementById('audio-visualizer');
    const canvasCtx = canvas.getContext('2d');

    let mediaRecorder = null;
    let audioChunks = [];
    let recordedBlob = null;
    let timerInterval = null;
    let startTime = null;
    let audioContext = null;
    let analyser = null;
    let animationFrame = null;
    let isRecording = false;

    // Chant selection
    chantSelect.addEventListener('change', function () {
        const option = this.selectedOptions[0];
        lineSelect.innerHTML = '<option value="">-- Select a line --</option>';
        lineSelect.disabled = true;
        linePreview.classList.add('hidden');
        btnRecord.disabled = true;
        recordHint.textContent = 'Select a chant and line to start recording.';

        if (!this.value) return;

        const lines = JSON.parse(option.dataset.lines || '[]');
        lines.forEach(function (line, i) {
            const opt = document.createElement('option');
            opt.value = i;
            opt.textContent = (i + 1) + '. ' + (typeof line === 'string' ? line.substring(0, 60) : (line.text || '').substring(0, 60));
            opt.dataset.text = typeof line === 'string' ? line : (line.text || '');
            lineSelect.appendChild(opt);
        });
        lineSelect.disabled = false;
    });

    lineSelect.addEventListener('change', function () {
        if (!this.value && this.value !== '0') {
            linePreview.classList.add('hidden');
            btnRecord.disabled = true;
            return;
        }
        const text = this.selectedOptions[0].dataset.text || '';
        lineText.textContent = text;
        linePreview.classList.remove('hidden');
        btnRecord.disabled = false;
        recordHint.textContent = 'Press the red button to start recording.';
    });

    // Recording
    btnRecord.addEventListener('click', async function () {
        if (isRecording) {
            stopRecording();
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: { sampleRate: 16000, channelCount: 1 } });
            audioChunks = [];
            mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm;codecs=opus' });

            // Audio visualization
            audioContext = new AudioContext();
            const source = audioContext.createMediaStreamSource(stream);
            analyser = audioContext.createAnalyser();
            analyser.fftSize = 256;
            source.connect(analyser);
            drawVisualizer();

            mediaRecorder.ondataavailable = function (e) {
                if (e.data.size > 0) audioChunks.push(e.data);
            };

            mediaRecorder.onstop = function () {
                recordedBlob = new Blob(audioChunks, { type: 'audio/webm' });
                playbackAudio.src = URL.createObjectURL(recordedBlob);
                playbackSection.classList.remove('hidden');
                stream.getTracks().forEach(t => t.stop());
                if (animationFrame) cancelAnimationFrame(animationFrame);
            };

            mediaRecorder.start(100);
            isRecording = true;
            startTime = Date.now();
            updateTimer();

            iconMic.classList.add('hidden');
            iconStop.classList.remove('hidden');
            recorderStatus.innerHTML = '<div class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"><span class="w-2.5 h-2.5 bg-red-500 rounded-full mr-2 animate-pulse"></span>Recording...</div>';
            recordHint.textContent = 'Press the stop button when finished.';
        } catch (err) {
            alert('Microphone access denied. Please allow microphone access to record.');
        }
    });

    function stopRecording() {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
        isRecording = false;
        clearInterval(timerInterval);
        iconMic.classList.remove('hidden');
        iconStop.classList.add('hidden');
        recorderStatus.innerHTML = '<div class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"><span class="w-2.5 h-2.5 bg-green-500 rounded-full mr-2"></span>Recording Complete</div>';
    }

    function updateTimer() {
        timerInterval = setInterval(function () {
            const elapsed = (Date.now() - startTime) / 1000;
            const mins = Math.floor(elapsed / 60).toString().padStart(2, '0');
            const secs = Math.floor(elapsed % 60).toString().padStart(2, '0');
            const ms = Math.floor((elapsed % 1) * 10);
            timerDisplay.textContent = mins + ':' + secs + '.' + ms;
        }, 100);
    }

    function drawVisualizer() {
        if (!analyser) return;
        animationFrame = requestAnimationFrame(drawVisualizer);
        const bufferLength = analyser.frequencyBinCount;
        const dataArray = new Uint8Array(bufferLength);
        analyser.getByteFrequencyData(dataArray);

        canvas.width = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;
        canvasCtx.clearRect(0, 0, canvas.width, canvas.height);

        const barWidth = (canvas.width / bufferLength) * 2.5;
        let x = 0;
        for (let i = 0; i < bufferLength; i++) {
            const barHeight = (dataArray[i] / 255) * canvas.height;
            canvasCtx.fillStyle = 'rgb(' + (212) + ',' + (166) + ',' + (71) + ')';
            canvasCtx.fillRect(x, canvas.height - barHeight, barWidth, barHeight);
            x += barWidth + 1;
        }
    }

    // Save
    btnSave.addEventListener('click', function () {
        if (!recordedBlob) return;

        btnSave.disabled = true;
        btnSave.textContent = 'Saving...';

        const reader = new FileReader();
        reader.onloadend = function () {
            const base64 = reader.result.split(',')[1];
            fetch("{{ route('admin.aipray.record.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    audio_data: base64,
                    chant_id: chantSelect.value,
                    line_index: parseInt(lineSelect.value),
                    transcript: lineSelect.selectedOptions[0]?.dataset.text || '',
                    mime_type: recordedBlob.type
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    addLogEntry('Saved: ' + (data.filename || 'sample') + ' (' + chantSelect.selectedOptions[0].text.trim() + ', line ' + (parseInt(lineSelect.value) + 1) + ')', 'success');
                    resetRecorder();
                } else {
                    alert('Save failed: ' + (data.message || 'Unknown error'));
                }
                btnSave.disabled = false;
                btnSave.innerHTML = '<svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Save Recording';
            })
            .catch(err => {
                alert('Network error: ' + err.message);
                btnSave.disabled = false;
                btnSave.innerHTML = '<svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Save Recording';
            });
        };
        reader.readAsDataURL(recordedBlob);
    });

    // Discard
    btnDiscard.addEventListener('click', function () {
        resetRecorder();
        addLogEntry('Recording discarded', 'discard');
    });

    function resetRecorder() {
        recordedBlob = null;
        playbackSection.classList.add('hidden');
        timerDisplay.textContent = '00:00.0';
        recorderStatus.innerHTML = '<div class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300"><span class="w-2.5 h-2.5 bg-gray-400 rounded-full mr-2"></span>Ready</div>';
        recordHint.textContent = 'Press the red button to start recording.';
        canvasCtx.clearRect(0, 0, canvas.width, canvas.height);
    }

    function addLogEntry(message, type) {
        const firstChild = recordingLog.firstElementChild;
        if (firstChild && firstChild.tagName === 'P' && firstChild.classList.contains('italic')) {
            recordingLog.innerHTML = '';
        }
        const div = document.createElement('div');
        const time = new Date().toLocaleTimeString();
        const color = type === 'success' ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400';
        div.className = 'flex items-start gap-2 ' + color;
        div.innerHTML = '<span class="text-xs text-gray-400 whitespace-nowrap">' + time + '</span><span class="text-xs">' + message + '</span>';
        recordingLog.prepend(div);
    }
});
</script>
@endpush
