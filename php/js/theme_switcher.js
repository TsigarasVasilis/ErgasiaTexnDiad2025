// js/theme_switcher.js
document.addEventListener('DOMContentLoaded', function () {
    const themeToggleButton = document.getElementById('theme-toggle-button');
    const currentTheme = getCookie('theme_preference');

    // Apply saved theme on load
    if (currentTheme) {
        document.body.classList.add(currentTheme);
    } else {
        // Default to light theme if no preference or cookie expires
        document.body.classList.add('light-theme'); 
    }

    themeToggleButton.addEventListener('click', () => {
        if (document.body.classList.contains('dark-theme')) {
            document.body.classList.remove('dark-theme');
            document.body.classList.add('light-theme');
            setCookie('theme_preference', 'light-theme', 365);
        } else {
            document.body.classList.remove('light-theme');
            document.body.classList.add('dark-theme');
            setCookie('theme_preference', 'dark-theme', 365);
        }
    });

    // Cookie helper functions
    function setCookie(name, value, days) {
        let expires = "";
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Lax";
    }

    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
});