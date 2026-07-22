# Etap 1b — uzupełnienie EAN w polu SKU (staging)

**Data:** 2026-07-21
**Środowisko:** `/opt/gorvita-staging` (`gorvita.srv1594477.hstgr.cloud`)
**Wynik:** ✅ 37/37 zapisanych, 0 błędów, 0 duplikatów, 0 zmian poza listą AUTO.

---

## Zmiany progów poprzedzające wykonanie

### Margines `AMBIGUOUS`: 0,05 → 0,20

Zastosowany do obu plików. Hardkod „para niestabilna" dla lp55 **usunięty** z wersji PROD.

**Bramka kontrolna zaliczona:** po zmianie lp55 na PROD jest łapane automatycznie —
`POMIŃ [AMBIGUOUS]`, score 1,129 vs konkurent 0,955. Reguła zastąpiła listę ręczną
zgodnie z zamierzeniem. lp54 również `POMIŃ [AMBIGUOUS]`.

### ⚠️ Z AUTO wypadły 2 pozycje, nie 3

Wcześniejsza prognoza (42 → 39, spadek o 3) była liczona na **surowym zbiorze kandydatów**
`metoda=NAZWA` ze score ≥ 0,95, **przed** pozostałymi regułami. W pełnym potoku jedna
z tych trzech była już wykluczona inną regułą, więc realny spadek to **2**.

| lp | score | runner-up | margines | Cennik | → produkt |
|---|---|---|---|---|---|
| 78 | 1,150 | 0,993 | 0,157 | MAŚĆ ŻYWOKOSTOWA Z OLEJEM CBD 140ml | 175 `GV-131` Maść żywokostowa z olejem CBD 140ml |
| 11 | 0,972 | 0,812 | 0,160 | ARTROFIT X 60 KAPS. | 18 `GV-33` Artrofit 3w1 60 kapsułek |

Identyczne w STAGING i PROD. lp78 to przypadek, który już wcześniej oznaczyłem jako
najciaśniejszy — konkurentem jest `GV-151` *„Maść żywokostowa **z gojnikiem** i olejem
CBD 140ml"*, inny produkt spoza cennika.

### Liczby po zmianie progu

| Decyzja | STAGING | PROD |
|---|---|---|
| AUTO | 39 → **37** (−2) | 40 → **38** (−2) |
| DO_WERYFIKACJI | 35 → **13** (−22) | 38 → **15** (−23) |
| POMIŃ | 50 → **74** (+24) | 46 → **71** (+25) |

### 🟠 Efekt uboczny nieujęty w kalkulacji

`AMBIGUOUS` jest sprawdzane **przed** progami score, więc podniesienie marginesu
przeniosło ~23 wiersze z DO_WERYFIKACJI wprost do POMIŃ. Dla samego Etapu 1b to
bez znaczenia — obie kategorie i tak nie zapisują. Konsekwencja jest downstream:
**kolejka do ręcznego uzupełnienia EAN skurczyła się z 38 do 15 pozycji.**
Te 23 produkty wypadają z backlogu zamiast czekać na weryfikację, co obniża docelowe
pokrycie EAN. Do rozważenia osobno — nie blokowało wykonania 1b, bo czyni je
bezpieczniejszym, nie ryzykowniejszym.

---

## Wykonanie

### 1. Backup

`_audyt/backup_przed_ean_20260721_1523.sql` — 39,6 MB, 179 tabel,
stopka `Dump completed on 2026-07-21 15:23:40` obecna.

### 2. Kontrole wstępne listy AUTO (37 pozycji) — wszystkie czyste

| Kontrola | Wynik |
|---|---|
| Pusty SKU (brak klucza głównego) | brak ✅ |
| SKU już w formacie EAN-13 (nadpisanie istniejącego) | brak ✅ |
| Duplikaty EAN wewnątrz listy | brak ✅ |
| Duplikaty SKU wewnątrz listy | brak ✅ |
| Nowy EAN kolidujący z SKU innego produktu | brak ✅ |
| EAN o niepoprawnym formacie (≠ 13 cyfr) | brak ✅ |
| SKU spoza katalogu | brak ✅ |

