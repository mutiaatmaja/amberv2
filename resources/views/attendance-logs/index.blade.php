<x-layouts.app :title="'Kelola Absensi | ' . config('app.name', 'Amber')" heading="Kelola Absensi">
    <div class="space-y-6">
        {{-- Filter --}}
        <section
            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h3 class="mb-4 text-base font-semibold">Filter Absensi</h3>
            <form method="GET" action="{{ route('attendance-logs.index') }}"
                class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="user_id" class="mb-1 block text-sm font-medium">Satpam</label>
                    <select id="user_id" name="user_id" required
                        class="block w-full rounded-2xl border-slate-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        <option value="">— Pilih Satpam —</option>
                        @foreach ($satpams as $satpam)
                            <option value="{{ $satpam->id }}" @selected($selectedUserId == $satpam->id)>
                                {{ $satpam->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-44">
                    <label for="month" class="mb-1 block text-sm font-medium">Bulan</label>
                    <input id="month" name="month" type="month" value="{{ $selectedMonth }}"
                        class="block w-full rounded-2xl border-slate-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                <button type="submit"
                    class="rounded-2xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                    Tampilkan
                </button>
            </form>
        </section>

        @if (!$selectedUserId)
            {{-- Prompt to pick satpam --}}
            <section
                class="rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-sm text-slate-500 dark:text-slate-400">Pilih satpam dan bulan untuk melihat data
                    absensi.</p>
            </section>
        @elseif (!$selectedSatpam)
            <section
                class="rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-sm text-rose-500">Satpam tidak ditemukan.</p>
            </section>
        @else
            {{-- Daily Table --}}
            <section
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div
                    class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <div>
                        <p class="font-semibold">{{ $selectedSatpam->name }}</p>
                        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                            {{ \Carbon\Carbon::createFromFormat('Y-m', $selectedMonth)->translatedFormat('F Y') }}
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-800/70">
                            <tr>
                                <th
                                    class="sticky left-0 bg-slate-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/70">
                                    Tanggal</th>
                                @foreach ($pointColumns as $col)
                                    <th
                                        class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        {{ $col['label'] }}
                                        @if ($col['schedule_time'])
                                            <span
                                                class="block font-normal normal-case text-slate-400">({{ $col['schedule_time'] }})</span>
                                        @endif
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @foreach ($dailyRows as $row)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td
                                        class="sticky left-0 bg-white px-4 py-2.5 text-sm font-medium dark:bg-slate-900">
                                        {{ $row['date']->format('d') }}
                                        <span
                                            class="ml-1 text-xs text-slate-400">{{ $row['date']->translatedFormat('D') }}</span>
                                        @if ($row['cycle']?->status === 'expired')
                                            <span
                                                class="mt-1 block text-[11px] font-semibold uppercase tracking-wide text-rose-500">Expired</span>
                                        @endif
                                    </td>
                                    @foreach ($row['points'] as $point)
                                        <td class="px-3 py-2.5 text-center text-sm">
                                            @if ($point['log'])
                                                <div class="inline-flex items-center gap-1.5">
                                                    <span
                                                        class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $point['is_expired'] ? 'bg-rose-50 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
                                                        {{ $point['display_time'] }}
                                                    </span>
                                                    <a href="{{ route('attendance-logs.edit', $point['log']->id) }}"
                                                        class="rounded-lg border border-slate-300 px-2 py-0.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                                        title="Edit">
                                                        Ubah
                                                    </a>
                                                </div>
                                            @elseif ($point['is_missed'])
                                                <span
                                                    class="inline-flex rounded-lg bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-200">Tidak
                                                    Absen</span>
                                            @else
                                                <span class="text-xs text-slate-300 dark:text-slate-600">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-layouts.app>
