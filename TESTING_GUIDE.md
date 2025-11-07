# 🧪 TESTING GUIDE - Sigurnosna Poboljšanja

Ovaj vodič pokazuje kako testirati sva nova sigurnosna poboljšanja.

---

## 📋 PRE-FLIGHT CHECKLIST

Pre testiranja, proveri:

- [ ] PHP 7.4+ instaliran
- [ ] Composer instaliran (`composer install` pokrenut)
- [ ] `env/.env` fajl postoji i ima sve potrebne keys
- [ ] Web server pokrenut (Apache/Nginx)
- [ ] Browser sa Dev Tools (Chrome/Firefox)

---

## 1️⃣ TESTIRANJE UKLANJANJA HARDKODOVANIH KLJUČEVA

### Test 1: Sistem bez .env fajla

**Cilj:** Proveriti da sistem pada ako nema reCAPTCHA key umesto da koristi fallback.

**Koraci:**
```bash
# 1. Backup trenutnog .env
cd env
copy .env .env.backup

# 2. Privremeno ukloni .env
ren .env .env.disabled

# 3. Pokušaj otvoriti watch.php
```

**Očekivani rezultat:**
```
Configuration error: reCAPTCHA not configured. Please contact administrator.
```

**Restore:**
```bash
cd env
ren .env.disabled .env
```

---

### Test 2: Provera logova

**Koraci:**
```bash
# Očisti postojeće logove
del data\php_errors.log

# Pokušaj pristup sa disabled .env (iz Test 1)
# Zatim proveri log:
type data\php_errors.log
```

**Očekivano u logu:**
```
[31-Oct-2025 12:00:00] CRITICAL: RECAPTCHA_SITE_KEY not configured in .env file
```

---

## 2️⃣ TESTIRANJE .GITIGNORE

### Test 3: Git status check

**Cilj:** Proveriti da `.env` i osetljivi fajlovi nisu tracked.

**Koraci:**
```bash
# Inicijalizuj git repo ako već nije
git init

# Proveri status
git status
```

**Očekivani rezultat:**
- `env/.env` **NE SME** biti u "Untracked files"
- `data/ppv_*.json` **NE SME** biti u listi
- `vendor/` **NE SME** biti u listi

**Ako se vidi `.env`:**
```bash
# Dodaj u .gitignore ako nije
echo env/.env >> .gitignore
git add .gitignore
git commit -m "Add env/.env to gitignore"
```

---

## 3️⃣ TESTIRANJE CSRF ZAŠTITE

### Test 4: Normalan payment flow (validni token)

**Cilj:** Proveriti da payment radi sa validnim CSRF tokenom.

**Koraci:**
1. Otvori `http://localhost/bif-PPV/watch.php?event=1`
2. Otvori Dev Tools (F12) → Network tab
3. Popuni payment formu i klikni "Kupi Pristup"
4. U Network tab, nađi POST request ka `/api/ppv.php`
5. Klikni na request → Payload tab

**Očekivani rezultat:**
```json
{
  "action": "create_payment",
  "event_id": "1",
  "email": "test@example.com",
  "name": "Test User",
  "csrf_token": "abc123...def456",  // ← Token prisutan!
  ...
}
```

**Status:** `200 OK`
**Response:** `{ "success": true, ... }`

---

### Test 5: Payment bez CSRF tokena (attack simulation)

**Cilj:** Proveriti da sistem blokira requeste bez CSRF tokena.

**Koraci:**
1. Otvori Dev Tools → Console
2. Izvrši ovaj JavaScript kod:

```javascript
// Simuliraj CSRF napad - slanje requesta BEZ tokena
fetch('/api/ppv.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'create_payment',
        event_id: '1',
        email: 'attacker@evil.com',
        name: 'Hacker',
        payment_method_id: 'pm_fake123'
        // csrf_token намerno izostavljен!
    })
})
.then(r => r.json())
.then(d => console.log('Response:', d))
.catch(e => console.error('Error:', e));
```

**Očekivani rezultat:**
```json
{
  "success": false,
  "error": "CSRF validation failed",
  "message": "CSRF token missing"
}
```

**HTTP Status:** `403 Forbidden`

---

### Test 6: Payment sa pogrešnim CSRF tokenom

**Koraci:**
```javascript
// U Console
fetch('/api/ppv.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'create_payment',
        event_id: '1',
        email: 'test@example.com',
        name: 'Test',
        csrf_token: 'FAKE_TOKEN_12345', // ← Nevažeći token
        payment_method_id: 'pm_test'
    })
})
.then(r => r.json())
.then(d => console.log(d));
```

