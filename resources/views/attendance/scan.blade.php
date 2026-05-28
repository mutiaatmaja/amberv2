<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1f6f64">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <title>Catat Absen | {{ config('app.name', 'Amber') }}</title>
    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        heading: ['Poppins', 'ui-sans-serif', 'system-ui'],
                        body: ['Manrope', 'ui-sans-serif', 'system-ui'],
                    },
                },
            },
        };
    </script>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script defer src="{{ asset('js/pwa.js') }}?v={{ filemtime(public_path('js/pwa.js')) }}"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Poppins:wght@600;700&display=swap"
        rel="stylesheet">
</head>

<body class="min-h-full bg-slate-100 font-body text-slate-900">
    <div class="mx-auto flex min-h-screen w-full max-w-2xl flex-col px-4 pb-8 pt-5 sm:px-6">
        <header class="rounded-3xl bg-slate-900 px-5 py-5 text-white shadow-xl">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-emerald-300">Absensi Satpam</p>
            <h1 class="mt-3 font-heading text-2xl font-bold">{{ $pointLabel }}</h1>
            <p class="mt-3 text-sm text-slate-200" id="liveDate">
                {{ now()->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</p>
            <p class="mt-1 text-3xl font-extrabold leading-none" id="liveClock">{{ now()->format('H:i:s') }} WIB</p>
        </header>

        @if (session('toast'))
            <div
                class="mt-4 rounded-2xl border px-4 py-3 text-sm font-semibold {{ session('toast.type') === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
                {{ session('toast.message') }}
            </div>
        @endif

        <section class="mt-4 rounded-3xl bg-white p-5 shadow-sm">
            <form method="POST"
                action="{{ route('attendance.store', ['token' => $token, 'pointType' => $pointType]) }}"
                id="attendanceForm" class="space-y-3">
                @csrf
                <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
                <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">

                <button type="submit" id="submitBtn" @disabled($scanAvailability['disabled'])
                    class="w-full rounded-2xl px-4 py-4 text-lg font-extrabold text-white transition {{ $scanAvailability['disabled'] ? 'cursor-not-allowed bg-slate-400 shadow-none' : 'bg-emerald-600 shadow-lg shadow-emerald-700/25 hover:bg-emerald-700' }}">
                    CATAT ABSEN
                </button>

                @if ($scanAvailability['message'])
                    <div
                        class="rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-700">
                        {{ $scanAvailability['message'] }}
                    </div>
                @endif

                <p class="text-center text-xs text-slate-500" id="locationStatus">
                    {{ $settings->require_gps ? 'Mendeteksi lokasi GPS...' : 'GPS bersifat opsional sesuai pengaturan admin.' }}
                </p>

                @if ($errors->any())
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                        {{ $errors->first() }}
                    </div>
                @endif
            </form>
        </section>

        @if ($settings->show_map)
            <section class="mt-4 rounded-3xl bg-white p-4 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-heading text-base font-bold">MAP</h2>
                    <span class="text-xs font-semibold text-slate-500" id="coordsLabel">Lat: - | Lng: -</span>
                </div>
                <iframe id="mapFrame" class="h-64 w-full rounded-2xl border border-slate-200"
                    src="https://www.openstreetmap.org/export/embed.html?bbox=106.80%2C-6.25%2C106.90%2C-6.15&layer=mapnik"
                    loading="lazy"></iframe>
            </section>
        @endif

        <section class="mt-4 rounded-3xl bg-white p-4 shadow-sm">
            <h2 class="font-heading text-base font-bold">Absensi Sesuai Jadwal</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-slate-600">Jenis</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-600">Jadwal</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-600">Realisasi</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-600">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($attendanceRows as $row)
                            <tr>
                                <td class="px-3 py-2 font-semibold">{{ $row['label'] }}</td>
                                <td class="px-3 py-2 text-slate-600">
                                    {{ $row['schedule_time'] ? substr($row['schedule_time'], 0, 5) : '-' }}
                                </td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['scanned_at']?->format('H:i') ?? '-' }}
                                </td>
                                <td class="px-3 py-2">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $row['status']['class'] }}">{{ $row['status']['label'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        (function() {
            var clockEl = document.getElementById('liveClock');
            var dateEl = document.getElementById('liveDate');
            var latInput = document.getElementById('latitude');
            var lngInput = document.getElementById('longitude');
            var mapFrame = document.getElementById('mapFrame');
            var coordsLabel = document.getElementById('coordsLabel');
            var locationStatus = document.getElementById('locationStatus');
            var form = document.getElementById('attendanceForm');
            var submitBtn = document.getElementById('submitBtn');
            var isGpsRequired = {{ $settings->require_gps ? 'true' : 'false' }};

            function updateClock() {
                var now = new Date();
                clockEl.textContent = now.toLocaleTimeString('id-ID', {
                    hour12: false
                }) + ' WIB';
                dateEl.textContent = now.toLocaleDateString('id-ID', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                });
            }

            function updateLocation(lat, lng) {
                latInput.value = lat;
                lngInput.value = lng;
                if (coordsLabel) {
                    coordsLabel.textContent = 'Lat: ' + Number(lat).toFixed(6) + ' | Lng: ' + Number(lng).toFixed(6);
                }
                locationStatus.textContent = 'Lokasi GPS siap.';

                var delta = 0.004;
                var left = (lng - delta).toFixed(6);
                var right = (lng + delta).toFixed(6);
                var top = (lat + delta).toFixed(6);
                var bottom = (lat - delta).toFixed(6);

                if (mapFrame) {
                    mapFrame.src = 'https://www.openstreetmap.org/export/embed.html?bbox=' + left + '%2C' + bottom +
                        '%2C' +
                        right + '%2C' + top + '&layer=mapnik&marker=' + lat + '%2C' + lng;
                }
            }

            function detectLocation() {
                if (!navigator.geolocation) {
                    locationStatus.textContent = isGpsRequired ?
                        'Perangkat tidak mendukung GPS browser.' :
                        'GPS tidak tersedia di perangkat, absensi tetap bisa dilanjutkan.';
                    return;
                }

                navigator.geolocation.getCurrentPosition(function(position) {
                    updateLocation(position.coords.latitude, position.coords.longitude);
                }, function() {
                    locationStatus.textContent = isGpsRequired ?
                        'Lokasi belum diizinkan. Aktifkan GPS untuk mencatat absensi.' :
                        'Lokasi belum diizinkan. Absensi tetap bisa dilanjutkan karena GPS opsional.';
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 5000
                });
            }

            form.addEventListener('submit', function(event) {
                if (submitBtn.disabled) {
                    event.preventDefault();
                    return;
                }

                if (isGpsRequired && (!latInput.value || !lngInput.value)) {
                    event.preventDefault();
                    locationStatus.textContent = 'GPS wajib aktif. Izinkan lokasi lalu coba lagi.';
                    detectLocation();
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.textContent = 'Memproses...';
            });

            updateClock();
            window.setInterval(updateClock, 1000);
            detectLocation();
        })();
    </script>
</body>

</html>
