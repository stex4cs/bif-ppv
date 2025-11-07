# 🚀 GitHub Auto-Deploy Setup za Hostinger

## 📋 Šta će se desiti:

1. Push kod na GitHub → Main branch
2. GitHub Actions automatski:
   - Build-uje projekat
   - Instalira dependencies (composer)
   - Deploy-uje na Hostinger FTP
3. `env/.env` NEĆE biti deploy-ovan (u `.gitignore`)
4. Log fajlovi i test fajlovi NEĆE biti deploy-ovani

---

## 🔧 Setup - Korak po Korak

### 1. Kreiraj GitHub Repository

```bash
# Ako već nemaš git repo
cd c:\xampp\htdocs\bif-PPV
git init
git add .
git commit -m "Initial commit - BIF PPV website"

# Kreiraj repo na GitHub (github.com/new)
# Zatim:
git remote add origin https://github.com/TVOJ_USERNAME/bif-ppv.git
git branch -M main
git push -u origin main
```

### 2. Dobij FTP Credentials od Hostinger

Idi u Hostinger cPanel:
1. **File Manager** ili **FTP Accounts**
2. Otvori **FTP/SFTP Details**

Trebaće ti:
```
Server: ftp.bif.events (ili srv962-files.hstgr.io)
Username: tvoj_ftp_username
Password: tvoj_ftp_password
Port: 21 (FTP) ili 22 (SFTP)
```

### 3. Dodaj Secrets u GitHub Repository

Idi na GitHub repo:
```
Settings → Secrets and variables → Actions → New repository secret
```

Dodaj 3 секрета:

**Secret 1:**
```
Name: FTP_SERVER
Value: ftp.bif.events (ili srv962-files.hstgr.io)
```

**Secret 2:**
```
Name: FTP_USERNAME
Value: tvoj_ftp_username
```

**Secret 3:**
```
Name: FTP_PASSWORD
Value: tvoj_ftp_password
```

### 4. Kreiraj `env/.env` na Serveru (MANUAL - Samo Jednom!)

⚠️ **VAŽNO:** `env/.env` neće biti deploy-ovan sa GitHub-a (u `.gitignore`).

**Opcija A - Preko Hostinger File Manager:**
```
1. Idi u Hostinger cPanel → File Manager
2. Navigiraj u public_html/env/
3. Kreiraj fajl .env
4. Kopiraj sadržaj iz DEPLOYMENT_SIMPLE.md (sekcija 3)
5. Promeni SVE lozinke i keys
6. Sačuvaj
```

**Opcija B - Preko FTP:**
```
1. Poveži se sa FileZilla
2. Navigiraj u /public_html/env/
3. Upload local env/.env (ali PRE TOGA promeni lozinke!)
```

**Opcija C - Preko SSH (ako imaš pristup):**
```bash
ssh username@srv962-files.hstgr.io
cd public_html/env/
nano .env
# Paste content, edit passwords, Ctrl+X, Y, Enter
chmod 600 .env
```

### 5. Test Deploy

**Push test izmenu:**
```bash
cd c:\xampp\htdocs\bif-PPV
git add .
git commit -m "Test: Enable auto-deploy"
git push origin main
```

**Proveri GitHub Actions:**
```
GitHub repo → Actions tab
Trebalo bi da vidiš "Deploy to Hostinger" job koji je pokrenut
```

**Praćenje live:**
- Klikni na job → Deploy → Praćenje output-a

---

## ✅ Provera da li radi

### 1. Proveri da je deploy uspeo
```
GitHub Actions → Zelena kvačica ✅
```

### 2. Test da je env/.env zaštićen
```bash
curl -I https://bif.events/env/.env
→ Trebalo bi: 403 Forbidden
```

### 3. Test da sajt radi
```
https://bif.events
→ Trebalo bi da se učita homepage
```

---

## 🔄 Workflow - Kako ćeš koristiti

### Svakodnevni rad:

```bash
# 1. Edituj fajlove lokalno
# 2. Test na localhost
# 3. Kada si zadovoljan:

git add .
git commit -m "Opis izmena"
git push origin main

# 4. GitHub automatski deploy-uje na production!
# 5. Proveri https://bif.events da li radi
```

### Staging vs Production (opciono):

Ako želiš staging environment:

```bash
# Kreiraj dev branch
git checkout -b dev
git push origin dev

# Edituj .github/workflows/deploy.yml
# Dodaj deploy za dev branch na staging.bif.events
```

---

## 🚨 Troubleshooting

### Problem: "env/.env not found" greška na sajtu

**Razlog:** env/.env nije kreiran na serveru

**Fix:**
```
1. SSH ili File Manager na Hostinger
2. Kreiraj env/.env manual
3. Copy sadržaj iz DEPLOYMENT_SIMPLE.md
4. Chmod 600 env/.env
```

