@props([
    'title' => config('app.name', 'Amber'),
    'heading' => 'Dashboard',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
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
                            700: '#11433c',
                            900: '#0e1e1b',
                        },
                    },
                    boxShadow: {
                        panel: '0 22px 50px -24px rgba(15, 23, 42, 0.35)',
                    },
                },
            },
        };
    </script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script defer src="{{ asset('js/app-shell.js') }}?v={{ filemtime(public_path('js/app-shell.js')) }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="min-h-full bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100" x-data="appShell()"
    x-init="init()" data-toast='@json(session('toast'))' @keydown.escape.window="closeSidebar()">
    <div class="flex min-h-screen">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 px-6" x-show="processing"
            x-transition.opacity>
            <div class="rounded-2xl bg-white px-6 py-4 text-center shadow-xl dark:bg-slate-900">
                <div class="mx-auto h-7 w-7 animate-spin rounded-full border-2 border-emerald-600 border-t-transparent">
                </div>
                <p class="mt-3 text-sm font-medium" x-text="processingMessage"></p>
            </div>
        </div>

        <div class="fixed right-4 top-4 z-50 w-full max-w-sm" x-show="showToast && toast"
            x-transition.opacity.duration.200ms>
            <div class="rounded-2xl border bg-white p-4 shadow-lg dark:bg-slate-900"
                :class="toast && toast.type === 'success' ? 'border-emerald-200' : 'border-rose-200'">
                <p class="text-sm font-semibold"
                    :class="toast && toast.type === 'success' ? 'text-emerald-700 dark:text-emerald-300' :
                        'text-rose-700 dark:text-rose-300'"
                    x-text="toast ? toast.message : ''"></p>
            </div>
        </div>

        <div class="fixed inset-0 z-30 bg-slate-950/50 backdrop-blur-sm transition md:hidden" x-show="sidebarOpen"
            x-transition.opacity.duration.200ms x-cloak @click="closeSidebar"></div>

        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white/95 px-5 py-6 shadow-panel transition duration-300 ease-out dark:border-slate-800 dark:bg-slate-900/95 md:static md:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : ''" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="-translate-x-full opacity-90" x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="-translate-x-full opacity-90">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-emerald-600">Amber</p>
                    <h1 class="mt-2 text-xl font-semibold">Security Panel</h1>
                </div>
                <button type="button"
                    class="rounded-full border border-slate-200 p-2 text-slate-500 dark:border-slate-700 dark:text-slate-300 md:hidden"
                    @click="closeSidebar">
                    <span class="sr-only">Tutup sidebar</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div class="mt-8 rounded-3xl bg-slate-900 px-4 py-5 text-white dark:bg-slate-800">
                <p class="text-sm text-slate-300">Masuk sebagai</p>
                <p class="mt-2 text-lg font-semibold">{{ auth()->user()->name }}</p>
                <p class="mt-1 text-sm text-slate-300">{{ auth()->user()->email }}</p>
            </div>

            <nav class="mt-8 space-y-2">
                <a href="{{ route('dashboard') }}" @click="handleNavClick()"
                    class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                    <span
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 {{ request()->routeIs('dashboard') ? 'text-white' : 'bg-slate-100 text-emerald-700 dark:bg-slate-800 dark:text-emerald-300' }}">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path
                                d="M10.894 2.553a1 1 0 0 0-1.788 0l-1.07 2.166-2.39.347a1 1 0 0 0-.554 1.706l1.73 1.686-.408 2.38a1 1 0 0 0 1.45 1.054L10 10.77l2.14 1.122a1 1 0 0 0 1.45-1.054l-.408-2.38 1.73-1.686a1 1 0 0 0-.554-1.706l-2.39-.347-1.07-2.166Z" />
                            <path
                                d="M4 13.5A1.5 1.5 0 0 1 5.5 12h9a1.5 1.5 0 0 1 1.5 1.5v1A3.5 3.5 0 0 1 12.5 18h-5A3.5 3.5 0 0 1 4 14.5v-1Z" />
                        </svg>
                    </span>
                    Dashboard
                </a>

                @role('admin|supervisor')
                    <a href="{{ route('users.index') }}" @click="handleNavClick()"
                        class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('users.*') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                        <span
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 {{ request()->routeIs('users.*') ? 'text-white' : 'bg-slate-100 text-emerald-700 dark:bg-slate-800 dark:text-emerald-300' }}">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path
                                    d="M3 5.75A2.75 2.75 0 0 1 5.75 3h8.5A2.75 2.75 0 0 1 17 5.75v8.5A2.75 2.75 0 0 1 14.25 17h-8.5A2.75 2.75 0 0 1 3 14.25v-8.5Zm3.5 2.25a1 1 0 0 0 0 2h7a1 1 0 1 0 0-2h-7Zm0 4a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2h-4Z" />
                            </svg>
                        </span>
                        Kelola User
                    </a>

                    <a href="{{ route('schedules.index') }}" @click="handleNavClick()"
                        class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('schedules.*') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                        <span
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 {{ request()->routeIs('schedules.*') ? 'text-white' : 'bg-slate-100 text-emerald-700 dark:bg-slate-800 dark:text-emerald-300' }}">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path
                                    d="M5.75 3A2.75 2.75 0 0 0 3 5.75v8.5A2.75 2.75 0 0 0 5.75 17h8.5A2.75 2.75 0 0 0 17 14.25v-8.5A2.75 2.75 0 0 0 14.25 3h-8.5ZM6 7.25a.75.75 0 0 1 .75-.75h6.5a.75.75 0 0 1 0 1.5h-6.5A.75.75 0 0 1 6 7.25Zm0 3.5a.75.75 0 0 1 .75-.75h3.5a.75.75 0 0 1 0 1.5h-3.5A.75.75 0 0 1 6 10.75Zm0 3.5a.75.75 0 0 1 .75-.75h5.5a.75.75 0 0 1 0 1.5h-5.5a.75.75 0 0 1-.75-.75Z" />
                            </svg>
                        </span>
                        Jadwal
                    </a>

                    <a href="{{ route('qr-sets.index') }}" @click="handleNavClick()"
                        class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('qr-sets.*') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                        <span
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 {{ request()->routeIs('qr-sets.*') ? 'text-white' : 'bg-slate-100 text-emerald-700 dark:bg-slate-800 dark:text-emerald-300' }}">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path
                                    d="M4 3a1 1 0 0 0-1 1v4a1 1 0 1 0 2 0V5h3a1 1 0 1 0 0-2H4Zm8 0a1 1 0 1 0 0 2h3v3a1 1 0 1 0 2 0V4a1 1 0 0 0-1-1h-4ZM4 11a1 1 0 0 0-1 1v4a1 1 0 0 0 1 1h4a1 1 0 1 0 0-2H5v-3a1 1 0 0 0-1-1Zm12 0a1 1 0 0 0-1 1v3h-3a1 1 0 1 0 0 2h4a1 1 0 0 0 1-1v-4a1 1 0 0 0-1-1ZM8 8a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2H8Zm0 4a1 1 0 1 0 0 2h2a1 1 0 1 0 0-2H8Z" />
                            </svg>
                        </span>
                        Cetak QR
                    </a>

                    <a href="{{ route('settings.edit') }}" @click="handleNavClick()"
                        class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('settings.*') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                        <span
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 {{ request()->routeIs('settings.*') ? 'text-white' : 'bg-slate-100 text-emerald-700 dark:bg-slate-800 dark:text-emerald-300' }}">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M7.84 1.804a1 1 0 0 1 1.91 0l.21.732a1 1 0 0 0 .95.722h.769a1 1 0 0 0 .95-.722l.21-.732a1 1 0 1 1 1.91 0l.21.732a1 1 0 0 0 .733.693l.74.188a1 1 0 0 1 .588 1.626l-.495.588a1 1 0 0 0-.219.96l.2.737a1 1 0 0 1-1.17 1.229l-.76-.16a1 1 0 0 0-.936.27l-.553.54a1 1 0 0 0-.286.932l.148.773a1 1 0 0 1-1.245 1.154l-.734-.22a1 1 0 0 0-.96.22l-.588.495a1 1 0 0 1-1.626-.588l-.188-.74a1 1 0 0 0-.693-.733l-.732-.21a1 1 0 0 1 0-1.91l.732-.21a1 1 0 0 0 .693-.733l.188-.74a1 1 0 0 1 1.626-.588l.588.495a1 1 0 0 0 .96.219l.737-.2a1 1 0 0 0 .693-.733l.188-.74a1 1 0 0 1 .722-.95l.732-.21ZM10 7.5A2.5 2.5 0 1 0 10 12.5 2.5 2.5 0 0 0 10 7.5Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                        Pengaturan
                    </a>
                @endrole
            </nav>

            <div class="mt-auto space-y-3 pt-8">
                <button type="button"
                    class="flex w-full items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-600 transition hover:border-emerald-300 hover:text-emerald-700 dark:border-slate-700 dark:text-slate-200 dark:hover:border-emerald-500 dark:hover:text-emerald-300"
                    @click="toggleTheme">
                    <span>Mode tampilan</span>
                    <span x-text="darkMode ? 'Gelap' : 'Terang'"></span>
                </button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex flex-1 flex-col md:pl-0">
            <header
                class="sticky top-0 z-20 border-b border-slate-200 bg-white/80 backdrop-blur dark:border-slate-800 dark:bg-slate-950/75">
                <div class="flex items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button"
                            class="inline-flex shrink-0 items-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 md:hidden"
                            @click="toggleSidebar">
                            <span>Menu</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M2 4.75A.75.75 0 0 1 2.75 4h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 4.75Zm0 5A.75.75 0 0 1 2.75 9h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 9.75Zm0 5a.75.75 0 0 1 .75-.75h9.5a.75.75 0 0 1 0 1.5h-9.5a.75.75 0 0 1-.75-.75Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-emerald-600">Area Aman</p>
                            <h2 class="mt-1 truncate text-lg font-semibold">{{ $heading }}</h2>
                        </div>
                    </div>
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-medium">
                            {{ now()->locale(app()->getLocale())->isoFormat('dddd, D MMMM YYYY') }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ now()->format('H:i') }} WIB</p>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>

</html>