### 3. Zapis

Kluczowano **po dotychczasowym SKU** (decyzja 3), nigdy po `wc_id`.

Użyto `WC_Product::set_sku()` + `save()`, a **nie** `UPDATE` na `wp_postmeta` — bo
`wp_wc_product_meta_lookup` również przechowuje kolumnę `sku`. Bezpośredni UPDATE
zostawiłby tabelę lookup nieaktualną. Dodatkowa korzyść: `set_sku()` sam waliduje
unikalność i rzuca `WC_Data_Exception` przy kolizji — wywołania opakowano w `try/catch`.

**Wynik: zapisano 37, błędów 0.**

<details>
<summary>Pełna lista 37 zmian (stary SKU → EAN)</summary>

| Stary SKU | Nowy EAN | ID | Produkt |
|---|---|---|---|
| `GV-30` | `5907636994831` | 97 | ARCACET pianka 100 ml |
| `GV-127` | `5903317643081` | 21 | BABKA PŁESZNIK mielona 150g |
| `GV-45` | `5907636994862` | 109 | Diabelski (Czarci) Pazur balsam 200 ml |
| `GV-57` | `5907636994541` | 145 | Maść Kasztanowa 130 ml |
| `GV-60` | `5907636994244` | 159 | Maść Niedźwiedzi Ząb 200 ml |
| `GV-111` | `5903317643029` | 163 | Maść Rokitnikowa 140ml |
| `GV-100` | `5907636994237` | 167 | Maść Świerkowa 135 ml |
| `GV-62` | `5907636994633` | 169 | Maść Witaminowa A,E,F 20ml |
| `GV-99` | `5907636994930` | 181 | Olej Kokosowy w żelu 200 ml |
| `GV-129` | `5903317643074` | 73 | OSTROPEST mielony 150g |
| `GV-128` | `5903317643067` | 75 | OSTROPEST x60 kaps. |
| `GV-72` | `5907636994206` | 232 | Pneumovit Spray do nosa 35 ml |
| `GV-107` | `5907636994374` | 197 | RABKA SPA MINERALE żel 200 ml |
| `GV-79` | `5907636994343` | 203 | Zdrowa Stopa Kremo – Żel do stóp 100 ml |
| `GV-84` | `5907636994169` | 91 | Zielony Jęczmień w proszku 100 gram |
| `GV-85` | `5907636994497` | 205 | Żel Anticelluitis 200 ml |
| `GV-89` | `5907636994527` | 210 | Żel z Arniką i Bursztynem 200 ml |
| `GV-92` | `5907636994466` | 212 | Żel z Kasztanowcem 200 ml |
| `GV-98` | `5907636994916` | 224 | Żyworódka w Zasypce 50 ml |
| `GV-42` | `8594011210173` | 41 | Colafit SLIM z Chitosanem 60 kapsułek |
| `GV-134` | `5907636994077` | 27 | CARBOSAL + z czarną jagodą 30 kapsułek |
| `GV-76` | `5907636994015` | 81 | Silicum + H (KRZEM+BIOTYNA) 30 tabletek |
| `GV-83` | `5907636994176` | 87 | Zielony Jęczmień 120 kapsułek |
| `GV-82` | `5907636994732` | 89 | Zielony Jęczmień 60 kapsułek |
| `GV-35` | `5907636994749` | 19 | Babka Płesznik 60 kapsułek |
| `GV-81` | `5907636994428` | 85 | Zielona Kawa 60 kapsułek |
| `GV-31` | `5907636994800` | 16 | ARTREVIT 60 kapsułek |
| `GV-136` | `5907636994183` | 57 | Hepasal 40 kapsułek |
| `GV-59` | `5907636994602` | 113 | Końska Maść Chłodząca 130 ml |
| `GV-87` | `5907636994312` | 189 | Pneumovit Żel 100 ml |
| `GV-88` | `5907636994213` | 191 | Pneumovit Żel 200 ml |
| `GV-150` | `5903317643241` | 179 | Mosqitos Zestaw Płyn 20ml i Żel 20ml |
| `GV-132` | `5903317643180` | 171 | Maść z kurkumą, nagietkiem i olejem CBD 5% |
| `GV-46` | `5907636994091` | 51 | Erotic dla mężczyzn – afrodyzjak 20 kapsułek |
| `GV-66` | `5907636994329` | 183 | Olejek Pichtowy 100 ml |
| `GV-47` | `5907636994084` | 49 | Erotic dla kobiet – afrodyzjak 20 kapsułek |
| `GV-36` | `5907636994664` | 101 | Balsam do ust z witaminami A,E,F 20 ml |

