<x-layouts.app :title="'Cetak Rekap Kebersihan | ' . config('app.name', 'Amber')" heading="CETAK REKAP KEBERSIHAN">
    <div class="space-y-6">
        <section
            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-700 dark:text-emerald-300">
                        Rekap Kebersihan per Bulan
                    </p>
                    <h3 class="mt-2 text-2xl font-semibold">Cetak Statistik + Rekap PDF</h3>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                        Pilih kebersihan dan bulan untuk mencetak laporan absensi bulanan berisi statistik dan
                        rekap harian.
                    </p>
                </div>

                <form method="GET" action="{{ route('cleaning-attendance-reports.index') }}"
                    class="grid gap-3 md:grid-cols-3">
                    <div>
                        <label for="cleaning_id" class="mb-2 block text-sm font-medium">Kebersihan</label>
                        <select id="cleaning_id" name="cleaning_id"
                            class="block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach ($cleaningUsers as $cleaningUser)
                                <option value="{{ $cleaningUser->id }}" @selected((int) request('cleaning_id', $selectedCleaning?->id) === $cleaningUser->id)>
                                    {{ $cleaningUser->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="month" class="mb-2 block text-sm font-medium">Bulan</label>
                        <select id="month" name="month"
                            class="block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach ($monthOptions as $monthOption)
                                <option value="{{ $monthOption['value'] }}" @selected($selectedMonth?->format('Y-m') === $monthOption['value'])>
                                    {{ $monthOption['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-3">
                        <button type="submit"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                            Tampilkan
                        </button>
                    </div>
                </form>
            </div>
        </section>

        @if ($report)
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($report['summaryCards'] as $card)
                    <article
                        class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">{{ $card['label'] }}
                        </p>
                        <p class="mt-4 text-2xl font-semibold text-slate-900 dark:text-white">{{ $card['value'] }}</p>
                        <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $card['description'] }}
                        </p>
                    </article>
                @endforeach
            </section>

            <section
                class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p
                            class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-700 dark:text-emerald-300">
                            PDF Siap Dicetak
                        </p>
                        <h4 class="mt-2 text-xl font-semibold">{{ $report['cleaningUser']->name }} -
                            {{ $report['monthLabel'] }}</h4>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Klik tombol di bawah untuk mengunduh PDF laporan lengkap.
                        </p>
                    </div>

                    <a href="{{ route('cleaning-attendance-reports.download', ['cleaning_id' => $report['cleaningUser']->id, 'month' => $report['month']->format('Y-m')]) }}"
                        class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Download PDF
                    </a>
                </div>
            </section>

            <section
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <h4 class="text-lg font-semibold">Statistik Bulanan</h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-800/70">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Titik</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Jadwal</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Total</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Tepat Waktu</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Terlambat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @foreach ($report['pointRows'] as $row)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium">{{ $row['label'] }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                        {{ $row['schedule_time'] ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                        {{ $row['total'] }} kali</td>
                                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                        {{ $row['on_time'] }} kali</td>
                                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                        {{ $row['late'] }} kali</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <h4 class="text-lg font-semibold">Rekap Harian</h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-800/70">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Tanggal</th>
                                @foreach ($report['dailyPointColumns'] as $column)
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        {{ $column['label'] }} ({{ $column['schedule_time'] ?? '-' }})
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @foreach ($report['dailyPointRows'] as $row)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium">
                                        {{ $row['date']->translatedFormat('d M Y') }}</td>
                                    @foreach ($row['points'] as $point)
                                        <td class="px-4 py-3 text-sm">
                                            <span
                                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                                {{ $point['status_category'] === 'late' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : '' }}
                                                {{ $point['status_category'] === 'on_time' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : '' }}
                                                {{ $point['status_category'] === 'expired' ? 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' : '' }}
                                                {{ $point['status_category'] === 'missed' ? 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200' : '' }}
                                                {{ $point['status_category'] === 'empty' ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' : '' }}">
                                                {{ $point['display_time'] }}
                                            </span>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @else
            <section
                class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-400">
                Belum ada data absensi kebersihan untuk ditampilkan.
            </section>
        @endif
    </div>
</x-layouts.app>
