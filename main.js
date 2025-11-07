/**
 * BIF - Balkan Influence Fighting
 * Main JavaScript File
 * Modern ES6+ Implementation with Performance Optimizations
 */

class BIFApp {
    constructor() {
        this.currentLang = 'sr';
        this.currentSlideIndex = 0;
        this.slidesToShow = window.innerWidth > 768 ? 3 : 1;
        this.totalSlides = 0;
        this.maxSlideIndex = 0;
        this.isAutoPlaying = true;
        this.autoPlayInterval = null;
        this.touchStartX = 0;
        this.touchEndX = 0;
        this.isScrolling = false;
        this.observers = new Map();
        
        // DOM element references
        this.themeToggleButton = null;
        this.langButtons = null;
        this.carouselPrevBtn = null;
        this.carouselNextBtn = null;
        this.carouselNavDots = null;
        this.newsletterForm = null;

        this.init();
    }

    /**
     * Initialize the application
     */
    init() {
        this.bindEvents();
        this.initializeTheme();
        this.bindThemeEvents();
        this.handleInitialLoad();
        console.log('BIF App initialized successfully');
    }

    /**
     * Bind all event listeners
     */
    bindEvents() {
        // DOM Content Loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.onDOMReady());
            } else {
            this.onDOMReady();
        }

        // Window events
        window.addEventListener('load', this.onWindowLoad.bind(this));
        window.addEventListener('resize', this.debounce(this.onWindowResize.bind(this), 250));
        window.addEventListener('scroll', this.throttle(this.onWindowScroll.bind(this), 16));
        window.addEventListener('beforeunload', this.onBeforeUnload.bind(this));

        // Visibility change for performance
        document.addEventListener('visibilitychange', this.onVisibilityChange.bind(this));

        // Error handling
        window.addEventListener('error', this.onError.bind(this));
        window.addEventListener('unhandledrejection', this.onUnhandledRejection.bind(this));

