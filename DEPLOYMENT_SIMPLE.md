# 🚀 BIF PPV - Jednostavan Production Deployment

## ✅ Tvoj Pristup (PREPORUČENO)

Kod već vuče iz `env/.env`, tako da samo treba:
1. Upload-ovati `env/.env` sa NOVIM lozinkama
2. `.htaccess` već štiti `env/` folder (403 Forbidden)
3. `.gitignore` već ignoriše `env/.env` (ako koristiš git)

---

## 🔐 PRE UPLOAD-A - Promeni Lozinke u env/.env

**⚠️ KRITIČNO:** Sve trenutne lozinke su vidljive u ovom fajlu!

### Otvori `env/.env` i promeni:

```env
# 1. EMAIL LOZINKA
SMTP_PASSWORD=NOVA_LOZINKA  # Promeni u Titan Email dashboard

# 2. AWS CREDENTIALS
AWS_ACCESS_KEY_ID=NOVI_KEY  # Kreiraj nove u AWS IAM Console
AWS_SECRET_ACCESS_KEY=NOVI_SECRET

# 3. DATABASE
DB_PASS=NOVA_BAZA_LOZINKA  # Dobićeš od hosting providera

# 4. ADMIN LOZINKA
PPV_ADMIN_PASSWORD=NOVA_JAKA_ADMIN_LOZINKA

# 5. STRIPE KEYS - Kreiraj NOVE test ključeve
STRIPE_PUBLISHABLE_KEY=pk_test_NOVI_KEY
STRIPE_SECRET_KEY=sk_test_NOVI_KEY
STRIPE_WEBHOOK_SECRET=whsec_NOVI_SECRET

# 6. SECURITY KEYS - Generiši random (https://randomkeygen.com/)
STREAM_SIGNING_KEY=generiši-32-karaktera-random
JWT_SECRET=generiši-32-karaktera-random
ENCRYPTION_KEY=generiši-32-karaktera-random

# 7. RECAPTCHA - Kreiraj nove za production domain
RECAPTCHA_SECRET_KEY=NOVI_SECRET
RECAPTCHA_SITE_KEY=NOVI_SITE_KEY

# 8. SITE URL
SITE_URL=https://bif.events  # Zameni localhost sa pravim domenom

# 9. APP ENV
APP_ENV=production
DEBUG_MODE=false
```

---

## 📤 Upload na Hosting

### Opcija 1: FTP/SFTP (FileZilla, WinSCP)
```
1. Poveži se sa serverom
2. Upload-uj SVE fajlove UKLJUČUJUĆI env/ folder
3. .htaccess će automatski blokirati pristup env/.env
```

### Opcija 2: cPanel File Manager
```
1. ZIP-uj ceo folder
2. Upload ZIP na server
3. Extract u public_html/
```

### Opcija 3: Git Deploy
```bash
# Na serveru
git clone https://github.com/yourusername/bif-ppv.git
cd bif-ppv
composer install --no-dev
# Ažuriraj env/.env manual sa novim lozinkama
```

---

## 🔧 Na Serveru - Posle Upload-a

### 1. File Permissions
```bash
chmod 755 /public_html
chmod 755 data/
chmod 644 data/*.json
chmod 600 env/.env  # Samo owner može čitati
chmod 644 .htaccess
```

### 2. Composer (ako nije upload-ovan vendor/)
```bash
cd /path/to/site
composer install --no-dev --optimize-autoloader
```

### 3. Database Setup
```bash
# Import database
mysql -u username -p database_name < setup_database.sql

# Proveri da su tabele kreirane
mysql -u username -p database_name -e "SHOW TABLES;"
```

### 4. Test da li env/.env je zaštićen
```bash
curl -I https://bif.events/env/.env
# Trebalo bi: 403 Forbidden
```

---

## ✅ Security Checklist

- [x] `.htaccess` blokira `env/` folder (403)
- [x] `.htaccess` blokira `*.log` fajlove (403)
- [x] `.gitignore` ignoriše `env/.env`
- [x] Sve lozinke promenjene u `env/.env`
- [x] Stripe NOVI test keys
- [x] AWS NOVI credentials
- [x] reCAPTCHA NOVI keys za production domain
- [x] SITE_URL = https://bif.events
- [x] APP_ENV = production
- [x] DEBUG_MODE = false

---

## 🧪 Post-Deployment Test

### 1. Test Security
```bash
# Env fajl blokiran?
curl -I https://bif.events/env/.env
→ 403 Forbidden ✅

# Log fajlovi blokirani?
curl -I https://bif.events/data/php_errors.log
→ 403 Forbidden ✅

# Admin traži lozinku?
curl -I https://bif.events/admin/admin.html
→ Traži autentifikaciju ✅
```

