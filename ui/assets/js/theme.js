(function () {
    'use strict';

    var STORAGE_KEY = 'mf-staffcore-theme';
    var DEFAULT_THEME = 'dark';
    var root = document.querySelector('.staffcore-admin-wrap');
    var toggles = root ? root.querySelectorAll('[data-staffcore-theme-toggle]') : null;

    if (!root || !toggles || toggles.length === 0) {
        return;
    }

    function normalizeTheme(theme) {
        return theme === 'light' || theme === 'dark' ? theme : DEFAULT_THEME;
    }

    function getStoredTheme() {
        try {
            return normalizeTheme(localStorage.getItem(STORAGE_KEY));
        } catch (e) {
            return DEFAULT_THEME;
        }
    }

    function applyTheme(theme) {
        var normalized = normalizeTheme(theme);
        var isLight = normalized === 'light';

        root.setAttribute('data-bs-theme', normalized);
        toggles.forEach(function (toggle) {
            toggle.classList.toggle('is-light', isLight);
            toggle.setAttribute('aria-pressed', isLight ? 'true' : 'false');
        });
    }

    function setTheme(theme) {
        var normalized = normalizeTheme(theme);

        try {
            localStorage.setItem(STORAGE_KEY, normalized);
        } catch (e) {
            /* ignore quota / private mode */
        }

        applyTheme(normalized);
    }

    applyTheme(getStoredTheme());

    toggles.forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            setTheme(getStoredTheme() === 'dark' ? 'light' : 'dark');
        });
    });
})();
