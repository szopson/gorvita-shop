# Etap 0 — weryfikacja punktu startowego + odtworzenie audytu na stagingu

**Data:** 2026-07-21
**Środowisko:** `/opt/gorvita-staging` (`gorvita.srv1594477.hstgr.cloud`)
**Charakter:** wyłącznie odczyt. Żadnego zapisu do bazy.

---

## Bramka Etapu 0

| Sprawdzenie | Oczekiwane | Wynik | |
|---|---|---|---|
| Katalog roboczy | `/opt/gorvita-staging` | `/opt/gorvita-staging`, kontenery up/healthy | ✅ |
| `siteurl` | `gorvita.srv1594477.hstgr.cloud` | `https://gorvita.srv1594477.hstgr.cloud` | ✅ |
| `woocommerce_prices_include_tax` | `yes` | `yes` | ✅ |
| **`md5sum` cennika** | `4e3a008d7f8f521791cf80d329ae5aa9` | `4e3a008d7f8f521791cf80d329ae5aa9` | ✅ |
| `wc -l` cennika | 127 | 127 | ✅ |
| `grep -c 'MAŚĆ'` | 21 | **22** | ⚠️ |

### Sprostowanie do `grep -c 'MAŚĆ'`

Wartość oczekiwana w planie (21) jest o jeden za niska. **MD5 się zgadza, więc plik jest
bajt w bajt tym, czego plan oczekuje** — suma kontrolna jest rozstrzygająca, a `grep`
tylko pomocniczy. Wypisane 22 wiersze faktycznie zawierają „MAŚĆ":

- 20 pozycji zaczynających się od „MAŚĆ" — lp 56–74 (19 szt.) oraz lp 78
- lp 2 `ALANTOIN MAŚĆ 50ML`
- lp 19 `BLIZNA MAŚĆ NA BLIZNY 20 ML`

Prawdopodobne źródło pomyłki: policzono tylko pozycje *zaczynające się* od „MAŚĆ",
plus jedną z dwóch pozostałych.

> **Zastrzeżenie podtrzymane z raportu audytowego:** MD5 potwierdza zgodność z treścią
> dostarczoną w rozmowie, ale **nie** potwierdza, że CSV wiernie oddaje oryginalny
> arkusz `.xls` — tego pliku nadal nie ma na serwerze.

### Pliki na stagingu

`_audyt/` nie istniał — utworzony. Skopiowano (`cp`, bez edycji treści), właściciel
`deploy:deploy` (inaczej rsync z GitHub Actions przewróci się na permission-denied):

| Plik | Pochodzenie |
|---|---|
| `cennik_2025-06-01.csv` | kopia 1:1, MD5 zachowany |
| `wc_produkty.csv` | **odtworzony z bazy stagingu** |
| `dopasowanie.csv` | **odtworzony z bazy stagingu** |

---

## Konfiguracja podatkowa stagingu — identyczna z produkcją

| Opcja | staging | prod |
|---|---|---|
| `woocommerce_prices_include_tax` | `yes` | `yes` |
| `woocommerce_tax_display_shop` / `_cart` | `incl` / `incl` | `incl` / `incl` |
| `woocommerce_tax_round_at_subtotal` | `no` | `no` |
| `woocommerce_calc_taxes` | `yes` | `yes` |

Stawki (`wp_woocommerce_tax_rates`) identyczne: `reduced-rate` 8% · *(standard)* 23% ·
`zero-rate` 0%. Placeholdery B2BKing (ID 1058 „Offer", 1059 „Credit") obecne po obu
stronach i wyłączone z porównania.

---

## 🛑 Staging nie jest wierną kopią produkcji

128 produktów po obu stronach, **31 się różni**. Decyzją użytkownika audyt został
odtworzony na danych stagingu i to one są punktem odniesienia dla kolejnych etapów.
Poniżej konsekwencje, bo są istotne dla Etapów 1, 2 i 4.

### 1. Cztery rozjazdy VAT, których na produkcji nie ma

| lp | ID | SKU (EAN) | Produkt | Cennik | staging | prod |
|---|---|---|---|---|---|---|
| 63 | 2056 | `5907636994558` | Maść Końska Rozgrzewająca 130ml | 23% | 🔴 **8%** | 23% ✅ |
| 77 | 2058 | `5903317643258` | Mosqitos Płyn 20ml | 23% | 🔴 **8%** | 23% ✅ |
| 91 | 2059 | `5907636994596` | Pneumovit Pastylki x16 | 23% | 🔴 **8%** | 23% ✅ |
| 92 | 2060 | `5907636994565` | Pneumovit Pastylki x8 | 23% | 🔴 **8%** | 23% ✅ |

Wszystkie cztery dopasowane po **EAN**, więc niezgodność jest pewna — to nie artefakt
dopasowania po nazwie.

