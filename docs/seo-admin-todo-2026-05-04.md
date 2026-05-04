# SEO admin checklist — 2026-05-04

Krok po kroku. Każda sekcja = osobne zadanie. Login: `https://gorvita.srv1594477.hstgr.cloud/wp-admin/`.

---

## 1. RankMath — Sitemap fix (~5 min) — **ZACZNIJ TUTAJ**

**Cel**: usunąć demo śmieci z sitemap, włączyć kategorie produktowe.

### 1a. Włącz kategorie produktów

`Rank Math SEO` > `Sitemap Settings` > zakładka **`Products`**

- ☑ **Include Product Categories**: ON
- Save changes

### 1b. Wyłącz nieużywane post types z sitemap

W tej samej sekcji `Sitemap Settings` > zakładka **`Posts`**:
- Sprawdź `post-sitemap.xml` na froncie. Jeśli zawiera śmieci z demo Blocksy (lastmod 2024-05-30), wyłącz Posts w sitemap albo usuń te wpisy z `Posts` w admin.

### 1c. Usuń demo kategorie WooCommerce

`Produkty` > `Kategorie produktów` — usuń jeśli istnieją:
- `Accessories`
- `Decoration`
- `How-to`
- `Process`

(To są leftover z Blocksy demo theme.)

### 1d. Refresh sitemap

`Rank Math SEO` > `Sitemap Settings` — kliknij **`See the XML Sitemap`** (otworzy się `https://...../sitemap_index.xml`). Ręcznie zweryfikuj że:
- `category-sitemap.xml` zawiera teraz polskie kategorie (skora-cialo, odpornosc, stawy-miesnie, etc.) **zamiast** demo (accessories, decoration)
- `page-sitemap.xml` nie ma duplikatów `/sklep/` + `/shop/`, `/koszyk/` + `/cart/`

---

## 2. RankMath — Title/Description rewrite per page (~15 min)

Każda strona poniżej: edytuj w admin > w sidebar znajdź **Rank Math SEO box** > wklej Title + Description.

### 2a. Strona główna (`/`)

`Rank Math SEO` > `Titles & Meta` > `Homepage`

```
Title:     Gorvita — naturalne suplementy i zioła z Gorców
Description: Polska manufaktura suplementów i kosmetyków ziołowych z Gorców. 37 lat tradycji, jakość ISO 9001 i GMP. Zamów online z dostawą do 48h.
```

### 2b. `/sklep/`

`Rank Math SEO` > `Titles & Meta` > `Products` > sekcja **`Product Archive`** (lub edytuj stronę `/sklep/` jeśli to Page):

```
Title:     Sklep online — suplementy i kosmetyki Gorvita
Description: Pełna oferta Gorvita: suplementy ziołowe, herbatki, balsamy i maści. Polskie surowce z Gorców, jakość GMP. Wysyłka 48h, darmowa od 250 zł.
```

**Plus dodaj H1**: `Sklep Gorvita — naturalne produkty z Gorców`
(WooCommerce > Customize > Shop page header lub edytuj stronę bezpośrednio jeśli `/sklep/` jest Page)

### 2c. `/product-category/skora-cialo/`

Edytuj kategorię: `Produkty` > `Kategorie produktów` > **`Skóra i ciało`** > Edit:

W polu **Description** dodaj na początku:

```html
<h1>Skóra i ciało — naturalne kosmetyki ziołowe</h1>
<p>Pełna oferta kosmetyków ziołowych Gorvita do codziennej pielęgnacji ciała.</p>
```

Scroll do sekcji `Rank Math SEO`:

```
Title:     Naturalne kosmetyki ziołowe — Gorvita skóra i ciało
Description: Kosmetyki ziołowe Gorvita: balsamy, maści i żele z Aloesem, Propolisem i Arniką. Polskie zioła z Gorców, formuły bez parabenów.
```

**Powtórz dla pozostałych kategorii** (`/product-category/odpornosc/`, `/stawy-miesnie/`, etc.) — dodaj H1 i napisz title/desc analogicznie.

