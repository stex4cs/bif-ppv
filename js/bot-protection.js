/**
 * KREIRAJ: js/bot-protection.js
 * Advanced Client-Side Bot Protection
 */

class BIF_BotProtection {
    constructor() {
        this.startTime = Date.now();
        this.interactions = [];
        this.fingerprint = {};
        this.jsChallenge = null;
        this.recaptchaToken = null;
        this.mouseMovements = 0;
        this.keystrokes = 0;
        this.formFocusTime = 0;
        
        this.init();
    }
    
    init() {
        console.log('🛡️ BIF Bot Protection initializing...');
        
        this.generateFingerprint();
        this.setupEventListeners();
        this.createHoneypots();
        this.generateJSChallenge();
        this.initRecaptcha();
        this.startBehaviorTracking();
        
        console.log('✅ Bot protection active');
    }
    
    /**
     * GENERATE ADVANCED DEVICE FINGERPRINT
     */
    generateFingerprint() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        ctx.textBaseline = 'top';
        ctx.font = '14px Arial';
        ctx.fillText('BIF Security', 2, 2);
        const canvasFingerprint = canvas.toDataURL();
        
        // WebGL fingerprinting
        const gl = canvas.getContext('webgl');
        const webglInfo = gl ? {
            vendor: gl.getParameter(gl.VENDOR),
            renderer: gl.getParameter(gl.RENDERER),
            version: gl.getParameter(gl.VERSION)
        } : null;
        
        // Audio context fingerprinting
        let audioFingerprint = '';
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioCtx.createOscillator();
            const analyser = audioCtx.createAnalyser();
            const gainNode = audioCtx.createGain();
            
            oscillator.type = 'triangle';
            oscillator.frequency.value = 10000;
            gainNode.gain.value = 0;
            
            oscillator.connect(analyser);
            analyser.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            
            oscillator.start();
            const dataArray = new Uint8Array(analyser.frequencyBinCount);
            analyser.getByteFrequencyData(dataArray);
            oscillator.stop();
            
