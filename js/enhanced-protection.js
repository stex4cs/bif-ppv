class EnhancedProtection {
    constructor() {
        this.apiUrl = 'api/ppv.php';
        this.deviceId = null;
        this.accessToken = null;
        this.heartbeatInterval = null;
        this.storageKey = 'bif_device_id_v4'; // Novi kljuÄ da garantovano krene od nule
        console.log('EnhancedProtection Class Instantiated');
    }

    // Glavna funkcija za dobijanje ID-a ureÄ‘aja
    getDeviceId() {
        // Ako je ID veÄ‡ uÄitan u memoriju, samo ga vrati
        if (this.deviceId) {
            return this.deviceId;
        }

        let storedId = null;
        try {
            // ÄŒitaj ID iz localStorage, jer se on deli izmeÄ‘u svih tabova
            storedId = localStorage.getItem(this.storageKey);
        } catch (e) {
            console.warn("localStorage nije dostupan. KoristiÄ‡e se privremeni ID.");
        }

        if (storedId && storedId.length > 10) {
            console.log('âœ… PronaÄ‘en postojan Device ID u localStorage:', storedId);
            this.deviceId = storedId;
            return this.deviceId;
        }

        // Ako ID ne postoji, generiÅ¡i novi
        const newId = 'fp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        
        try {
            localStorage.setItem(this.storageKey, newId);
            console.log('âœ¨ Generisan i saÄuvan novi postojani Device ID:', newId);
            this.deviceId = newId;
            return this.deviceId;
        } catch (e) {
            console.warn('Nije moguÄ‡e saÄuvati Device ID. KoristiÄ‡e se privremeni ID.');
            // Fallback ako localStorage nije dostupan
            this.deviceId = 'temp_' + Date.now();
            return this.deviceId;
        }
    }

    // PokreÄ‡e slanje "otkucaja srca" serveru
    startHeartbeat() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
        }
        console.log('ðŸ’“ PokreÄ‡em heartbeat signal...');

        const sendHeartbeat = async () => {
    if (!this.accessToken) return;
    try {
        const response = await fetch(this.apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'heartbeat',
                token: this.accessToken,
                device_id: this.getDeviceId()
            })
        });
        const data = await response.json();
        if(data.success) {
            console.log('Heartbeat OK');
        } else {
            console.error('Heartbeat neuspešan:', data.error);
            
            // NOVO: Proveri da li je event završen
            if (data.error && data.error.includes('završen')) {
                this.handleEventFinished();
            }
        }
    } catch (error) {
        console.error('Greška u mreži tokom slanja heartbeat-a:', error);
    }
};

   sendHeartbeat(); // PoÅ¡alji odmah prvi
        this.heartbeatInterval = setInterval(sendHeartbeat, 60000); // I onda na svakih 60 sekundi
    }
    

handleEventFinished() {
    console.log('Event je završen - zatvaranje strima...');
    
    // Zaustavi heartbeat
    if (this.heartbeatInterval) {
        clearInterval(this.heartbeatInterval);
    }
    
    // Zatvori stream kontejner
    const streamContainer = document.getElementById('stream-container');
    if (streamContainer) {
        streamContainer.remove();
    }
    
    // Prikaži poruku
    document.body.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: linear-gradient(135deg, #c41e3a 0%, #8b0000 100%);">
            <div style="text-align: center; color: white; padding: 40px;">
                <img src="assets/images/logo/logo.png" alt="BIF Logo" style="max-width: 150px; margin-bottom: 30px;">
                <h2 style="font-size: 2rem; margin-bottom: 20px;">Događaj je Završen</h2>
                <p style="font-size: 1.2rem; margin-bottom: 30px;">Hvala vam što ste gledali!</p>
                <a href="index.php" style="display: inline-block; background: #ffd700; color: #000; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: 600;">Nazad na Početnu</a>
            </div>
        </div>
    `;
}

    // Inicijalizacija zaÅ¡tite
    initialize(token) {
        console.log('ðŸ›¡ï¸ Enhanced Protection se Inicijalizuje...');
        this.accessToken = token;
        this.getDeviceId(); // UÄitaj ili generiÅ¡i ID
        this.startHeartbeat();
    }
    
    // ÄŒiÅ¡Ä‡enje
    cleanup() {
        console.log('ðŸ§¹ ÄŒistim sloj zaÅ¡tite...');
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
        }
        this.accessToken = null;
    }
}

// Inicijalizuj odmah i zakaÄi na globalni 'window' objekat
window.enhancedProtection = new EnhancedProtection();