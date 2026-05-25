<x-layouts.app :title="'Detail User | ' . config('app.name', 'Amber')" heading="Detail User">
    <div class="mx-auto max-w-3xl">
        <section
            class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-xl font-semibold">Informasi Pengguna</h3>
                <a href="{{ route('users.edit', $user) }}"
                    class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                    Ubah
                </a>
            </div>

            <dl class="mt-6 space-y-4 text-sm">
                <div>
                    <dt class="font-semibold text-slate-500">Nama</dt>
                    <dd class="mt-1">{{ $user->name }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Email</dt>
                    <dd class="mt-1">{{ $user->email }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Role</dt>
                    <dd class="mt-1">{{ $user->roles->pluck('display_name')->join(', ') ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Dibuat</dt>
                    <dd class="mt-1">
                        {{ $user->created_at?->locale(app()->getLocale())->isoFormat('D MMMM YYYY, HH:mm') }}</dd>
                </div>
            </dl>

            <div class="mt-6">
                <a href="{{ route('users.index') }}"
                    class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                    Kembali
                </a>
            </div>
        </section>
    </div>
</x-layouts.app>
