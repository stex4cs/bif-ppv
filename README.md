# 🥊 BIF PPV - Balkan Influence Fighting

![Deploy Status](https://github.com/USERNAME/bif-ppv/workflows/Deploy%20to%20Hostinger/badge.svg)

Official website and Pay-Per-View platform for BIF - Balkan Influence Fighting.

## 🌐 Live Site

**Production:** https://bif.events

## 🚀 Features

- 🌍 **Bilingual** (Serbian / English)
- 📰 **News CMS** with TinyMCE editor
- 🥋 **Fighters Database** with dynamic pages
- 💳 **Stripe Payment Integration** (PPV)
- 📺 **Live Streaming** (AWS MediaLive)
- 🔐 **Security** (CSP, rate limiting, anti-fraud)
- 📱 **Responsive Design**
- 🚀 **Auto-Deploy** via GitHub Actions

## 📋 Tech Stack

- **Frontend:** HTML5, CSS3, JavaScript
- **Backend:** PHP 8.2+
- **Database:** MySQL
- **Payment:** Stripe
- **Streaming:** AWS MediaLive + S3
- **Email:** Titan Email (SMTP)
- **Hosting:** Hostinger
- **Deploy:** GitHub Actions (FTP)

## 🔧 Development Setup

### Prerequisites
```bash
- PHP 8.2+
- MySQL 5.7+
- Composer
- Apache (mod_rewrite enabled)
```

### Local Installation

1. **Clone repository:**
```bash
git clone https://github.com/USERNAME/bif-ppv.git
cd bif-ppv
```

2. **Install dependencies:**
```bash
composer install
```

3. **Setup environment:**
```bash
cp env/.env.example env/.env
# Edit env/.env with your credentials
```

4. **Import database:**
```bash
mysql -u root -p bif_ppv < setup_database.sql
```

5. **Start local server:**
```bash
# Apache/XAMPP
http://localhost/bif-ppv

# Or PHP built-in server
php -S localhost:8000
```

## 🚀 Deployment

### Auto-Deploy via GitHub Actions

Push to `main` branch triggers automatic deployment:

```bash
git add .
git commit -m "Update"
git push origin main
```

See [GITHUB_DEPLOY_SETUP.md](GITHUB_DEPLOY_SETUP.md) for setup instructions.

### Manual Deployment

See [DEPLOYMENT_SIMPLE.md](DEPLOYMENT_SIMPLE.md) for manual FTP deployment.

## 📁 Project Structure

```
bif-ppv/
├── admin/              # Admin panel
├── api/                # REST API endpoints
├── assets/             # Images, fonts, icons
├── borci/              # Fighters (dynamic pages)
├── config/             # Configuration files
├── css/                # Stylesheets
├── data/               # JSON data files
├── env/                # Environment files (.env)
├── includes/           # PHP includes (headers, security)
├── js/                 # JavaScript files
├── vesti/              # News (dynamic pages)
├── .github/workflows/  # GitHub Actions
├── .htaccess           # Apache config
├── index.php           # Homepage
├── watch.php           # PPV streaming page
└── composer.json       # PHP dependencies
```

## 🔐 Security

- ✅ `.htaccess` blocks access to `env/.env`
- ✅ CSP (Content Security Policy) headers
- ✅ Rate limiting on APIs
- ✅ SQL injection prevention
- ✅ XSS protection
- ✅ Anti-fraud detection (Stripe)
- ✅ reCAPTCHA v3

## 📝 Documentation

- [GitHub Deploy Setup](GITHUB_DEPLOY_SETUP.md) - Auto-deploy via GitHub Actions
- [Deployment Guide](DEPLOYMENT_SIMPLE.md) - Manual deployment
- [Upload Checklist](UPLOAD_CHECKLIST.txt) - Pre-deployment checklist
- [Quick Start](QUICK_START_GITHUB.txt) - 5-minute setup

## 💳 Stripe Configuration

### Test Mode (Current)
```env
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
```

Test cards:
- Success: `4242 4242 4242 4242`
- Decline: `4000 0000 0000 0002`

### Live Mode (When PPV Goes Live)
Update `env/.env` on server with live keys from Stripe Dashboard.

## 🧪 Testing

```bash
# Test env protection
curl -I https://bif.events/env/.env
# Should return: 403 Forbidden

# Test main site
curl -I https://bif.events
# Should return: 200 OK

# Test Stripe checkout (test mode)
https://bif.events/watch.php
```

## 📞 Support

- **Hosting:** Hostinger Support
- **Payment:** [Stripe Support](https://support.stripe.com/)
- **Email:** business@bif.events

## 📄 License

Proprietary - All rights reserved by BIF Organization

## 🎯 Roadmap

- [x] Bilingual CMS
- [x] Fighter profiles
- [x] News system with TinyMCE
- [x] Stripe payment integration
- [x] GitHub auto-deploy
- [ ] Live streaming (AWS MediaLive)
- [ ] User accounts
- [ ] Mobile app

## 🤝 Contributing

This is a private project. For authorized contributors:

1. Create feature branch: `git checkout -b feature/my-feature`
2. Commit changes: `git commit -m "Add feature"`
3. Push to branch: `git push origin feature/my-feature`
4. Create Pull Request

---

**Built with ❤️ by Stefan for BIF**

*Last updated: November 2025*