**Očekivani rezultat:**
```json
{
  "success": false,
  "error": "CSRF validation failed",
  "message": "CSRF token invalid or expired"
}
```

---

### Test 7: Token expiracija

**Cilj:** Proveriti da token expiruje posle 1 sata.

**Koraci:**
1. Otvori `includes/csrf-protection.php`
2. Privremeno promeni `TOKEN_EXPIRY` na 10 sekundi:
```php
private const TOKEN_EXPIRY = 10; // Umesto 3600
```
3. Otvori watch.php i čekaj 15 sekundi
4. Pokušaj payment

**Očekivani rezultat:**
```json
{
  "success": false,
  "error": "CSRF validation failed",
  "message": "CSRF token invalid or expired"
}
```

**Restore:**
```php
private const TOKEN_EXPIRY = 3600; // Vrati na 1h
```

---

## 4️⃣ TESTIRANJE CSP HEADERS

### Test 8: Provera CSP headera u responsu

**Cilj:** Proveriti da svi security headeri postoje.

**Koraci:**
1. Otvori `http://localhost/bif-PPV/watch.php`
2. Dev Tools → Network tab
3. Reload stranicu (F5)
4. Klikni na glavni HTML request (`watch.php`)
5. Idi na Headers tab → Response Headers

**Očekivani headeri:**
```http
Content-Security-Policy: default-src 'self'; script-src 'self' https://js.stripe.com ...
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=(), ...
```

**Ako je HTTPS:**
```http
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
```

---

### Test 9: CSP blokiranje nedozvoljenih skripti

**Cilj:** Proveriti da CSP blokira eksterne skripte koji nisu whitelisted.

**Koraci:**
1. Otvori Dev Tools → Console
2. Pokušaj učitati eksternu skriptu:

```javascript
// Pokušaj dodati malicious script
const script = document.createElement('script');
script.src = 'https://evil.com/malware.js';
document.head.appendChild(script);
```

**Očekivani rezultat (u Console):**
```
Refused to load the script 'https://evil.com/malware.js' because it violates
the following Content Security Policy directive: "script-src 'self'
https://js.stripe.com https://www.google.com ..."
```

**Skripta NE SME da se učita!**

---

### Test 10: CSP blokiranje inline event handlers

**Koraci:**
```javascript
// Pokušaj dodati inline onclick
const btn = document.createElement('button');
btn.innerHTML = 'Click me';
btn.setAttribute('onclick', 'alert("XSS!")'); // ← Ovo CSP blokira!
document.body.appendChild(btn);

// Klikni na dugme
```

**Očekivani rezultat:**
- Dugme se doda na stranicu
- Klikom na dugme **NIŠTA** se ne dešava
- U Console:
```
Refused to execute inline event handler because it violates CSP directive
```

---

### Test 11: X-Frame-Options (Clickjacking zaštita)

**Cilj:** Proveriti da sajt ne može biti embedovan u iframe.

**Koraci:**
1. Kreiraj test HTML fajl: `test-iframe.html`
```html
<!DOCTYPE html>
<html>
<head><title>Iframe Test</title></head>
<body>
    <h1>Trying to embed watch.php in iframe:</h1>
    <iframe src="http://localhost/bif-PPV/watch.php"
            width="800" height="600"></iframe>
</body>
</html>
```
2. Otvori `test-iframe.html` u browseru

**Očekivani rezultat:**
- Iframe je prazan (ne prikazuje sadržaj)
- U Console:
```
Refused to display 'http://localhost/bif-PPV/watch.php' in a frame
because it set 'X-Frame-Options' to 'deny'.
```

---

### Test 12: API CSP (stroži od frontend)

**Koraci:**
1. Otvori `http://localhost/bif-PPV/api/ppv.php?action=config`
2. Dev Tools → Network → Headers

**Očekivani header:**
```http
Content-Security-Policy: default-src 'none'; frame-ancestors 'none'
```

**Objašnjenje:** API ne prikazuje HTML, zato je CSP najstroži (`'none'`).

---

## 5️⃣ TESTIRANJE ADMIN PANEL HEADERS

### Test 13: Admin CSP (blažiji)

**Koraci:**
1. Otvori `http://localhost/bif-PPV/admin/admin.html`
2. Dev Tools → Network → Headers (za `ppv_admin.php` request)

**Očekivani header:**
```http
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' ...
```

**Napomena:** Admin ima `unsafe-inline` i `unsafe-eval` jer admin tools često koriste dinamički JavaScript.

---

## 6️⃣ INTEGRACIJA TEST

### Test 14: Kompletan payment flow