            audioFingerprint = Array.from(dataArray).slice(0, 20).join(',');
            audioCtx.close();
        } catch (e) {
            audioFingerprint = 'unavailable';
        }
        
        this.fingerprint = {
            screen: `${screen.width}x${screen.height}`,
            availScreen: `${screen.availWidth}x${screen.availHeight}`,
            colorDepth: screen.colorDepth,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            language: navigator.language,
            languages: navigator.languages ? navigator.languages.join(',') : '',
            platform: navigator.platform,
            hardwareConcurrency: navigator.hardwareConcurrency || 0,
            deviceMemory: navigator.deviceMemory || 0,
            maxTouchPoints: navigator.maxTouchPoints || 0,
            cookieEnabled: navigator.cookieEnabled,
            doNotTrack: navigator.doNotTrack,
            webdriver: navigator.webdriver || false,
            plugins: Array.from(navigator.plugins).map(p => p.name).join(','),
            canvas: canvasFingerprint.substring(0, 50),
            webgl: webglInfo,
            audio: audioFingerprint,
            userAgent: navigator.userAgent,
            connection: navigator.connection ? {
                effectiveType: navigator.connection.effectiveType,
                downlink: navigator.connection.downlink,
                rtt: navigator.connection.rtt
            } : null,
            battery: null // Will be filled later if available
        };
        
        // Battery API (if available)
        if ('getBattery' in navigator) {
            navigator.getBattery().then(battery => {
                this.fingerprint.battery = {
                    charging: battery.charging,
                    level: Math.round(battery.level * 100)
                };
            }).catch(() => {});
        }
        
        console.log('📱 Device fingerprint generated');
    }
    
    /**
     * SETUP EVENT LISTENERS FOR HUMAN BEHAVIOR
     */
    setupEventListeners() {
        let mouseTimer = null;
        let keyTimer = null;
        
        // Mouse movement tracking
        document.addEventListener('mousemove', (e) => {
            this.mouseMovements++;
            this.interactions.push({
                type: 'mouse',
                timestamp: Date.now(),
                x: e.clientX,
                y: e.clientY
            });
            
            // Throttle interactions logging
            if (mouseTimer) clearTimeout(mouseTimer);
            mouseTimer = setTimeout(() => {
                if (this.interactions.length > 100) {
                    this.interactions = this.interactions.slice(-50);
                }
            }, 1000);
        });
        
        // Keyboard tracking
        document.addEventListener('keydown', (e) => {
            this.keystrokes++;
            this.interactions.push({
                type: 'key',
                timestamp: Date.now(),
                key: e.key,
                code: e.code
            });
        });
        
        // Form focus tracking
        document.addEventListener('focus', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                this.formFocusTime = Date.now();
            }
        }, true);
        
        // Touch events for mobile
        document.addEventListener('touchstart', (e) => {
            this.interactions.push({
                type: 'touch',
                timestamp: Date.now(),
                touches: e.touches.length
            });
        });
        
        // Window focus/blur detection
        let focusTime = Date.now();
        window.addEventListener('focus', () => {
            focusTime = Date.now();
        });
        
        window.addEventListener('blur', () => {
            const timeAway = Date.now() - focusTime;
            if (timeAway > 100) { // Ignore very short blurs
                this.interactions.push({
                    type: 'focus_loss',
                    timestamp: Date.now(),
                    duration: timeAway
                });
            }
        });
        
        console.log('👆 Event listeners setup complete');
    }
    
    /**
     * CREATE HONEYPOT FIELDS
     */
    createHoneypots() {
        // Invisible field that bots might fill
        const honeypot = document.createElement('input');
        honeypot.type = 'text';
        honeypot.name = 'website';
        honeypot.style.cssText = 'position: absolute; left: -9999px; opacity: 0; pointer-events: none;';
        honeypot.tabIndex = -1;
        honeypot.setAttribute('autocomplete', 'off');
        
        // Add to form if it exists
        const form = document.getElementById('payment-form');
        if (form) {
            form.appendChild(honeypot);
        }
        
        // Time-based honeypot
        const timeField = document.createElement('input');
        timeField.type = 'hidden';
        timeField.name = 'form_time';
        timeField.value = '0';
        if (form) form.appendChild(timeField);
        
        console.log('🍯 Honeypots deployed');
    }
    
    /**
     * GENERATE JAVASCRIPT CHALLENGE
     */
    generateJSChallenge() {
        // Mathematical challenge that bots might struggle with
        const challenges = [
            () => Math.sqrt(144) + Math.pow(2, 3),
            () => [1,2,3,4,5].reduce((a,b) => a+b) * 2,
            () => new Date().getFullYear() - 2000,
            () => 'BIF'.length * 'PPV'.length + 1
        ];
        
        const challenge = challenges[Math.floor(Math.random() * challenges.length)];
        this.jsChallenge = challenge();
        
        console.log('🧮 JS Challenge generated');
    }
    
    /**
     * INITIALIZE RECAPTCHA V3
     */
    initRecaptcha() {
        const siteKey = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'; // Test key - replace with real
        
        if (!window.grecaptcha) {
            // Load reCAPTCHA script
            const script = document.createElement('script');
            script.src = `https://www.google.com/recaptcha/api.js?render=${siteKey}`;
            script.onload = () => {
                console.log('🤖 reCAPTCHA loaded');
                this.setupRecaptcha(siteKey);
            };
            document.head.appendChild(script);
        } else {
            this.setupRecaptcha(siteKey);
        }
    }
    
    setupRecaptcha(siteKey) {
        grecaptcha.ready(() => {
            console.log('✅ reCAPTCHA ready');
        });
    }
    
    async getRecaptchaToken(action = 'payment') {
        const siteKey = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
        
        return new Promise((resolve, reject) => {
            if (!window.grecaptcha) {
                reject(new Error('reCAPTCHA not loaded'));
                return;
            }
            
            grecaptcha.ready(() => {
                grecaptcha.execute(siteKey, { action }).then(token => {
                    this.recaptchaToken = token;
                    resolve(token);
                }).catch(reject);
            });
        });
    }
    
    /**
     * START BEHAVIOR TRACKING
     */
    startBehaviorTracking() {
        // Track typing patterns
        let keyTimes = [];
        document.addEventListener('keydown', (e) => {
            keyTimes.push(Date.now());
            if (keyTimes.length > 10) keyTimes.shift();
        });
        
        // Track scroll behavior
        let scrollCount = 0;
        window.addEventListener('scroll', () => {
            scrollCount++;
        });
        
        // Store behavior data
        setInterval(() => {
            this.behaviorData = {
                mouseMovements: this.mouseMovements,
                keystrokes: this.keystrokes,
                scrollCount: scrollCount,
                interactionCount: this.interactions.length,
                sessionDuration: Date.now() - this.startTime,
                keyTiming: this.analyzeKeyTiming(keyTimes)
            };
        }, 5000);
        
        console.log('📊 Behavior tracking started');
    }
    
    analyzeKeyTiming(keyTimes) {
        if (keyTimes.length < 2) return { avg: 0, variance: 0 };
        
        const intervals = [];
        for (let i = 1; i < keyTimes.length; i++) {
            intervals.push(keyTimes[i] - keyTimes[i-1]);
        }
        
        const avg = intervals.reduce((a,b) => a+b) / intervals.length;
        const variance = intervals.reduce((a,b) => a + Math.pow(b - avg, 2), 0) / intervals.length;
        
        return { avg, variance };
    }
    
    /**
     * DETECT AUTOMATION TOOLS
     */
    detectAutomation() {
        const indicators = [];
        
        // Check for webdriver
        if (navigator.webdriver) {
            indicators.push('webdriver_present');
        }
        
        // Check for automation properties
        const automationProps = [
            'window.callPhantom',
            'window._phantom',
            'window.Buffer',
            'window.emit',
            'window.spawn'
        ];
        
        automationProps.forEach(prop => {
            if (eval(`typeof ${prop} !== 'undefined'`)) {
                indicators.push(`automation_${prop.split('.')[1]}`);
            }
        });
        
        // Check for missing properties that browsers should have
        if (!window.chrome && navigator.userAgent.includes('Chrome')) {
            indicators.push('fake_chrome');
        }
        
        // Check for unusual timing
        const start = performance.now();
        for (let i = 0; i < 100; i++) {
            Math.random();
        }
        const end = performance.now();
        
        if (end - start < 0.1) { // Too fast execution
            indicators.push('execution_too_fast');
        }
        
        return indicators;
    }
    
    /**
     * GENERATE DEVICE ID
     */
    generateDeviceId() {
        const components = [
            this.fingerprint.screen,
            this.fingerprint.timezone,
            this.fingerprint.language,
            this.fingerprint.platform,
            this.fingerprint.canvas,
            this.fingerprint.audio,
            navigator.userAgent
        ];
        
        let hash = 0;
        const str = components.join('|');
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        
        return 'bif_' + Math.abs(hash).toString(16) + '_' + Date.now().toString(36);
    }
    
    /**
     * COLLECT ALL SECURITY DATA
     */
    async collectSecurityData() {
        const formTime = this.formFocusTime ? 
            Math.round((Date.now() - this.formFocusTime) / 1000) : 0;
        
        // Get fresh reCAPTCHA token
        let recaptchaToken;
        try {
            recaptchaToken = await this.getRecaptchaToken('payment');
        } catch (e) {
            console.warn('reCAPTCHA token failed:', e);
            recaptchaToken = null;
        }
        
        const automationIndicators = this.detectAutomation();
        
        return {
            // Required security fields
            device_fingerprint: this.generateDeviceId(),
            recaptcha_token: recaptchaToken,
            
            // Behavior analysis
            form_time: formTime,
            session_duration: Math.round((Date.now() - this.startTime) / 1000),
            mouse_movements: this.mouseMovements,
            keystrokes: this.keystrokes,
            interaction_events: this.interactions.length,
            
            // JavaScript challenge
            js_challenge_response: this.jsChallenge,
            
            // Technical fingerprinting
            screen_width: screen.width,
            screen_height: screen.height,
            color_depth: screen.colorDepth,
            timezone_offset: new Date().getTimezoneOffset(),
            
            // Browser capabilities
            webgl_supported: !!this.fingerprint.webgl,
            canvas_supported: true,
            local_storage: typeof localStorage !== 'undefined',
            session_storage: typeof sessionStorage !== 'undefined',
            
            // Automation detection
            automation_indicators: automationIndicators,
            webdriver: navigator.webdriver || false,
            
            // Performance metrics
            memory_info: performance.memory ? {
                used: performance.memory.usedJSHeapSize,
                total: performance.memory.totalJSHeapSize,
                limit: performance.memory.jsHeapSizeLimit
            } : null,
            
            // Network information
            connection_info: this.fingerprint.connection,
            
            // Timing data
            behavior_data: this.behaviorData || {},
            
            // Full fingerprint
            full_fingerprint: this.fingerprint
        };
    }
    
    /**
     * VALIDATE FORM BEFORE SUBMISSION
     */
    async validateFormSubmission(formData) {
        console.log('🔍 Validating form submission...');
        
        const securityData = await this.collectSecurityData();
        
        // Client-side validation checks
        const checks = {
            hasMouseMovement: this.mouseMovements > 5,
            hasKeystrokes: this.keystrokes > 10,
            hasFormTime: securityData.form_time > 10 && securityData.form_time < 3600,
            hasRecaptcha: !!securityData.recaptcha_token,
            noAutomation: securityData.automation_indicators.length === 0,
            hasInteractions: this.interactions.length > 20
        };
        
        const passedChecks = Object.values(checks).filter(Boolean).length;
        const isValid = passedChecks >= 4; // At least 4 out of 6 checks must pass
        
        console.log('📊 Client validation:', { 
            checks, 
            passedChecks, 
            isValid,
            automationIndicators: securityData.automation_indicators
        });
        
        return {
            isValid,
            securityData,
            checks,
            passedChecks
        };
    }
}

// Initialize bot protection when DOM is ready
let bifBotProtection;
document.addEventListener('DOMContentLoaded', () => {
    bifBotProtection = new BIF_BotProtection();
    window.bifBotProtection = bifBotProtection;
    console.log('🛡️ BIF Bot Protection initialized globally');
});

// Export for use in other scripts
window.BIF_BotProtection = BIF_BotProtection;