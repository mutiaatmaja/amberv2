<x-layouts.app :title="'Detail Jadwal | ' . config('app.name', 'Amber')" heading="Detail Jadwal Satpam">
    <div class="mx-auto max-w-3xl">
        <section
            class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-xl font-semibold">Informasi Jadwal</h3>
                <a href="{{ route('schedules.edit', $schedule) }}"
                    class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                    Ubah
                </a>
            </div>

            <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="font-semibold text-slate-500">Nama Satpam</dt>
                    <dd class="mt-1">{{ $schedule->user->name }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Email Satpam</dt>
                    <dd class="mt-1">{{ $schedule->user->email }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Jam Checkin</dt>
                    <dd class="mt-1">{{ \Illuminate\Support\Carbon::parse($schedule->checkin_time)->format('H:i') }}
                    </dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Jam Checkout</dt>
                    <dd class="mt-1">{{ \Illuminate\Support\Carbon::parse($schedule->checkout_time)->format('H:i') }}
                    </dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Patroli 1</dt>
                    <dd class="mt-1">
                        {{ \Illuminate\Support\Carbon::parse($schedule->patrol_1_time)->format('H:i') }}
                    </dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Standby 1</dt>
                    <dd class="mt-1">
                        {{ \Illuminate\Support\Carbon::parse($schedule->standby_1_time)->format('H:i') }}
                    </dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Patroli 2</dt>
                    <dd class="mt-1">
                        {{ \Illuminate\Support\Carbon::parse($schedule->patrol_2_time)->format('H:i') }}
                    </dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Standby 2</dt>
                    <dd class="mt-1">
                        {{ \Illuminate\Support\Carbon::parse($schedule->standby_2_time)->format('H:i') }}
                    </dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Diperbarui</dt>
                    <dd class="mt-1">
                        {{ $schedule->updated_at?->locale(app()->getLocale())->isoFormat('D MMMM YYYY, HH:mm') }}</dd>
                </div>
            </dl>

            <div class="mt-6 flex items-center justify-between">
                <a href="{{ route('schedules.index') }}"
                    class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                    Kembali
                </a>

                <form method="POST" action="{{ route('schedules.destroy', $schedule) }}" data-progress-form
                    data-progress-message="Menghapus jadwal...">
                    @csrf
                    @method('DELETE')
                    <button type="submit" data-loading-text="Menghapus..."
                        class="rounded-2xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700">
                        Hapus Jadwal
                    </button>
                </form>
            </div>
        </section>
    </div>
</x-layouts.app>
