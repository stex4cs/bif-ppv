<!-- Header -->
<header role="banner">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="/#home" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: var(--space-md);">
                    <img src="/assets/images/logo/biflogo.png" alt="BIF Logo" class="logo-img" width="72" height="72">
                    <span class="lang-content active" data-lang="sr">Balkan Influence Fighting</span>
                    <span class="lang-content" data-lang="en">Balkan Influence Fighting</span>
                </a>
            </div>

            <!-- Mobile hamburger menu button -->
            <button class="mobile-menu-toggle" aria-label="Toggle menu" aria-expanded="false">
                <span class="hamburger-icon">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
            </button>

            <nav role="navigation" aria-label="Main navigation">
                <ul class="nav-menu">
                    <li>
                        <a href="/#home" aria-label="Pocetna stranica">
                            <span class="lang-content active" data-lang="sr">Pocetna</span>
                            <span class="lang-content" data-lang="en">Home</span>
                        </a>
                    </li>
                    <li>
                        <a href="/#fighters" aria-label="Nasi borci">
                            <span class="lang-content active" data-lang="sr">Borci</span>
                            <span class="lang-content" data-lang="en">Fighters</span>
                        </a>
                    </li>
                    <li>
                        <a href="/#news" aria-label="Najnovije vesti">
                            <span class="lang-content active" data-lang="sr">Vesti</span>
                            <span class="lang-content" data-lang="en">News</span>
                        </a>
                    </li>
                    <li>
                        <a href="/#events" aria-label="Dogadjaji">
                            <span class="lang-content active" data-lang="sr">Dogadjaji</span>
                            <span class="lang-content" data-lang="en">Events</span>
                        </a>
                    </li>
                    <li>
                        <a href="/#contact" aria-label="Kontakt informacije">
                            <span class="lang-content active" data-lang="sr">Kontakt</span>
                            <span class="lang-content" data-lang="en">Contact</span>
                        </a>
                    </li>
                    <li>
                        <a href="/watch" aria-label="PPV Prenos uzivo">
                            <span class="lang-content active" data-lang="sr">PPV</span>
                            <span class="lang-content" data-lang="en">PPV</span>
                        </a>
                    </li>

                    <!-- Mobile only controls -->
                    <li class="mobile-only-controls">
                        <div class="mobile-theme-language">
                            <button class="theme-toggle-mobile" aria-label="Toggle dark mode">
                                <span class="theme-icon-mobile">&#127769;</span>
                                <span class="lang-content active" data-lang="sr">Tema</span>
                                <span class="lang-content" data-lang="en">Theme</span>
                            </button>

                            <div class="language-switch-mobile">
                                <button class="lang-btn active" onclick="bifApp.switchLanguage('sr')" data-lang="sr" aria-label="Srpski jezik">SR</button>
                                <button class="lang-btn" onclick="bifApp.switchLanguage('en')" data-lang="en" aria-label="English language">EN</button>
                            </div>
                        </div>
                    </li>
                </ul>
            </nav>

            <div class="header-controls">
                <!-- Theme Toggle Button -->
                <button class="theme-toggle" aria-label="Toggle dark mode">
                    <span class="theme-icon">&#127769;</span>
                </button>

                <!-- Language Switch -->
                <div class="language-switch" role="group" aria-label="Language selection">
                    <button class="lang-btn active" onclick="bifApp.switchLanguage('sr')" data-lang="sr" aria-label="Srpski jezik">SR</button>
                    <button class="lang-btn" onclick="bifApp.switchLanguage('en')" data-lang="en" aria-label="English language">EN</button>
                </div>
            </div>
        </div>
    </div>
</header>
