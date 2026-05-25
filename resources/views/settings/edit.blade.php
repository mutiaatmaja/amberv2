<x-layouts.app :title="'Pengaturan | ' . config('app.name', 'Amber')" heading="Pengaturan">
    <div class="space-y-6">
        <section
            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div>
                <h3 class="text-xl font-semibold">Pengaturan Absensi</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Atur toleransi keterlambatan, kewajiban GPS, dan tampilan peta pada halaman absensi satpam.
                </p>
            </div>
        </section>

        <section
            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-5" data-progress-form
                data-progress-message="Menyimpan pengaturan...">
                @csrf
                @method('PUT')

                <div>
                    <label for="late_tolerance_minutes" class="text-sm font-semibold">Toleransi Keterlambatan
                        (Menit)</label>
                    <input id="late_tolerance_minutes" name="late_tolerance_minutes" type="number" min="0"
                        max="180" value="{{ old('late_tolerance_minutes', $settings->late_tolerance_minutes) }}"
                        class="mt-2 w-full rounded-2xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <p class="mt-1 text-xs text-slate-500">Dipakai untuk menghitung status Tepat Waktu / Terlambat.</p>
                    @error('late_tolerance_minutes')
                        <p class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                        <input type="hidden" name="require_gps" value="0">
                        <input type="checkbox" name="require_gps" value="1"
                            {{ old('require_gps', $settings->require_gps) ? 'checked' : '' }}
                            class="mt-1 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span>
                            <span class="block text-sm font-semibold">Wajib GPS</span>
                            <span class="mt-1 block text-xs text-slate-500">Jika aktif, absensi satpam wajib kirim
                                latitude/longitude.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                        <input type="hidden" name="show_map" value="0">
                        <input type="checkbox" name="show_map" value="1"
                            {{ old('show_map', $settings->show_map) ? 'checked' : '' }}
                            class="mt-1 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span>
                            <span class="block text-sm font-semibold">Tampilkan Peta</span>
                            <span class="mt-1 block text-xs text-slate-500">Jika nonaktif, section peta disembunyikan
                                dari halaman satpam.</span>
                        </span>
                    </label>
                </div>

                @error('require_gps')
                    <p class="text-sm font-medium text-rose-600">{{ $message }}</p>
                @enderror

                @error('show_map')
                    <p class="text-sm font-medium text-rose-600">{{ $message }}</p>
                @enderror

                <div>
                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                        data-loading-text="Menyimpan...">
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </section>
    </div>
</x-layouts.app>
