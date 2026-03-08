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

    console.log('Mobile menu setup:', { toggle: !!mobileMenuToggle, menu: !!navMenu });

    if (mobileMenuToggle && navMenu) {
        mobileMenuToggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const isExpanded = mobileMenuToggle.getAttribute('aria-expanded') === 'true';
            mobileMenuToggle.setAttribute('aria-expanded', !isExpanded);
            mobileMenuToggle.classList.toggle('active');
            navMenu.classList.toggle('active');

            console.log('Menu toggled:', { isActive: navMenu.classList.contains('active') });
        });

        // Close menu when clicking on a nav link
        navMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
                mobileMenuToggle.classList.remove('active');
                navMenu.classList.remove('active');
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!mobileMenuToggle.contains(e.target) && !navMenu.contains(e.target)) {
                if (navMenu.classList.contains('active')) {
                    mobileMenuToggle.setAttribute('aria-expanded', 'false');
                    mobileMenuToggle.classList.remove('active');
                    navMenu.classList.remove('active');
                }
            }
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

    // Update page title if data attributes are present
    const titleAttr = document.documentElement.getAttribute('data-title-' + lang);
    if (titleAttr) {
        document.title = titleAttr;
    }
}

// ========================================
// BIF APP - Global application object
// ========================================
window.bifApp = {
    currentSlide: 0,
    totalSlides: 0,
    touchStartX: 0,
    touchEndX: 0,

    // Initialize carousel
    initCarousel() {
        const container = document.getElementById('fightersContainer');
        if (!container) return;

        const cards = container.querySelectorAll('.fighter-card');
        this.totalSlides = cards.length;

        if (this.totalSlides === 0) return;

        // Add touch event listeners for swipe
        container.addEventListener('touchstart', (e) => {
            this.touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        container.addEventListener('touchend', (e) => {
            this.touchEndX = e.changedTouches[0].screenX;
            this.handleSwipe();
        }, { passive: true });

        // Build dots and show first slide
        this.buildDots();
        this.showSlide(0);
        this.updateArrows();

        // Recalculate on resize
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                this.buildDots();
                this.showSlide(this.currentSlide);
                this.updateArrows();
            }, 150);
        });
    },

    // How many cards visible at once
    getVisibleCount() {
        const carousel = document.querySelector('.fighters-carousel');
        if (!carousel) return 1;
        const carouselWidth = carousel.clientWidth;
        if (carouselWidth >= 1024) return 3;
        if (carouselWidth >= 680) return 2;
        return 1;
    },

    // Max valid slide index
    getMaxSlide() {
        const visible = this.getVisibleCount();
        return Math.max(0, this.totalSlides - visible);
    },

    // Build navigation dots dynamically based on positions
    buildDots() {
        const nav = document.getElementById('carouselNav');
        if (!nav) return;
        const maxSlide = this.getMaxSlide();
        const dotCount = maxSlide + 1;
        nav.innerHTML = '';
        for (let i = 0; i < dotCount; i++) {
            const dot = document.createElement('button');
            dot.className = 'nav-dot' + (i === this.currentSlide ? ' active' : '');
            dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
            dot.addEventListener('click', () => this.showSlide(i));
            nav.appendChild(dot);
        }
    },

    // Handle swipe gesture
    handleSwipe() {
        const swipeThreshold = 50;
        const diff = this.touchStartX - this.touchEndX;

        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                this.nextSlide();
            } else {
                this.previousSlide();
            }
        }
    },

    // Show specific slide
    showSlide(index) {
        if (this.totalSlides === 0) return;

        const maxSlide = this.getMaxSlide();

        // Clamp - no wrapping
        if (index < 0) index = 0;
        if (index > maxSlide) index = maxSlide;

        this.currentSlide = index;

        const container = document.getElementById('fightersContainer');
        if (container) {
            const cards = container.querySelectorAll('.fighter-card');
            if (cards.length === 0) return;
            // Calculate offset based on actual card width + gap
            const card = cards[0];
            const style = getComputedStyle(container);
            const gap = parseFloat(style.gap) || 0;
            const cardWidth = card.offsetWidth + gap;
            container.style.transform = `translateX(-${index * cardWidth}px)`;
        }

        // Update navigation dots
        document.querySelectorAll('.nav-dot').forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });

        this.updateArrows();
    },

    // Update arrow visibility
    updateArrows() {
        const prevBtn = document.querySelector('.carousel-arrow.prev');
        const nextBtn = document.querySelector('.carousel-arrow.next');
        if (prevBtn) prevBtn.style.opacity = this.currentSlide <= 0 ? '0.3' : '1';
        if (nextBtn) nextBtn.style.opacity = this.currentSlide >= this.getMaxSlide() ? '0.3' : '1';
        if (prevBtn) prevBtn.style.pointerEvents = this.currentSlide <= 0 ? 'none' : 'auto';
        if (nextBtn) nextBtn.style.pointerEvents = this.currentSlide >= this.getMaxSlide() ? 'none' : 'auto';
    },

    // Next slide
    nextSlide() {
        this.showSlide(this.currentSlide + 1);
    },

    // Previous slide
    previousSlide() {
        this.showSlide(this.currentSlide - 1);
    },

    // Switch language
    switchLanguage(lang) {
        switchLanguage(lang);
        localStorage.setItem('bif-language', lang);
    },

    // Open jersey modal
    openJerseyModal(type) {
        alert(`Jersey details modal for ${type} jersey - Coming soon!`);
        // TODO: Implement jersey details modal
    },

    // Open order form
    openOrderForm(type) {
        const modal = document.getElementById('orderModal');
        if (modal) {
            const jerseyType = document.getElementById('jersey-type');
            if (jerseyType) {
                jerseyType.value = type;
            }
            modal.style.display = 'flex';
        }
    },

    // Close order modal
    closeOrderModal() {
        const modal = document.getElementById('orderModal');
        if (modal) {
            modal.style.display = 'none';
        }
    },

    // Handle order submit
    async handleOrderSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);

        try {
            const response = await fetch('api/submit-jersey-order.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                alert('Porudžbina uspešno poslata! / Order successfully submitted!');
                this.closeOrderModal();
                form.reset();
            } else {
                alert('Greška: ' + (result.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Order submission error:', error);
            alert('Došlo je do greške. Pokušajte ponovo.');
        }
    },

    // Handle contact submit
    async handleContactSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);

        try {
            const response = await fetch('api/submit-contact.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                alert('Poruka uspešno poslata! / Message sent successfully!');
                form.reset();
            } else {
                alert('Greška: ' + (result.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Contact form error:', error);
            alert('Došlo je do greške. Pokušajte ponovo.');
        }
    },

    // Handle newsletter submit
    async handleNewsletterSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);

        try {
            const response = await fetch('api/submit-newsletter.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                alert('Uspešno ste se prijavili! / Successfully subscribed!');
                form.reset();
            } else {
                alert('Greška: ' + (result.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Newsletter submission error:', error);
            alert('Došlo je do greške. Pokušajte ponovo.');
        }
    }
};

// Initialize carousel when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    bifApp.initCarousel();
    console.log('BIF App initialized');
});
