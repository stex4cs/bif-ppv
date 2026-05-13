/**
 * BIF Ticket Click Tracker
 * Hvata klikove na dugmad sa data-ticket-source attribute-om
 * Šalje u GA4 + u sopstveni backend log za admin pregled
 */
(function() {
    'use strict';

    function trackTicketClick(source, href) {
        // 1. GA4 event
        if (typeof gtag === 'function') {
            try {
                gtag('event', 'ticket_click', {
                    'source': source,
                    'destination': href,
                    'page_location': window.location.href
                });
            } catch (err) {}
        }

        // 2. Internal backend log
        try {
            const payload = {
                source: source,
                page: window.location.pathname,
                ref: document.referrer || ''
            };
            // keepalive ensures request completes even when navigating away
            fetch('/api/track-ticket-click.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                keepalive: true,
                credentials: 'omit'
            }).catch(() => {});
        } catch (err) {}
    }

    document.addEventListener('click', function(e) {
        const link = e.target.closest('a[data-ticket-source]');
        if (!link) return;
        const source = link.dataset.ticketSource || 'unknown';
        const href = link.href || '';
        trackTicketClick(source, href);
    }, { passive: true });
})();