</details>

### 4. Weryfikacja odczytem — zero rozbieżności

| Kontrola | Wynik |
|---|---|
| 37 pozycji AUTO zgodnych z planem w `wp_postmeta` | ✅ |
| 37 pozycji AUTO zgodnych z planem w `wp_wc_product_meta_lookup` | ✅ |
| Zmiany SKU **poza** listą AUTO | **brak** ✅ |
| Duplikaty SKU w całej bazie | **brak** ✅ |
| Liczba produktów (przed i po) | 128 = 128 ✅ |

Stan katalogu: 128 produktów · 123 z SKU · **123 unikalnych SKU** · 5 bez SKU
(3 realne produkty + 2 placeholdery B2BKing).

### 5. Cache

Redis (`wp cache flush`) → WP Rocket (`rocket_clean_domain()` + `rocket_clean_minify()`),
w tej kolejności.

---

## Efekt

| Metryka | Przed | Po |
|---|---|---|
| SKU w formacie EAN-13 | 37 | **74** (+37) |
| SKU `GV-xxx` | 86 | **49** |
| Bez SKU | 5 | 5 |

**Pokrycie EAN wzrosło z 29% do 59% katalogu.** Oznacza to, że w Etapie 2 znacznie
większa część planu cenowego będzie mogła opierać się na dopasowaniu po EAN
(`metoda = ean`), a nie na nazwach.

---

## Punkty otwarte

1. **Trzy produkty bez SKU** — 1486 LIBIDO Vegan, 1549 LUTEINA Vegan, 1550 Magnez B6 VEGAN.
   Na stagingu wszystkie wypadły na `AMBIGUOUS`, więc Etap 1b wykonał się bez wyjątków.
   **Przed wersją PROD trzeba rozstrzygnąć**, jak je zaadresować — decyzja 3 zabrania
   kluczowania po `wc_id`, a te produkty nie mają SKU. Na PROD dwa z nich (1486, 1549)
   były AUTO przy poprzednim progu; po podniesieniu marginesu do 0,20 wymaga to
   ponownego sprawdzenia w aktualnym `plan_ean_PROD.csv`.
2. **Kolejka DO_WERYFIKACJI skurczona z 38 do 15** przez podniesienie marginesu —
   23 pozycje przeszły do POMIŃ i wypadły z backlogu ręcznego uzupełniania EAN.
3. **`plan_ean_PROD.csv`** przeliczony nowym progiem, ale **nie wykonany** — czeka
   na osobną akceptację.
4. **Druga tura Etapu 1** dla pozycji DO_WERYFIKACJI — po ręcznej weryfikacji par.

---

## Bezpieczeństwo

Zapisano wyłącznie pole `_sku` na 37 produktach z listy AUTO, kluczując po SKU.
**Nie dotknięto:** cen, klas podatkowych, `_gspb_post_css`, wariantów, opisów, obrazków,
kategorii, stanów magazynowych, reguł B2BKing. **Produkcji nie modyfikowano** —
`plan_ean_PROD.csv` powstał wyłącznie z odczytów.

Rollback: `_audyt/backup_przed_ean_20260721_1523.sql`.