**Cilj:** End-to-end test sa svim sigurnosnim feature-ima.

**Koraci:**
1. Otvori `watch.php?event=1`
2. Dev Tools → Console i Network otvoreni
3. Popuni formu:
   - **Email:** test@example.com
   - **Ime:** Test User
4. Unesi test karticu:
   - **Broj:** 4242 4242 4242 4242
   - **Exp:** 12/34
   - **CVC:** 123
5. Klikni "Kupi Pristup"

**Očekivani flow:**
1. ✅ reCAPTCHA challenge (automatski)
2. ✅ Stripe PaymentMethod kreiran
3. ✅ POST request sa CSRF tokenom
4. ✅ Backend validira CSRF
5. ✅ Backend validira security score
6. ✅ Stripe PaymentIntent kreiran
7. ✅ Redirect na stream sa access tokenom

**Provera u Network tab:**
- Request `create_payment` → Status 200
- Response sadrži `success: true`
- Redirect na stream

**Provera u Console:**
- Nema CSP violations
- Nema CSRF errors

---

## 7️⃣ SECURITY SCAN (Online Tools)

### Test 15: Mozilla Observatory

**Koraci:**
1. Ako imaš public URL, otvori: https://observatory.mozilla.org/
2. Unesi URL: `https://tvoj-sajt.com`
3. Klikni "Scan Me"

**Očekivana ocena:** A ili A+ (90-100 bodova)

**Provere:**
- ✅ Content Security Policy implemented
- ✅ HTTP Strict Transport Security (ako HTTPS)
- ✅ X-Content-Type-Options
- ✅ X-Frame-Options

---

### Test 16: SecurityHeaders.com

**Koraci:**
1. Otvori: https://securityheaders.com/
2. Unesi URL

**Očekivana ocena:** A ili A+

---

### Test 17: CSP Evaluator (Google)

**Koraci:**
1. Otvori: https://csp-evaluator.withgoogle.com/
2. Kopiraj CSP iz Response Headers
3. Paste u evaluator

**Očekivano:**
- ✅ Majority of checks pass
- ⚠️ Eventualna upozorenja za `'unsafe-inline'` (prihvatljivo)

---

## 🔍 DEBUGGING TIPS

### Problem: CSRF token ne stigne do fronenda

**Debug koraci:**
```javascript
// U Console
console.log(bifPPV.csrfToken); // Proveri da nije undefined
```

**Ako je `undefined`:**
1. Proveri da `loadConfig()` ispravno čuva token
2. Proveri Network tab → `/api/ppv.php?action=config` response
3. Proveri da backend vraća `csrf_token` u response

---

### Problem: CSP blokira legitiman resource

**Debug:**
1. Otvori Console
2. Nađi CSP violation error
3. Kopiraj URL koji je blokiran
4. Dodaj domen u odgovarajuću CSP direktivu u `security-headers.php`

**Primer:**
```
Refused to load 'https://new-cdn.com/script.js'
```

**Fix:**
```php
// U security-headers.php
"script-src 'self' https://js.stripe.com https://new-cdn.com 'unsafe-inline'",
```

---

### Problem: Stripe ne radi zbog CSP

**Proveri da CSP uključuje:**
```
script-src: https://js.stripe.com
frame-src: https://js.stripe.com https://hooks.stripe.com
connect-src: https://api.stripe.com
```

**Ako i dalje ne radi, privremeno dodaj:**
```
script-src: ... 'unsafe-eval'
```

---

## ✅ FINAL CHECKLIST

Pre produkcije, proveri da sve prolazi:

- [ ] Test 1: Sistem pada bez .env ✅
- [ ] Test 4: Payment radi sa CSRF tokenom ✅
- [ ] Test 5: Payment blokiran bez CSRF tokena ✅
- [ ] Test 6: Payment blokiran sa lažnim tokenom ✅
- [ ] Test 8: Svi security headeri prisutni ✅
- [ ] Test 9: CSP blokira nedozvoljene skripte ✅
- [ ] Test 11: X-Frame-Options blokira iframe ✅
- [ ] Test 14: Kompletan payment flow radi ✅
- [ ] Test 15: Mozilla Observatory > 90 bodova ✅
- [ ] Git ne tracka `.env` fajl ✅

---

## 📞 POMOĆ

Ako neki test ne prolazi:

1. Proveri `data/php_errors.log` za PHP greške
2. Proveri Browser Console za JavaScript greške
3. Proveri Network tab za failed requests
4. Pročitaj `SECURITY_IMPROVEMENTS.md` za detalje implementacije

---

**Sretno testiranje! 🚀**
