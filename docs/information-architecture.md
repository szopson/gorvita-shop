# Gorvita Information Architecture

Projekt taksonomii i nawigacji oparty na analizie 99 faktycznych produktów i najlepszych praktykach e-commerce dla naturalnych suplementów (inspirowane Sylveco, Apteka Meduz, Weleda).

## Zasada przewodnia

Użytkownik przychodzi do sklepu z **problemem** lub **potrzebą**, nie z typem produktu. Stary podział ("suplementy diety" / "do zastosowania zewnętrznego") jest zbyt techniczny — klient często nie wie, czy chce tabletkę czy maść, tylko że bolą go stawy.

**3 osie nawigacji (równolegle):**

1. **Potrzeba** (primary) — "Na co to pomaga?" (skóra, stawy, odporność, trawienie…)
2. **Forma** (filter) — "W jakiej postaci?" (kapsułki, maść, żel, spray…)
3. **Składnik aktywny** (discovery) — "Szukam konkretnego składnika" (CBD, kolagen, żywokost, kasztanowiec…)

## Główna nawigacja (top menu)

```
[LOGO]    Potrzeby ▾   Formy ▾   CBD   Nowości   Promocje   B2B    🔍 👤 🛒
```

### Menu "Potrzeby ▾" (mega menu, 3-kolumnowe)

Kolumny dzielą kategorie tematyczne:

| Kolumna 1: Ciało & Skóra | Kolumna 2: Odporność & Zdrowie | Kolumna 3: Szczególne |
|--------------------------|--------------------------------|----------------------|
| Stawy i mięśnie           | Odporność                     | CBD i konopie         |
| Skóra codziennej pielęgnacji | Wątroba i trawienie        | Problemy kobiece      |
| Urazy i blizny            | Krążenie                       | Dla dzieci            |
| Stopy i dłonie            | Energia i stres                | Na ukąszenia owadów   |
| Jama ustna, nos, gardło   |                                | Intymne              |

### Menu "Formy ▾"

| Forma | Liczba | Ikona |
|-------|--------|-------|
| Suplementy (kapsułki, tabletki) | 33 | 💊 |
| Maści i balsamy | ~30 | 🫙 |
| Żele | ~15 | 🧴 |
| Sprays i pianki | ~8 | 💨 |
| Proszki i mielone | ~4 | 🌿 |
| Zestawy | ~2 | 🎁 |

## Kategorie WooCommerce (hierarchia)

```
📁 Potrzeby (meta, dla nav)
├── 🦴 Stawy i mięśnie
│   ├── Artrofit, Artrevit, Kolagen Colafit, Maść Żywokostowa
│   └── Maść Kasztanowa, ArtroŻel, Maść Kurkumowa
├── 🌿 Skóra i ciało
│   ├── Balsamy codzienne (Aloevera, Propolis)
│   ├── Blizny i regeneracja (Blizna Maść, Panthenol)
│   └── Naturalne kosmetyki (Arnika, Kurkuma, Rumianek)
├── 🛡️ Odporność
│   ├── Witamina C (Acerola, Apleplus)
│   ├── Propolis
│   └── Rokitnik
├── 🍃 Wątroba i trawienie
│   ├── Ostropest, Babka Płesznik, Carbosal
│   └── Hepasal, Kudzu, Spirulina
├── ❤️ Krążenie
│   ├── Kasztanowiec (żele, maści)
│   └── Chrom, Magnez, Cynk
├── 💼 Energia i stres
│   └── Energia, Geriafix, Gotu Kola
├── 🌬️ Jama ustna, nos, gardło
│   └── Pneumovit, Aurix, Aphtihelp
├── 🦟 Odstraszacze owadów
│   └── Mosqitos, Żel łagodzący po ukąszeniach
└── 🌱 CBD / Konopie
    ├── Oleje CBD (5%, 10%)
    ├── Maści konopne (CBD)
    └── Kapsułki CBD

📁 Formy (główna taksonomia WP, 1 produkt = 1 forma)
├── Kapsułki i tabletki
├── Maści i balsamy
├── Żele
├── Spray i pianki
├── Proszki i mielone
└── Zestawy

🏷️ Tagi (luźne, dla faceted search)
aloevera, rokitnik, kasztanowiec, żywokost, CBD, propolis, kurkuma,
dla_sportowcow, dla_kobiet, dla_mezczyzn, bestseller, nowosc, promocja,
wege, bez_glutenu, bez_laktozy, hipoalergiczny
```

## Strony landing (SEO/content)

| URL | Zawartość | Cel |
|-----|-----------|-----|
| `/potrzeby/stawy-miesnie/` | H1 + ekspercki content (kolagen, żywokost, kasztanowiec), produkty, FAQ | SEO long-tail + konwersja |
| `/potrzeby/odpornosc/` | To samo dla odporności | SEO + edukacja |
| `/cbd/` | Dedykowany landing: co to jest CBD, dawkowanie, oleje, maści | High-intent traffic |
| `/o-marce/` | Historia Gorvita, woda z Rabki, certyfikaty | Trust, GEO |
| `/b2b/` | Oferta B2B, cenniki, formularz | B2B acquisition |
| `/leksykon-skladnikow/` | Kolagen, Żywokost, CBD, Rokitnik itd. z linkami do produktów | SEO, cross-sell |

## Filtry na PLP (sidebar desktop, bottom-sheet mobile)

```
┌─ Filtry ─────────────────┐
│ ☐ Forma                  │
│   ▸ Kapsułki (33)        │
│   ▸ Maści (22)           │
│   ▸ Żele (15)            │
│                          │
│ ☐ Cena                   │
│   ◯───●──── 0 – 100 zł   │
│                          │
│ ☐ Składnik aktywny       │
│   ▸ CBD (10)             │
│   ▸ Kolagen (4)          │
│   ▸ Żywokost (5)         │
│                          │
│ ☐ Tylko dostępne         │
│ ☐ Tylko promocje         │
│                          │
│ Sortuj: Popularność ▾    │
│  · Popularność           │
│  · Cena rosnąco          │
│  · Cena malejąco         │
│  · Najnowsze             │
│  · Nazwa A-Z             │
└──────────────────────────┘
```

## Wnioski i decyzje

### ✅ Usuwamy ze starego sklepu
- **"Pozostałe: Nos, Gardło, Ucho"** jako główna kategoria — za słaby sygnał (5 prod.). Przenosimy do podkategorii pod "Odporność" lub "Dla dzieci".
- **Pojedyncze widoki "Bestsellery" / "Promocje" / "Nowości"** jako taksonomia — zastępujemy flagą/tagiem.

### ➕ Dodajemy
- **CBD jako osobna główna kategoria** — to rosnący segment, wymaga dedykowanego landing z edukacją
- **Faceted filtering** — nowoczesne sklepy bez tego tracą konwersję (audit Sylveco Issue #5)
- **Landing strony "Potrzeby"** — SEO + edukacja = long-tail ruch

### 🔄 Restrukturyzujemy
- Produkty będą miały **multiple kategorie** (np. Olej CBD 5% → "Krążenie", "CBD", "Stres") + **1 formę** + **tagi składników**

### 🎯 Strategia B2B
- B2B klienci widzą TĘ SAMĄ nawigację + dodatkowy link "Cennik hurtowy"
- Dedykowana strona B2B z rejestracją i "Korzyściami partnerskimi"
