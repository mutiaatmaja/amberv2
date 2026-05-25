<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | {{ config('app.name', 'Amber') }}</title>
    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f4f8f7',
                            100: '#dce8e5',
                            500: '#1f6f64',
                            600: '#18594f',
                            900: '#0e1e1b',
                        },
                    },
                },
            },
        };
    </script>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script defer src="{{ asset('js/app-shell.js') }}?v={{ filemtime(public_path('js/app-shell.js')) }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="min-h-full bg-slate-950 text-slate-100" x-data="appShell()">
    <div class="relative isolate flex min-h-screen items-center overflow-hidden px-4 py-10 sm:px-6 lg:px-8">
        <div
            class="absolute inset-0 -z-20 bg-[radial-gradient(circle_at_top_right,_rgba(31,111,100,0.35),_transparent_35%),radial-gradient(circle_at_bottom_left,_rgba(148,163,184,0.25),_transparent_30%),linear-gradient(135deg,_#020617,_#0f172a_45%,_#111827)]">
        </div>
        <div class="absolute inset-0 -z-10 opacity-40" aria-hidden="true">
            <div class="absolute left-1/2 top-24 h-72 w-72 -translate-x-1/2 rounded-full bg-brand-500/30 blur-3xl">
            </div>
            <div class="absolute bottom-0 right-0 h-72 w-72 rounded-full bg-cyan-400/20 blur-3xl"></div>
        </div>

        <div class="mx-auto grid w-full max-w-6xl gap-8 lg:grid-cols-[1.1fr_0.9fr]">
            <section class="hidden rounded-[2rem] border border-white/10 bg-white/5 p-8 backdrop-blur lg:block xl:p-12">
                <p class="text-sm font-semibold uppercase tracking-[0.35em] text-brand-100">Amber Security</p>
                <h1 class="mt-6 max-w-xl text-4xl font-semibold leading-tight text-white xl:text-5xl">
                    Sistem akses satpam yang ringkas, cepat, dan siap dipakai di dashboard operasional.
                </h1>
                <p class="mt-6 max-w-lg text-base leading-7 text-slate-300">
                    Login untuk mengakses panel administrasi dan monitoring awal. Halaman ini menggunakan session auth
                    Laravel standar tanpa fitur registrasi publik.
                </p>

                <div class="mt-10 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-5">
                        <p class="text-sm text-slate-400">Keamanan sesi</p>
                        <p class="mt-2 text-2xl font-semibold text-white">Laravel Session</p>
                        <p class="mt-3 text-sm leading-6 text-slate-300">Regenerasi session ID dilakukan otomatis
                            setelah login berhasil.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-5">
                        <p class="text-sm text-slate-400">Landing page</p>
                        <p class="mt-2 text-2xl font-semibold text-white">Dashboard</p>
                        <p class="mt-3 text-sm leading-6 text-slate-300">Semua user yang lolos autentikasi diarahkan
                            langsung ke dashboard utama.</p>
                    </div>
                </div>
            </section>

            <section
                class="rounded-[2rem] border border-white/10 bg-white p-6 text-slate-900 shadow-2xl shadow-black/30 sm:p-8 lg:p-10 dark:bg-slate-900 dark:text-slate-100">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.3em] text-brand-500">Masuk</p>
                        <h2 class="mt-3 text-3xl font-semibold">Login ke dashboard</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400">
                            Gunakan akun yang sudah dibuat admin. Fitur register publik dinonaktifkan.
                        </p>
                    </div>
                    <button type="button"
                        class="inline-flex h-11 items-center rounded-2xl border border-slate-300 bg-slate-100 px-4 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                        @click="toggleTheme">
                        <span x-text="darkMode ? 'Mode gelap' : 'Mode terang'"></span>
                    </button>
                </div>

                @if (session('status'))
                    <div
                        class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-200">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div
                        class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-200">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="mb-2 block text-sm font-medium">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required
                            autofocus autocomplete="username"
                            class="block w-full rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                            placeholder="admin@example.com">
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <label for="password" class="block text-sm font-medium">Password</label>
                            <span class="text-xs text-slate-400">Session based auth</span>
                        </div>
                        <input id="password" name="password" type="password" required autocomplete="current-password"
                            class="block w-full rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                            placeholder="Masukkan password">
                    </div>

                    <label
                        class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:bg-slate-800/70 dark:text-slate-300">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))
                            class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        Ingat saya di perangkat ini
                    </label>

                    <button type="submit"
                        class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-900/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:bg-emerald-500 dark:hover:bg-emerald-400">
                        Login
                    </button>
                </form>
            </section>
        </div>
    </div>
</body>

</html>