### 2d. `/promocje/`

`Strony` > `Promocje` > Edit:

W treści strony zamień obecny H1 (`Promocje`) na:
```
Promocje — naturalne produkty Gorvita w niższych cenach
```

W RankMath sidebar:
```
Title:     Promocje Gorvita — naturalne suplementy w niższych cenach
Description: Wybrane suplementy i kosmetyki Gorvita w obniżonych cenach. Ta sama jakość GMP, polskie zioła z Gorców — tylko taniej. Sprawdź oferty.
```

### 2e. `/kontakt-2/` (zmiana sluga w sekcji 4)

`Strony` > `Kontakt` (slug `kontakt-2`) > Edit:

Na samej górze treści dodaj H1:
```
<h1>Kontakt z Gorvita</h1>
```

W RankMath sidebar:
```
Title:     Kontakt — PPUH Gorvita Sp. z o.o. (Szczawa)
Description: Skontaktuj się z Gorvita: telefon, email, formularz. Siedziba PPUH Gorvita Sp. z o.o. — Szczawa 106, małopolskie. Odpowiadamy w 24h.
```

---

## 3. `/o-marce/` — pełny rewrite treści (~10 min)

`Strony` > `O marce` > Edit. **Skasuj cały obecny content** i wklej:

```html
<h1>Marka z Gorców — Gorvita od 1989</h1>

<p>Gorvita to polska manufaktura naturalnych suplementów i kosmetyków ziołowych, działająca nieprzerwanie od 1989 roku. Siedziba i zakład produkcyjny znajdują się w Szczawie 106, w gminie Kamienica (powiat limanowski, województwo małopolskie), u podnóża Gorców. Łączymy 37 lat doświadczenia z surowcami zbieranymi w Beskidach Wyspowych oraz wodą leczniczą z Rabki-Zdroju w wybranych formułach.</p>

<h2>Ze Szczawy w Gorcach</h2>
<p>Adres Szczawa 106 to nie przypadek. Wieś Szczawa leży na wysokości 540–700 m n.p.m., w dolinie potoku Kamienica Gorczańska, otoczona szczytami Mogielicy (1170 m), Modyni i Łopienia. To serce Beskidów Wyspowych — pasma górskiego, w którym pojedyncze szczyty wyrastają samotnie ponad doliny niczym wyspy. Czyste powietrze, niski poziom emisji przemysłowych i unikatowa flora górska tworzą warunki, w których od pokoleń zbiera się zioła do celów leczniczych. Naszą siedzibę założyliśmy tutaj świadomie — żeby surowiec trafiał do produkcji w godzinach, nie dniach od zbioru.</p>

<h2>Surowce z Gorców</h2>
<p>Pracujemy wyłącznie z polskimi ziołami. Część pochodzi z kontrolowanego zbioru ze stanu naturalnego w Gorcach i Beskidzie Wyspowym (m.in. dziurawiec, krwawnik, podbiał, mięta, melisa, pokrzywa), część z certyfikowanych upraw ekologicznych z Małopolski i Podkarpacia. Każda partia surowca przechodzi kontrolę organoleptyczną i analizę zawartości substancji czynnych przed dopuszczeniem do produkcji. Nie używamy ekstraktów anonimowego pochodzenia ani surowców importowanych z Azji.</p>

<h2>Woda lecznicza z Rabki</h2>
<p>W wybranych formułach kosmetycznych i suplementach stosujemy wodę leczniczą z Rabki-Zdroju — uznanej polskiej miejscowości uzdrowiskowej. Jest to woda hydrochlorowo-chlorkowo-sodowa, jodkowa i borowa, o udokumentowanych właściwościach pielęgnacyjnych dla skóry oraz wspierających układ oddechowy. Woda z Rabki pojawia się jako składnik tylko tam, gdzie ma sens technologiczny i zdrowotny — nie we wszystkich naszych produktach. Sprawdź skład konkretnej formuły, żeby upewnić się, czy zawiera ten składnik.</p>

<h2>Standardy: ISO 9001 + GMP</h2>
<p>Produkujemy zgodnie z normą ISO 9001 (system zarządzania jakością) oraz GMP (Good Manufacturing Practice — Dobre Praktyki Wytwarzania). Dla klienta oznacza to: powtarzalność partii, pełną identyfikowalność surowca (od działki zbioru do numeru serii produktu), kontrolę mikrobiologiczną każdej partii oraz dokumentację procesu produkcyjnego przechowywaną przez minimum 5 lat. Każdy produkt ma numer serii i datę przydatności wydrukowane bezpośrednio na opakowaniu.</p>

<h2>Tradycja od 1989</h2>
<p>Gorvita powstała w 1989 roku jako rodzinna manufaktura, łącząca wiedzę o ziołolecznictwie przekazywaną w regionie od pokoleń z nowoczesnymi standardami produkcji farmaceutycznej. Przez 37 lat rozbudowaliśmy ofertę do ponad 100 produktów: suplementów w kapsułkach, balsamów, maści, żeli i herbatek ziołowych. Pozostajemy niezależnym, polskim producentem — nie należymy do żadnej międzynarodowej grupy kapitałowej. Nasi klienci to apteki, sklepy zielarskie, hurtownie farmaceutyczne oraz odbiorcy indywidualni w całej Polsce.</p>

[gorvita_faq]
{ "q": "Czy Gorvita to polska marka?", "a": "Tak. Gorvita to w 100% polska marka, założona w 1989 roku. Właścicielem jest PPUH Gorvita Sp. z o.o. z siedzibą w Szczawie 106 (gmina Kamienica, województwo małopolskie). Cała produkcja odbywa się w Polsce." },
{ "q": "Skąd pochodzą surowce Gorvita?", "a": "Zioła pochodzą z Gorców i Beskidu Wyspowego (kontrolowany zbiór ze stanu naturalnego) oraz z certyfikowanych upraw ekologicznych w Małopolsce i na Podkarpaciu. Woda lecznicza w wybranych formułach pochodzi z uzdrowiska Rabka-Zdrój." },
{ "q": "Czy produkty Gorvita są certyfikowane?", "a": "Tak. Produkujemy zgodnie z normą ISO 9001 oraz standardem GMP (Dobre Praktyki Wytwarzania). Suplementy diety są zgłoszone do GIS (Główny Inspektorat Sanitarny), kosmetyki posiadają wymagane oceny bezpieczeństwa i są zgłoszone do CPNP." },
{ "q": "Czy mogę odwiedzić producenta?", "a": "Siedziba i zakład produkcyjny znajdują się w Szczawie 106, gmina Kamienica. Wizyty odbiorców biznesowych (apteki, hurtownie, dystrybutorzy) są możliwe po wcześniejszym umówieniu. Skontaktuj się z nami przez formularz kontaktowy lub telefonicznie." },
{ "q": "Czym Gorvita różni się od innych marek ziołowych?", "a": "Po pierwsze — lokalizacja: produkujemy bezpośrednio u źródła surowca, w Gorcach. Po drugie — woda lecznicza z Rabki-Zdroju jako składnik wybranych formuł, czego nie oferują marki spoza regionu. Po trzecie — 37 lat ciągłości jednego producenta bez zmian właścicielskich. Po czwarte — pełna identyfikowalność: od działki zbioru ziół do numeru serii produktu." }
[/gorvita_faq]
```

