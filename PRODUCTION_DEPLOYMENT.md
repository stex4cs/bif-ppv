# 🚀 BIF PPV - Production Deployment Checklist

## ⚠️ KRITIČNO - Pre Upload-a na Server

### 1. 🔐 SIGURNOSNE AKCIJE (OBAVEZNO!)

#### A. Promena Lozinki
**VAŽNO:** Sve trenutne lozinke su kompromitirane u env/.env fajlu!

```bash
# AWS Credentials - KREIRAJ NOVE u AWS Console:
# 1. Idi na AWS IAM Console
# 2. Users → Tvoj user → Security credentials
# 3. Create access key → Kopiraj NOVE vrednosti
AWS_ACCESS_KEY_ID=NOVA_VREDNOST
AWS_SECRET_ACCESS_KEY=NOVA_VREDNOST

# Email lozinka - PROMENI u Titan Email dashboard
SMTP_PASSWORD=NOVA_LOZINKA

# Database lozinka - PROMENI u hosting control panel
DB_PASS=NOVA_LOZINKA

# Admin lozinka - PROMENI ovde i zapamti
PPV_ADMIN_PASSWORD=NOVA_JAKA_LOZINKA

# Stripe - KREIRAJ NOVE test ključeve u Stripe Dashboard
STRIPE_PUBLISHABLE_KEY=pk_test_NOVI_KLJUC
STRIPE_SECRET_KEY=sk_test_NOVI_KLJUC
```

#### B. Generisanje Novih Ključeva

```bash
# Random generator za encryption keys:
# https://randomkeygen.com/ (256-bit)

STREAM_SIGNING_KEY=generiši-novi-random-key-32-karaktera
JWT_SECRET=generiši-novi-random-key-32-karaktera
ENCRYPTION_KEY=generiši-novi-random-key-32-karaktera
```

#### C. reCAPTCHA
```bash
# Kreiraj NOVE ključeve na: https://www.google.com/recaptcha/admin
# Dodaj production domain
RECAPTCHA_SECRET_KEY=NOVI_SECRET
RECAPTCHA_SITE_KEY=NOVI_SITE_KEY
```

---

### 2. 📁 Fajlovi za Brisanje Pre Upload-a

**Obriši ili ne upload-uj:**

```
❌ env/.env                      # NIKAD ne uploaduj
❌ env/.env.example              # Opciono
❌ data/php_errors.log           # Log fajlovi
❌ composer.lock                 # Generiše se na serveru
❌ vendor/                       # Generiše se na serveru (composer install)
❌ .git/                         # Git folder
❌ .gitignore                    # Opciono
❌ *.md fajlovi                  # README, dokumentacija
❌ test-*.php                    # Test fajlovi
❌ debug_*.php                   # Debug fajlovi
❌ tools/                        # Development tools
❌ temp_index.html               # Temporary fajlovi
❌ nul                          # Prazni fajlovi
❌ CSP_FIX_README.md
❌ SECURITY_*.md
❌ TESTING_GUIDE.md
```

---

### 3. ⚙️ Production .env Fajl

**Kreirati na serveru kao `.env` u root folderu:**