**To wywraca założenie Etapu 2.** Plan mówi: „każda nowa niezgodność oznacza, że coś się
zmieniło od czasu audytu — wtedy zatrzymaj się i zapytaj". Nic się nie zmieniło —
audyt robiłem na **produkcji**, a staging był rozjechany już wcześniej. Etap 2 na
stagingu wyprodukuje **4 wiersze BLOKADA** i zatrzyma się z definicji.

Dodatkowo **ID 209 `GV-147` „Żel łagodzący po ukąszeniach"** ma na stagingu klasę
`zero-rate` (**0% VAT**), a na produkcji `reduced-rate` (8%). W cenniku komórka VAT jest
pusta, więc nie da się rozstrzygnąć z danych — ale 0% to stan jeszcze trudniejszy do
obrony niż 8%. Pozycja i tak jest na liście WYKLUCZONE w Etapie 2.

### 2. Regresja jakości dopasowania — 4 pozycje przypisane inaczej niż na produkcji

Staging ma **starsze, krótsze nazwy produktów** (~24 pozycje: `OSTROPEST x60 kaps.`
zamiast `OSTROPEST 60 kapsułek`, `Chrom z zieloną herbatą` bez gramatury itd.).
Fuzzy matching na krótszych nazwach degraduje się:

| lp | Cennik | prod → | staging → | Ocena |
|---|---|---|---|---|
| 28 | CHROM Z ZIELONĄ HERBATĄ X 30 KAPS. | ID 31 *Chrom z zieloną herbatą 30 kapsułek* | 🔴 **ID 14 *Apleplus C,E 30 kapsułek*** | **FALSE POSITIVE** |
| 27 | CHROM Z ZIELONĄ HERBATĄ X 30 TAB. | — brak — | ID 31 *Chrom z zieloną herbatą* | przesunięte |
| 54 | MAGNEZ B6 + CYNK x60 KAPS. | ID 65 *Magnez B6 60 kapsułek* | 🔴 ID 1550 *Magnez B6 VEGAN* | **ZAMIENIONE** |
| 55 | MAGNEZ B6 VEGAN X 60 KAPS. | ID 1550 *Magnez B6 VEGAN 60 kapsułek* | 🔴 ID 65 *Magnez B6 60 kapsułek* | **ZAMIENIONE** |

**lp28 to dokładnie ten scenariusz, przed którym ostrzegał raport audytowy:** gdyby
Etap 1 poszedł po tym dopasowaniu, wpisałby **EAN Chromu z zieloną herbatą
(`5907636994046`) na produkt Apleplus C,E**. Produkt dostałby cudzy kod kreskowy,
a przy Etapie 2 — cudzą cenę.

Wiersze te są oznaczone w `dopasowanie.csv` kolumną **`flaga`**
(`FALSE_POSITIVE`, `PRZESUNIETE`, `ZAMIENIONE`). Muszą trafić do POMIŃ / DO_WERYFIKACJI
w Etapie 1a — nigdy do AUTO.

### 3. Inne różnice danych

| Co | prod | staging |
|---|---|---|
| „Maść żywokostowa z gojnikiem i olejem CBD 140ml" (`GV-151`, 24,72 zł) | **ID 11586** | **ID 2304** |
| ID 165 `GV-148` Maść Rumiankowa | 14,00 zł, bez promocji | **25,00 zł + promocja 12,51** |
| Nazwy produktów | nowsze | starsze (~24 poz.) |
| SKU | — | **identyczne, 128/128** ✅ |

Ten sam produkt pod dwoma różnymi ID oznacza, że **każda operacja kluczowana po `wc_id`
z produkcyjnego `dopasowanie.csv` rozjechałaby się na tej pozycji.** Dlatego odtworzenie
było konieczne. Obie pozycje są zresztą niedopasowane (produkt spoza cennika).

Dobra wiadomość: **wszystkie 128 SKU są identyczne po obu stronach** (37 EAN-13 / 86
`GV-xxx` / 5 pustych), więc sam mechanizm Etapu 1 jest przenośny — problem był w danych
odniesienia, nie w kluczu.

---

## Wyniki dopasowania na stagingu

| Metryka | staging | prod |
|---|---|---|
| Pozycji cennika | 126 | 126 |
| Produktów (bez placeholderów) | 126 | 126 |
| Dopasowane po **EAN** (pewne) | **37** | 37 |
| Dopasowane po **nazwie** (niepewne) | **87** | 87 |
| Niedopasowane — cennik | 2 | 2 |
| Niedopasowane — WooCommerce | 2 | 2 |
| **Niezgodności VAT** | 🔴 **4** | **0** |
| Delta brutto ≥ 0,60 zł | 🔴 **9** | **4** |