        // Keyboard navigation
        document.addEventListener('keydown', this.onKeyDown.bind(this));
    }

    /**
     * Handle DOM ready event
     */
    onDOMReady() {
        // Query elements once DOM is ready
        this.themeToggleButton = document.querySelector('.theme-toggle');
        this.langButtons = document.querySelectorAll('.lang-btn');
        this.carouselPrevBtn = document.querySelector('.carousel-arrow.prev');
        this.carouselNextBtn = document.querySelector('.carousel-arrow.next');
        this.carouselNavDots = document.querySelectorAll('.nav-dot');
        this.newsletterForm = document.querySelector('.newsletter-form');

        this.initializeNavigation();
        this.initializeLanguageSwitcher();
        this.initializeMobileMenu();
        this.initializeSmoothScrolling();
        this.initializeCarousel();
        this.initializeAnimations();
        this.initializeLazyLoading();
        this.initializePerformanceOptimizations();
        
        // Attach event listeners
        if (this.themeToggleButton) {
            this.themeToggleButton.addEventListener('click', () => this.toggleTheme());
        }

        // Mobile theme toggle
        const mobileThemeToggle = document.querySelector('.theme-toggle-mobile');
        if (mobileThemeToggle) {
            mobileThemeToggle.addEventListener('click', () => this.toggleTheme());
        }

        // Mobile menu toggle (hamburger)
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const navMenu = document.querySelector('.nav-menu');
        if (mobileMenuToggle && navMenu) {
            mobileMenuToggle.addEventListener('click', () => {
                mobileMenuToggle.classList.toggle('active');
                navMenu.classList.toggle('active');
                const isExpanded = navMenu.classList.contains('active');
                mobileMenuToggle.setAttribute('aria-expanded', isExpanded);
            });

            // Close menu when clicking on a link
            navMenu.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenuToggle.classList.remove('active');
                    navMenu.classList.remove('active');
                    mobileMenuToggle.setAttribute('aria-expanded', 'false');
                });
            });
        }

        if (this.newsletterForm) {
            this.newsletterForm.addEventListener('submit', (event) => this.handleNewsletterSubmit(event));
        }

        // Contact form event listener
        const contactForm = document.querySelector('.contact-form');
        if (contactForm) {
            contactForm.addEventListener('submit', (event) => this.handleContactSubmit(event));
        }

        // Order form event listener
        const orderForm = document.querySelector('.order-form');
        if (orderForm) {
            orderForm.addEventListener('submit', (event) => this.handleOrderSubmit(event));
        }
        
        // Update select options when language changes
        const contactSubject = document.querySelector('#contact-subject');
        if (contactSubject) {
            this.updateSelectOptions(contactSubject);
        }

        // Close modal when clicking outside
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-overlay')) {
                this.closeOrderModal();
                this.closeJerseyModal();
            }
        });

        // Update jersey form language when language changes
        document.addEventListener('languageChanged', () => {
            this.updateSizeOptions();
            this.updateOpenModalContent();
        });

        this.showSlide(0);
    }

    /**
     * Handle window load event
     */
    onWindowLoad() {
        this.startAutoPlay();
        this.initializeHeaderEffects();
        this.preloadCriticalImages();
        
        document.body.classList.add('loaded');
        this.triggerEntranceAnimations();
    }

    /**
     * Handle window resize event
     */
    onWindowResize() {
        const oldSlidesToShow = this.slidesToShow;
        this.slidesToShow = window.innerWidth > 768 ? 3 : 1;
        this.maxSlideIndex = Math.max(0, this.totalSlides - this.slidesToShow);
        
        if (oldSlidesToShow !== this.slidesToShow) {
            this.currentSlideIndex = Math.min(this.currentSlideIndex, this.maxSlideIndex);
            this.showSlide(this.currentSlideIndex, false);
        }
        
        this.updateCarouselLayout();
    }

    onWindowScroll() {
        this.updateHeaderOnScroll();
        this.updateProgressIndicator();
        this.handleScrollAnimations();
    }

    onVisibilityChange() {
        if (document.hidden) {
            this.pauseAutoPlay();
            this.pauseAnimations();
        } else {
            this.resumeAutoPlay();
            this.resumeAnimations();
        }
    }

    onBeforeUnload() {
        this.cleanup();
    }

    onError(event) {
        console.error('Application Error:', event.error);
    }

    onUnhandledRejection(event) {
        console.error('Unhandled Promise Rejection:', event.reason);
    }

    onKeyDown(event) {
        // Add focus visible class on first Tab press
        if (event.key === 'Tab') {
            document.body.classList.add('keyboard-navigation');
        }

        // Carousel keyboard navigation
        const activeElement = document.activeElement;
        if (activeElement && activeElement.closest('.fighters-carousel')) {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                this.previousSlide();
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                this.nextSlide();
            }
        }
        
        if (event.key === 'Escape') {
            this.closeOrderModal();
            this.closeJerseyModal();
            this.closeMobileMenu();
        }
    }

    // ===== THEME SYSTEM =====
    
    /**
     * Bind theme-related events
     */
    bindThemeEvents() {
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'D') {
                e.preventDefault();
                this.toggleTheme();
            }
        });

        // Removed duplicate themeChanged listener - updateThemeDependentElements is now called directly in setTheme()
    }

    initializeTheme() {
        const savedTheme = localStorage.getItem('bif-theme');
        const systemPrefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme) {
            this.setTheme(savedTheme);
        } else if (systemPrefersDark) {
            this.setTheme('dark');
        } else {
            this.setTheme('light');
        }
        
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!localStorage.getItem('bif-theme')) {
                    this.setTheme(e.matches ? 'dark' : 'light');
                }
            });
        }
        this.updateThemeToggleIcon();
        this.preloadThemeAssets();
    }

    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
        setTimeout(() => {
            document.body.style.transition = '';
        }, 300);

        this.setTheme(newTheme);
    }

    setTheme(theme) {
        const currentAttr = document.documentElement.getAttribute('data-theme');
        if (currentAttr === theme) {
            return;
        }

        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.style.colorScheme = theme === 'dark' ? 'dark' : 'light';
        if (document.body) {
            document.body.setAttribute('data-theme', theme);
            document.body.style.colorScheme = theme === 'dark' ? 'dark' : 'light';
        }
        localStorage.setItem('bif-theme', theme);
        this.updateThemeToggleIcon();
        this.updateMetaThemeColor(theme);
        this.updateThemeDependentElements();
        this.announceThemeChange(theme);
        this.preloadThemeAssets();
        this.dispatchCustomEvent('themeChanged', { theme });
    }

    updateThemeToggleIcon() {
        const themeIcon = document.querySelector('.theme-toggle .theme-icon');
        const themeIconMobile = document.querySelector('.theme-icon-mobile');
        const currentTheme = this.getCurrentTheme();
        const currentLang = this.currentLang || (navigator.language && navigator.language.startsWith('sr') ? 'sr' : 'en');

        const icon = currentTheme === 'dark' ? '☀️' : '🌙';
        const ariaLabel = currentTheme === 'dark'
            ? (currentLang === 'sr' ? 'Prebaci na svetli režim' : 'Switch to light mode')
            : (currentLang === 'sr' ? 'Prebaci na tamni režim' : 'Switch to dark mode');

        // Update desktop icon
        if (themeIcon) {
            themeIcon.textContent = icon;
            themeIcon.parentElement.setAttribute('aria-label', ariaLabel);
        }

        // Update mobile icon
        if (themeIconMobile) {
            themeIconMobile.textContent = icon;
            themeIconMobile.parentElement.setAttribute('aria-label', ariaLabel);
        }
    }
    updateMetaThemeColor(theme) {
        const metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (metaThemeColor) {
            metaThemeColor.setAttribute('content', theme === 'dark' ? '#1a1a1a' : '#c41e3a');
        }
    }

    getCurrentTheme() {
        return document.documentElement.getAttribute('data-theme') || 'light';
    }

    isDarkMode() {
        return this.getCurrentTheme() === 'dark';
    }

    updateThemeDependentElements() {
        const isDark = this.isDarkMode();
        const carouselArrows = document.querySelectorAll('.carousel-arrow');
        carouselArrows.forEach(arrow => {
            if (isDark) {
                arrow.style.background = 'linear-gradient(45deg, #e53354, #b02a47)';
            } else {
                arrow.style.background = 'linear-gradient(45deg, var(--primary-red), var(--dark-red))';
            }
        });
    }

    announceThemeChange(theme) {
        let announcement = document.getElementById('theme-announcement');
        if (!announcement) {
            announcement = document.createElement('div');
            announcement.id = 'theme-announcement';
            announcement.setAttribute('aria-live', 'polite');
            announcement.setAttribute('aria-atomic', 'true');
            announcement.style.position = 'absolute';
            announcement.style.left = '-10000px';
            announcement.style.width = '1px';
            announcement.style.height = '1px';
            announcement.style.overflow = 'hidden';
            document.body.appendChild(announcement);
        }
        
        const message = theme === 'dark' 
            ? (this.currentLang === 'sr' ? 'Uključen je tamni režim' : 'Dark mode enabled')
            : (this.currentLang === 'sr' ? 'Uključen je svetli režim' : 'Light mode enabled');
        
        announcement.textContent = message;
    }

    preloadThemeAssets() {
        if (this.isDarkMode()) {
            const darkImages = [];
            darkImages.forEach(src => {
                const img = new Image();
                img.src = src;
            });
        }
    }

    // ===== NAVIGATION SYSTEM =====

    initializeNavigation() {
        const navLinks = document.querySelectorAll('.nav-menu a[href^="#"]');
        navLinks.forEach(link => {
            link.addEventListener('click', this.handleNavClick.bind(this));
        });
    }

    handleNavClick(event) {
        event.preventDefault();
        const targetId = event.currentTarget.getAttribute('href');
        const target = document.querySelector(targetId);
        
        if (target) {
            this.smoothScrollTo(target);
            this.closeMobileMenu();
            history.pushState(null, null, targetId);
        }
    }

    smoothScrollTo(element, options = {}) {
        if (this.isScrolling) return;
        this.isScrolling = true;
        const headerHeight = document.querySelector('header')?.offsetHeight || (window.innerWidth > 768 ? 80 : 64);
        const targetPosition = element.getBoundingClientRect().top + window.pageYOffset - headerHeight;
        
        const startPosition = window.pageYOffset;
        const distance = targetPosition - startPosition;
        const duration = options.duration || 800;
        let startTime = null;

        const animation = (currentTime) => {
            if (startTime === null) startTime = currentTime;
            const timeElapsed = currentTime - startTime;
            const run = this.easeInOutQuad(timeElapsed, startPosition, distance, duration);
            
            window.scrollTo(0, run);
            
            if (timeElapsed < duration) {
                requestAnimationFrame(animation);
            } else {
                this.isScrolling = false;
            }
        };
        requestAnimationFrame(animation);
    }

    easeInOutQuad(t, b, c, d) {
        t /= d / 2;
        if (t < 1) return c / 2 * t * t + b;
        t--;
        return -c / 2 * (t * (t - 2) - 1) + b;
    }

    initializeSmoothScrolling() {
        document.querySelectorAll('a[href^="#"]:not(.nav-menu a)').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = anchor.getAttribute('href');
                try {
                    const target = document.querySelector(targetId);
                    if (target) {
                        this.smoothScrollTo(target);
                        history.pushState(null, null, targetId);
                    }
                } catch (err) {
                    console.warn('Invalid selector for smooth scroll:', targetId, err);
                }
            });
        });
    }

    // ===== LANGUAGE SYSTEM =====

    initializeLanguageSwitcher() {
        if (this.langButtons) {
            this.langButtons.forEach(btn => {
                btn.addEventListener('click', (e) => this.switchLanguage(e.target.dataset.lang || e.target.textContent.toLowerCase()));
                if (!btn.dataset.lang) btn.dataset.lang = btn.textContent.toLowerCase();
            });
        }
        this.updateLanguageButtons();
    }
    
    updateLanguageButtons() {
        if (this.langButtons) {
            this.langButtons.forEach(btn => {
                btn.classList.toggle('active', (btn.dataset.lang || btn.textContent.toLowerCase()) === this.currentLang);
            });
        }
    }

    switchLanguage(lang) {
        if (lang === this.currentLang) return;
        this.currentLang = lang;
        
        document.querySelectorAll('.lang-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll(`[data-lang="${lang}"]`).forEach(el => el.classList.add('active'));
        
        this.updateLanguageButtons();
        this.updateThemeToggleIcon();
        
        const contactSubject = document.querySelector('#contact-subject');
        if (contactSubject) {
            this.updateSelectOptions(contactSubject);
        }
        
        localStorage.setItem('bif-language', lang);
        this.dispatchCustomEvent('languageChanged', { language: lang });
    }

    updateSelectOptions(selectElement) {
        if (!selectElement) return;
        
        const options = selectElement.querySelectorAll('option');
        options.forEach(option => {
            const srText = option.getAttribute('data-sr');
            const enText = option.getAttribute('data-en');
            
            if (srText && enText) {
                option.textContent = this.currentLang === 'sr' ? srText : enText;
            }
        });
    }

    // ===== MOBILE MENU =====

    initializeMobileMenu() {
        const headerContent = document.querySelector('.header-content');
        if (!headerContent) return;
        
        let hamburger = document.querySelector('.hamburger');
        if (!hamburger) {
            hamburger = this.createHamburgerButton();
            headerContent.appendChild(hamburger);
        }
        
        hamburger.addEventListener('click', this.toggleMobileMenu.bind(this));
        
        document.addEventListener('click', (e) => {
            const navMenu = document.querySelector('.nav-menu');
            if (navMenu && navMenu.classList.contains('mobile-active') && 
                !e.target.closest('.nav-menu') && !e.target.closest('.hamburger')) {
                this.closeMobileMenu();
            }
        });
    }

    createHamburgerButton() {
        const button = document.createElement('button');
        button.className = 'hamburger';
        button.innerHTML = `<span class="hamburger-line"></span><span class="hamburger-line"></span><span class="hamburger-line"></span>`;
        button.setAttribute('aria-label', 'Toggle navigation menu');
        button.setAttribute('aria-expanded', 'false');
        
        if (!document.getElementById('hamburger-styles')) {
            const style = document.createElement('style');
            style.id = 'hamburger-styles';
            style.textContent = `
                .hamburger { display: none; flex-direction: column; gap: 4px; background: none; border: none; padding: 8px; cursor: pointer; border-radius: 6px; transition: all 0.3s ease; z-index: 1001;}
                .hamburger-line { width: 24px; height: 3px; background: white; border-radius: 2px; transition: all 0.3s ease; }
                .hamburger:hover { background: rgba(255,255,255,0.1); }
                .hamburger.active .hamburger-line:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
                .hamburger.active .hamburger-line:nth-child(2) { opacity: 0; }
                .hamburger.active .hamburger-line:nth-child(3) { transform: rotate(-45deg) translate(7px, -6px); }
                @media (max-width: 768px) { .hamburger { display: flex; } }
            `;
            document.head.appendChild(style);
        }
        return button;
    }

    toggleMobileMenu() {
        const navMenu = document.querySelector('.nav-menu');
        const hamburger = document.querySelector('.hamburger');
        if (!navMenu || !hamburger) return;
        
        const isActive = navMenu.classList.toggle('mobile-active');
        hamburger.classList.toggle('active');
        hamburger.setAttribute('aria-expanded', isActive.toString());
        
        document.body.style.overflow = isActive ? 'hidden' : '';
    }

    closeMobileMenu() {
        const navMenu = document.querySelector('.nav-menu');
        const hamburger = document.querySelector('.hamburger');
        if (!navMenu || !hamburger) return;

        if (navMenu.classList.contains('mobile-active')) {
            navMenu.classList.remove('mobile-active');
            hamburger.classList.remove('active');
            hamburger.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }
    }

    // ===== CAROUSEL SYSTEM =====

    initializeCarousel() {
        const fighterCardElements = document.querySelectorAll('#fightersContainer .fighter-card');
        this.totalSlides = fighterCardElements.length;
        this.maxSlideIndex = Math.max(0, this.totalSlides - this.slidesToShow);

        this.bindCarouselEvents();
        this.updateCarouselLayout();
        this.bindTouchEvents();
        
        if (this.carouselNavDots && this.carouselNavDots.length !== this.totalSlides) {
            this.createCarouselDots();
        }
    }
    
    createCarouselDots() {
        const navContainer = document.querySelector('.carousel-nav');
        if (!navContainer) return;
        navContainer.innerHTML = '';

        for (let i = 0; i <= this.maxSlideIndex; i++) {
            const dot = document.createElement('button');
            dot.classList.add('nav-dot');
            dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
            dot.addEventListener('click', () => this.showSlide(i));
            navContainer.appendChild(dot);
        }
        this.carouselNavDots = navContainer.querySelectorAll('.nav-dot');
        this.updateNavigationDots();
    }

    bindCarouselEvents() {
        if (this.carouselPrevBtn) this.carouselPrevBtn.addEventListener('click', () => this.previousSlide());
        if (this.carouselNextBtn) this.carouselNextBtn.addEventListener('click', () => this.nextSlide());
        
        if (this.carouselNavDots) {
            this.carouselNavDots.forEach((dot, index) => {
                if (index <= this.maxSlideIndex) {
                    dot.style.display = '';
                    dot.onclick = () => this.showSlide(index);
                } else {
                    dot.style.display = 'none';
                }
            });
        }
        
        const carousel = document.querySelector('.fighters-carousel');
        if (carousel) {
            carousel.addEventListener('mouseenter', () => this.pauseAutoPlay());
            carousel.addEventListener('mouseleave', () => this.resumeAutoPlay());
        }
    }

    bindTouchEvents() {
        const carousel = document.querySelector('.fighters-carousel');
        if (!carousel) return;
        
        if (window.innerWidth > 768) {
            carousel.addEventListener('touchstart', (e) => {
                this.touchStartX = e.touches[0].clientX;
                this.pauseAutoPlay();
            }, { passive: true });
            
            carousel.addEventListener('touchmove', (e) => {
                this.touchEndX = e.touches[0].clientX;
            }, { passive: true });
            
            carousel.addEventListener('touchend', () => {
                this.handleSwipeGesture();
                if(this.isAutoPlaying) this.resumeAutoPlay();
            });
        } else {
            const container = document.getElementById('fightersContainer');
            if (container) {
                let scrollTimeout;
                container.addEventListener('scroll', () => {
                    clearTimeout(scrollTimeout);
                    scrollTimeout = setTimeout(() => {
                        const scrollLeft = container.scrollLeft;
                        const screenWidth = window.innerWidth;
                        
                        const currentIndex = Math.round(scrollLeft / screenWidth);
                        
                        if (currentIndex !== this.currentSlideIndex && currentIndex >= 0 && currentIndex <= this.maxSlideIndex) {
                            this.currentSlideIndex = currentIndex;
                            this.updateNavigationDots();
                        }
                    }, 100);
                }, { passive: true });
            }
        }
    }

    handleSwipeGesture() {
        if (this.touchStartX === 0 || this.touchEndX === 0) return;
        const swipeThreshold = 50;
        const swipeDistance = this.touchStartX - this.touchEndX;
        
        if (Math.abs(swipeDistance) > swipeThreshold) {
            if (swipeDistance > 0) this.nextSlide();
            else this.previousSlide();
        }
        this.touchStartX = 0;
        this.touchEndX = 0;
    }

    showSlide(index, animate = true) {
        if (this.totalSlides === 0) return;
        index = Math.max(0, Math.min(index, this.maxSlideIndex));
        
        const container = document.getElementById('fightersContainer');
        if (!container) return;
        
        this.currentSlideIndex = index;
        
        const firstSlide = container.querySelector('.fighter-card');
        if (!firstSlide) return;
        
        if (window.innerWidth <= 768) {
            const slideWidth = window.innerWidth;
            const scrollPosition = index * slideWidth;
            
            if (animate) {
                container.style.scrollBehavior = 'smooth';
                container.scrollLeft = scrollPosition;
                setTimeout(() => {
                    container.style.scrollBehavior = '';
                }, 600);
            } else {
                container.style.scrollBehavior = 'auto';
                container.scrollLeft = scrollPosition;
                container.style.scrollBehavior = '';
            }
        } else {
            const cardWidth = firstSlide.offsetWidth;
            const containerStyle = window.getComputedStyle(container);
            const gap = parseFloat(containerStyle.gap) || 24;
            
            const slideWidthWithGap = cardWidth + gap;
            const translateX = -index * slideWidthWithGap;
            
            container.style.transition = animate ? 'transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)' : 'none';
            container.style.transform = `translateX(${translateX}px)`;
            
            if (!animate) {
                requestAnimationFrame(() => {
                    container.style.transition = 'transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                });
            }
        }
        
        this.updateNavigationDots();
    }

    updateNavigationDots() {
        if (!this.carouselNavDots) return;
        this.carouselNavDots.forEach((dot, idx) => {
            dot.classList.toggle('active', idx === this.currentSlideIndex);
            dot.style.display = (idx <= this.maxSlideIndex) ? '' : 'none';
        });
    }

    nextSlide() {
        if (this.totalSlides === 0) return;
        let newIndex = this.currentSlideIndex + 1;
        if (newIndex > this.maxSlideIndex) {
            newIndex = 0;
        }
        this.showSlide(newIndex);
    }

    previousSlide() {
        if (this.totalSlides === 0) return;
        let newIndex = this.currentSlideIndex - 1;
        if (newIndex < 0) {
            newIndex = this.maxSlideIndex;
        }
        this.showSlide(newIndex);
    }

    startAutoPlay() {
        if (!this.isAutoPlaying || this.autoPlayInterval) return;
        if (this.totalSlides <= this.slidesToShow) return;

        this.autoPlayInterval = setInterval(() => {
            if (document.visibilityState === 'visible' && this.isAutoPlaying) {
                this.nextSlide();
            }
        }, 5000);
    }

    pauseAutoPlay() {
        if (this.autoPlayInterval) {
            clearInterval(this.autoPlayInterval);
            this.autoPlayInterval = null;
        }
    }

    resumeAutoPlay() {
        if (this.isAutoPlaying && !this.autoPlayInterval && this.totalSlides > this.slidesToShow) {
            this.startAutoPlay();
        }
    }

    updateCarouselLayout() {
        const fighterCardElements = document.querySelectorAll('#fightersContainer .fighter-card');
        this.totalSlides = fighterCardElements.length;
        
        this.slidesToShow = window.innerWidth > 768 ? 3 : 1;
        this.maxSlideIndex = Math.max(0, this.totalSlides - this.slidesToShow);

        this.currentSlideIndex = Math.min(this.currentSlideIndex, this.maxSlideIndex);
        
        this.createCarouselDots();
        this.showSlide(this.currentSlideIndex, false);
        this.bindCarouselEvents();
    }

    // ===== HEADER EFFECTS =====

    initializeHeaderEffects() {
        this.updateHeaderOnScroll();
    }

    updateHeaderOnScroll() {
        const header = document.querySelector('header');
        if (!header) return;
        const scrollY = window.pageYOffset;
        const threshold = 50;
        
        if (scrollY > threshold) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    }

    // ===== ANIMATIONS =====

    initializeAnimations() {
        this.createIntersectionObservers();
        this.initializeScrollAnimations();
    }

    createIntersectionObservers() {
        const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };

        const fadeInObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fighter-card, .news-card, .partner-logo, .event-card, .footer-section').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            fadeInObserver.observe(el);
        });
        this.observers.set('fadeIn', fadeInObserver);
    }

    initializeScrollAnimations() {
        this.initializeParallax();
        this.createProgressIndicator();
    }

    initializeParallax() {
        const hero = document.querySelector('.hero');
        if (!hero) return;

        const parallaxScrollHandler = this.throttle(() => {
            if (document.hidden) return;
            const scrolled = window.pageYOffset;
            if (scrolled < window.innerHeight) { 
                const rate = scrolled * -0.3;
                hero.style.transform = `translateY(${rate}px)`;
            }
        }, 16);

        window.addEventListener('scroll', parallaxScrollHandler, { passive: true });
    }

    createProgressIndicator() {
        if (document.querySelector('.scroll-progress')) return;
        const progressBarContainer = document.createElement('div');
        progressBarContainer.className = 'scroll-progress';
        progressBarContainer.innerHTML = '<div class="scroll-progress-bar"></div>';
        
        if (!document.getElementById('scroll-progress-styles')) {
            const style = document.createElement('style');
            style.id = 'scroll-progress-styles';
            style.textContent = `
                .scroll-progress { position: fixed; top: 0; left: 0; width: 100%; height: 3px; background: rgba(255,255,255,0.1); z-index: 9999; opacity: 0; transition: opacity 0.3s ease; pointer-events: none; }
                .scroll-progress.visible { opacity: 1; }
                .scroll-progress-bar { height: 100%; background: linear-gradient(90deg, var(--accent-gold, #DAA520), var(--primary-red, #C41E3A)); width: 0%; transition: width 0.1s linear; }
            `;
            document.head.appendChild(style);
        }
        document.body.appendChild(progressBarContainer);
    }

    updateProgressIndicator() {
        const progressBar = document.querySelector('.scroll-progress-bar');
        const progressContainer = document.querySelector('.scroll-progress');
        if (!progressBar || !progressContainer) return;
        
        const scrollableHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        if (scrollableHeight <= 0) {
            progressBar.style.width = '0%';
            progressContainer.classList.remove('visible');
            return;
        }
        const winScroll = document.documentElement.scrollTop;
        const scrolled = (winScroll / scrollableHeight) * 100;
        
        progressBar.style.width = `${Math.min(100, Math.max(0, scrolled))}%`;
        progressContainer.classList.toggle('visible', winScroll > 100 && scrollableHeight > 0);
    }

    handleScrollAnimations() {
        this.animateCounters();
        this.handleStaggerAnimations();
    }

    animateCounters() {
        document.querySelectorAll('[data-counter]').forEach(counter => {
            if (this.isElementInViewport(counter, -50) && !counter.classList.contains('animated')) {
                counter.classList.add('animated');
                this.animateCounter(counter);
            }
        });
    }

    animateCounter(element) {
        const target = parseInt(element.dataset.counter, 10);
        if (isNaN(target)) return;
        const duration = 2000;
        let startTimestamp = null;

        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            element.textContent = Math.floor(progress * target).toLocaleString(this.currentLang === 'sr' ? 'sr-RS' : 'en-US');
            if (progress < 1) {
                requestAnimationFrame(step);
            }
        };
        requestAnimationFrame(step);
    }

    handleStaggerAnimations() {
        document.querySelectorAll('[data-stagger]').forEach(group => {
            if (this.isElementInViewport(group) && !group.classList.contains('stagger-animated')) {
                group.classList.add('stagger-animated');
                Array.from(group.children).forEach((child, index) => {
                    child.style.transitionDelay = `${index * 100}ms`;
                    child.classList.add('stagger-item-animate');
                });
            }
        });
    }

    pauseAnimations() {
        document.documentElement.classList.add('animations-paused');
    }

    resumeAnimations() {
        document.documentElement.classList.remove('animations-paused');
    }

    triggerEntranceAnimations() {
        const heroContent = document.querySelector('.hero-content');
        if (heroContent) heroContent.classList.add('animate-hero-entrance');
        
        document.querySelectorAll('.nav-menu a, .header-controls > *').forEach((item, index) => {
            if (item.style) {
                 item.style.transitionDelay = `${index * 100 + 500}ms`;
                 item.classList.add('animate-nav-entrance');
            }
        });
    }

    // ===== LAZY LOADING =====

    initializeLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            img.onload = () => img.classList.add('loaded');
                            img.onerror = () => img.classList.add('error');
                        } else if (img.dataset.srcset) {
                            img.srcset = img.dataset.srcset;
                            img.removeAttribute('data-srcset');
                            img.onload = () => img.classList.add('loaded');
                            img.onerror = () => img.classList.add('error');
                        }
                        observer.unobserve(img);
                    }
                });
            }, { rootMargin: '0px 0px 200px 0px' });

            document.querySelectorAll('img[data-src], img[data-srcset]').forEach(img => imageObserver.observe(img));
            this.observers.set('images', imageObserver);
        } else {
            document.querySelectorAll('img[data-src]').forEach(img => {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            });
            document.querySelectorAll('img[data-srcset]').forEach(img => {
                img.srcset = img.dataset.srcset;
                img.removeAttribute('data-srcset');
            });
        }
    }

    preloadCriticalImages() {
        const criticalImages = [];
        criticalImages.forEach(src => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'image';
            link.href = src;
            document.head.appendChild(link);
        });
    }

    // ===== PERFORMANCE OPTIMIZATIONS =====

    initializePerformanceOptimizations() {
        if (this.isSlowDevice()) {
            document.documentElement.classList.add('reduce-motion');
        }
        this.optimizeForBattery();
        this.initializePrefetching();
    }

    isSlowDevice() {
        return (navigator.hardwareConcurrency && navigator.hardwareConcurrency < 4) || 
               (window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    optimizeForBattery() {
        if ('getBattery' in navigator) {
            navigator.getBattery().then(battery => {
                const lowBatteryHandler = () => {
                    if (battery.level < 0.2 || battery.charging === false && battery.level < 0.3) {
                        this.isAutoPlaying = false;
                        this.pauseAutoPlay();
                        document.documentElement.classList.add('low-battery', 'reduce-motion');
                    }
                };
                lowBatteryHandler();
                battery.addEventListener('levelchange', lowBatteryHandler);
                battery.addEventListener('chargingchange', lowBatteryHandler);
            });
        }
    }

    initializePrefetching() {
        document.querySelectorAll('a[href]').forEach(link => {
            if (link.origin === window.location.origin) {
                link.addEventListener('mouseenter', () => this.prefetchResource(link.href), { once: true, passive: true });
                link.addEventListener('focus', () => this.prefetchResource(link.href), { once: true, passive: true });
            }
        });
    }

    prefetchResource(url) {
        try {
            new URL(url);
            const link = document.createElement('link');
            link.rel = 'prefetch';
            link.href = url;
            document.head.appendChild(link);
        } catch (e) {
            console.warn('Invalid URL for prefetch:', url);
        }
    }

    // ===== FORM HANDLERS =====

    async handleNewsletterSubmit(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const emailInput = form.querySelector('input[type="email"]');
        const email = emailInput.value;
        const button = form.querySelector('button[type="submit"]');
        const originalButtonText = button.innerHTML;

        if (!this.isValidEmail(email)) {
            this.showNotification(
                this.currentLang === 'sr' ? 'Unesite validnu email adresu.' : 'Please enter a valid email address.', 
                'error'
            );
            emailInput.focus();
            return;
        }

        button.innerHTML = '<span class="loading-spinner" role="status" aria-label="Submitting..."></span>';
        button.disabled = true;
        form.classList.add('form-loading');

        try {
            const success = await this.subscribeNewsletter(email);
            if (success) {
                form.reset();
            }
        } catch (error) {
            console.error("Newsletter submission error:", error);
        } finally {
            button.innerHTML = originalButtonText;
            button.disabled = false;
            form.classList.remove('form-loading');
        }
    }

    async subscribeNewsletter(email) {
        try {
            const response = await fetch('/newsletter.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: email,
                    language: this.currentLang,
                    name: ''
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data && data.success) {
                this.showNotification(data.message, 'success');
                return true;
            } else {
                this.showNotification(
                    data?.message || (this.currentLang === 'sr' ? 'Neočekivana greška.' : 'Unexpected error.'), 
                    'error'
                );
                return false;
            }
        } catch (error) {
            console.error('Newsletter subscription error:', error);
            
            let errorMessage;
            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                errorMessage = this.currentLang === 'sr' ? 
                    'Problem sa mrežom. Proverite internetsku vezu.' : 
                    'Network error. Please check your internet connection.';
            } else if (error.message.includes('HTTP error')) {
                errorMessage = this.currentLang === 'sr' ? 
                    'Problem sa serverom. Pokušajte ponovo.' : 
                    'Server error. Please try again.';
            } else {
                errorMessage = this.currentLang === 'sr' ? 
                    'Greška pri pretplati. Pokušajte ponovo.' : 
                    'Subscription error. Please try again.';
            }
            
            this.showNotification(errorMessage, 'error');
            return false;
        }
    }

    async handleContactSubmit(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const formData = new FormData(form);
        const button = form.querySelector('button[type="submit"]');
        const originalButtonText = button.innerHTML;

        if (!this.validateContactForm(form)) {
            return;
        }

        button.innerHTML = '<span class="loading-spinner" role="status" aria-label="Sending..."></span>';
        button.disabled = true;
        form.classList.add('form-loading');

        const contactData = {
            name: formData.get('name'),
            email: formData.get('email'),
            subject: formData.get('subject'),
            phone: formData.get('phone') || '',
            message: formData.get('message'),
            language: this.currentLang
        };

        try {
            const success = await this.sendContactForm(contactData);
            if (success) {
                form.reset();
            }
        } catch (error) {
            console.error("Contact form submission error:", error);
        } finally {
            button.innerHTML = originalButtonText;
            button.disabled = false;
            form.classList.remove('form-loading');
        }
    }

    validateContactForm(form) {
        const name = form.querySelector('[name="name"]').value.trim();
        const email = form.querySelector('[name="email"]').value.trim();
        const subject = form.querySelector('[name="subject"]').value;
        const message = form.querySelector('[name="message"]').value.trim();

        if (!name) {
            this.showNotification(
                this.currentLang === 'sr' ? 'Molimo unesite vaše ime.' : 'Please enter your name.',
                'error'
            );
            form.querySelector('[name="name"]').focus();
            return false;
        }

        if (!this.isValidEmail(email)) {
            this.showNotification(
                this.currentLang === 'sr' ? 'Molimo unesite validnu email adresu.' : 'Please enter a valid email address.',
                'error'
            );
            form.querySelector('[name="email"]').focus();
            return false;
        }

        if (!subject) {
            this.showNotification(
                this.currentLang === 'sr' ? 'Molimo izaberite temu poruke.' : 'Please select a subject.',
                'error'
            );
            form.querySelector('[name="subject"]').focus();
            return false;
        }

        if (!message || message.length < 10) {
            this.showNotification(
                this.currentLang === 'sr' ? 'Poruka mora imati najmanje 10 karaktera.' : 'Message must be at least 10 characters long.',
                'error'
            );
            form.querySelector('[name="message"]').focus();
            return false;
        }

        return true;
    }

    async sendContactForm(contactData) {
        try {
            const response = await fetch('/contact.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(contactData)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data && data.success) {
                this.showNotification(
                    this.currentLang === 'sr' ? 
                        'Hvala vam! Vaša poruka je uspešno poslata. Odgovoriću vam u najkraćem roku.' : 
                        'Thank you! Your message has been sent successfully. We will respond shortly.',
                    'success'
                );
                return true;
            } else {
                this.showNotification(
                    data?.message || (this.currentLang === 'sr' ? 'Greška pri slanju poruke.' : 'Error sending message.'),
                    'error'
                );
                return false;
            }
        } catch (error) {
            console.error('Contact form error:', error);

            let errorMessage;
            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                errorMessage = this.currentLang === 'sr' ? 
                    'Problem sa mrežom. Proverite internetsku vezu.' : 
                    'Network error. Please check your internet connection.';
            } else if (error.message.includes('HTTP error')) {
                errorMessage = this.currentLang === 'sr' ? 
                    'Problem sa serverom. Pokušajte ponovo.' : 
                    'Server error. Please try again.';
            } else {
                errorMessage = this.currentLang === 'sr' ? 
                    'Greška pri slanju poruke. Pokušajte ponovo.' : 
                    'Error sending message. Please try again.';
            }

            this.showNotification(errorMessage, 'error');
            return false;
        }
    }

    // ===== JERSEY MODAL SYSTEM =====

    openJerseyModal(jerseyType) {
        const jerseyData = {
            black: {
                name: {
                    sr: 'BIF Crni Dres',
                    en: 'BIF Black Jersey'
                },
                image: 'assets/images/jerseys/crni.png',
                description: {
                    sr: 'Klasičan crni dizajn sa BIF logom. Izrađen od visokokvalitetnog pamuka sa modernim krojom.',
                    en: 'Classic black design with BIF logo. Made from high-quality cotton with modern cut.'
                }
            },
            white: {
                name: {
                    sr: 'BIF Beli Dres',
                    en: 'BIF White Jersey'
                },
                image: 'assets/images/jerseys/beli.png',
                description: {
                    sr: 'Elegantan beli dizajn sa BIF logom. Izrađen od visokokvalitetnog pamuka sa modernim krojom.',
                    en: 'Elegant white design with BIF logo. Made from high-quality cotton with modern cut.'
                }
            }
        };

        const jersey = jerseyData[jerseyType];
        if (!jersey) return;

        const modalHTML = `
            <div class="jersey-detail-modal">
                <div class="modal-overlay" onclick="bifAppInstance.closeJerseyModal()"></div>
                <div class="modal-content">
                    <button class="modal-close" onclick="bifAppInstance.closeJerseyModal()">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    
                    <div class="jersey-detail-content">
                        <div class="jersey-detail-image">
                            <img src="${jersey.image}" alt="${jersey.name[this.currentLang]}" width="300" height="300">
                        </div>
                        
                        <div class="jersey-detail-info">
                            <h3>${jersey.name[this.currentLang]}</h3>
                            <p class="jersey-detail-description">${jersey.description[this.currentLang]}</p>
                            
                            <div class="jersey-features">
                                <h4>
                                    <span class="lang-content ${this.currentLang === 'sr' ? 'active' : ''}" data-lang="sr">Karakteristike:</span>
                                    <span class="lang-content ${this.currentLang === 'en' ? 'active' : ''}" data-lang="en">Features:</span>
                                </h4>
                                <ul>
                                    <li class="lang-content ${this.currentLang === 'sr' ? 'active' : ''}" data-lang="sr">100% pamuk visoke kvalitete</li>
                                    <li class="lang-content ${this.currentLang === 'en' ? 'active' : ''}" data-lang="en">100% high-quality cotton</li>
                                    
                                    <li class="lang-content ${this.currentLang === 'sr' ? 'active' : ''}" data-lang="sr">Zvanični BIF logo</li>
                                    <li class="lang-content ${this.currentLang === 'en' ? 'active' : ''}" data-lang="en">Official BIF logo</li>
                                    
                                    <li class="lang-content ${this.currentLang === 'sr' ? 'active' : ''}" data-lang="sr">Moderan kroj</li>
                                    <li class="lang-content ${this.currentLang === 'en' ? 'active' : ''}" data-lang="en">Modern fit</li>
                                    
                                    <li class="lang-content ${this.currentLang === 'sr' ? 'active' : ''}" data-lang="sr">Dostupne veličine: S-XXL</li>
                                    <li class="lang-content ${this.currentLang === 'en' ? 'active' : ''}" data-lang="en">Available sizes: S-XXL</li>
                                </ul>
                            </div>
                            
                            <div class="jersey-price-section">
                                <div class="price">3.500 RSD</div>
                                <div class="shipping-note">
                                    <span class="lang-content ${this.currentLang === 'sr' ? 'active' : ''}" data-lang="sr">+ poštarina</span>
                                    <span class="lang-content ${this.currentLang === 'en' ? 'active' : ''}" data-lang="en">+ shipping</span>
                                </div>
                            </div>
                            
                            <button class="btn btn-primary order-now-btn" onclick="bifAppInstance.openOrderForm('${jerseyType}')">
                                <span class="lang-content ${this.currentLang === 'sr' ? 'active' : ''}" data-lang="sr">Poruči Sada</span>
                                <span class="lang-content ${this.currentLang === 'en' ? 'active' : ''}" data-lang="en">Order Now</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const existingModal = document.getElementById('jerseyDetailModal');
        if (existingModal) {
            existingModal.remove();
        }

        const modalElement = document.createElement('div');
        modalElement.id = 'jerseyDetailModal';
        modalElement.className = 'order-modal';
        modalElement.innerHTML = modalHTML;
        modalElement.style.display = 'flex';
        
        document.body.appendChild(modalElement);
        document.body.style.overflow = 'hidden';

        requestAnimationFrame(() => {
            modalElement.style.opacity = '0';
            modalElement.style.transition = 'opacity 0.3s ease';
            requestAnimationFrame(() => {
                modalElement.style.opacity = '1';
            });
        });
    }

    closeJerseyModal() {
        const modal = document.getElementById('jerseyDetailModal');
        if (modal) {
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.remove();
                document.body.style.overflow = '';
            }, 300);
        }
    }

    openOrderForm(jerseyType) {
        this.closeJerseyModal();

        const jerseyData = {
            black: {
                name: {
                    sr: 'BIF Crni Dres',
                    en: 'BIF Black Jersey'
                },
                image: 'assets/images/jerseys/crni.png'
            },
            white: {
                name: {
                    sr: 'BIF Beli Dres',
                    en: 'BIF White Jersey'
                },
                image: 'assets/images/jerseys/beli.png'
            }
        };

        const jersey = jerseyData[jerseyType];
        if (!jersey) return;

        const modal = document.getElementById('orderModal');
        const selectedJerseyInput = document.getElementById('selectedJersey');
        const selectedJerseyImage = document.getElementById('selectedJerseyImage');
        const selectedJerseyName = document.getElementById('selectedJerseyName');

        if (selectedJerseyInput) selectedJerseyInput.value = jerseyType;
        if (selectedJerseyImage) {
            selectedJerseyImage.src = jersey.image;
            selectedJerseyImage.alt = jersey.name[this.currentLang];
        }
        if (selectedJerseyName) {
            selectedJerseyName.textContent = jersey.name[this.currentLang];
        }

        this.updateSizeOptions();

        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            requestAnimationFrame(() => {
                modal.style.opacity = '0';
                modal.style.transition = 'opacity 0.3s ease';
                requestAnimationFrame(() => {
                    modal.style.opacity = '1';
                });
            });
        }
    }

    closeOrderModal() {
        const modal = document.getElementById('orderModal');
        if (modal) {
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }, 300);
        }
    }

    updateSizeOptions() {
        const sizeSelect = document.getElementById('order-size');
        if (!sizeSelect) return;

        const options = sizeSelect.querySelectorAll('option');
        options.forEach(option => {
            if (option.value === '') {
                option.textContent = this.currentLang === 'sr' ? 'Izaberite veličinu' : 'Select size';
            }
        });
    }

    updateOpenModalContent() {
        const modal = document.getElementById('orderModal');
        if (modal && modal.style.display === 'flex') {
            const jerseyType = document.getElementById('selectedJersey')?.value;
            if (jerseyType) {
                const jerseyData = {
                    black: { sr: 'BIF Crni Dres', en: 'BIF Black Jersey' },
                    white: { sr: 'BIF Beli Dres', en: 'BIF White Jersey' }
                };
                
                const selectedJerseyName = document.getElementById('selectedJerseyName');
                if (selectedJerseyName && jerseyData[jerseyType]) {
                    selectedJerseyName.textContent = jerseyData[jerseyType][this.currentLang];
                }
            }
        }
    }

    async handleOrderSubmit(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const formData = new FormData(form);
        const button = form.querySelector('button[type="submit"]');
        const originalButtonText = button.innerHTML;

        if (!this.validateOrderForm(form)) {
            return;
        }

        button.innerHTML = '<span class="loading-spinner" role="status" aria-label="Sending..."></span>';
        button.disabled = true;
        form.classList.add('form-loading');

        const jerseyNames = {
            black: this.currentLang === 'sr' ? 'BIF Crni Dres' : 'BIF Black Jersey',
            white: this.currentLang === 'sr' ? 'BIF Beli Dres' : 'BIF White Jersey'
        };

        const orderData = {
            name: formData.get('name'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            jersey: jerseyNames[formData.get('jersey')] || formData.get('jersey'),
            size: formData.get('size'),
            address: formData.get('address'),
            notes: formData.get('notes') || '',
            language: this.currentLang,
            subject: 'jersey_order',
            message: this.createOrderMessage(formData)
        };

        try {
            const success = await this.sendOrderForm(orderData);
            if (success) {
                form.reset();
                this.closeOrderModal();
            }
        } catch (error) {
            console.error("Order submission error:", error);
        } finally {
            button.innerHTML = originalButtonText;
            button.disabled = false;
            form.classList.remove('form-loading');
        }
    }

    createOrderMessage(formData) {
        const jerseyNames = {
            black: this.currentLang === 'sr' ? 'BIF Crni Dres' : 'BIF Black Jersey',
            white: this.currentLang === 'sr' ? 'BIF Beli Dres' : 'BIF White Jersey'
        };

        if (this.currentLang === 'sr') {
            return `PORUDŽBINA DRESA

Proizvod: ${jerseyNames[formData.get('jersey')] || formData.get('jersey')}
Veličina: ${formData.get('size')}
Cena: 3.500 RSD + poštarina

Adresa za dostavu:
${formData.get('address')}

${formData.get('notes') ? `Napomene: ${formData.get('notes')}` : ''}

Molimo vas da me kontaktirate radi potvrde porudžbine i dogovora o načinu plaćanja i dostave.

Hvala!`;
        } else {
            return `JERSEY ORDER

Product: ${jerseyNames[formData.get('jersey')] || formData.get('jersey')}
Size: ${formData.get('size')}
Price: 3,500 RSD + shipping

Delivery address:
${formData.get('address')}

${formData.get('notes') ? `Notes: ${formData.get('notes')}` : ''}

Please contact me to confirm the order and arrange payment and delivery.

Thank you!`;
        }
    }

    validateOrderForm(form) {
   const name = form.querySelector('[name="name"]').value.trim();
   const email = form.querySelector('[name="email"]').value.trim();
   const phone = form.querySelector('[name="phone"]').value.trim();
   const jersey = form.querySelector('[name="jersey"]').value;
   const size = form.querySelector('[name="size"]').value;
   const address = form.querySelector('[name="address"]').value.trim();

   if (!name) {
       this.showNotification(
           this.currentLang === 'sr' ? 'Molimo unesite vaše ime.' : 'Please enter your name.',
           'error'
       );
       form.querySelector('[name="name"]').focus();
       return false;
   }

   if (!this.isValidEmail(email)) {
       this.showNotification(
           this.currentLang === 'sr' ? 'Molimo unesite validnu email adresu.' : 'Please enter a valid email address.',
           'error'
       );
       form.querySelector('[name="email"]').focus();
       return false;
   }

   if (!phone) {
       this.showNotification(
           this.currentLang === 'sr' ? 'Molimo unesite broj telefona.' : 'Please enter your phone number.',
           'error'
       );
       form.querySelector('[name="phone"]').focus();
       return false;
   }

   if (!jersey) {
       this.showNotification(
           this.currentLang === 'sr' ? 'Dres nije izabran.' : 'Jersey not selected.',
           'error'
       );
       return false;
   }

   if (!size) {
       this.showNotification(
           this.currentLang === 'sr' ? 'Molimo izaberite veličinu.' : 'Please select a size.',
           'error'
       );
       form.querySelector('[name="size"]').focus();
       return false;
   }

   if (!address || address.length < 10) {
       this.showNotification(
           this.currentLang === 'sr' ? 'Molimo unesite kompletnu adresu za dostavu.' : 'Please enter complete delivery address.',
           'error'
       );
       form.querySelector('[name="address"]').focus();
       return false;
   }

   return true;
}

async sendOrderForm(orderData) {
   try {
       const response = await fetch('/contact.php', {
           method: 'POST',
           headers: {
               'Content-Type': 'application/json',
           },
           body: JSON.stringify(orderData)
       });

       if (!response.ok) {
           throw new Error(`HTTP error! status: ${response.status}`);
       }

       const data = await response.json();

       if (data && data.success) {
           this.showNotification(
               this.currentLang === 'sr' ? 
                   'Hvala vam! Vaša porudžbina je poslata. Kontaktiraćemo vas u najkraćem roku radi potvrde.' : 
                   'Thank you! Your order has been sent. We will contact you shortly for confirmation.',
               'success'
           );
           return true;
       } else {
           this.showNotification(
               data?.message || (this.currentLang === 'sr' ? 'Greška pri slanju porudžbine.' : 'Error sending order.'),
               'error'
           );
           return false;
       }
   } catch (error) {
       console.error('Order form error:', error);

       let errorMessage;
       if (error.name === 'TypeError' && error.message.includes('fetch')) {
           errorMessage = this.currentLang === 'sr' ? 
               'Problem sa mrežom. Proverite internetsku vezu.' : 
               'Network error. Please check your internet connection.';
       } else if (error.message.includes('HTTP error')) {
           errorMessage = this.currentLang === 'sr' ? 
               'Problem sa serverom. Pokušajte ponovo.' : 
               'Server error. Please try again.';
       } else {
           errorMessage = this.currentLang === 'sr' ? 
               'Greška pri slanju porudžbine. Pokušajte ponovo.' : 
               'Error sending order. Please try again.';
       }

       this.showNotification(errorMessage, 'error');
       return false;
   }
}

// ===== UTILITY FUNCTIONS =====

showNotification(message, type = 'info', duration = 3000) {
   let notification = document.querySelector('.notification');
   if (notification) notification.remove();

   notification = document.createElement('div');
   notification.className = `notification notification-${type}`;
   notification.setAttribute('role', 'alert');
   notification.textContent = message;
   
   if (!document.getElementById('notification-styles')) {
       const style = document.createElement('style');
       style.id = 'notification-styles';
       style.textContent = `
           .notification { position: fixed; top: 20px; right: 20px; padding: 16px 24px; border-radius: 8px; color: white; font-weight: 600; z-index: 10000; transform: translateX(calc(100% + 20px)); opacity:0; transition: transform 0.3s ease, opacity 0.3s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
           .notification-success { background: #28a745; } .notification-error { background: #dc3545; }
           .notification-info { background: #17a2b8; } .notification-warning { background: #ffc107; color: #000; }
           .notification.show { transform: translateX(0); opacity:1; }
           @media (max-width: 640px) { .notification { right: 10px; left: 10px; top:10px; transform: translateY(calc(-100% - 10px));} .notification.show { transform: translateY(0); } }
           .loading-spinner { width: 1em; height: 1em; border: 2px solid currentColor; border-right-color: transparent; border-radius: 50%; display: inline-block; animation: spin 0.6s linear infinite; margin: 0 0.25em; vertical-align: middle; }
           @keyframes spin { to { transform: rotate(360deg); } }
       `;
       document.head.appendChild(style);
   }
   
   document.body.appendChild(notification);
   
   requestAnimationFrame(() => notification.classList.add('show'));
   
   setTimeout(() => {
       notification.classList.remove('show');
       notification.addEventListener('transitionend', () => notification.remove(), {once: true});
   }, duration);
}

shareOnSocial(platform, url = window.location.href, title = document.title) {
   const encodedUrl = encodeURIComponent(url);
   const encodedTitle = encodeURIComponent(title);
   const shareUrls = {
       facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
       twitter: `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`,
       linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`,
       whatsapp: `https://api.whatsapp.com/send?text=${encodedTitle}%20${encodedUrl}`,
       telegram: `https://t.me/share/url?url=${encodedUrl}&text=${encodedTitle}`
   };
   
   if (shareUrls[platform]) {
       window.open(shareUrls[platform], 'share-popup', 'width=600,height=400,scrollbars=yes,resizable=yes');
   }
}

debounce(func, wait) {
   let timeout;
   return (...args) => {
       clearTimeout(timeout);
       timeout = setTimeout(() => func.apply(this, args), wait);
   };
}

throttle(func, limit) {
    let inThrottle;
    return (...args) => {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

delay(ms) {
   return new Promise(resolve => setTimeout(resolve, ms));
}

isValidEmail(email) {
   const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
   return emailRegex.test(email);
}

isElementInViewport(el, offset = 0) {
   if (!el) return false;
   const rect = el.getBoundingClientRect();
   return (
       rect.top < (window.innerHeight || document.documentElement.clientHeight) + offset &&
       rect.bottom > -offset &&
       rect.left < (window.innerWidth || document.documentElement.clientWidth) + offset &&
       rect.right > -offset
   );
}

dispatchCustomEvent(eventName, detail = {}) {
   document.dispatchEvent(new CustomEvent(eventName, { detail }));
}

async fetchData(endpoint, options = {}) {
   try {
       const response = await fetch(endpoint, {
           headers: { 'Content-Type': 'application/json', ...options.headers },
           ...options
       });
       if (!response.ok) throw new Error(`HTTP error! status: ${response.status} ${response.statusText}`);
       return await response.json();
   } catch (error) {
       console.error('Fetch error:', error);
       this.showNotification(`Error fetching data: ${error.message}`, 'error');
       throw error;
   }
}

renderFighters(fighters) { 
   console.log('Fighters data:', fighters); 
}

renderNews(news) { 
   console.log('News data:', news); 
}

async loadFighters() {
   try {
       const fighters = await this.fetchData('/api/fighters');
       this.renderFighters(fighters);
   } catch (error) {
       // Error already logged by fetchData
   }
}

async loadNews() {
   try {
       const news = await this.fetchData('/api/news');
       this.renderNews(news);
   } catch (error) {
       // Error already logged by fetchData
   }
}

// ===== INITIALIZATION HELPERS =====

handleInitialLoad() {
   const savedLang = localStorage.getItem('bif-language');
   if (savedLang && savedLang !== this.currentLang) {
       this.switchLanguage(savedLang);
   } else {
       this.switchLanguage(this.currentLang); 
   }
   
   this.handleURLHash();
}

handleURLHash() {
   if (window.location.hash) {
       const targetId = window.location.hash;
       try {
           const target = document.querySelector(targetId);
           if (target) {
               setTimeout(() => this.smoothScrollTo(target), 300); 
           }
       } catch (e) {
           console.warn('Invalid hash for selector:', targetId, e);
       }
   }
}

cleanup() {
   this.pauseAutoPlay();
   this.observers.forEach(observer => observer.disconnect());
   console.log('BIF App cleaned up (observers disconnected, autoplay stopped)');
}


}
// ===== GLOBAL INITIALIZATION =====

let bifAppInstance;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        bifAppInstance = new BIFApp();
        window.BIFApp = bifAppInstance;
        window.bifAppInstance = bifAppInstance;
        window.bifApp = bifAppInstance; // <-- DODAJ OVU LINIJU
    });
} else {
    bifAppInstance = new BIFApp();
    window.BIFApp = bifAppInstance;
    window.bifAppInstance = bifAppInstance;
    window.bifApp = bifAppInstance; // <-- I OVU LINIJU
}






