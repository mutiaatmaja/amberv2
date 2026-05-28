(function () {
    var installEvent = null;

    function registerServiceWorker() {
        if (!('serviceWorker' in navigator)) {
            return;
        }

        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function () {
                // Ignore registration failures silently in the UI.
            });
        });
    }

    function updateInstallButtons() {
        document.querySelectorAll('[data-pwa-install]').forEach(function (button) {
            button.hidden = !installEvent;
            button.disabled = !installEvent;
        });
    }

    function bindInstallButtons() {
        document.querySelectorAll('[data-pwa-install]').forEach(function (button) {
            if (button.dataset.pwaBound === '1') {
                return;
            }

            button.dataset.pwaBound = '1';

            button.addEventListener('click', async function () {
                if (!installEvent) {
                    return;
                }

                installEvent.prompt();
                await installEvent.userChoice;
                installEvent = null;
                updateInstallButtons();
            });
        });

        updateInstallButtons();
    }

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        installEvent = event;
        updateInstallButtons();
    });

    window.addEventListener('appinstalled', function () {
        installEvent = null;
        updateInstallButtons();
    });

    registerServiceWorker();
    bindInstallButtons();
    document.addEventListener('DOMContentLoaded', bindInstallButtons);
})();
