<x-layouts.app :title="'Tambah Jadwal Kebersihan | ' . config('app.name', 'Amber')" heading="Tambah Jadwal Kebersihan">
    <div class="mx-auto max-w-3xl">
        <section
            class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-xl font-semibold">Form Jadwal Kebersihan</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Atur jam checkin, checkout, istirahat IN,
                dan istirahat OUT untuk kebersihan.</p>

            @if ($errors->any())
                <div
                    class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('cleaning-schedules.store') }}" class="mt-6 space-y-5" data-progress-form
                data-progress-message="Menyimpan jadwal kebersihan...">
                @csrf

                <div>
                    <label for="user_id" class="mb-2 block text-sm font-medium">Kebersihan</label>
                    <select id="user_id" name="user_id" required
                        class="block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Pilih kebersihan</option>
                        @foreach ($cleaningUsers as $cleaningUser)
                            <option value="{{ $cleaningUser->id }}" @selected((string) old('user_id') === (string) $cleaningUser->id)>
                                {{ $cleaningUser->name }} ({{ $cleaningUser->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="checkin_time" class="mb-2 block text-sm font-medium">Jam Checkin</label>
                        <input id="checkin_time" name="checkin_time" type="time" value="{{ old('checkin_time') }}"
                            required
                            class="block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="checkout_time" class="mb-2 block text-sm font-medium">Jam Checkout</label>
                        <input id="checkout_time" name="checkout_time" type="time" value="{{ old('checkout_time') }}"
                            required
                            class="block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="break_in_time" class="mb-2 block text-sm font-medium">Jam Istirahat IN</label>
                        <input id="break_in_time" name="break_in_time" type="time" value="{{ old('break_in_time') }}"
                            required
                            class="block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="break_out_time" class="mb-2 block text-sm font-medium">Jam Istirahat OUT</label>
                        <input id="break_out_time" name="break_out_time" type="time"
                            value="{{ old('break_out_time') }}" required
                            class="block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('cleaning-schedules.index') }}"
                        class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                        Batal
                    </a>
                    <button type="submit" data-loading-text="Menyimpan..."
                        class="rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Simpan Jadwal
                    </button>
                </div>
            </form>
        </section>
    </div>
</x-layouts.app>