```env
# ===== PRODUCTION CONFIGURATION =====
APP_ENV=production
DEBUG_MODE=false
LOG_LEVEL=ERROR

# Site URL
SITE_URL=https://bif.events

# ===== EMAIL CONFIGURATION =====
FROM_EMAIL=business@bif.events
FROM_NAME="BIF - Balkan Influence Fighting"
ADMIN_EMAIL=business@bif.events

SMTP_HOST=smtp.titan.email
SMTP_PORT=587
SMTP_USERNAME=business@bif.events
SMTP_PASSWORD=NOVA_LOZINKA_OVDE
SMTP_ENCRYPTION=tls

# ===== AWS CREDENTIALS (NOVI!) =====
AWS_ACCESS_KEY_ID=NOVI_AWS_KEY
AWS_SECRET_ACCESS_KEY=NOVI_AWS_SECRET
AWS_REGION=eu-north-1
AWS_ACCOUNT_ID=409263327103
AWS_MEDIALIVE_ROLE_ARN=arn:aws:iam::409263327103:role/MediaLiveRole
AWS_S3_BUCKET=bif-ppv-streams-stefan-2025

# ===== DATABASE CONFIGURATION =====
DB_HOST=localhost
DB_NAME=bif_ppv
DB_USER=root
DB_PASS=NOVA_BAZA_LOZINKA

# ===== STRIPE CONFIGURATION =====
# Za prvih 2 meseca - TEST MODE
STRIPE_PUBLISHABLE_KEY=pk_test_NOVI_TEST_KEY
STRIPE_SECRET_KEY=sk_test_NOVI_TEST_KEY
STRIPE_WEBHOOK_SECRET=whsec_NOVI_WEBHOOK_SECRET

# Kada bude live PPV - zameni sa LIVE keys:
# STRIPE_PUBLISHABLE_KEY=pk_live_...
# STRIPE_SECRET_KEY=sk_live_...
# STRIPE_WEBHOOK_SECRET=whsec_live_...

# ===== PPV SETTINGS =====
PPV_ACCESS_DURATION_DAYS=30
PPV_MAX_CONCURRENT_STREAMS=1
PPV_DEFAULT_PRICE=199900
PPV_DEFAULT_EARLY_BIRD_PRICE=149900
PPV_ADMIN_PASSWORD=NOVA_ADMIN_LOZINKA
PPV_ADMIN_ALLOWED_IPS=TVOJ_IP,185.71.88.226
PPV_MAX_DEVICES=1
PPV_DEVICE_COOLDOWN=3600
PPV_SESSION_TIMEOUT=300
PPV_VIOLATION_LIMIT=5
PPV_ENABLE_GEOLOCATION_CHECK=false

# ===== SECURITY KEYS (NOVI!) =====
STREAM_SIGNING_KEY=generiši-32-karaktera-random
JWT_SECRET=generiši-32-karaktera-random
ENCRYPTION_KEY=generiši-32-karaktera-random

# ===== RECAPTCHA (NOVI!) =====
RECAPTCHA_SECRET_KEY=NOVI_SECRET
RECAPTCHA_SITE_KEY=NOVI_SITE_KEY

# ===== RATE LIMITING =====
RATE_LIMIT_REQUESTS=5
RATE_LIMIT_MINUTES=10
WEBHOOK_RATE_LIMIT=100
WEBHOOK_RATE_LIMIT_WINDOW=3600

# ===== MONITORING =====
MONITORING_ENABLED=true
ALERT_EMAIL=admin@bif.events
DEFAULT_TIMEZONE=Europe/Belgrade
LOG_FILE_MAX_SIZE=10485760

# ===== SECURITY THRESHOLDS =====
SECURITY_MAX_IP_ATTEMPTS=10
SECURITY_MAX_EMAIL_ATTEMPTS=3
SECURITY_FRAUD_THRESHOLD=70
SECURITY_RECAPTCHA_MIN_SCORE=0.5
```

---

### 4. 🔧 Server Konfiguracija

#### A. PHP Requirements
```
PHP >= 7.4
Extensions: mysqli, json, curl, mbstring, openssl
```

#### B. Apache .htaccess
Proveri da je `mod_rewrite` enabled

#### C. File Permissions
```bash
chmod 755 /path/to/bif-PPV
chmod 755 data/
chmod 644 data/*.json
chmod 600 .env
```

#### D. Composer Instalacija
```bash
cd /path/to/bif-PPV
composer install --no-dev --optimize-autoloader
```

---

### 5. 🗄️ Database Setup

**Import database:**
```bash
mysql -u root -p bif_ppv < setup_database.sql
```

**Proveri tabele:**
```sql
SHOW TABLES;
-- Trebalo bi da vidis: ppv_purchases, ppv_access, ppv_streams, itd.
```

---

### 6. 🌐 Stripe Webhook Setup

**Kada budeš na production:**

1. Idi na Stripe Dashboard → Webhooks
2. Add endpoint: `https://bif.events/api/webhook.php`
3. Select events:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `charge.refunded`
4. Kopiraj **Webhook signing secret** u `.env`

---

### 7. ✅ Post-Deployment Checklist

#### Proveri sve funkcionalnosti:

