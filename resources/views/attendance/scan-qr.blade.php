<x-layouts.app :title="'Scan QR | ' . config('app.name', 'Amber')" heading="Scan QR">
    <div class="space-y-6">
        <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <div class="rounded-4xl bg-emerald-700 p-6 text-white shadow-panel sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-100">PWA Scanner</p>
                <h3 class="mt-4 text-3xl font-semibold leading-tight sm:text-4xl">
                    Scan QR absensi langsung dari aplikasi.
                </h3>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-100/90 sm:text-base">
                    Arahkan kamera ke QR absensi. QR harus memakai domain aplikasi ini dan format path
                    <span class="font-semibold">/absen/token/pointType</span>.
                </p>

                <div class="mt-8 flex flex-wrap gap-3 text-sm font-semibold">
                    <button type="button" id="startScanner"
                        class="rounded-full bg-white px-4 py-2 text-emerald-700 transition hover:bg-emerald-50">
                        Mulai Scanner
                    </button>
                    <button type="button" id="stopScanner"
                        class="rounded-full border border-white/20 bg-white/10 px-4 py-2 transition hover:bg-white/20">
                        Hentikan
                    </button>
                    <button type="button" data-finish-scanner data-dashboard-url="{{ route('dashboard') }}"
                        class="hidden rounded-full border border-white/20 bg-slate-900/25 px-4 py-2 transition hover:bg-slate-900/40 md:inline-flex">
                        Selesai dan Kembali
                    </button>
                </div>
            </div>

            <div
                class="rounded-4xl border border-slate-200 bg-white p-6 shadow-panel dark:border-slate-800 dark:bg-slate-900">
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-700 dark:text-emerald-300">
                    Status Scanner
                </p>
                <dl class="mt-6 space-y-5 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-[0.25em] text-slate-400">Pengguna</dt>
                        <dd class="mt-2 text-lg font-semibold">{{ $user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-[0.25em] text-slate-400">Status</dt>
                        <dd class="mt-2 text-slate-600 dark:text-slate-300" id="scannerStatus">Siap memulai kamera.</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-[0.25em] text-slate-400">Hasil Terakhir</dt>
                        <dd class="mt-2 break-all text-slate-600 dark:text-slate-300" id="scanResult">-</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section
            class="rounded-4xl border border-slate-200 bg-white p-6 shadow-panel dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-700 dark:text-emerald-300">
                        Kamera
                    </p>
                    <h4 class="mt-2 text-2xl font-semibold">Arahkan kamera ke QR absensi</h4>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400">Gunakan browser PWA pada domain server yang sama.
                </p>
            </div>

            <div
                class="mt-6 overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 p-3 dark:border-slate-800">
                <div id="reader" class="mx-auto min-h-80 max-w-xl rounded-2xl bg-slate-900"></div>
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-[1fr_auto_auto]">
                <div>
                    <label for="cameraSelect" class="mb-2 block text-sm font-medium">Pilih Kamera</label>
                    <select id="cameraSelect"
                        class="block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950">
                        <option value="">Kamera belakang otomatis</option>
                    </select>
                </div>
                <button type="button" id="refreshCameras"
                    class="self-end rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                    Muat Kamera
                </button>
                <button type="button" id="restartScanner"
                    class="self-end rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                    Ganti Kamera
                </button>
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-[1fr_auto]">
                <div>
                    <label for="manualQrUrl" class="mb-2 block text-sm font-medium">Scanner pihak ketiga / input
                        manual</label>
                    <input id="manualQrUrl" type="text" inputmode="url" autocomplete="off" autocapitalize="off"
                        spellcheck="false" placeholder="Scan langsung dari handheld scanner atau tempel URL /absen/..."
                        class="block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950">
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        Cocok untuk scanner handheld. Jika perangkat mengirim tombol Enter otomatis, URL akan langsung
                        dibuka.
                    </p>
                </div>
                <button type="button" id="openManualQr"
                    class="self-end rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                    Buka URL
                </button>
            </div>

            <div id="scannerAlert" hidden
                class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300">
            </div>
        </section>

        <div class="sticky bottom-4 z-10 md:hidden">
            <div
                class="rounded-3xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur dark:border-slate-800 dark:bg-slate-900/95">
                <button type="button" data-finish-scanner data-dashboard-url="{{ route('dashboard') }}"
                    class="flex w-full items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                    Selesai dan Kembali
                </button>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/html5-qrcode.min.js') }}?v={{ filemtime(public_path('js/html5-qrcode.min.js')) }}"></script>
    <script>
        (function() {
            var readerElementId = 'reader';
            var scannerStatus = document.getElementById('scannerStatus');
            var scanResult = document.getElementById('scanResult');
            var alertBox = document.getElementById('scannerAlert');
            var startButton = document.getElementById('startScanner');
            var stopButton = document.getElementById('stopScanner');
            var finishScannerButtons = document.querySelectorAll('[data-finish-scanner]');
            var cameraSelect = document.getElementById('cameraSelect');
            var refreshCamerasButton = document.getElementById('refreshCameras');
            var restartScannerButton = document.getElementById('restartScanner');
            var manualInput = document.getElementById('manualQrUrl');
            var manualOpenButton = document.getElementById('openManualQr');
            var qrScanner = null;
            var scannerRunning = false;
            var availableCameras = [];
            var hasAttemptedAutoStart = false;

            function setStatus(message) {
                scannerStatus.textContent = message;
            }

            function setAlert(message) {
                if (!message) {
                    alertBox.hidden = true;
                    alertBox.textContent = '';
                    return;
                }

                alertBox.hidden = false;
                alertBox.textContent = message;
            }

            function normalizeScanUrl(rawValue) {
                var value = (rawValue || '').trim();

                if (!value) {
                    throw new Error('QR kosong atau tidak terbaca.');
                }

                var parsedUrl = new URL(value, window.location.origin);
                var pathMatch = parsedUrl.pathname.match(/^\/absen\/([^/]+)\/([^/?#]+)$/i);

                if (!pathMatch) {
                    throw new Error('Format QR tidak sesuai. Gunakan QR absensi Amber.');
                }

                if (parsedUrl.origin !== window.location.origin) {
                    throw new Error('Domain QR berbeda dengan domain aplikasi yang sedang dibuka.');
                }

                parsedUrl.search = '';
                parsedUrl.hash = '';

                return parsedUrl.toString();
            }

            function openScannedUrl(rawValue) {
                try {
                    var normalizedUrl = normalizeScanUrl(rawValue);
                    scanResult.textContent = normalizedUrl;
                    setAlert('');
                    setStatus('QR valid. Membuka halaman absensi...');
                    window.location.href = normalizedUrl;
                } catch (error) {
                    setStatus('QR belum valid.');
                    setAlert(error.message);
                }
            }

            function updateCameraOptions(cameras) {
                availableCameras = cameras || [];
                cameraSelect.innerHTML = '<option value="">Kamera belakang otomatis</option>';

                availableCameras.forEach(function(camera, index) {
                    var option = document.createElement('option');
                    option.value = camera.id;
                    option.textContent = camera.label || 'Kamera ' + (index + 1);
                    cameraSelect.appendChild(option);
                });

                var preferredCameraId = resolvePreferredCameraId();

                if (preferredCameraId) {
                    cameraSelect.value = preferredCameraId;
                }
            }

            function isRearCameraLabel(label) {
                var normalizedLabel = (label || '').toLowerCase();

                return normalizedLabel.includes('back') || normalizedLabel.includes('rear') || normalizedLabel.includes(
                        'environment') ||
                    normalizedLabel.includes('belakang') || normalizedLabel.includes('trasera');
            }

            function resolvePreferredCameraId() {
                var rearCamera = availableCameras.find(function(camera) {
                    return isRearCameraLabel(camera.label);
                });

                return rearCamera ? rearCamera.id : availableCameras[0] ? availableCameras[0].id : '';
            }

            function shouldAutoStartScanner() {
                return window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
            }

            async function attemptAutoStart() {
                if (hasAttemptedAutoStart || !shouldAutoStartScanner()) {
                    return;
                }

                hasAttemptedAutoStart = true;
                await startScanner();
            }

            async function loadCameras() {
                if (typeof Html5Qrcode === 'undefined') {
                    return;
                }

                try {
                    var cameras = await Html5Qrcode.getCameras();

                    if (!cameras || cameras.length === 0) {
                        setAlert('Perangkat tidak menemukan kamera yang bisa digunakan.');
                        return;
                    }

                    updateCameraOptions(cameras);
                    setAlert('');
                    setStatus('Kamera siap digunakan.');
                    attemptAutoStart();
                } catch (error) {
                    setAlert('Daftar kamera belum bisa dimuat. Izinkan kamera lalu coba lagi.');
                }
            }

            function selectedCameraConfig() {
                if (cameraSelect.value) {
                    return cameraSelect.value;
                }

                return {
                    facingMode: 'environment',
                };
            }

            async function stopScanner() {
                if (!qrScanner || !scannerRunning) {
                    return;
                }

                try {
                    await qrScanner.stop();
                    await qrScanner.clear();
                } catch (error) {
                    // Ignore stop errors to keep the UI recoverable.
                }

                scannerRunning = false;
                setStatus('Scanner dihentikan.');
            }

            function closeOrRedirect(fallbackUrl) {
                try {
                    window.close();
                } catch (error) {
                    window.location.href = fallbackUrl;
                    return;
                }

                window.setTimeout(function() {
                    if (!document.hidden) {
                        window.location.href = fallbackUrl;
                    }
                }, 250);
            }

            async function startScanner() {
                setAlert('');
                setStatus('Menyalakan kamera...');

                if (typeof Html5Qrcode === 'undefined') {
                    setStatus('Scanner tidak tersedia.');
                    setAlert('Library scanner gagal dimuat. Periksa koneksi internet server.');
                    return;
                }

                if (!qrScanner) {
                    qrScanner = new Html5Qrcode(readerElementId);
                }

                if (scannerRunning) {
                    return;
                }

                try {
                    await qrScanner.start(selectedCameraConfig(), {
                            fps: 10,
                            qrbox: {
                                width: 280,
                                height: 280,
                            },
                            formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
                        },
                        function(decodedText) {
                            stopScanner().finally(function() {
                                openScannedUrl(decodedText);
                            });
                        },
                        function() {}
                    );

                    scannerRunning = true;
                    setStatus('Kamera aktif. Arahkan ke QR absensi.');
                } catch (error) {
                    setStatus('Kamera gagal diaktifkan.');
                    setAlert('Izin kamera belum tersedia atau kamera terpilih tidak bisa dipakai.');
                }
            }

            startButton.addEventListener('click', function() {
                startScanner();
            });

            stopButton.addEventListener('click', function() {
                stopScanner();
            });

            refreshCamerasButton.addEventListener('click', function() {
                loadCameras();
            });

            restartScannerButton.addEventListener('click', function() {
                stopScanner().finally(function() {
                    startScanner();
                });
            });

            finishScannerButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    setAlert('');
                    setStatus('Menutup scanner dan kembali ke dashboard...');

                    stopScanner().finally(function() {
                        closeOrRedirect(button.dataset.dashboardUrl);
                    });
                });
            });

            manualOpenButton.addEventListener('click', function() {
                openScannedUrl(manualInput.value);
            });

            manualInput.addEventListener('keydown', function(event) {
                if (event.key !== 'Enter') {
                    return;
                }

                event.preventDefault();
                openScannedUrl(manualInput.value);
            });

            window.addEventListener('beforeunload', function() {
                stopScanner();
            });

            loadCameras();
        })();
    </script>
</x-layouts.app>
