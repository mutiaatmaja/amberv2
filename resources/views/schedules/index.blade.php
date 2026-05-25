<x-layouts.app :title="'Jadwal Satpam | ' . config('app.name', 'Amber')" heading="Jadwal Satpam">
    <div class="space-y-6">
        <section
            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-xl font-semibold">Daftar Jadwal Satpam</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Kelola jadwal checkin, checkout,
                        patroli 1, standby 1, patroli 2, dan standby 2 per satpam.</p>
                </div>
                <a href="{{ route('schedules.create') }}"
                    class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                    Tambah Jadwal
                </a>
            </div>
        </section>

        <section
            class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-800/70">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Satpam</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Checkin</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Checkout</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Patroli 1</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Standby 1</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Patroli 2</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Standby 2</th>
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($schedules as $schedule)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium">{{ $schedule->user->name }}</td>
                                <td class="px-4 py-3 text-sm">
                                    {{ \Illuminate\Support\Carbon::parse($schedule->checkin_time)->format('H:i') }}</td>
                                <td class="px-4 py-3 text-sm">
                                    {{ \Illuminate\Support\Carbon::parse($schedule->checkout_time)->format('H:i') }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {{ \Illuminate\Support\Carbon::parse($schedule->patrol_1_time)->format('H:i') }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {{ \Illuminate\Support\Carbon::parse($schedule->standby_1_time)->format('H:i') }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {{ \Illuminate\Support\Carbon::parse($schedule->patrol_2_time)->format('H:i') }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {{ \Illuminate\Support\Carbon::parse($schedule->standby_2_time)->format('H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('schedules.show', $schedule) }}"
                                            class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                            Detail
                                        </a>
                                        <a href="{{ route('schedules.edit', $schedule) }}"
                                            class="rounded-xl bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                                            Ubah
                                        </a>
                                        <form method="POST" action="{{ route('schedules.destroy', $schedule) }}"
                                            data-progress-form data-progress-message="Menghapus jadwal...">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" data-loading-text="Menghapus..."
                                                class="rounded-xl bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-rose-700">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-sm text-slate-500">Belum ada jadwal
                                    satpam.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">
                {{ $schedules->links() }}
            </div>
        </section>
    </div>
</x-layouts.app>
