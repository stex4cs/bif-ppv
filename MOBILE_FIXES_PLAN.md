# 📱 BIF PPV - Mobile & Responsiveness Fixes

## 🔴 PROBLEMI IDENTIFIKOVANI (Live):

1. ❌ **Hamburger navigacija ne radi na mobilnom**
2. ❌ **Borci se ne mogu pomerati levo-desno (swipe)**
3. ❌ **Dresovi - ne mogu da se pogledaju details ni da se poruče (PC & mobile)**
4. ❌ **"Postani Deo BIF Porodice" sekcija - overflow na desno, nije responsive**

## ✅ BACKUP KREIRAN:

```
c:\xampp\htdocs\bif-PPV-BACKUP\
```

---

## 🔧 FIX PLAN:

### 1. Hamburger Navigation Fix

**Problem**: Menu toggle button ne radi na mobilnom.

**Mogući uzroci**:
- JavaScript se ne učitava
- CSS konflikt
- Event listener ne firing

**Fajlovi za proveru**:
- `js/main.js` - JavaScript za toggle
- `index.php` - CSS za `.mobile-menu-toggle` i `.nav-menu.active`

**Fix strategija**:
- Proveriti da li se `js/main.js` učitava pre kraja `</body>` taga
- Dodati fallback inline JavaScript ako external nije loaded
- Testirati CSS za `.nav-menu.active` { display: flex; }

---

### 2. Fighter Carousel Swipe Fix

**Problem**: Ne mogu da se pomeraju borci levo-desno na touch devices.

**Trenutno**: Samo desktop arrows (onclick="bifApp.nextSlide()")

**Potrebno**:
- Touch events (touchstart, touchmove, touchend)
- Swipe gesture detection
- Smooth transitions

**Fajlovi za izmenu**:
- `js/main.js` ili novi `js/fighters-carousel.js`

**Fix strategija**:
- Dodati touch event listeners na `.fighters-container`
- Implementirati swipe detection (min 50px movement)
- Povezati sa existing `bifApp.nextSlide()` / `bifApp.previousSlide()`

---

### 3. Jersey Details & Order Buttons Fix

**Problem**: Ne može da se otvori detaljna strana dresova ni da se poruči.

**Fajlovi za proveru**:
- `index.php` - Jersey section HTML
- Buttons/Links za details

**Fix strategija**:
- Kreirati dedik ovane stranice za svaki dres ili modal
- Dodati onclick handlers za order buttons
- Povezati sa Stripe checkout ili kontakt formom

---

### 4. "Postani Deo BIF Porodice" Responsive Fix

**Problem**: Sekcija overflow-uje u desno, nije full responsive.

**Mogući uzroci**:
- Fiksna širina umesto max-width
- Padding/margin koji prelazi viewport
- Nedostatak media query za mobile

**Fajlovi za proveru**:
- `index.php` - CSS za .newsletter-section ili .join-section

**Fix strategija**:
- max-width: 100% umesto fixed width
- Dodati padding: 0 20px; za mobile
- Media query @media (max-width: 768px)

---

## 📝 EXECUTION CHECKLIST:

- [ ] 1. Fix hamburger navigation JavaScript
- [ ] 2. Fix hamburger navigation CSS (active state)
- [ ] 3. Add touch/swipe support for fighters carousel
- [ ] 4. Create jersey detail pages/modals
- [ ] 5. Fix jersey order buttons
- [ ] 6. Fix "Postani Deo BIF Porodice" responsive overflow
- [ ] 7. Test all fixes on localhost
- [ ] 8. Deploy to production (git push)
- [ ] 9. Test on live site (bif.events)
- [ ] 10. Test on real mobile device

---

## 🚀 DEPLOYMENT AFTER FIXES:

```bash
git add .
git commit -m "Fix: Mobile responsiveness - hamburger menu, fighter carousel swipe, jersey details, join section overflow"
git push origin main
```

Auto-deploy via Hostinger webhook (~2 min)

---

**Priority**: 🔥 HIGH
**Estimated Time**: 1-2 hours
**Impact**: Critical user experience on mobile

---
