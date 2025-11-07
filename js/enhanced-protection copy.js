/**
 * js/enhanced-protection.js
 * KREIRAJ OVAJ FAJL U: js/enhanced-protection.js
 */

class EnhancedDRMProtection {
    constructor() {
        this.protectionLevel = 'maximum';
        this.violations = [];
        this.isRecording = false;
        this.accessToken = null;
        this.deviceId = null;
        this.heartbeatInterval = null;
    }
    
    // Inicijalizuj zaštitu
    initialize(accessToken, deviceId) {
        this.accessToken = accessToken;
        this.deviceId = deviceId;
        
        console.log('🛡️ Initializing enhanced DRM protection...');
        
        // Pokreni sve zaštite
        this.detectDevTools();
        this.detectScreenRecording();
        this.monitorNetworkRequests();
        this.preventScreenshots();
        this.startHeartbeat();
        
        console.log('✅ Enhanced protection active');
    }
    
    // 1. DevTools detekcija - POBOLJŠANA
    detectDevTools() {
        let devtools = false;
        const threshold = 160;
        
        // Metoda 1: Veličina prozora
        setInterval(() => {
            const widthThreshold = window.outerWidth - window.innerWidth > threshold;
            const heightThreshold = window.outerHeight - window.innerHeight > threshold;
            
            if (widthThreshold || heightThreshold) {
                if (!devtools) {
                    devtools = true;
                    this.handleViolation('devtools_size_detection', {
                        outerWidth: window.outerWidth,
                        innerWidth: window.innerWidth,
                        outerHeight: window.outerHeight,
                        innerHeight: window.innerHeight
                    });
                }
            } else {
                devtools = false;
            }
        }, 1000);
        
        // Metoda 2: Console detekcija
        let devToolsOpen = false;
        const element = new Image();
        Object.defineProperty(element, 'id', {
            get: function() {
                devToolsOpen = true;
                console.clear(); // Očisti konzolu
                return '';
            }
        });
        
        // Metoda 3: Debugger statement
        setInterval(() => {
            const before = Date.now();
            debugger; // Ovo će zaustaviti izvršavanje ako je konzola otvorena
            const after = Date.now();
            
            if (after - before > 100) { // Zaustavka duža od 100ms
                this.handleViolation('devtools_debugger_detection');
            }
        }, 3000);
        
        // Metoda 4: Function toString detekcija
        setInterval(() => {
            if (console.log.toString().indexOf('[native code]') === -1) {
                this.handleViolation('devtools_console_override');
            }
        }, 2000);
    }
    
    // 2. Screen recording detekcija
    detectScreenRecording() {
        // Blokiranje getDisplayMedia
        if (navigator.mediaDevices && navigator.mediaDevices.getDisplayMedia) {
            const original = navigator.mediaDevices.getDisplayMedia;
            navigator.mediaDevices.getDisplayMedia = (...args) => {
                this.handleViolation('screen_recording_attempt', {
                    method: 'getDisplayMedia',
                    args: args
                });
                throw new Error('🚫 Screen recording is blocked for protected content');
            };
        }
        
        // Performance monitoring za detekciju recording software-a
        this.monitorPerformanceForRecording();
        
        // Detekcija recording software u User Agent
        this.detectRecordingSoftware();
        
        // Monitor za Media Recorder API
        if (window.MediaRecorder) {
            const originalMediaRecorder = window.MediaRecorder;
            window.MediaRecorder = function(...args) {
                this.handleViolation('media_recorder_attempt');
                throw new Error('🚫 Media recording is blocked');
            }.bind(this);
        }
    }
    
    // 3. Performance monitoring
    monitorPerformanceForRecording() {
        let performanceChecks = 0;
        const maxChecks = 10;
        
        const checkPerformance = () => {
            if (performanceChecks >= maxChecks) return;
            
            const start = performance.now();
            
            // Težak kalkulacija za test performansi
            for (let i = 0; i < 1000000; i++) {
                Math.random();
            }
            
            const duration = performance.now() - start;
            
            // Ako je sistem značajno sporiji, možda je recording aktivan
            if (duration > 100) { // Threshold
                this.handleViolation('performance_degradation_detected', {
                    duration: duration,
                    expected: '<50ms',
                    actual: duration + 'ms'
                });
            }
            
            performanceChecks++;
            setTimeout(checkPerformance, 10000); // Svaki 10 sekundi
        };
        
        setTimeout(checkPerformance, 5000); // Počni nakon 5 sekundi
    }
    
    // 4. Detekcija recording software
    detectRecordingSoftware() {
        const userAgent = navigator.userAgent;
        const recordingSoftware = [
            'OBS', 'Camtasia', 'Bandicam', 'Fraps', 'XSplit', 
            'Streamlabs', 'Wirecast', 'ManyCam', 'CamStudio'
        ];
        
        recordingSoftware.forEach(software => {
            if (userAgent.includes(software)) {
                this.handleViolation('recording_software_detected', {
                    software: software,
                    userAgent: userAgent
                });
            }
        });
    }
    
    // 5. Network monitoring
    monitorNetworkRequests() {
        // Monitor fetch requests
        const originalFetch = window.fetch;
        window.fetch = (...args) => {
            this.analyzeRequest(args[0], 'fetch');
            return originalFetch.apply(window, args);
        };
        
        // Monitor XMLHttpRequest
        const originalXHR = window.XMLHttpRequest.prototype.open;
        window.XMLHttpRequest.prototype.open = function(...args) {
            window.enhancedProtection.analyzeRequest(args[1], 'xhr');
            return originalXHR.apply(this, args);
        };
    }
    
