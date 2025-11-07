/**
 * Main JavaScript for BIF website
 * Handles language switching, mobile menu, and theme toggle
 */

// Language switching
document.addEventListener('DOMContentLoaded', () => {
    // Get saved language or default to 'sr'
    const savedLang = localStorage.getItem('bif-language') || 'sr';
    switchLanguage(savedLang);

    // Add click listeners to all language buttons
    document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const lang = btn.getAttribute('data-lang');
            switchLanguage(lang);
            localStorage.setItem('bif-language', lang);
        });
    });

    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');

    if (mobileMenuToggle && navMenu) {
        mobileMenuToggle.addEventListener('click', () => {
            const isExpanded = mobileMenuToggle.getAttribute('aria-expanded') === 'true';
            mobileMenuToggle.setAttribute('aria-expanded', !isExpanded);
            navMenu.classList.toggle('active');
        });
    }

    // Theme toggle
    const themeToggle = document.querySelector('.theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            document.documentElement.style.colorScheme = newTheme;
            localStorage.setItem('bif-theme', newTheme);

            // Update icon
            const icon = themeToggle.querySelector('.theme-icon');
            if (icon) {
                icon.textContent = newTheme === 'dark' ? '🌙' : '☀️';
            }
        });
    }
});

function switchLanguage(lang) {
    // Hide all language content
    document.querySelectorAll('.lang-content').forEach(el => {
        el.classList.remove('active');
    });

    // Show content for selected language
    document.querySelectorAll(`.lang-content[data-lang="${lang}"]`).forEach(el => {
        el.classList.add('active');
    });

    // Update language buttons
    document.querySelectorAll('.lang-btn').forEach(btn => {
        if (btn.getAttribute('data-lang') === lang) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    // Update HTML lang attribute
    document.documentElement.setAttribute('lang', lang);
}
