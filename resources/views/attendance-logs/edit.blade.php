@push('styles')
    <link rel="stylesheet" href="{{ asset('css/leaflet.css') }}?v={{ filemtime(public_path('css/leaflet.css')) }}" />
@endpush

@push('scripts')
    @if ($log->latitude !== null && $log->longitude !== null)
        <script src="{{ asset('js/leaflet.js') }}?v={{ filemtime(public_path('js/leaflet.js')) }}"></script>
        <script>
            (function() {
                var mapElement = document.getElementById('gpsMap');

                if (!mapElement || typeof L === 'undefined') {
                    return;
                }

                var latitude = Number(mapElement.dataset.lat);
                var longitude = Number(mapElement.dataset.lng);

                if (Number.isNaN(latitude) || Number.isNaN(longitude)) {
                    return;
                }

                var map = L.map(mapElement, {
                    scrollWheelZoom: false,
                }).setView([latitude, longitude], 16);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(map);

                L.marker([latitude, longitude]).addTo(map)
                    .bindPopup('Lokasi absensi')
                    .openPopup();
            })();
        </script>
    @endif
@endpush

<x-layouts.app :title="'Ubah Absensi | ' . config('app.name', 'Amber')" heading="Ubah Absensi">
    <div class="mx-auto max-w-4xl">
        <section
            class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">

            {{-- Info Log --}}
            <div class="mb-6 rounded-2xl bg-slate-50 px-5 py-4 dark:bg-slate-800/50">
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <dt class="font-medium text-slate-500 dark:text-slate-400">Satpam</dt>
                    <dd class="font-semibold">{{ $log->user?->name ?? '-' }}</dd>

                    <dt class="font-medium text-slate-500 dark:text-slate-400">Titik</dt>
                    <dd>{{ str_replace('_', ' ', $log->point_type) }}</dd>
                </dl>
            </div>

            <div
                class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900/60">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold">Lokasi GPS</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Detail posisi saat absensi dicatat.
                        </p>
                    </div>
                    @if ($log->latitude !== null && $log->longitude !== null)
                        <a href="https://www.google.com/maps?q={{ $log->latitude }},{{ $log->longitude }}"
                            target="_blank" rel="noopener noreferrer"
                            class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                            Buka di Google Maps
                        </a>
                    @endif
                </div>

                @if ($log->latitude !== null && $log->longitude !== null)
                    <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70">
                            <dt
                                class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                Latitude</dt>
                            <dd class="mt-1 text-sm font-semibold">{{ $log->latitude }}</dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70">
                            <dt
                                class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                Longitude</dt>
                            <dd class="mt-1 text-sm font-semibold">{{ $log->longitude }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
                        <div id="gpsMap" class="h-72 w-full" data-lat="{{ $log->latitude }}"
                            data-lng="{{ $log->longitude }}"></div>
                    </div>
                @else
                    <div
                        class="mt-4 rounded-2xl border border-dashed border-slate-300 px-4 py-4 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        Lokasi GPS tidak tersedia untuk absensi ini.
                    </div>
                @endif
            </div>

            <h3 class="text-xl font-semibold">Form Ubah Absensi</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Perbarui waktu scan, status, dan keterangan absensi.
            </p>

            @if ($errors->any())
                <div
                    class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('attendance-logs.update', $log) }}" class="mt-6 space-y-5"
                data-progress-form data-progress-message="Menyimpan perubahan...">
                @csrf
                @method('PUT')

                <div>
                    <label for="scanned_at" class="mb-2 block text-sm font-medium">Waktu Scan</label>
                    <input id="scanned_at" name="scanned_at" type="datetime-local"
                        value="{{ old('scanned_at', $log->scanned_at?->format('Y-m-d\TH:i')) }}" required
                        class="block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                <div>
                    <label for="status" class="mb-2 block text-sm font-medium">Status</label>
                    <select id="status" name="status" required
                        class="block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        <option value="accepted" @selected(old('status', $log->status) === 'accepted')>Diterima</option>
                        <option value="rejected" @selected(old('status', $log->status) === 'rejected')>Ditolak</option>
                    </select>
                </div>

                <div>
                    <label for="reason" class="mb-2 block text-sm font-medium">
                        Keterangan <span class="text-slate-400">(opsional)</span>
                    </label>
                    <textarea id="reason" name="reason" rows="3"
                        class="block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        placeholder="Tambahkan keterangan jika diperlukan...">{{ old('reason', $log->reason) }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('attendance-logs.index', ['user_id' => $log->user_id]) }}" wire:navigate
                        class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                        Batal
                    </a>
                    <button type="submit" data-loading-text="Menyimpan..."
                        class="rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </section>
    </div>
</x-layouts.app>