    analyzeRequest(url, method) {
        if (typeof url !== 'string') return;
        
        const suspiciousPatterns = [
            /download/i,
            /save/i,
            /record/i,
            /capture/i,
            /\.mp4/i,
            /\.mkv/i,
            /\.avi/i,
            /stream.*download/i
        ];
        
        suspiciousPatterns.forEach(pattern => {
            if (pattern.test(url)) {
                this.handleViolation('suspicious_network_request', {
                    url: url.substring(0, 100), // Ograniči dužinu
                    method: method,
                    pattern: pattern.toString()
                });
            }
        });
    }
    
    // 6. Screenshot zaštita
    preventScreenshots() {
        // Blokiranje Print Screen tastera
        document.addEventListener('keydown', (e) => {
            if (e.key === 'PrintScreen') {
                e.preventDefault();
                this.handleViolation('screenshot_attempt', {
                    key: e.key,
                    keyCode: e.keyCode
                });
                
                // Očisti clipboard
                if (navigator.clipboard) {
                    navigator.clipboard.writeText('');
                }
            }
        });
        
        // Blur video na print
        window.addEventListener('beforeprint', (e) => {
            e.preventDefault();
            this.handleViolation('print_attempt');
        });
    }
    
    // 7. Heartbeat sistem
    startHeartbeat() {
        if (!this.accessToken || !this.deviceId) {
            console.error('❌ Cannot start heartbeat: missing token or device ID');
            return;
        }
        
        this.heartbeatInterval = setInterval(async () => {
            try {
                const response = await fetch('api/ppv.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'heartbeat',
                        token: this.accessToken,
                        device_id: this.deviceId,
                        violations_count: this.violations.length,
                        performance_metrics: this.getPerformanceMetrics()
                    })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    console.log('💔 Heartbeat failed:', data.error);
                    this.terminateStream('Session expired: ' + data.error);
                    return;
                }
                
                console.log('💓 Heartbeat OK');
                
            } catch (error) {
                console.error('💔 Heartbeat error:', error);
            }
        }, 120000); // 2 minute
    }
    
    // 8. Performance metrics
    getPerformanceMetrics() {
        return {
            memory_used: performance.memory ? performance.memory.usedJSHeapSize : 0,
            timing: performance.timing ? (performance.timing.loadEventEnd - performance.timing.navigationStart) : 0,
            connection: navigator.connection ? {
                effective_type: navigator.connection.effectiveType,
                downlink: navigator.connection.downlink
            } : null
        };
    }
    
    // 9. Violation handler
    handleViolation(type, details = {}) {
        const violation = {
            type: type,
            details: details,
            timestamp: Date.now(),
            url: window.location.href,
            userAgent: navigator.userAgent
        };
        
        this.violations.push(violation);
        
        console.warn('🚨 Security violation detected:', type, details);
        
        // Kritične violation-e = instant termination
        const criticalViolations = [
            'devtools_console_detection',
            'screen_recording_attempt',
            'media_recorder_attempt'
        ];
        
        if (criticalViolations.includes(type)) {
            this.terminateStream(`Critical security violation: ${type}`);
            return;
        }
        
        // Previše violation-a = termination
        if (this.violations.length >= 5) {
            this.terminateStream('Too many security violations detected');
            return;
        }
        
        // Pošalji na server
        this.reportViolation(violation);
    }
    
    // 10. Report violation na server
    async reportViolation(violation) {
        try {
            await fetch('api/ppv.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'report_violation',
                    token: this.accessToken,
                    device_id: this.deviceId,
                    violation: violation,
                    total_violations: this.violations.length
                })
            });
        } catch (error) {
            console.log('Could not report violation to server');
        }
    }
    
    // 11. Terminate stream
    terminateStream(reason) {
        console.error('🚫 STREAM TERMINATED:', reason);
        
        // Zaustavi heartbeat
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
        }
        
        // Ukloni video
        const iframe = document.getElementById('protected-iframe');
        if (iframe) {
            iframe.src = 'about:blank';
        }
        
        // Prikaži poruku
        const container = document.getElementById('stream-container');
        if (container) {
            container.innerHTML = `
                <div style="
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                    height: 100%; 
                    background: linear-gradient(135deg, #c41e3a, #8b0000); 
                    color: white; 
                    text-align: center; 
                    font-family: Arial, sans-serif;
                    padding: 40px;
                ">
                    <div>
                        <h1 style="font-size: 3rem; margin-bottom: 20px;">🚨</h1>
                        <h2 style="margin-bottom: 20px;">Stream Terminated</h2>
                        <p style="margin-bottom: 10px; font-size: 1.1rem;">${reason}</p>
                        <p style="margin-bottom: 30px; opacity: 0.8;">Security violation detected. Access has been suspended.</p>
                        <button 
                            onclick="window.close()" 
                            style="
                                padding: 15px 30px; 
                                background: rgba(255,255,255,0.2); 
                                color: white; 
                                border: 2px solid white; 
                                border-radius: 8px; 
                                cursor: pointer; 
                                font-size: 1rem;
                                font-weight: 600;
                                transition: all 0.3s ease;
                            "
                            onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                            onmouseout="this.style.background='rgba(255,255,255,0.2)'"
                        >
                            Close Window
                        </button>
                    </div>
                </div>
            `;
        }
        
        // Pošalji final report
        this.reportViolation({
            type: 'stream_terminated',
            details: { reason: reason },
            timestamp: Date.now()
        });
    }
    
    // 12. Cleanup
    cleanup() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
        }
        console.log('🛡️ Enhanced protection cleanup completed');
    }
}

// Kreiraj globalnu instancu
window.enhancedProtection = new EnhancedDRMProtection();

// Export za module sisteme
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EnhancedDRMProtection;
}