### Problem: GitHub Actions fail sa "FTP connection failed"

**Razlog:** Pogrešni FTP credentials

**Fix:**
```
1. Proveri FTP credentials u Hostinger cPanel
2. Update GitHub Secrets (Settings → Secrets)
3. Re-run workflow (Actions → Failed job → Re-run)
```

### Problem: Deploy uspeo ali sajt ne radi

**Razlog:** File permissions ili composer dependencies

**Fix:**
```bash
# SSH na server
cd /public_html
chmod 755 .
chmod 755 data/
chmod 600 env/.env
chmod 644 .htaccess

# Instalirati dependencies
composer install --no-dev
```

### Problem: "403 Forbidden" na svim stranicama

**Razlog:** .htaccess permissions ili syntax greška

**Fix:**
```bash
# SSH na server
cd /public_html
chmod 644 .htaccess

# Test syntax
cat .htaccess | grep -i "error"

# Backup i restore
mv .htaccess .htaccess.backup
# Upload clean .htaccess
```

---

## 📊 Monitoring Auto-Deploy

### GitHub Actions Status Badge (opciono)

Dodaj u README.md:
```markdown
![Deploy Status](https://github.com/USERNAME/bif-ppv/workflows/Deploy%20to%20Hostinger/badge.svg)
```

### Email Notifikacije

GitHub automatski šalje email ako deploy fail-uje.

### Slack/Discord Notifikacije (opciono)

Dodaj u workflow:
```yaml
- name: Notify Discord
  if: failure()
  uses: sarisia/actions-status-discord@v1
  with:
    webhook: ${{ secrets.DISCORD_WEBHOOK }}
    status: ${{ job.status }}
```

---

## 🔐 Sigurnost

### ✅ Šta JE zaštićeno:

- [x] `env/.env` nije u git-u (`.gitignore`)
- [x] `env/.env` nije deploy-ovan (GitHub Actions exclude)
- [x] `env/.env` blokiran je u `.htaccess` (403)
- [x] FTP credentials u GitHub Secrets (enkriptovani)
- [x] Log fajlovi nisu deploy-ovani

### ⚠️ Šta treba MANUAL:

- [ ] Kreiraj `env/.env` na serveru (jednom)
- [ ] Promeni sve lozinke u `env/.env` (jednom)
- [ ] Set file permissions (jednom)
- [ ] Import database (jednom)

---

## 🎯 Alternativni Deploy Metodi

### Opcija 1: GitHub Actions + FTP (TRENUTNO)
✅ Auto deploy on push
✅ Build process
✅ Exclude sensitive files
❌ Malo sporije (3-5 min)

### Opcija 2: GitHub Actions + Hostinger Git Deploy
✅ Brže (1-2 min)
✅ Native Hostinger integration
❌ Treba Git Version Control u Hostinger (Premium)

### Opcija 3: Manual FTP Upload
✅ Jednostavno
✅ Brzo za male izmene
❌ Manual process
❌ Nema backup/rollback

### Opcija 4: rsync preko SSH
✅ Najbrže
✅ Incremental sync
❌ Potreban SSH access (možda nemaš)

---

## ✅ Quick Checklist

**Initial Setup:**
- [ ] Kreiran GitHub repo
- [ ] Push-ovan kod na GitHub
- [ ] Dodati FTP secrets u GitHub
- [ ] Kreiran `env/.env` na serveru (manual)
- [ ] Test deploy - push izmenu
- [ ] Proveren deploy success (GitHub Actions)
- [ ] Proveren sajt (https://bif.events)
- [ ] Proveren env/.env zaštita (curl test)

**Svaki Put Kada Menjaš Kod:**
- [ ] Test lokalno (localhost)
- [ ] git add, commit, push
- [ ] Proveri GitHub Actions (zelena kvačica)
- [ ] Test na production (https://bif.events)

**Kada Menjaš env/.env:**
- [ ] NE push-uj na GitHub!
- [ ] Manual update na serveru (SSH/FTP/File Manager)
- [ ] Test da sajt radi nakon promene

---

## 📞 Help

**GitHub Actions dokumentacija:**
https://docs.github.com/en/actions

**FTP Deploy Action:**
https://github.com/SamKirkland/FTP-Deploy-Action

**Hostinger Git Deploy:**
https://support.hostinger.com/en/articles/6823801-how-to-deploy-with-git-version-control

**Problemi?**
1. Proveri GitHub Actions log
2. Proveri Hostinger error log
3. Test FTP credentials lokalno (FileZilla)

---

**SRECNO SA AUTO-DEPLOY-OM! 🚀**

*Autor: Claude Code*
*Datum: 7. Novembar 2025*
