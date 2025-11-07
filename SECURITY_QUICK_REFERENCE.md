# 🔒 SECURITY QUICK REFERENCE

Brzi vodič za korišćenje novih sigurnosnih feature-a.

---

## 📁 NOVI FAJLOVI

```
includes/
├── csrf-protection.php      # CSRF token management
└── security-headers.php     # HTTP security headers

env/
└── .env.example            # Template za environment variables

SECURITY_IMPROVEMENTS.md    # Detaljna dokumentacija
TESTING_GUIDE.md           # Test scenariji
```

---

## 🔑 CSRF PROTECTION

### Kako koristiti u novoj formi:

```php
<?php require_once 'includes/csrf-protection.php'; ?>

<form method="POST">
    <?php echo CSRF_Protection::getTokenField(); ?>
    <!-- Ostali input fields -->
</form>
```

### Validacija u backend-u:

```php
require_once 'includes/csrf-protection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Automatski blokira nevalidne requeste
    CSRF_Protection::requireValidToken();

    // Ili manuelno:
    $result = CSRF_Protection::validateRequest();
    if (!$result['valid']) {
        die('CSRF error: ' . $result['error']);
    }

    // Nastavi sa obradom...
}
```

### JavaScript/AJAX:

```javascript
// Uzmi token iz meta taga
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Pošalji sa AJAX requestom
fetch('/api/endpoint.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken  // Opcija 1: Header
    },
    body: JSON.stringify({
        action: 'something',
        csrf_token: csrfToken,  // Opcija 2: U payload-u
        data: 'value'
    })
});
```

---

## 🛡️ SECURITY HEADERS

### Frontend stranice (HTML):

```php
<?php
require_once 'includes/security-headers.php';
Security_Headers::apply();
?>
<!DOCTYPE html>
<html>
...
```

### API endpoints (JSON):

```php
<?php
require_once 'includes/security-headers.php';
Security_Headers::applyAPICSP();

header('Content-Type: application/json');
// ...
```

### Admin panel:

```php
<?php
require_once 'includes/security-headers.php';
Security_Headers::applyAdminCSP();
// ...
```

### File downloads:

```php
<?php
require_once 'includes/security-headers.php';
Security_Headers::applyDownloadHeaders($filename);

readfile($filePath);
```

---

## 🔧 CSP CUSTOMIZATION

### Dodavanje novog CDN-a:

Edituj `includes/security-headers.php`:

```php
private static function setCSP() {
    $cspDirectives = [
        // ...
        "script-src 'self' https://js.stripe.com https://novi-cdn.com 'unsafe-inline'",
        // ...
    ];
}
```

### Testiranje CSP pre produkcije:

Zameni `header("Content-Security-Policy: ..."` sa:

```php
header("Content-Security-Policy-Report-Only: " . $csp);
```

Ovo će samo reportovati violations bez blokiranja.

---

## 🌍 ENVIRONMENT SETUP

### Prvi put setup:

```bash
# 1. Kopiraj template
cd env
copy .env.example .env

# 2. Edituj .env i dodaj prave vrednosti
notepad .env

# 3. Minimum required:
RECAPTCHA_SITE_KEY=tvoj_site_key
RECAPTCHA_SECRET_KEY=tvoj_secret_key
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
```

### Provera da li .env radi:

```bash
# Otvori watch.php u browseru
# Ako nema greške = radi! ✅
# Ako piše "Configuration error" = ne radi ❌
```

---

## 🚨 COMMON ERRORS & FIXES

### Error: "CSRF token missing"

**Uzrok:** Frontend ne šalje token
**Fix:**
```javascript
// Proveri da token stigne sa API-ja
fetch('/api/ppv.php?action=config')
    .then(r => r.json())
    .then(d => console.log('CSRF token:', d.csrf_token));
```

---

### Error: "Refused to load script... CSP"

**Uzrok:** CSP blokira external script
**Fix:** Dodaj domen u `script-src` direktivu u `security-headers.php`

---

### Error: "Configuration error: reCAPTCHA not configured"

**Uzrok:** `RECAPTCHA_SITE_KEY` nije u `.env`
**Fix:**
```bash
# Dodaj u env/.env
RECAPTCHA_SITE_KEY=tvoj_key_ovde
```

---

### Error: "Refused to display in frame"

**Uzrok:** X-Frame-Options: DENY
**Fix:** Ovo je намerno! Ako stvarno trebaš iframe:
```php
// security-headers.php
header("X-Frame-Options: SAMEORIGIN"); // Umesto DENY
```

---

## 📊 SECURITY CHECKLIST

Pre deployment-a:

```bash
✅ env/.env postoji i popunjen
✅ env/.env je u .gitignore
✅ CSRF token validacija radi
✅ CSP headeri postavljeni
✅ Test payment flow prolazi
✅ Stripe production keys uneseni (ne test!)
✅ Admin panel zaštićen passwordom
✅ HTTPS aktivan (za HSTS header)
✅ Mozilla Observatory scan > 90
```

---

## 🔍 DEBUGGING COMMANDS

```bash
# Proveri PHP errore
type data\php_errors.log

# Proveri CSRF violations
findstr "CSRF" data\php_errors.log

# Proveri da .env nije tracked
git status | findstr ".env"

# Test API endpoint
curl -X POST http://localhost/bif-PPV/api/ppv.php ^
  -H "Content-Type: application/json" ^
  -d "{\"action\":\"config\"}"
```

---

## 📚 HELPFUL LINKS

| Resource | URL |
|----------|-----|
| **CSP Reference** | https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP |
| **OWASP CSRF** | https://owasp.org/www-community/attacks/csrf |
| **Security Headers Check** | https://securityheaders.com |
| **CSP Evaluator** | https://csp-evaluator.withgoogle.com |
| **Mozilla Observatory** | https://observatory.mozilla.org |

---

## 🆘 EMERGENCY ROLLBACK

Ako nešto pukne u produkciji:

```php
// U security-headers.php - privremeno disejbluj CSP
public static function apply() {
    // Komentiraj sve:
    // self::setCSP();
    // header("X-Frame-Options: DENY");
    // ...
}
```

```php
// U csrf-protection.php - privremeno disejbluj CSRF
public static function validateToken($token) {
    return true; // SAMO ZA EMERGENCY!
}
```

**⚠️ NE ZABORAVI DA VRATIŠ POSLE!**

---

## 🎯 QUICK TEST

```bash
# Test 1: CSRF radi?
curl -X POST http://localhost/bif-PPV/api/ppv.php ^
  -H "Content-Type: application/json" ^
  -d "{\"action\":\"create_payment\"}"

# Očekuješ: "CSRF token missing" ✅

# Test 2: CSP headers postavljeni?
curl -I http://localhost/bif-PPV/watch.php

# Očekuješ: Content-Security-Policy header ✅
```

---

**Kraj Quick Reference! 🚀**

Za detaljnu dokumentaciju: `SECURITY_IMPROVEMENTS.md`
Za testiranje: `TESTING_GUIDE.md`
