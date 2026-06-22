<x-layouts.app :title="'Kelola Absensi Kebersihan | ' . config('app.name', 'Amber')" heading="Kelola Absensi Kebersihan">
    <div class="space-y-6">
        {{-- Filter --}}
        <section
            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h3 class="mb-4 text-base font-semibold">Filter Absensi</h3>
            <form method="GET" action="{{ route('cleaning-attendance-logs.index') }}"
                class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="user_id" class="mb-1 block text-sm font-medium">Kebersihan</label>
                    <select id="user_id" name="user_id" required
                        class="block w-full rounded-2xl border-slate-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        <option value="">— Pilih Kebersihan —</option>
                        @foreach ($cleaningUsers as $cleaningUser)
                            <option value="{{ $cleaningUser->id }}" @selected($selectedUserId == $cleaningUser->id)>
                                {{ $cleaningUser->name }}
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
            {{-- Prompt to pick cleaning user --}}
            <section
                class="rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-sm text-slate-500 dark:text-slate-400">Pilih kebersihan dan bulan untuk melihat
                    data
                    absensi.</p>
            </section>
        @elseif (!$selectedCleaningUser)
            <section
                class="rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-sm text-rose-500">Kebersihan tidak ditemukan.</p>
            </section>
        @else
            {{-- Daily Table --}}
            <section
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div
                    class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <div>
                        <p class="font-semibold">{{ $selectedCleaningUser->name }}</p>
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
                                                    <a href="{{ route('cleaning-attendance-logs.edit', $point['log']->id) }}"
                                                        class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-300 text-slate-600 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                                        title="Lihat detail absensi" aria-label="Lihat detail absensi">
                                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"
                                                            aria-hidden="true">
                                                            <path
                                                                d="M10 4c4.438 0 7.52 3.126 8.71 5.1a1.67 1.67 0 0 1 0 1.8C17.52 12.874 14.438 16 10 16s-7.52-3.126-8.71-5.1a1.67 1.67 0 0 1 0-1.8C2.48 7.126 5.562 4 10 4Zm0 2C6.645 6 4.09 8.35 3.01 10 4.09 11.65 6.645 14 10 14s5.91-2.35 6.99-4C15.91 8.35 13.355 6 10 6Zm0 1.5A2.5 2.5 0 1 1 10 12.5 2.5 2.5 0 0 1 10 7.5Zm0 2A.5.5 0 1 0 10 10.5.5.5 0 0 0 10 9.5Z" />
                                                        </svg>
                                                        <span class="sr-only">Lihat detail absensi</span>
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
