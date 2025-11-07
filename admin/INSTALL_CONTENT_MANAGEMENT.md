# Instalacija Content Management Ekstenzije za BIF Admin Panel

## Šta je urađeno:

✅ **Backend (ppv_admin.php)** - Kompletno implementirano!
- Dodati novi JSON fajlovi: fighters.json, news.json, website_events.json
- CRUD metode za borce, vesti i događaje
- API endpoints spremni

✅ **Frontend HTML i JavaScript** - Spremno u `content-management-extension.html`

## Kako instalirati:

### Automatska instalacija (PREPORUČENO):

Pokrenite sledеću komandu iz terminala:

```bash
php integrate-content-management.php
```

Ili nastavite sa manuelnom instalacijom ispod.

---

### Manualna instalacija:

Otvorite `admin/admin.html` i napravite sledeće izmene:

#### 1. Dodaj nove nav items (oko linije 617, nakon "Event Management"):

```html
                    <a href="#" class="nav-item" data-section="events">
                        <i>🎬</i> Event Management
                    </a>
                    <!-- DODAJ OVO: -->
                    <a href="#" class="nav-item" data-section="fighters">
                        <i>🥊</i> Borci
                    </a>
                    <a href="#" class="nav-item" data-section="content-news">
                        <i>📰</i> Vesti
                    </a>
                    <a href="#" class="nav-item" data-section="website-events">
                        <i>🎪</i> Događaji
                    </a>
```

#### 2. Dodaj content sections (oko linije 990, posle `<div id="access" class="content-section">`):

Kopiraj sledeće sekcije iz `content-management-extension.html`:
- `<div id="fighters" class="content-section">` (linija ~25)
- `<div id="content-news" class="content-section">` (linija ~50)
- `<div id="website-events" class="content-section">` (linija ~75)

#### 3. Dodaj modale (pre `</body>` taga, oko linije 1980):

Kopiraj sledeće modale iz `content-management-extension.html`:
- `<div id="fighterModal" class="modal">` (linija ~100)
- `<div id="newsModal" class="modal">` (linija ~175)
- `<div id="websiteEventModal" class="modal">` (linija ~245)

#### 4. Dodaj JavaScript (pre `</script></body>`, oko linije 1980):

Kopiraj ceo JavaScript deo iz `content-management-extension.html` (linija ~300 do kraja).

---

## Test

Nakon instalacije, pristupite admin panelu:

```
http://localhost/bif-PPV/admin
```

U sidebaru ćete videti nove opcije:
- 🥊 Borci
- 📰 Vesti
- 🎪 Događaji

## Struktura podataka:

### Borac (Fighter):
```json
{
  "id": "fighter_abc123",
  "name": "Marko Milovanović",
  "nickname": "Jack",
  "slug": "marko-jack",
  "weight": 100,
  "height": 180,
  "age": 33,
  "wins": 10,
  "losses": 2,
  "draws": 0,
  "bio": "Opis borca...",
  "image_url": "/assets/images/fighters/jack.png",
  "status": "active",
  "created_at": "2025-01-03 12:00:00",
  "updated_at": "2025-01-03 12:00:00"
}
```

### Vest (News):
```json
{
  "id": "news_xyz789",
  "title": "BIF 2 - Najava",
  "slug": "bif-2-najava",
  "excerpt": "Kratak opis...",
  "content": "Pun tekst vesti...",
  "category": "news",
  "image_url": "/assets/images/news/news-1.png",
  "status": "published",
  "published_at": "2025-01-03 12:00:00",
  "created_at": "2025-01-03 12:00:00",
  "updated_at": "2025-01-03 12:00:00"
}
```

### Događaj (Website Event):
```json
{
  "id": "wevent_def456",
  "title": "BIF 2: Showdown",
  "slug": "bif-2-showdown",
  "description": "Opis događaja...",
  "date": "2025-02-15 20:00:00",
  "location": "Beograd Arena",
  "image_url": "/assets/images/events/event-1.jpg",
  "status": "upcoming",
  "created_at": "2025-01-03 12:00:00",
  "updated_at": "2025-01-03 12:00:00"
}
```

## Sledeci koraci:

Nakon što content management sistem radi, možete:

1. **Integracija sa frontend-om** - Kreiraj PHP skriptu koja čita iz JSON fajlova i dinamički generiše stranice
2. **Image upload** - Dodaj mogućnost upload-a slika umesto unošenja URL-a
3. **WYSIWYG Editor** - Integriši CKEditor ili TinyMCE za lakše formatiranje tekstova
4. **API za frontend** - Kreiraj javni API endpoint da frontend može da učitava podatke

## Podrška:

Ako nešto ne radi, proverite:
1. Da li su JSON fajlovi kreirani u `data/` folderu?
2. Da li je `data/` folder writable (chmod 755)?
3. Da li konzola u browseru pokazuje greške?