**Po wklejeniu**:
- Zapisz stronę
- Otwórz `/o-marce/` w nowej karcie i sprawdź czy:
  - Jest TYLKO jeden `<h1>` (poprzednio były 2)
  - FAQ section się renderuje (dl/dt/dd) na końcu
  - View Source: szukaj `"@type":"FAQPage"` — powinien tam być

W RankMath sidebar tej strony:
```
Title:     O marce Gorvita — manufaktura z Gorców od 1989
Description: Gorvita to polska manufaktura suplementów i kosmetyków ziołowych z Gorców. Produkujemy w Szczawie od 1989, ISO 9001 i GMP. Poznaj naszą historię.
```

---

## 4. FAQ na `/kontakt-2/` (~5 min)

`Strony` > `Kontakt` > Edit

Na końcu treści (przed ewentualnym formularzem) wklej:

```html
[gorvita_faq]
{ "q": "Czy mogę odwiedzić Was osobiście w Szczawie?", "a": "Tak, po wcześniejszym umówieniu telefonicznym. Siedziba: Szczawa 106, gm. Kamienica." },
{ "q": "Jakie są godziny pracy biura?", "a": "Pon-Pt 8:00–16:00. Zamówienia online realizujemy 7 dni w tygodniu." },
{ "q": "Jak długo trwa wysyłka?", "a": "1–2 dni robocze kurierem InPost lub paczkomatem. Darmowa dostawa od 250 zł." },
{ "q": "Czy oferujecie sprzedaż hurtową B2B?", "a": "Tak — apteki, sklepy zielarskie i dystrybutorzy. Minimum zamówienia 250 zł netto. Skontaktuj się: sklep@gorvita.pl." },
{ "q": "Skąd pochodzą Wasze surowce?", "a": "Zioła zbieramy z Gorców (Beskid Wyspowy). Część formuł zawiera leczniczą wodę z Rabki. Produkcja w standardach ISO 9001 i GMP." }
[/gorvita_faq]
```

