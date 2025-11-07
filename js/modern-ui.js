/**
 * BIF - Modern UI Interactions
 * Animacije, scroll effects i micro-interactions
 */

(function() {
    'use strict';

    // ========================================
    // HEADER SCROLL EFFECT
    // ========================================
    const header = document.querySelector('header');
    let lastScroll = 0;

    function handleScroll() {
        const currentScroll = window.pageYOffset;

        // Add 'scrolled' class when scrolled down
        if (currentScroll > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }

        lastScroll = currentScroll;
    }

    window.addEventListener('scroll', handleScroll, { passive: true });

    // ========================================
    // INTERSECTION OBSERVER - FADE IN ANIMATIONS
    // ========================================
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe all cards
    const cards = document.querySelectorAll('.fighter-card, .news-card, .timer-card');
    cards.forEach(card => {
        observer.observe(card);
    });

    // ========================================
    // SMOOTH SCROLL FOR ANCHOR LINKS
    // ========================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();

            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                const headerHeight = header.offsetHeight;
                const targetPosition = targetElement.offsetTop - headerHeight - 20;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });

    // ========================================
    // MOBILE MENU TOGGLE
    // ========================================
    const createMobileMenuToggle = () => {
        const navMenu = document.querySelector('.nav-menu');
        const headerContent = document.querySelector('.header-content');

        // Check if toggle button already exists
        if (document.querySelector('.mobile-menu-toggle')) return;

        // Create toggle button
        const toggleButton = document.createElement('button');
        toggleButton.className = 'mobile-menu-toggle';
        toggleButton.setAttribute('aria-label', 'Toggle menu');
        toggleButton.innerHTML = '<i class="fas fa-bars"></i>';

        // Insert toggle button after logo
        const logo = document.querySelector('.logo');
        logo.after(toggleButton);

        // Toggle menu on click
        toggleButton.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            const icon = toggleButton.querySelector('i');

            if (navMenu.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });

        // Close menu when clicking a link
        document.querySelectorAll('.nav-menu a').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                const icon = toggleButton.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            });
        });
    };

    // Only create toggle on mobile
    if (window.innerWidth <= 768) {
        createMobileMenuToggle();
    }

    window.addEventListener('resize', () => {
        if (window.innerWidth <= 768) {
            createMobileMenuToggle();
        }
    });

    // ========================================
    // FIGHTER CARDS STAGGER ANIMATION
    // ========================================
    const staggerElements = (selector, delay = 100) => {
        const elements = document.querySelectorAll(selector);
        elements.forEach((el, index) => {
            el.style.animationDelay = `${index * delay}ms`;
        });
    };

    staggerElements('.fighter-card', 150);
    staggerElements('.news-card', 150);

    // ========================================
    // PARALLAX EFFECT FOR HERO - ISKLJUČENO
    // ========================================
    // Parallax efekat je isključen na zahtev korisnika
    // const hero = document.querySelector('.hero');
    // if (hero) {
    //     window.addEventListener('scroll', () => {
    //         const scrolled = window.pageYOffset;
    //         const parallaxSpeed = 0.5;
    //         hero.style.transform = `translateY(${scrolled * parallaxSpeed}px)`;
    //     }, { passive: true });
    // }

    // ========================================
    // COPY TO CLIPBOARD UTILITY
    // ========================================
    window.copyToClipboard = (text) => {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Kopirano u clipboard!');
        }).catch(err => {
            console.error('Failed to copy:', err);
        });
    };

    // ========================================
    // TOAST NOTIFICATIONS
    // ========================================
    function showToast(message, duration = 3000) {
        // Remove existing toasts
        const existingToast = document.querySelector('.toast');
        if (existingToast) {
            existingToast.remove();
        }

        // Create toast
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
            z-index: 10000;
            animation: slideInRight 0.3s ease-out;
            font-weight: 600;
        `;

        document.body.appendChild(toast);

        // Auto remove
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    // Add animation keyframes
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }

        .mobile-menu-toggle {
            display: none;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }

        .mobile-menu-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }
        }
    `;
    document.head.appendChild(style);

    // ========================================
    // LOADING STATE - HIDE LOADING SCREEN
    // ========================================
    window.addEventListener('load', () => {
        document.body.classList.add('loaded');

        // Hide loading screen after minimum display time
        const loadingScreen = document.getElementById('loading-screen');
        if (loadingScreen) {
            setTimeout(() => {
                loadingScreen.classList.add('hidden');
                // Remove from DOM after animation completes
                setTimeout(() => {
                    loadingScreen.remove();
                }, 500);
            }, 1000); // Minimum 1 second display
        }
    });

    // ========================================
    // COUNTER ANIMATION (for stats)
    // ========================================
    function animateCounter(element) {
        const target = parseInt(element.getAttribute('data-target'));
        const duration = 2000;
        const start = 0;
        const increment = target / (duration / 16);
        let current = start;

        const updateCounter = () => {
            current += increment;
            if (current < target) {
                element.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target;
            }
        };

        updateCounter();
    }

    // Observe stat elements
    const statObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                statObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('[data-target]').forEach(stat => {
        statObserver.observe(stat);
    });

    // ========================================
    // IMAGE LAZY LOADING FALLBACK
    // ========================================
    if ('loading' in HTMLImageElement.prototype) {
        const images = document.querySelectorAll('img[loading="lazy"]');
        images.forEach(img => {
            img.src = img.dataset.src || img.src;
        });
    } else {
        // Fallback for browsers that don't support lazy loading
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.3.2/lazysizes.min.js';
        document.body.appendChild(script);
    }

    console.log('✨ Modern UI initialized');
})();