- [ ] Glavni sajt se učitava (index.php)
- [ ] Newsletter signup radi
- [ ] Admin panel pristup (`/admin/admin.html`)
- [ ] Borci stranice (`/borci/ime-borca`)
- [ ] Vesti stranice (`/vesti/slug`)
- [ ] PPV watch page (`/watch.php`) - trebalo bi da kaže "nema aktivnih događaja"
- [ ] Stripe payment flow (test mode)
- [ ] Email notifikacije (test subscribe)
- [ ] reCAPTCHA se prikazuje na formama

#### Test Security:

```bash
# Test rate limiting
curl -I https://bif.events/api/newsletter.php

# Test admin access
curl https://bif.events/admin/admin.html
# Trebalo bi da traži lozinku

# Test .env pristup
curl https://bif.events/.env
# Trebalo bi 403 Forbidden
```

---

### 8. 📊 Monitoring Setup

#### A. Error Logging
```bash
# Proveri da se logovi kreiraju
tail -f data/php_errors.log
tail -f data/security.log
```

#### B. Uptime Monitoring
- Postavi na [UptimeRobot](https://uptimerobot.com/) (besplatno)
- Monitoriši: https://bif.events

#### C. SSL Certificate
```bash
# Proveri SSL validnost
https://www.ssllabs.com/ssltest/analyze.html?d=bif.events
```

---

### 9. 🔄 Backup Plan

**Pre deploy-a:**
```bash
# Backup trenutnog stanja (ako već imaš site)
tar -czf bif-backup-$(date +%Y%m%d).tar.gz /path/to/current/site
```

**Database backup:**
```bash
mysqldump -u root -p bif_ppv > bif-db-backup-$(date +%Y%m%d).sql
```

---

### 10. 🚨 Emergency Contacts

```
Hosting Support: [broj/email]
Domain Registrar: [info]
Stripe Support: https://support.stripe.com/
AWS Support: https://console.aws.amazon.com/support/
```

---

## 🎯 Kada Bude Live PPV (Za 2 Meseca)

### Stripe Transition: TEST → LIVE

1. **Verifikuj business u Stripe:**
   - Business details
   - Bank account
   - Tax information

2. **Dobij LIVE keys:**
   - Dashboard → Developers → API keys
   - Toggle "View test data" → OFF
   - Copy LIVE keys

3. **Update .env:**
```env
STRIPE_PUBLISHABLE_KEY=pk_live_TVOJ_LIVE_KEY
STRIPE_SECRET_KEY=sk_live_TVOJ_LIVE_KEY
```

4. **Postavi LIVE webhook:**
   - URL: `https://bif.events/api/webhook.php`
   - Kopiraj novi webhook secret

5. **Test sa realnim karticama** (male vrednosti)

---

## ⚠️ VAŽNE NAPOMENE

### Test Mode Stripe:
✅ **MOŽEŠ** staviti na production sa test ključevima
✅ Niko neće moći da plati (samo test kartice)
✅ Možeš testirati ceo flow
❌ Ne možeš dobiti pravi novac

### Kada prelazis na LIVE:
🔴 **OBAVEZNO** promeni ključeve u `.env`
🔴 Testiraj sa svojom karticom (mala vrednost)
🔴 Prati Stripe Dashboard za greške

---

## 📞 Support

Ako nešto ne radi:
1. Proveri `data/php_errors.log`
2. Proveri `data/security.log`
3. Proveri Stripe Dashboard za webhook errors
4. Proveri AWS CloudWatch za stream errors

---

**Autor:** Claude Code
**Datum:** 7. Novembar 2025
**Verzija:** 1.0

---

## ✅ Final Check Before Deploy

```bash
# Pre nego što upload-uješ:
[ ] SVE lozinke promenjene
[ ] .env fajl NIJE u upload folderu
[ ] Test fajlovi obrisani
[ ] Database backup kreiran
[ ] Novi Stripe test keys kreirani
[ ] reCAPTCHA keys kreirani za production domain
[ ] SSL certificate aktivan
[ ] DNS pointuje na server

# Kada upload-uješ:
[ ] Kreiran .env na serveru (manual)
[ ] composer install
[ ] Database import
[ ] File permissions podešeni
[ ] Test sajt - sve stranice rade

# Post-deployment:
[ ] Newsletter signup test
[ ] Admin panel pristup test
[ ] Stripe test payment
[ ] Email notifikacije test
[ ] Security scan (nmap, nikto)
```

**SRECNO! 🚀**