---

## 5. Permalink cleanup (~10 min) — **OSTROŻNIE, są 301 redirects**

### 5a. Zmiana sluga `/kontakt-2/` → `/kontakt/`

`Strony` > Edit `Kontakt`:
- Slug: zmień z `kontakt-2` na `kontakt`
- Save

WooCommerce/WordPress automatycznie utworzy 301 ze starego sluga. Zweryfikuj:
```
curl -I https://gorvita.srv1594477.hstgr.cloud/kontakt-2/
```
powinien zwracać `301 Moved Permanently` z `Location: /kontakt/`.

### 5b. WooCommerce permalinks — `/sklep/` jako canonical (decyzja klienta)

Sprzedaż jest wyłącznie na Polskę → polskie slugi mają sens, angielskie należy zlikwidować.

`WooCommerce` > `Ustawienia` > `Produkty` > zakładka **`Ogólne`** (lub `Strony`):

- **Sklep**: strona `Sklep` (slug `sklep`)
- **Koszyk**: strona `Koszyk` (slug `koszyk`)
- **Płatność**: strona `Zamówienie` (slug `zamowienie`)
- **Moje konto**: strona `Moje konto` (slug `moje-konto`)

Duplikaty angielskie (`/shop/`, `/cart/`, `/checkout/`, `/checkout-2/`, `/my-account/`):
1. Najpierw **przekieruj** je na PL ekwiwalenty (krok 5c) — żeby Google nie zgubił rankingów
2. Dopiero **potem** usuń strony angielskie

### 5c. 301 redirects: angielskie slugi → polskie

`Rank Math SEO` > `Redirections` > **Add Redirection**

| Source URL (regex/exact) | Target URL | Type |
|---|---|---|
| `/shop` | `/sklep` | 301 |
| `/shop/` | `/sklep/` | 301 |
| `/cart` | `/koszyk` | 301 |
| `/cart/` | `/koszyk/` | 301 |
| `/checkout` | `/zamowienie` | 301 |
| `/checkout/` | `/zamowienie/` | 301 |
| `/checkout-2` | `/zamowienie` | 301 |
| `/checkout-2/` | `/zamowienie/` | 301 |
| `/my-account` | `/moje-konto` | 301 |
| `/my-account/` | `/moje-konto/` | 301 |
| `/about-us` | `/o-marce` | 301 |
| `/about-us/` | `/o-marce/` | 301 |

