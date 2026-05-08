<?php
require_once __DIR__ . '/includes/security-headers.php';
Security_Headers::apply();
?><!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0a0a">
    <title>BIF — Svi linkovi | Balkan Influence Fighting</title>
    <meta name="description" content="Sve BIF linkove na jednom mestu — Instagram, TikTok, YouTube, ulaznice za BIF 2 i Oktagonbet bonus.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://bif.events/links">

    <!-- Open Graph -->
    <meta property="og:title" content="BIF — Svi linkovi">
    <meta property="og:description" content="Sve BIF linkove na jednom mestu — Instagram, TikTok, YouTube, ulaznice za BIF 2 i Oktagonbet bonus.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://bif.events/links">
    <meta property="og:image" content="https://bif.events/assets/images/logo/biflogo.png">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            color: #fff;
            background: #050505;
            background-image:
                radial-gradient(circle at 20% 10%, rgba(196, 30, 58, 0.25) 0%, transparent 45%),
                radial-gradient(circle at 80% 90%, rgba(255, 215, 0, 0.12) 0%, transparent 45%),
                radial-gradient(circle at 50% 50%, rgba(20, 0, 0, 0.4) 0%, transparent 70%);
            background-attachment: fixed;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem 1rem 4rem;
            line-height: 1.6;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
            z-index: 0;
        }

        .links-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 480px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.85rem;
        }

        /* Header */
        .links-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .links-logo {
            width: 110px;
            height: 110px;
            object-fit: contain;
            margin-bottom: 1rem;
            filter: drop-shadow(0 0 25px rgba(196, 30, 58, 0.55)) drop-shadow(0 0 50px rgba(255, 215, 0, 0.2));
            animation: logoPulse 3s ease-in-out infinite;
        }

        @keyframes logoPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.04); }
        }

        .links-title {
            font-family: 'Oswald', sans-serif;
            font-size: clamp(1.75rem, 5vw, 2.25rem);
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 0.3rem;
            background: linear-gradient(180deg, #fff 0%, #d0d0d0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .links-handle {
            font-size: 0.9rem;
            color: rgba(255, 215, 0, 0.85);
            font-weight: 500;
            letter-spacing: 1px;
        }

        /* Generic link button */
        .link-btn {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            width: 100%;
            padding: 1rem 1.25rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.98rem;
            letter-spacing: 0.4px;
            transition: transform 0.25s ease, background 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .link-btn:hover {
            transform: translateY(-3px);
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.25);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        .link-btn .ico {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            flex-shrink: 0;
        }

        .link-btn .ico svg {
            width: 22px;
            height: 22px;
        }

        .link-btn .label {
            flex: 1;
            text-align: center;
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
            font-size: 1rem;
        }

        /* Brand color variants */
        .link-btn--instagram .ico {
            background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
        }

        .link-btn--tiktok .ico {
            background: #000;
            border: 1px solid rgba(255,255,255,0.15);
        }

        .link-btn--youtube .ico {
            background: #ff0000;
        }

        .link-btn--instagram:hover { border-color: rgba(220, 39, 67, 0.6); }
        .link-btn--tiktok:hover { border-color: rgba(255, 255, 255, 0.4); }
        .link-btn--youtube:hover { border-color: rgba(255, 0, 0, 0.6); }

        /* Tickets button — primary CTA, full red */
        .link-btn--tickets {
            background: linear-gradient(135deg, #c41e3a 0%, #8b0000 100%);
            border-color: rgba(196, 30, 58, 0.6);
            font-size: 1.1rem;
            padding: 1.15rem 1.25rem;
            box-shadow: 0 10px 30px rgba(196, 30, 58, 0.35);
        }

        .link-btn--tickets:hover {
            background: linear-gradient(135deg, #e63946 0%, #a30000 100%);
            border-color: #ffd700;
            box-shadow: 0 15px 40px rgba(196, 30, 58, 0.55);
        }

        .link-btn--tickets .label {
            color: #fff;
            font-size: 1.1rem;
            letter-spacing: 2px;
        }

        /* Sponsor card (Oktagonbet) — featured large block */
        .sponsor-card {
            display: block;
            width: 100%;
            margin-top: 0.5rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #0d1421 0%, #0a0a0a 100%);
            border: 1px solid rgba(255, 215, 0, 0.25);
            border-radius: 16px;
            text-decoration: none;
            color: #fff;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .sponsor-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #c41e3a, #ffd700, #c41e3a);
        }

        .sponsor-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255, 215, 0, 0.6);
            box-shadow: 0 15px 40px rgba(255, 215, 0, 0.2);
        }

        .sponsor-card__badge {
            display: inline-block;
            background: linear-gradient(135deg, #c41e3a, #8b0000);
            color: #fff;
            font-family: 'Oswald', sans-serif;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 2.5px;
            padding: 0.3rem 0.85rem;
            border-radius: 100px;
            margin-bottom: 0.85rem;
        }

        .sponsor-card__logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.85rem;
            padding: 0.75rem 0;
        }

        .sponsor-card__logo img {
            max-width: 220px;
            max-height: 80px;
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .sponsor-card__bonus {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 1px;
            color: #ffd700;
            text-align: center;
            line-height: 1.4;
            padding: 0.75rem 1rem;
            background: rgba(255, 215, 0, 0.08);
            border-left: 3px solid #ffd700;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .sponsor-card__cta {
            display: block;
            text-align: center;
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #c41e3a;
            padding-top: 0.5rem;
            border-top: 1px solid rgba(255, 215, 0, 0.15);
        }

        .sponsor-card:hover .sponsor-card__cta {
            color: #ffd700;
        }

        /* Footer note */
        .links-footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.4);
            letter-spacing: 0.5px;
        }

        .links-footer a {
            color: rgba(255, 215, 0, 0.7);
            text-decoration: none;
        }

        .links-footer a:hover {
            color: #ffd700;
        }

        @media (max-width: 480px) {
            .links-logo { width: 90px; height: 90px; }
            .link-btn { padding: 0.95rem 1.1rem; font-size: 0.95rem; }
        }
    </style>
</head>
<body>
    <div class="links-wrap">

        <header class="links-header">
            <img src="/assets/images/logo/biflogo.png" alt="BIF" class="links-logo">
            <h1 class="links-title">Balkan Influence Fighting</h1>
            <p class="links-handle">@bif.events</p>
        </header>

        <!-- Oktagonbet — featured sponsor card FIRST -->
        <a href="https://www.oktagonbet.com/mob/sr/registracija" target="_blank" rel="noopener" class="sponsor-card">
            <div class="sponsor-card__badge">⭐ GENERALNI SPONZOR BIF 2</div>
            <div class="sponsor-card__logo">
                <img src="/assets/images/partners/oktagon.jpg" alt="Oktagonbet">
            </div>
            <div class="sponsor-card__bonus">
                🎁 Bonus dobrodošlice<br>
                <small style="font-size:0.8rem;font-weight:500;letter-spacing:0.5px;opacity:0.9;display:block;margin-top:4px;">uz tiket bez rizika i do 700 free spinova</small>
            </div>
            <div class="sponsor-card__cta">Registruj se →</div>
        </a>

        <!-- Tickets — primary CTA -->
        <a href="#" class="link-btn link-btn--tickets" rel="noopener">
            <span class="ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 7v3a2 2 0 0 1 0 4v3a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3a2 2 0 0 1 0-4V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2z"/>
                    <line x1="13" y1="5" x2="13" y2="7"/>
                    <line x1="13" y1="11" x2="13" y2="13"/>
                    <line x1="13" y1="17" x2="13" y2="19"/>
                </svg>
            </span>
            <span class="label">🎟 Kupi Ulaznice za BIF 2</span>
        </a>

        <!-- Instagram -->
        <a href="https://www.instagram.com/lolipopz.bif/" target="_blank" rel="noopener" class="link-btn link-btn--instagram">
            <span class="ico">
                <svg viewBox="0 0 24 24" fill="#fff"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
            </span>
            <span class="label">Instagram · @lolipopz.bif</span>
        </a>

        <!-- TikTok -->
        <a href="https://www.tiktok.com/@bif.events" target="_blank" rel="noopener" class="link-btn link-btn--tiktok">
            <span class="ico">
                <svg viewBox="0 0 24 24" fill="#fff"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
            </span>
            <span class="label">TikTok · @bif.events</span>
        </a>

        <!-- YouTube -->
        <a href="https://www.youtube.com/@bif.events" target="_blank" rel="noopener" class="link-btn link-btn--youtube">
            <span class="ico">
                <svg viewBox="0 0 24 24" fill="#fff"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
            </span>
            <span class="label">YouTube · @bif.events</span>
        </a>

        <footer class="links-footer">
            © <?php echo date('Y'); ?> Balkan Influence Fighting · <a href="/">bif.events</a>
        </footer>

    </div>
</body>
</html>
