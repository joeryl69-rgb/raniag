/**
 * In-app "document camera" — a guided live-capture experience (framing
 * overlay + instructions + flash toggle), the same idea as the GPS
 * evidence camera (public/js/gps-camera.js), reused here for scanning
 * case documents. This replaces handing off to the phone's native camera
 * app via <input capture>, which was producing poorly framed/lit shots
 * and, in turn, inaccurate OCR text.
 *
 * Usage: window.RaniagDocCamera.open(function (file) { ...use the File... });
 */
(function (window, document) {
    let modalEl, videoEl, canvasEl, captureBtn, switchBtn, flashBtn, closeBtn;
    let previewWrap, previewImg, retakeBtn, useBtn, errorEl, guideEl;
    let mediaStream = null;
    let facingMode = 'environment';
    let torchOn = false;
    let onConfirm = null;
    let capturedBlob = null;
    let built = false;

    function build() {
        if (built) return;
        built = true;

        modalEl = document.createElement('div');
        modalEl.className = 'modal fade';
        modalEl.id = 'docCameraModal';
        modalEl.tabIndex = -1;
        modalEl.setAttribute('data-bs-backdrop', 'static');
        modalEl.innerHTML = `
            <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
                <div class="modal-content bg-dark text-white border-0">
                    <div class="modal-header border-0 pb-0">
                        <h6 class="modal-title"><i class="bi bi-camera-fill me-2"></i>Scan Document</h6>
                        <button type="button" class="btn-close btn-close-white" id="docCamCloseBtn"></button>
                    </div>
                    <div class="modal-body pt-1">
                        <div class="alert alert-info py-2 px-3 small mb-2" id="docCamHint">
                            <i class="bi bi-info-circle me-1"></i>
                            Lay the document flat, fill the frame, and hold steady. Use the flash icon if the room is dark.
                        </div>
                        <div class="doc-cam-viewport position-relative rounded overflow-hidden" id="docCamViewport" style="background:#000;">
                            <video id="docCamVideo" autoplay playsinline muted class="w-100 d-block"></video>
                            <canvas id="docCamCanvas" class="w-100 d-none"></canvas>
                            <div id="docCamGuide" class="doc-cam-guide"></div>
                            <div id="docCamPreviewWrap" class="d-none text-center">
                                <img id="docCamPreviewImg" class="w-100" alt="Captured document">
                            </div>
                            <div id="docCamError" class="doc-cam-error small text-center d-none"></div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mt-3">
                            <button type="button" class="btn btn-outline-light" id="docCamFlashBtn" title="Toggle flash">
                                <i class="bi bi-lightning-charge"></i>
                            </button>
                            <div class="d-flex gap-2" id="docCamLiveControls">
                                <button type="button" class="btn btn-light rounded-circle" id="docCamCaptureBtn" style="width:56px;height:56px;" title="Capture">
                                    <i class="bi bi-circle-fill fs-4"></i>
                                </button>
                            </div>
                            <div class="d-flex gap-2 d-none" id="docCamReviewControls">
                                <button type="button" class="btn btn-outline-light btn-sm" id="docCamRetakeBtn"><i class="bi bi-arrow-counterclockwise me-1"></i>Retake</button>
                                <button type="button" class="btn btn-primary btn-sm" id="docCamUseBtn"><i class="bi bi-check-lg me-1"></i>Use Photo</button>
                            </div>
                            <button type="button" class="btn btn-outline-light" id="docCamSwitchBtn" title="Switch camera">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>`;
        document.body.appendChild(modalEl);

        videoEl = modalEl.querySelector('#docCamVideo');
        canvasEl = modalEl.querySelector('#docCamCanvas');
        captureBtn = modalEl.querySelector('#docCamCaptureBtn');
        switchBtn = modalEl.querySelector('#docCamSwitchBtn');
        flashBtn = modalEl.querySelector('#docCamFlashBtn');
        closeBtn = modalEl.querySelector('#docCamCloseBtn');
        previewWrap = modalEl.querySelector('#docCamPreviewWrap');
        previewImg = modalEl.querySelector('#docCamPreviewImg');
        retakeBtn = modalEl.querySelector('#docCamRetakeBtn');
        useBtn = modalEl.querySelector('#docCamUseBtn');
        errorEl = modalEl.querySelector('#docCamError');
        guideEl = modalEl.querySelector('#docCamGuide');

        captureBtn.addEventListener('click', capture);
        switchBtn.addEventListener('click', switchCamera);
        flashBtn.addEventListener('click', toggleFlash);
        closeBtn.addEventListener('click', close);
        retakeBtn.addEventListener('click', retake);
        useBtn.addEventListener('click', confirmUse);
        modalEl.addEventListener('hidden.bs.modal', stopStream);
    }

    function setError(msg) {
        if (!errorEl) return;
        errorEl.textContent = msg;
        errorEl.classList.toggle('d-none', !msg);
    }

    function getTrack() {
        return mediaStream ? mediaStream.getVideoTracks()[0] : null;
    }

    function updateFlashAvailability() {
        const track = getTrack();
        const caps = track && track.getCapabilities ? track.getCapabilities() : null;
        const supported = facingMode === 'environment' && caps && caps.torch;
        flashBtn.classList.toggle('d-none', !supported);
        flashBtn.classList.remove('active');
        torchOn = false;
    }

    async function toggleFlash() {
        const track = getTrack();
        if (!track || !track.applyConstraints) return;
        try {
            torchOn = !torchOn;
            await track.applyConstraints({ advanced: [{ torch: torchOn }] });
            flashBtn.classList.toggle('active', torchOn);
        } catch (e) {
            torchOn = false;
            setError('Flash is not supported on this device/camera.');
        }
    }

    async function startStream() {
        setError('');
        try {
            if (mediaStream) mediaStream.getTracks().forEach((t) => t.stop());
            mediaStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode, width: { ideal: 1920 }, height: { ideal: 1080 } },
                audio: false,
            });
            videoEl.srcObject = mediaStream;
            await videoEl.play();
            updateFlashAvailability();
            startReadinessCheck();
        } catch (err) {
            const messages = {
                NotAllowedError: 'Camera permission denied. Allow camera access to scan documents.',
                NotFoundError: 'No camera found on this device.',
                NotReadableError: 'Camera is in use by another application.',
            };
            setError(messages[err.name] || err.message || 'Unable to start the camera.');
        }
    }

    function stopStream() {
        if (mediaStream) {
            mediaStream.getTracks().forEach((t) => t.stop());
            mediaStream = null;
        }
        torchOn = false;
        stopReadinessCheck();
    }

    async function switchCamera() {
        facingMode = facingMode === 'environment' ? 'user' : 'environment';
        await startStream();
    }

    function capture() {
        if (!mediaStream) return;
        canvasEl.width = videoEl.videoWidth;
        canvasEl.height = videoEl.videoHeight;
        const ctx = canvasEl.getContext('2d');
        ctx.drawImage(videoEl, 0, 0, canvasEl.width, canvasEl.height);
        canvasEl.toBlob((blob) => {
            capturedBlob = blob;
            previewImg.src = URL.createObjectURL(blob);
            videoEl.classList.add('d-none');
            guideEl.classList.add('d-none');
            previewWrap.classList.remove('d-none');
            document.getElementById('docCamHint').classList.add('d-none');
            document.getElementById('docCamLiveControls').classList.add('d-none');
            document.getElementById('docCamReviewControls').classList.remove('d-none');
            switchBtn.classList.add('d-none');
            flashBtn.classList.add('d-none');
            stopReadinessCheck();
        }, 'image/jpeg', 0.92);
    }

    // ------------------------------------------------------------------
    // Capture readiness check
    //
    // This does NOT do true document-edge/perspective detection (that
    // needs a real computer-vision library — OpenCV.js or similar — which
    // isn't part of this app and would be a much bigger addition to pull
    // in and test than fits here). What it DOES do: sample the live frame
    // a few times a second and estimate (a) sharpness, via a simple
    // Laplacian-style edge-variance score on a small downscaled grayscale
    // copy, and (b) average brightness. A blurry frame (camera still
    // moving, not focused) or a too-dark frame reliably produces the
    // garbled/unrelated OCR text this was meant to fix — not because the
    // wrong region got scanned, but because Tesseract is reading noise.
    // Gating the shutter on "sharp enough + bright enough" is a real,
    // honest improvement on that specific failure mode, distinct from (and
    // not a substitute for) actually detecting the document's placement.
    let readinessTimer = null;
    const READY_SHARPNESS_MIN = 18; // empirical threshold on the 0-255 scale
    const READY_BRIGHTNESS_MIN = 60;
    const READY_BRIGHTNESS_MAX = 235;
    const sampleCanvas = document.createElement('canvas');
    sampleCanvas.width = 160;
    sampleCanvas.height = 120;

    function sampleReadiness() {
        if (!mediaStream || !videoEl.videoWidth) return null;
        const sctx = sampleCanvas.getContext('2d');
        sctx.drawImage(videoEl, 0, 0, sampleCanvas.width, sampleCanvas.height);
        const { data } = sctx.getImageData(0, 0, sampleCanvas.width, sampleCanvas.height);
        const w = sampleCanvas.width;
        const h = sampleCanvas.height;
        const gray = new Float32Array(w * h);
        let brightnessSum = 0;
        for (let i = 0, p = 0; i < data.length; i += 4, p++) {
            const g = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
            gray[p] = g;
            brightnessSum += g;
        }
        const avgBrightness = brightnessSum / gray.length;

        // Crude Laplacian: |4*center - up - down - left - right|, averaged.
        let edgeSum = 0;
        let edgeCount = 0;
        for (let y = 1; y < h - 1; y++) {
            for (let x = 1; x < w - 1; x++) {
                const i = y * w + x;
                const lap = 4 * gray[i] - gray[i - 1] - gray[i + 1] - gray[i - w] - gray[i + w];
                edgeSum += Math.abs(lap);
                edgeCount++;
            }
        }
        const sharpness = edgeSum / edgeCount;

        return { sharpness, avgBrightness };
    }

    function updateReadinessUi() {
        const result = sampleReadiness();
        const hintEl = document.getElementById('docCamHint');
        if (!result || !hintEl) return;

        const { sharpness, avgBrightness } = result;
        let ready = true;
        let message = '<i class="bi bi-check-circle-fill me-1 text-success"></i>Looks sharp and well-lit — ready to capture.';

        if (avgBrightness < READY_BRIGHTNESS_MIN) {
            ready = false;
            message = '<i class="bi bi-exclamation-triangle-fill me-1"></i>Too dark — move to better light or use the flash icon.';
        } else if (avgBrightness > READY_BRIGHTNESS_MAX) {
            ready = false;
            message = '<i class="bi bi-exclamation-triangle-fill me-1"></i>Too bright/glare — tilt away from direct light.';
        } else if (sharpness < READY_SHARPNESS_MIN) {
            ready = false;
            message = '<i class="bi bi-exclamation-triangle-fill me-1"></i>Hold steady — image looks blurry.';
        } else {
            message = '<i class="bi bi-check-circle-fill me-1 text-success"></i>Lay the document flat inside the frame, then capture.';
        }

        hintEl.innerHTML = message;
        guideEl.classList.toggle('doc-cam-guide-ready', ready);
        guideEl.classList.toggle('doc-cam-guide-not-ready', !ready);
    }

    function startReadinessCheck() {
        stopReadinessCheck();
        readinessTimer = window.setInterval(updateReadinessUi, 400);
    }

    function stopReadinessCheck() {
        if (readinessTimer) {
            window.clearInterval(readinessTimer);
            readinessTimer = null;
        }
    }

    function retake() {
        capturedBlob = null;
        previewWrap.classList.add('d-none');
        videoEl.classList.remove('d-none');
        guideEl.classList.remove('d-none');
        document.getElementById('docCamHint').classList.remove('d-none');
        document.getElementById('docCamLiveControls').classList.remove('d-none');
        document.getElementById('docCamReviewControls').classList.add('d-none');
        switchBtn.classList.remove('d-none');
        updateFlashAvailability();
        startReadinessCheck();
    }

    function confirmUse() {
        if (!capturedBlob) return;
        const file = new File([capturedBlob], `document-scan-${Date.now()}.jpg`, { type: 'image/jpeg' });
        const cb = onConfirm;
        close();
        if (cb) cb(file);
    }

    function getModal() {
        return window.bootstrap ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
    }

    function close() {
        const m = getModal();
        if (m) m.hide();
        stopStream();
    }

    async function open(callback) {
        build();
        onConfirm = callback;
        capturedBlob = null;
        facingMode = 'environment';
        previewWrap.classList.add('d-none');
        videoEl.classList.remove('d-none');
        guideEl.classList.remove('d-none');
        document.getElementById('docCamHint').classList.remove('d-none');
        document.getElementById('docCamLiveControls').classList.remove('d-none');
        document.getElementById('docCamReviewControls').classList.add('d-none');
        switchBtn.classList.remove('d-none');
        setError('');

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setError('Camera is not supported on this browser.');
        }

        const m = getModal();
        if (m) m.show();
        await startStream();
    }

    window.RaniagDocCamera = { open };
})(window, document);
