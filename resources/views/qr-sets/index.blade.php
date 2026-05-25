<x-layouts.app :title="'Cetak QR | ' . config('app.name', 'Amber')" heading="Cetak QR">
    <div class="space-y-6">
        <section
            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-xl font-semibold">Cetak QR Titik Absen</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Generate satu set QR untuk titik Checkin, Checkout, dan Patroli A/B/C dalam satu file PDF.
                    </p>
                </div>

                <form method="POST" action="{{ route('qr-sets.store') }}" data-progress-form
                    data-progress-message="Membuat set QR...">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                        data-loading-text="Membuat set QR...">
                        Generate Set QR (PDF 5 Lembar)
                    </button>
                </form>
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
                                Set</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Status</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Jumlah QR</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Dibuat Oleh</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Dibuat</th>
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($qrSets as $qrSet)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium">
                                    <p>{{ $qrSet->code }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ Str::limit($qrSet->token_prefix, 22, '...') }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($qrSet->is_active)
                                        <span
                                            class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            Aktif
                                        </span>
                                    @else
                                        <span
                                            class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                    {{ $qrSet->points->count() }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                    {{ $qrSet->generator?->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                    {{ $qrSet->created_at?->format('d M Y H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('qr-sets.download', $qrSet) }}"
                                            class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                            Download PDF
                                        </a>

                                        @if (!$qrSet->is_active)
                                            <form method="POST" action="{{ route('qr-sets.activate', $qrSet) }}"
                                                data-progress-form data-progress-message="Mengaktifkan set QR...">
                                                @csrf
                                                <button type="submit"
                                                    class="rounded-xl bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200"
                                                    data-loading-text="Mengaktifkan...">
                                                    Aktifkan
                                                </button>
                                            </form>
                                        @else
                                            <span
                                                class="rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white">
                                                Sedang Aktif
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">
                                    Belum ada set QR. Klik tombol Generate Set QR untuk membuat set baru.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">
                {{ $qrSets->links() }}
            </div>
        </section>
    </div>
</x-layouts.app>
