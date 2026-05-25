<x-layouts.app :title="'Dashboard | ' . config('app.name', 'Amber')" heading="Dashboard Utama">
    <div class="space-y-6">
        <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <div
                class="overflow-hidden rounded-4xl bg-gradient-to-br from-emerald-700 via-teal-700 to-slate-900 p-6 text-white shadow-panel sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-100">Dashboard</p>
                <h3 class="mt-4 text-3xl font-semibold leading-tight sm:text-4xl">
                    Selamat datang, {{ $user->name }}.
                </h3>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-100/90 sm:text-base">
                    Autentikasi berhasil. Dari halaman ini nanti panel absensi, patroli, monitoring, dan laporan bisa
                    dikembangkan tanpa mengubah alur login/logout yang sudah ada.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    @foreach ($user->roles as $role)
                        <span
                            class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-white/90">
                            {{ $role->display_name ?? $role->name }}
                        </span>
                    @endforeach
                </div>
            </div>

            <div
                class="rounded-4xl border border-slate-200 bg-white p-6 shadow-panel dark:border-slate-800 dark:bg-slate-900">
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-700 dark:text-emerald-300">Akun
                    Aktif</p>
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
                </dl>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            @foreach ($summaryCards as $card)
                <article
                    class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-panel dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">{{ $card['label'] }}</p>
                    <p class="mt-4 text-2xl font-semibold text-slate-900 dark:text-white">{{ $card['value'] }}</p>
                    <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $card['description'] }}</p>
                </article>
            @endforeach
        </section>

        <section
            class="rounded-4xl border border-dashed border-slate-300 bg-slate-50 p-6 dark:border-slate-700 dark:bg-slate-900/60">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-700 dark:text-emerald-300">Langkah
                Berikutnya</p>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl bg-white p-5 dark:bg-slate-950">
                    <h4 class="text-base font-semibold">Panel Admin</h4>
                    <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Tambahkan statistik absensi,
                        patroli, dan monitoring realtime pada area utama dashboard.</p>
                </div>
                <div class="rounded-2xl bg-white p-5 dark:bg-slate-950">
                    <h4 class="text-base font-semibold">Manajemen Role</h4>
                    <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Akses sidebar dapat dibedakan
                        per role ketika modul admin, supervisor, dan satpam mulai dibuat.</p>
                </div>
                <div class="rounded-2xl bg-white p-5 dark:bg-slate-950">
                    <h4 class="text-base font-semibold">PWA & Scanner</h4>
                    <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Shell ini siap dijadikan basis
                        untuk halaman scan QR dan dashboard satpam mobile-first.</p>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
