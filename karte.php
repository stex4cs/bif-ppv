<?php
require_once __DIR__ . '/includes/security-headers.php';
Security_Headers::apply();

// Ticket URL (single source of truth on this page)
$TICKET_URL = 'https://ticketing.sajam.rs/catalog/dogadjaj/bif_2_46';
$EVENT_DATE = '2026-06-20T19:00:00';
$EVENT_NAME = 'BIF 2 — Beogradski Sajam';
?><!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#c41e3a">
    <title>🎟 Kupi Ulaznice za BIF 2 — 20. jun 2026 | Beogradski Sajam</title>
    <meta name="description" content="Karte za BIF 2 — najveći influenserski boks show. Marko Jack vs Kengur, Marko Filipović vs Duka Prase i još. 20.06.2026, Beogradski Sajam, Hala 3.">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="https://bif.events/karte">

    <!-- Open Graph -->
    <meta property="og:title" content="🎟 BIF 2 — Karte u prodaji | 20. jun 2026, Beogradski Sajam">
    <meta property="og:description" content="Marko Jack vs Kengur, Filipović vs Duka Prase, hendikep 3v1 i još. Powered by Oktagonbet. Karte se prodaju brzo — broj mesta ograničen.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://bif.events/karte">
    <meta property="og:image" content="https://bif.events/assets/images/events/bif2/bif2-poster.png">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="🎟 BIF 2 — Karte u prodaji">
    <meta name="twitter:description" content="20.06.2026 · Beogradski Sajam · Spektakl noći">
    <meta name="twitter:image" content="https://bif.events/assets/images/events/bif2/bif2-poster.png">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Meta Pixel (PageView fires automatically) -->
    <?php include __DIR__ . '/includes/meta-pixel.php'; ?>

    <!-- Google Analytics -->
    <?php include __DIR__ . '/includes/google-analytics.php'; ?>

    <!-- Internal ticket-click tracker -->
    <script src="/js/ticket-tracker.js" defer></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #050505;
            color: #fff;
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 20% 10%, rgba(196,30,58,0.35) 0%, transparent 45%),
                radial-gradient(circle at 80% 90%, rgba(255,215,0,0.15) 0%, transparent 45%);
            z-index: 0;
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
            background-size: 48px 48px;
            z-index: 0;
            pointer-events: none;
        }

        .wrap {
            position: relative;
            z-index: 1;
            max-width: 880px;
            margin: 0 auto;
            padding: 2.5rem 1.25rem 4rem;
        }

        /* HEADER */
        .header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .logo {
            width: 90px;
            height: auto;
            margin: 0 auto 1rem;
            filter: drop-shadow(0 0 30px rgba(196,30,58,0.5)) drop-shadow(0 0 60px rgba(255,215,0,0.2));
            animation: pulse 3s ease-in-out infinite;
        }
        @keyframes pulse {
            0%,100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .eyebrow {
            display: inline-block;
            background: rgba(196,30,58,0.2);
            border: 1px solid rgba(255,215,0,0.4);
            color: #ffd700;
            font-family: 'Oswald', sans-serif;
            font-size: 0.8rem;
            letter-spacing: 4px;
            text-transform: uppercase;
            padding: 0.5rem 1.2rem;
            border-radius: 100px;
            margin-bottom: 1rem;
        }
        .title {
            font-family: 'Oswald', sans-serif;
            font-size: clamp(2.5rem, 7vw, 4.5rem);
            font-weight: 800;
            text-transform: uppercase;
            line-height: 0.95;
            letter-spacing: clamp(2px,0.5vw,4px);
            background: linear-gradient(180deg, #fff 0%, #d4d4d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.75rem;
            text-shadow: 0 4px 30px rgba(0,0,0,0.6);
        }
        .title em {
            font-style: normal;
            background: linear-gradient(135deg, #c41e3a, #ffd700);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .subtitle {
            font-family: 'Inter', sans-serif;
            font-size: clamp(1rem, 1.6vw, 1.2rem);
            color: rgba(255,255,255,0.85);
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 500;
        }

        /* COUNTDOWN */
        .countdown {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.6rem;
            margin: 2rem 0;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        .countdown-box {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,215,0,0.2);
            border-radius: 10px;
            padding: 0.85rem 0.5rem;
            text-align: center;
        }
        .countdown-num {
            font-family: 'Oswald', sans-serif;
            font-size: clamp(1.5rem, 4vw, 2.5rem);
            font-weight: 800;
            background: linear-gradient(180deg, #fff 0%, #c0c0c0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            font-variant-numeric: tabular-nums;
        }
        .countdown-label {
            font-family: 'Oswald', sans-serif;
            font-size: 0.65rem;
            font-weight: 600;
            color: #ffd700;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 0.35rem;
        }

        /* PRIMARY CTA */
        .cta-primary {
            display: block;
            width: 100%;
            max-width: 460px;
            margin: 1rem auto 2rem;
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, #c41e3a 0%, #8b0000 100%);
            color: #fff;
            text-decoration: none;
            text-align: center;
            border-radius: 14px;
            font-family: 'Oswald', sans-serif;
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            border: 2px solid rgba(255,215,0,0.5);
            box-shadow: 0 15px 40px rgba(196,30,58,0.5), 0 0 30px rgba(255,215,0,0.2);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            cursor: pointer;
        }
        .cta-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 20px 50px rgba(196,30,58,0.7), 0 0 50px rgba(255,215,0,0.3);
            color: #fff;
        }
        .cta-primary:active {
            transform: translateY(0) scale(1);
        }

        /* INFO CARDS */
        .info-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.25rem;
        }
        .info-card__label {
            font-family: 'Oswald', sans-serif;
            color: #c41e3a;
            font-size: 0.75rem;
            letter-spacing: 3px;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 0.6rem;
        }
        .info-card__value {
            font-family: 'Oswald', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
        }

        /* FIGHT CARD */
        .fights {
            background: linear-gradient(135deg, #1a0a0c 0%, #0a0a0a 100%);
            border: 1px solid rgba(196,30,58,0.3);
            border-radius: 16px;
            padding: 1.75rem 1.5rem;
            margin: 2rem 0;
            position: relative;
            overflow: hidden;
        }
        .fights::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #c41e3a, #ffd700, #c41e3a);
        }
        .fights__title {
            font-family: 'Oswald', sans-serif;
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: 3px;
            text-transform: uppercase;
            text-align: center;
            color: #ffd700;
            margin-bottom: 1.25rem;
        }
        .fight-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            gap: 1rem;
        }
        .fight-row:last-child { border-bottom: 0; }
        .fight-row__name {
            font-family: 'Oswald', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #fff;
            flex: 1;
        }
        .fight-row__vs {
            font-family: 'Oswald', sans-serif;
            font-size: 0.75rem;
            color: #c41e3a;
            font-weight: 700;
            letter-spacing: 2px;
            text-align: center;
            min-width: 32px;
        }
        .fight-row__tag {
            font-family: 'Oswald', sans-serif;
            font-size: 0.65rem;
            background: linear-gradient(135deg, #c41e3a, #8b0000);
            color: #fff;
            padding: 0.2rem 0.6rem;
            border-radius: 100px;
            letter-spacing: 1.5px;
        }
        .fights__bonus {
            text-align: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px dashed rgba(255,215,0,0.3);
            font-family: 'Oswald', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: #ffd700;
            letter-spacing: 2px;
        }

        /* TICKET TIERS */
        .tiers {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            margin: 2rem 0;
        }
        .tier {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 1rem 0.75rem;
            text-align: center;
        }
        .tier--vip1 { border-color: rgba(196,30,58,0.5); background: rgba(196,30,58,0.08); }
        .tier--vip2 { border-color: rgba(255,165,0,0.5); background: rgba(255,165,0,0.08); }
        .tier__name {
            font-family: 'Oswald', sans-serif;
            font-weight: 800;
            font-size: 0.95rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 0.3rem;
        }
        .tier__type {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.7);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* SPONSORS */
        .sponsors {
            text-align: center;
            margin: 2.5rem 0;
            padding: 1.5rem 1rem;
            background: rgba(0,0,0,0.4);
            border-radius: 14px;
            border: 1px solid rgba(255,215,0,0.15);
        }
        .sponsors__label {
            font-family: 'Oswald', sans-serif;
            font-size: 0.7rem;
            color: rgba(255,215,0,0.7);
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 0.85rem;
        }
        .sponsors__name {
            font-family: 'Oswald', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: 3px;
        }

        /* FOOTER */
        .footer-note {
            text-align: center;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.4);
            margin-top: 2rem;
        }
        .footer-note a {
            color: rgba(255,215,0,0.7);
            text-decoration: none;
        }

        @media (max-width: 560px) {
            .tiers { grid-template-columns: 1fr; }
            .countdown { grid-template-columns: repeat(4, 1fr); gap: 0.4rem; }
            .fight-row { flex-direction: column; align-items: flex-start; gap: 0.3rem; }
            .fight-row__vs { display: none; }
            .fight-row__name { font-size: 0.95rem; }
            .info-card__value { font-size: 1.25rem; }
        }
    </style>
</head>
<body>
    <div class="wrap">

        <!-- HEADER -->
        <header class="header">
            <img src="/assets/images/logo/biflogo.png" alt="BIF" class="logo">
            <span class="eyebrow">⭐ Powered by Oktagonbet</span>
            <h1 class="title">BIF 2 — <em>Beogradski Sajam</em></h1>
            <p class="subtitle">20. JUN 2026 · HALA 3 · KARTE U PRODAJI</p>
        </header>

        <!-- COUNTDOWN -->
        <div class="countdown" id="countdown">
            <div class="countdown-box"><div class="countdown-num" id="cd-days">--</div><div class="countdown-label">Dana</div></div>
            <div class="countdown-box"><div class="countdown-num" id="cd-hours">--</div><div class="countdown-label">Sati</div></div>
            <div class="countdown-box"><div class="countdown-num" id="cd-mins">--</div><div class="countdown-label">Min</div></div>
            <div class="countdown-box"><div class="countdown-num" id="cd-secs">--</div><div class="countdown-label">Sek</div></div>
        </div>

        <!-- PRIMARY CTA -->
        <a href="<?php echo htmlspecialchars($TICKET_URL); ?>"
           target="_blank"
           rel="noopener"
           class="cta-primary"
           data-ticket-source="karte_hero"
           id="ticket-cta-top">
            🎟 Kupi Ulaznice
        </a>

        <!-- INFO CARDS -->
        <div class="info-card">
            <div class="info-card__label">📍 Lokacija</div>
            <div class="info-card__value">Beogradski Sajam, Hala 3<br><span style="font-size:0.85rem;font-weight:500;color:rgba(255,255,255,0.65);">Beograd, Srbija</span></div>
        </div>
        <div class="info-card">
            <div class="info-card__label">📅 Datum & Vreme</div>
            <div class="info-card__value">Petak, 20. jun 2026.<br><span style="font-size:0.85rem;font-weight:500;color:rgba(255,255,255,0.65);">Vrata: 19:00 · Glavni meč: ~22:00</span></div>
        </div>

        <!-- FIGHT CARD -->
        <div class="fights">
            <div class="fights__title">🥊 Glavni Card</div>

            <div class="fight-row">
                <div class="fight-row__name">Marko Jack</div>
                <div class="fight-row__vs">VS</div>
                <div class="fight-row__name" style="text-align:right;">Kengur</div>
                <div class="fight-row__tag">MAIN</div>
            </div>
            <div class="fight-row">
                <div class="fight-row__name">Marko Filipović</div>
                <div class="fight-row__vs">VS</div>
                <div class="fight-row__name" style="text-align:right;">Duka Prase</div>
                <div class="fight-row__tag" style="background:linear-gradient(135deg,#ca8a04,#854d0e);">CO-MAIN</div>
            </div>
            <div class="fight-row">
                <div class="fight-row__name">Pena Kamen</div>
                <div class="fight-row__vs">VS</div>
                <div class="fight-row__name" style="text-align:right;">Bukur</div>
            </div>
            <div class="fight-row">
                <div class="fight-row__name">Nenad Antonio</div>
                <div class="fight-row__vs">VS</div>
                <div class="fight-row__name" style="text-align:right;">Riđi, Ćure & Loki</div>
                <div class="fight-row__tag" style="background:linear-gradient(135deg,#f59e0b,#c2410c);">3v1</div>
            </div>
            <div class="fight-row">
                <div class="fight-row__name">Ksima</div>
                <div class="fight-row__vs">VS</div>
                <div class="fight-row__name" style="text-align:right;">Bakić</div>
            </div>

            <div class="fights__bonus">+ 2 MEČA IZNENAĐENJA</div>
        </div>

        <!-- TICKET TIERS -->
        <div class="tiers">
            <div class="tier">
                <div class="tier__name">Parter</div>
                <div class="tier__type">Stajanje · Najjača atmosfera</div>
            </div>
            <div class="tier tier--vip1">
                <div class="tier__name" style="color:#ff6b7c;">VIP 1</div>
                <div class="tier__type">Stolice · Najbolji pogled</div>
            </div>
            <div class="tier tier--vip2">
                <div class="tier__name" style="color:#ffa500;">VIP 2</div>
                <div class="tier__type">Stolice · Odličan pogled</div>
            </div>
        </div>

        <!-- SECONDARY CTA -->
        <a href="<?php echo htmlspecialchars($TICKET_URL); ?>"
           target="_blank"
           rel="noopener"
           class="cta-primary"
           data-ticket-source="karte_bottom"
           id="ticket-cta-bottom">
            🎟 Kupi Ulaznice Sada
        </a>

        <!-- SPONSORS -->
        <div class="sponsors">
            <div class="sponsors__label">⭐ Generalni Sponzor</div>
            <div class="sponsors__name">OKTAGONBET</div>
        </div>

        <!-- FOOTER -->
        <div class="footer-note">
            © <?php echo date('Y'); ?> Balkan Influence Fighting · <a href="/">bif.events</a>
        </div>

    </div>

    <script>
        // Countdown
        (function() {
            const target = new Date('<?php echo $EVENT_DATE; ?>').getTime();
            const d = document.getElementById('cd-days');
            const h = document.getElementById('cd-hours');
            const m = document.getElementById('cd-mins');
            const s = document.getElementById('cd-secs');
            function tick() {
                const diff = target - Date.now();
                if (diff <= 0) { d.textContent = h.textContent = m.textContent = s.textContent = '0'; return; }
                d.textContent = Math.floor(diff / 86400000);
                h.textContent = Math.floor((diff % 86400000) / 3600000);
                m.textContent = Math.floor((diff % 3600000) / 60000);
                s.textContent = Math.floor((diff % 60000) / 1000);
            }
            tick();
            setInterval(tick, 1000);
        })();

        // Fire Meta Pixel InitiateCheckout + GA conversion when user clicks any ticket CTA
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[data-ticket-source]');
            if (!link) return;
            // Meta Pixel InitiateCheckout
            if (typeof fbq === 'function') {
                try {
                    fbq('track', 'InitiateCheckout', {
                        content_name: '<?php echo addslashes($EVENT_NAME); ?>',
                        content_category: 'Live Event Tickets',
                        currency: 'EUR',
                        value: 0,
                        source: link.dataset.ticketSource
                    });
                } catch (err) {}
            }
            // GA4 conversion
            if (typeof gtag === 'function') {
                try {
                    gtag('event', 'begin_checkout', {
                        currency: 'EUR',
                        value: 0,
                        items: [{
                            item_id: 'bif-2-ticket',
                            item_name: '<?php echo addslashes($EVENT_NAME); ?>'
                        }],
                        source: link.dataset.ticketSource
                    });
                } catch (err) {}
            }
        });
    </script>
</body>
</html>
