<x-layouts.app :title="'Dashboard | ' . config('app.name', 'Amber')" :heading="$isSatpam ? 'Dashboard Satpam' : 'Dashboard Utama'">
    <div class="space-y-6" @if ($isSatpam) x-data="{ recapTab: 'daily' }" @endif>
        <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <div class="overflow-hidden rounded-4xl bg-emerald-700 p-6 text-white shadow-panel sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-100">Dashboard</p>
                <h3 class="mt-4 text-3xl font-semibold leading-tight sm:text-4xl">
                    Selamat datang, {{ $user->name }}.
                </h3>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-100/90 sm:text-base">
                    @if ($isSatpam)
                        Pantau proses absen hari ini, lihat rekap harian dan bulanan, lalu ubah password langsung dari
                        dashboard satpam.
                    @else
                        Autentikasi berhasil. Dari halaman ini nanti panel absensi, patroli, monitoring, dan laporan
                        bisa dikembangkan tanpa mengubah alur login/logout yang sudah ada.
                    @endif
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    @foreach ($user->roles as $role)
                        <span
                            class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-white/90">
                            {{ $role->display_name ?? $role->name }}
                        </span>
                    @endforeach
                </div>

                @if ($isSatpam)
                    <div class="mt-8 flex flex-wrap gap-3 text-sm font-semibold">
                        <a href="#proses-hari-ini"
                            class="rounded-full border border-white/20 bg-white/10 px-4 py-2 transition hover:bg-white/20">
                            Proses Absen Hari Ini
                        </a>
                        <a href="#rekap-absensi"
                            class="rounded-full border border-white/20 bg-white/10 px-4 py-2 transition hover:bg-white/20">
                            Rekap Absensi
                        </a>
                        <a href="#pengaturan"
                            class="rounded-full border border-white/20 bg-white/10 px-4 py-2 transition hover:bg-white/20">
                            Pengaturan
                        </a>
                    </div>
                @endif
            </div>

            <div
                class="rounded-4xl border border-slate-200 bg-white p-6 shadow-panel dark:border-slate-800 dark:bg-slate-900">
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-700 dark:text-emerald-300">
                    Akun Aktif
                </p>
                <dl class="mt-6 space-y-5">
                    <div>
                        <dt class="text-xs uppercase tracking-[0.25em] text-slate-400">Nama</dt>
                        <dd class="mt-2 text-lg font-semibold">{{ $user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-[0.25em] text-slate-400">Email</dt>
                        <dd class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-[0.25em] text-slate-400">Jumlah Role</dt>
                        <dd class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $user->roles->count() }}</dd>
                    </div>
                    @if ($isSatpam)
                        <div>
                            <dt class="text-xs uppercase tracking-[0.25em] text-slate-400">Jadwal Hari Ini</dt>
                            <dd class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                                Checkin {{ $schedule?->checkin_time ?? '-' }} - Checkout
                                {{ $schedule?->checkout_time ?? '-' }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($summaryCards as $card)
                <article
                    class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-panel dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">{{ $card['label'] }}</p>
                    <p class="mt-4 text-2xl font-semibold text-slate-900 dark:text-white">{{ $card['value'] }}</p>
                    <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $card['description'] }}</p>
                </article>
            @endforeach
        </section>

        @if ($isSatpam)
            <section id="proses-hari-ini"
                class="rounded-4xl border border-slate-200 bg-white p-6 shadow-panel dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p
                            class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-700 dark:text-emerald-300">
                            Proses Absen Hari Ini
                        </p>
                        <h4 class="mt-2 text-2xl font-semibold">Status titik absensi yang harus dijalankan</h4>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ now()->translatedFormat('l, d F Y') }}</p>
                </div>

                <div class="mt-6 overflow-hidden rounded-3xl border border-slate-200 dark:border-slate-800">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-950/40">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                    Titik</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                    Jadwal</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                    Scan Hari Ini</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                    Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                            @foreach ($todayProcessRows as $row)
                                <tr>
                                    <td class="px-4 py-4 text-sm font-semibold text-slate-900 dark:text-white">
                                        {{ $row['label'] }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                        {{ $row['schedule_time'] ?? '-' }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                        {{ $row['scanned_at'] ? $row['scanned_at']->format('H:i') : 'Belum' }}</td>
                                    <td class="px-4 py-4">
                                        <span
                                            class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $row['status']['class'] }}">{{ $row['status']['label'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="rekap-absensi"
                class="rounded-4xl border border-slate-200 bg-white p-6 shadow-panel dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p
                            class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-700 dark:text-emerald-300">
                            Rekap Absensi
                        </p>
                        <h4 class="mt-2 text-2xl font-semibold">Rekap harian dan bulanan</h4>
                    </div>
                    <div
                        class="inline-flex rounded-full border border-slate-200 bg-slate-100 p-1 text-sm font-semibold dark:border-slate-800 dark:bg-slate-950/60">
                        <button type="button" class="rounded-full px-4 py-2 transition"
                            :class="recapTab === 'daily' ?
                                'bg-white text-emerald-700 shadow-sm dark:bg-slate-900 dark:text-emerald-300' :
                                'text-slate-500 dark:text-slate-400'"
                            @click="recapTab = 'daily'">
                            Rekap Harian
                        </button>
                        <button type="button" class="rounded-full px-4 py-2 transition"
                            :class="recapTab === 'monthly' ?
                                'bg-white text-emerald-700 shadow-sm dark:bg-slate-900 dark:text-emerald-300' :
                                'text-slate-500 dark:text-slate-400'"
                            @click="recapTab = 'monthly'">
                            Rekap Bulanan
                        </button>
                    </div>
                </div>

                <div class="mt-6" x-show="recapTab === 'daily'" x-cloak>
                    <div class="overflow-hidden rounded-3xl border border-slate-200 dark:border-slate-800">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                            <thead class="bg-slate-50 dark:bg-slate-950/40">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                        Tanggal</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                        Total</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                        Tepat Waktu</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                        Terlambat</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                        Scan Terakhir</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                                @foreach ($dailyRecapRows as $row)
                                    <tr>
                                        <td class="px-4 py-4 text-sm font-semibold text-slate-900 dark:text-white">
                                            {{ $row['date']->translatedFormat('d M Y') }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                            {{ $row['total'] }} kali</td>
                                        <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                            {{ $row['on_time'] }} kali</td>
                                        <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                            {{ $row['late'] }} kali</td>
                                        <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                            {{ $row['last_scan_at']?->format('H:i') ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-6" x-show="recapTab === 'monthly'" x-cloak>
                    <div class="overflow-hidden rounded-3xl border border-slate-200 dark:border-slate-800">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                            <thead class="bg-slate-50 dark:bg-slate-950/40">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                        Bulan</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                        Total</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                        Tepat Waktu</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                        Terlambat</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                        Hari Aktif</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                                @foreach ($monthlyRecapRows as $row)
                                    <tr>
                                        <td class="px-4 py-4 text-sm font-semibold text-slate-900 dark:text-white">
                                            {{ $row['month']->translatedFormat('F Y') }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                            {{ $row['total'] }} kali</td>
                                        <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                            {{ $row['on_time'] }} kali</td>
                                        <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                            {{ $row['late'] }} kali</td>
                                        <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                            {{ $row['active_days'] }} hari</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section id="pengaturan"
                class="rounded-4xl border border-slate-200 bg-white p-6 shadow-panel dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p
                            class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-700 dark:text-emerald-300">
                            Pengaturan
                        </p>
                        <h4 class="mt-2 text-2xl font-semibold">Ganti password</h4>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Gunakan password kuat dan simpan di perangkat
                        pribadi.</p>
                </div>

                <form method="POST" action="{{ route('dashboard.password.update') }}"
                    class="mt-6 grid gap-4 xl:grid-cols-3" data-progress-form
                    data-progress-message="Menyimpan password baru...">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="mb-2 block text-sm font-medium">Password Saat Ini</label>
                        <input id="current_password" name="current_password" type="password" required
                            class="block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium">Password Baru</label>
                        <input id="password" name="password" type="password" required minlength="8"
                            class="block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="password_confirmation" class="mb-2 block text-sm font-medium">Ulangi Password
                            Baru</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                            minlength="8"
                            class="block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div class="xl:col-span-3 flex items-center justify-end gap-3 pt-2">
                        <button type="submit" data-loading-text="Menyimpan..."
                            class="rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            Simpan Password
                        </button>
                    </div>
                </form>
            </section>
        @else
            <section
                class="rounded-4xl border border-dashed border-slate-300 bg-slate-50 p-6 dark:border-slate-700 dark:bg-slate-900/60">
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-700 dark:text-emerald-300">
                    Langkah
                    Berikutnya</p>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl bg-white p-5 dark:bg-slate-950">
                        <h4 class="text-base font-semibold">Panel Admin</h4>
                        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Tambahkan statistik
                            absensi,
                            patroli, dan monitoring realtime pada area utama dashboard.</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 dark:bg-slate-950">
                        <h4 class="text-base font-semibold">Manajemen Role</h4>
                        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Akses sidebar dapat
                            dibedakan
                            per role ketika modul admin, supervisor, dan satpam mulai dibuat.</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 dark:bg-slate-950">
                        <h4 class="text-base font-semibold">PWA & Scanner</h4>
                        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Shell ini siap dijadikan
                            basis
                            untuk halaman scan QR dan dashboard satpam mobile-first.</p>
                    </div>
                </div>
            </section>
        @endif
    </div>
</x-layouts.app>