Po wszystkich redirectach **dopiero** `Strony` > znajdź angielskie duplikaty > **Przenieś do kosza** → opróżnij kosz.

### 5d. Verify redirects

```
curl -I https://gorvita.srv1594477.hstgr.cloud/shop/
# Powinno zwrócić: HTTP/2 301, Location: https://gorvita.srv1594477.hstgr.cloud/sklep/

curl -I https://gorvita.srv1594477.hstgr.cloud/cart/
# Powinno zwrócić: HTTP/2 301, Location: https://gorvita.srv1594477.hstgr.cloud/koszyk/
```

### 5c. Po cleanupie

```
WordPress > Ustawienia > Permalinks > Save (bez zmian, tylko refresh rules)
```

Potem `Rank Math SEO` > `Sitemap Settings` > **Refresh sitemap**.

---

## 6. Default OG image (~3 min)

`Rank Math SEO` > `Titles & Meta` > **`Global Meta`** > sekcja **`Social Meta`**:

- **Default Social Share Image**: upload obrazek 1200×630 z brandem Gorvita (logo + Gorce w tle + tagline). Jak nie ma — tymczasowo logo Gorvita na cream tle.
- Save

To zlikwiduje brak `og:image` na stronach `/`, `/promocje/`, `/o-marce/`.

---

## 7. Po wszystkim — flush cache + reverify

```
Rank Math SEO > Status & Tools > Database Tools > Flush cache
```

Potem otwórz w incognito każdą zmienioną stronę i sprawdź:
- Title w karcie przeglądarki
- View Source (Ctrl+U) → poszukaj `<meta name="description"` i `<title>`
- View Source → szukaj `"@type":"FAQPage"` na `/o-marce/` i `/kontakt/`

---

## Co robi za Ciebie kod (już aktywne, nic nie klikaj)

- ✅ Brand + manufacturer w każdym Product schema (106 produktów)
- ✅ hasMerchantReturnPolicy (14 dni, free returns) + shippingDetails (1-2d, 0 PLN PL) w każdym Offer
- ✅ Article schema NIE pojawia się już na pages (homepage, /promocje/, /kontakt-2/, /o-marce/)
- ✅ FAQ shortcode `[gorvita_faq]` — wklejasz JSON pytań, kod renderuje HTML + JSON-LD FAQPage
- ✅ Staging zablokowany od indeksacji Googlem (X-Robots-Tag header + meta robots)

## Dane od klienta otrzymane (2026-05-04)

- **NIP**: 7370006441
- **REGON**: 490772290
- **Adres**: Szczawa 106, 34-607 Szczawa, małopolskie
- **Właściciel**: mgr Paweł Domek
- **Telefon**: +48 18 332 41 81
- **Godziny**: Pon-Pt 8:00-16:00
- **Płatności**: PayU, Przelewy24
- **Dostawa**: InPost, Poczta Polska, FedEx
- **Geo** (geocoded): 49.6072597 N, 20.2944911 E

Wszystkie powyższe wlecione do `inc/seo-schema.php` — LocalBusiness JSON-LD aktywne na każdej stronie.

**Co jeszcze do potwierdzenia z Pawłem**:
- Email główny: `sklep@gorvita.pl` — czy ten jest aktualny?
- Czy jest profil Facebook / Instagram (do `sameAs` w schema)
- Czy chce uruchomić Google Business Profile (osobny ticket)

## Kolejność wykonania (rekomendacja)

1. Sekcja 1 (sitemap fix) — najważniejsze, najszybsze
2. Sekcja 6 (default OG image) — szybko, broad impact
3. Sekcja 2 (Title/Description per page) — clear wygrana CTR
4. Sekcja 3 (`/o-marce/` rewrite) — biggest content+GEO upgrade
5. Sekcja 4 (FAQ na `/kontakt-2/`) — szybko, GEO bonus
6. Sekcja 5 (permalink cleanup) — zostaw na koniec (najbardziej ryzykowne, redirects do testu)
7. Sekcja 7 (flush + reverify) — po wszystkim
