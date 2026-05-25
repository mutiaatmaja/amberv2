(function () {
    var root = document.documentElement;
    var storageKey = 'amber-theme';

    function applyTheme(theme) {
        root.classList.toggle('dark', theme === 'dark');
        root.dataset.theme = theme;
    }

    var savedTheme = localStorage.getItem(storageKey);
    var systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    applyTheme(savedTheme || (systemPrefersDark ? 'dark' : 'light'));

    window.appShell = function () {
        return {
            sidebarOpen: false,
            darkMode: root.classList.contains('dark'),
            processing: false,
            processingMessage: 'Memproses data...',
            toast: null,
            showToast: false,
            init: function () {
                var toastRaw = document.body.dataset.toast || '';
                if (toastRaw) {
                    try {
                        this.toast = JSON.parse(toastRaw);
                        this.showToast = true;
                        var self = this;
                        window.setTimeout(function () {
                            self.showToast = false;
                        }, 3500);
                    } catch (error) {
                        this.toast = null;
                    }
                }

                this.bindProgressForms();
            },
            toggleSidebar: function () {
                this.sidebarOpen = !this.sidebarOpen;
            },
            closeSidebar: function () {
                this.sidebarOpen = false;
            },
            handleNavClick: function () {
                if (window.innerWidth < 768) {
                    this.closeSidebar();
                }
            },
            toggleTheme: function () {
                this.darkMode = !this.darkMode;
                var nextTheme = this.darkMode ? 'dark' : 'light';
                localStorage.setItem(storageKey, nextTheme);
                applyTheme(nextTheme);
            },
            startProcessing: function (message) {
                this.processing = true;
                this.processingMessage = message || 'Memproses data...';
            },
            bindProgressForms: function () {
                var self = this;
                var forms = document.querySelectorAll('form[data-progress-form]');

                forms.forEach(function (form) {
                    if (form.dataset.progressBound === '1') {
                        return;
                    }

                    form.dataset.progressBound = '1';

                    form.addEventListener('submit', function () {
                        self.startProcessing(form.dataset.progressMessage);

                        form.querySelectorAll('button[type="submit"]').forEach(function (button) {
                            if (!button.dataset.originalText) {
                                button.dataset.originalText = button.innerHTML;
                            }

                            button.disabled = true;
                            button.classList.add('opacity-70', 'cursor-not-allowed');

                            if (button.dataset.loadingText) {
                                button.innerHTML = button.dataset.loadingText;
                            }
                        });
                    });
                });
            }
        };
    };

    function registerAppShell() {
        if (!window.Alpine || window.AlpineAppShellRegistered) {
            return;
        }

        window.Alpine.data('appShell', window.appShell);

        window.AlpineAppShellRegistered = true;
    }

    registerAppShell();
    document.addEventListener('alpine:init', registerAppShell);
})();