### Niedopasowane na stagingu

**Cennik bez odpowiednika:**

| lp | EAN | Nazwa | Netto |
|---|---|---|---|
| 29 | `5907636994824` | CHROM Z ZIELONĄ HERBATĄ X 60 KAPS. | 24,00 |
| 60 | `5903317643135` | MAŚĆ KONOPNA 80 ml | 21,90 |

lp60 **na produkcji był dopasowany** (score 0,902 → ID 155 `Maść Konopna 5% CBD 80 ml`).
Na stagingu nazwa to `Maść Konopna 5% CBD` — bez gramatury, więc kontrola zgodności
pojemności odrzuciła parę. To znów regresja nazw, nie brak produktu.

**WooCommerce bez odpowiednika:**

| ID | SKU | Nazwa | Ocena |
|---|---|---|---|
| 155 | `GV-126` | Maść Konopna 5% CBD | para dla lp60, rozbita przez brak gramatury |
| 2304 | `GV-151` | Maść żywokostowa z gojnikiem i olejem CBD 140ml | nowy produkt spoza cennika (odpowiednik prod. ID 11586) |

### Rozjazdy cen ≥ 0,60 zł brutto — 9 pozycji

| ID | SKU | Brutto oczek. | staging | Δ | Charakter |
|---|---|---|---|---|---|
| 165 | `GV-148` | 13,90 | 25,00 | **+11,10** | 🟣 różnica danych staging vs prod (prod: 14,00) |
| 14 | `GV-133` | 17,06 | 13,00 | −4,06 | ⚫ **artefakt FALSE POSITIVE lp28** — bez znaczenia |
| 37 | `GV-40` | 53,46 | 51,00 | −2,46 | 🔴 Colacal ↔ Colahial (jak na prod) |
| 1550 | — | 11,99 | 14,00 | +2,01 | ⚫ **artefakt zamiany lp54/55** |
| 65 | `GV-112` | 13,72 | 12,00 | −1,72 | ⚫ **artefakt zamiany lp54/55** |
| 43 | `GV-43` | 51,30 | 53,00 | +1,70 | 🔴 Colahial ↔ Colacal (jak na prod) |
| 201 | `GV-78` | 23,37 | 22,00 | −1,37 | 🔴 realny (jak na prod) |
| 31 | `GV-140` | 18,25 | 17,00 | −1,25 | ⚫ **artefakt przesunięcia lp27** |
| 157 | `GV-109` | 22,14 | 23,00 | +0,86 | 🔴 realny (jak na prod) |

**Cztery z dziewięciu to artefakty błędnego dopasowania**, nie rozjazdy cenowe.
Po odrzuceniu artefaktów i pozycji 165 zostają te same **4 realne rozjazdy co na
produkcji** (37, 43, 201, 157) — co potwierdza, że wnioski cenowe z audytu są trwałe.

---

## Wnioski dla kolejnych etapów

1. **Etap 1a** — wiersze z niepustą kolumną `flaga` (lp 27, 28, 54, 55) **muszą trafić do
   POMIŃ**, obok już zaplanowanych wykluczeń (ID 14, lp 27/28/29). lp28 przy AUTO wpisałby
   cudzy EAN na Apleplus.
2. **Etap 1a** — próg AUTO (`score >= 95`) wymaga doprecyzowania: score w
   `dopasowanie.csv` jest w skali 0–1,15 (0,5 × Jaccard + 0,5 × SequenceMatcher, ±bonus
   za zgodność gramatury), nie 0–100. Potrzebne ustalenie progu w tej skali.
3. **Etap 2** — wyprodukuje **4 wiersze BLOKADA** (lp 63, 77, 91, 92) z definicji, bo
   staging ma inne klasy podatkowe niż produkcja. Trzeba zdecydować: naprawić klasy na
   stagingu przed importem, czy dopisać je do WYKLUCZONE.
4. **Etap 4** — weryfikacja „netto_odtworzone == cennik_netto" na stagingu **nie będzie
   dowodem dla produkcji**, dopóki 4 klasy podatkowe i ID 165 się różnią. To ograniczenie
   trzeba przyjąć świadomie przy planowaniu wdrożenia produkcyjnego.
5. **Kontrola liczby produktów** w Etapie 4E: oczekiwane 128 — potwierdzone dla stagingu.

---

## Bezpieczeństwo wykonania

Wyłącznie `wp option get`, `wp db query` z `SELECT` oraz operacje na plikach w `_audyt/`.
**Zero UPDATE / INSERT / DELETE. Nie dotknięto `_gspb_post_css`, wariantów, opisów,
obrazków, kategorii ani stanów magazynowych. Nie czyszczono cache. Produkcji nie
modyfikowano** (odczyty z prod służyły wyłącznie porównaniu).