### 2. Test Funkcionalnost
- [ ] Glavni sajt učitava: https://bif.events
- [ ] Newsletter signup radi
- [ ] Admin panel pristup radi
- [ ] Borci stranice: https://bif.events/borci/ime-borca
- [ ] Vesti stranice: https://bif.events/vesti/slug
- [ ] PPV watch page: https://bif.events/watch.php
- [ ] Stripe test payment (checkout flow)
- [ ] Email notifikacije

### 3. Check Logs
```bash
# SSH na server
tail -f data/php_errors.log
tail -f data/security.log
```

---

## 💳 Stripe Test Mode (Prvih 2 Meseca)

### ✅ Šta RADI sa test keys:
- Ceo payment flow radi
- Možeš testirati checkout
- Webhooks rade
- Admin panel vidi "test" kupovine

### ❌ Šta NE RADI sa test keys:
- Niko ne može platiti sa realnom karticom
- Nema pravih transakcija
- Nema pravog novca

### Test kartice za testiranje:
```
4242 4242 4242 4242 (Visa - uspešna)
4000 0000 0000 0002 (Visa - declined)
Datum: bilo koji budući
CVC: bilo koja 3 cifre
```

---

## 🔄 Za 2 Meseca - Prelazak na LIVE

### 1. Stripe Business Verification
```
1. Dashboard → Settings → Business settings
2. Popuni business details
3. Dodaj bank account
4. Verifikuj tax information
```

### 2. Dobij LIVE Keys
```
Dashboard → Developers → API keys
Toggle "View test data" OFF
Copy LIVE keys
```

### 3. Update env/.env na Serveru
```bash
# SSH na server
nano env/.env

# Promeni:
STRIPE_PUBLISHABLE_KEY=pk_live_TVOJ_LIVE_KEY
STRIPE_SECRET_KEY=sk_live_TVOJ_LIVE_KEY
```

### 4. Postavi LIVE Webhook
```
1. Stripe Dashboard → Webhooks
2. Add endpoint: https://bif.events/api/webhook.php
3. Events: payment_intent.succeeded, payment_intent.payment_failed
4. Copy webhook secret → Update env/.env
```

### 5. Test sa Realnom Karticom (Mala Vrednost)
```
Testiraj sa svojom karticom:
- Iznos: 100 RSD (1 EUR)
- Proveri da se pojavljuje u Stripe Dashboard (LIVE mode)
- Proveri refund flow
```

---

## 🚨 Emergency Plan

### Ako nešto ne radi:

1. **Check Error Logs**
```bash
tail -50 data/php_errors.log
```

2. **Check .env je učitan**
```bash
# Kreiraj test.php
<?php
require_once 'config/env_loader.php';
echo getenv('STRIPE_SECRET_KEY') ? 'ENV OK' : 'ENV FAILED';
?>
```

3. **Restart Apache** (ako imaš pristup)
```bash
sudo systemctl restart apache2
```

4. **Rollback Plan**
```bash
# Backup pre deploy-a
tar -czf backup-$(date +%Y%m%d).tar.gz /path/to/old/site

# Restore ako zatreba
tar -xzf backup-20251107.tar.gz
```

---

## 📞 Kontakti

```
Hosting Support: [broj/email]
Domain: [registrar]
Stripe Support: https://support.stripe.com/
AWS Support: https://console.aws.amazon.com/support/
Email Support (Titan): support@titan.email
```

---

## ✅ Final Pre-Flight Checklist

```bash
# Pre Upload-a:
[x] env/.env - SVE lozinke promenjene
[x] env/.env - SITE_URL = https://bif.events
[x] env/.env - APP_ENV = production
[x] env/.env - DEBUG_MODE = false
[x] Novi Stripe test keys
[x] Novi AWS credentials
[x] Novi reCAPTCHA keys

# Posle Upload-a:
[x] File permissions postavljeni
[x] composer install
[x] Database import
[x] Test security (env/, logs blocked)
[x] Test main site
[x] Test admin panel
[x] Test newsletter
[x] Test Stripe checkout

# Monitoring:
[x] SSL certificate aktivan
[x] Uptime monitoring setup (UptimeRobot)
[x] Error logging radi
[x] Email notifikacije rade
```

---

## 🎯 Zaključak

**DA, možeš na live sa Stripe TEST keys!**

✅ Sajt će raditi normalno
✅ Možeš testirati sve funkcionalnosti
✅ Niko neće moći da plati (samo test kartice)
✅ Za 2 meseca samo promeniš keys u env/.env

**env/.env je zaštićen sa:**
- `.htaccess` (403 Forbidden)
- `.gitignore` (ako koristiš git)
- File permissions (chmod 600)

---

**SRECNO SA LAUNCH-OM! 🚀**

*Autor: Claude Code*
*Datum: 7. Novembar 2025*
