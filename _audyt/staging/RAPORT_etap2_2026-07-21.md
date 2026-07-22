# Etap 2 — plan importu cen (staging) + naprawa widoczności backlogu EAN

**Data:** 2026-07-21
**Środowisko:** `/opt/gorvita-staging` (`gorvita.srv1594477.hstgr.cloud`)
**Charakter:** ⚠️ **wyłącznie odczyt.** Zero `UPDATE` / `INSERT` / `DELETE`. Zero czyszczenia cache.
Powstały wyłącznie pliki w `_audyt/`.

---

## A. Backlog EAN — naprawa widoczności

Wcześniejszy problem: podniesienie marginesu `AMBIGUOUS` z 0,05 na 0,20 przerzuciło
~23 wiersze z `DO_WERYFIKACJI` prosto do `POMIŃ`, przez co **wypadły z kolejki ręcznego
uzupełniania EAN** (raport `RAPORT_ean_2026-07-21.md`, punkt otwarty 2). Backlog zbiera
je z powrotem.

### Pliki

| Plik | Wierszy danych |
|---|---|
| `/opt/gorvita-staging/_audyt/backlog_ean_STAGING.csv` | **50** |
| `/opt/gorvita-shop/_audyt/backlog_ean_PROD.csv` | **49** |

Kolumny zgodnie ze specyfikacją: `wc_id`, `sku_obecny`, `nazwa_wc`, `kandydat_1_nazwa`,
`kandydat_1_score`, `kandydat_2_nazwa`, `kandydat_2_score`, `margines`, `powod`.
Sortowanie malejąco po `kandydat_1_score`.

### Skład

| Kategoria | STAGING | PROD |
|---|---|---|
| `DO_WERYFIKACJI` (wszystkie) | 13 | 15 |
| `POMIŃ` / `AMBIGUOUS` | 33 | 33 |
| `POMIŃ` / `FLAGA` | 4 | 1 |
| **razem** | **50** | **49** |

`SKU_JUZ_EAN13` (33) i `PODCIAG` (4) do backlogu nie trafiają — zgodnie z poleceniem.
`SKU_JUZ_EAN13` to operacje puste, `PODCIAG` to pozycje odrzucone regułą deterministyczną.

### Jak czytać `kandydat_2_nazwa`

Runner-up w `plan_ean_*.csv` jest liczony **na dwóch osiach**: (a) inny produkt WC
konkurujący o tę samą pozycję cennika, (b) inna pozycja cennika konkurująca o ten sam
produkt. Reguła `AMBIGUOUS` bierze maksimum z obu. Żeby backlog nie kłamał o tym, co
faktycznie zdecydowało, wartość jest **prefiksowana**:

- `produkt: …` — konkurentem jest inny produkt WooCommerce (ryzyko: EAN trafiłby nie na ten produkt)
- `cennik: …` — konkurentem jest inna pozycja cennika (ryzyko: produkt dostałby nie ten EAN)

`margines` = `kandydat_1_score − kandydat_2_score`. **Wartość ujemna oznacza, że wygrał
kandydat o niższym score** — bo dopasowanie zachłanne 1:1 przydzieliło lepszego kandydata
gdzie indziej. To najostrzejsze przypadki w kolejce (np. `GV-40` Colacal m = −0,054,
`GV-109` Maść Nagietkowa m = −0,075).

### Bramka poprawności

Backlog nie jest liczony osobnym algorytmem — odtwarza pełny potok `plan_ean.py` na tych
samych wejściach i **asercją porównuje każdy wiersz** z istniejącym `plan_ean_*.csv`
(`decyzja`, `powod_pominiecia`, `score`, `wc_id`, `runner_up`). Oba przebiegi: 124/124 zgodne.
Gdyby backlog rozjechał się z planem, skrypt by się wywrócił, a nie po cichu wypisał plik.

---

## B. ⚠️ Ścieżka nieprzećwiczona — zapis SKU do produktu bez SKU

**Odnotowane jako luka w pokryciu testowym Etapu 1b.**

| wc_id | Produkt | SKU | PROD | STAGING |
|---|---|---|---|---|
| 1549 | LUTEINA Vegan 60 kapsułek | *(brak)* | **AUTO** (score 1,127 / ru 0,822) | `POMIŃ` `AMBIGUOUS` (0,611 / 0,650) |
| 1486 | LIBIDO Vegan 60 kapsułek | *(brak)* | **AUTO** (score 1,126 / ru 0,822) | `POMIŃ` `AMBIGUOUS` (0,603 / 0,639) |

Obie pozycje **nie mają SKU**, a na produkcji są `AUTO`. Na stagingu wypadły na
`AMBIGUOUS` (przez krótsze, starsze nazwy produktów), więc **Etap 1b wykonał się bez
ani jednego zapisu na produkcie bez SKU**.

Konsekwencje:

1. Wykonanie 1b na stagingu **nie jest dowodem** dla tych dwóch wierszy na produkcji.
   Cała ścieżka „produkt bez SKU → nadaj SKU" jest nieprzećwiczona: nie wiadomo empirycznie,
   jak zachowa się `WC_Product::set_sku()` przy pustej wartości wyjściowej, ani jak
   zaktualizuje się `wp_wc_product_meta_lookup`.
2. Kluczowanie po SKU (decyzja 3) **nie działa dla tych wierszy** — klucz jest pusty.
   Zapis wymagałby albo dopuszczenia `wc_id` jako klucza awaryjnego, albo wcześniejszego
   ręcznego nadania SKU.
3. **Przed wersją PROD obie pozycje wymagają ręcznego potwierdzenia.** Nie wolno ich
   puścić automatem tylko dlatego, że mają wysoki score.

Trzeci produkt bez SKU — 1550 Magnez B6 VEGAN — na PROD jest `POMIŃ` `AMBIGUOUS`
(1,129 vs 0,955), więc dziś nie stanowi problemu, ale dzieli tę samą wadę strukturalną.

---

## C. Etap 2 — `plan_cen.csv` (staging)

**Plik:** `/opt/gorvita-staging/_audyt/plan_cen.csv` — 126 wierszy, jeden na pozycję cennika.
**Migawka katalogu użyta do planu:** `_audyt/wc_produkty_po_1b.tsv` (odczyt po Etapie 1b,
128 produktów, `publish` + 2 draftowe placeholdery B2BKing wykluczone z analizy).

### Zasady

- **Klucz: SKU.** Dopasowanie wyłącznie `cennik.ean == _sku` (`metoda = EAN`).
  `wc_id` nie jest użyty do niczego poza wypisaniem w raporcie.
- `brutto_docelowy = round(netto_cennik × (1 + VAT_cennik), 2)`, `ROUND_HALF_UP`.
- VAT z klasy podatkowej WC: `reduced-rate` → 8%, *(pusta)* → 23%, `zero-rate` → 0%.
- Asercje przed zapisem: brak duplikatów SKU w katalogu, brak duplikatów `lp` w cenniku,
  żaden SKU nie dopasowany do dwóch pozycji cennika. Wszystkie przeszły.

### Kolejność decyzji (pierwszeństwo malejąco)

| # | Decyzja | Warunek |
|---|---|---|
| 1 | `WYKLUCZONE` | pozycja na liście wykluczeń — cena bez zmian |
| 2 | `BRAK_EAN` | brak produktu o SKU równym EAN z cennika |
| 3 | `BLOKADA` | VAT cennika ≠ VAT z klasy podatkowej WC (albo VAT pusty / brak ceny) |
| 4 | `BEZ_ZMIAN` | cena docelowa == obecna, co do grosza |
| 5 | `DO_IMPORTU` | reszta |

### Wynik

| Decyzja | Pozycji |
|---|---|
| **DO_IMPORTU** | **74** |
| **BLOKADA** | **0** |
| **BRAK_EAN** | **44** |
| **WYKLUCZONE** | **6** |
| **BEZ_ZMIAN** | **2** |
| razem | **126** |

Dopasowanych po EAN: **74** · ręcznie (poza EAN): **2** · razem 76.
Produktów katalogu bez pozycji w cenniku: 50.

### ✅ Bramka: zero `BLOKADA`

Raport Etapu 0 przewidywał, że Etap 2 na stagingu wyprodukuje **4 wiersze BLOKADA**
(lp 63, 77, 91, 92) i zatrzyma się z definicji, bo staging miał inne klasy podatkowe niż
produkcja. Po naprawie z Etapu 1 punkt 1 wszystkie cztery są czyste i wchodzą do
`DO_IMPORTU`:

| lp | SKU | Produkt | VAT cennik | klasa WC |
|---|---|---|---|---|
| 63 | `5907636994558` | Maść Końska Rozgrzewająca 130ml | 23% | *(standard)* ✅ |
| 77 | `5903317643258` | Mosqitos Płyn 20ml | 23% | *(standard)* ✅ |
| 91 | `5907636994596` | Pneumovit Pastylki x16 | 23% | *(standard)* ✅ |
| 92 | `5907636994565` | Pneumovit Pastylki x8 | 23% | *(standard)* ✅ |

Wymiar VAT stagingu = produkcja. Przewidywanie z Etapu 0 zostało zamknięte poprawką,
nie obejściem.

### WYKLUCZONE — 6 pozycji, cena bez zmian

| lp | Cennik | Powód |
|---|---|---|
| 27 | CHROM Z ZIELONĄ HERBATĄ X 30 TAB. | wariant nierozstrzygnięty (brak produktu w WC) |
| 28 | CHROM Z ZIELONĄ HERBATĄ X 30 KAPS. | wariant nierozstrzygnięty (dopasowanie niepewne) |
| 29 | CHROM Z ZIELONĄ HERBATĄ X 60 KAPS. | wariant nierozstrzygnięty (brak produktu w WC) |
| 64 | MAŚĆ NAGIETKOWA | cennik bez gramatury, dopasowanie niepotwierdzone |
| 105 | VENAL ŻEL CHŁODZĄCY 150 ML | cena bez zmian do decyzji |
| 115 | ŻEL ŁAGODZĄCY PO UKĄSZENIACH 20 ML | VAT w cenniku pusty — **klasy podatkowej też nie ruszać** |

Siódme wykluczenie działa po stronie produktu, nie cennika: **`GV-133` / wc_id 14
(duplikat Apleplus)**. Pozycja lp6 „APLEPLUS C,E – OCET JABŁKOWY" dopasowała się po EAN
`5907636994121` na **ID 2040** — czyli dokładnie tam, gdzie miała. ID 14 nie pojawia się
w planie w żadnym wierszu. Reguła wykluczająca została zostawiona w kodzie jako
zabezpieczenie, ale nie musiała zadziałać.

### 🔴 DO_IMPORTU — dwie pozycje ręczne (potwierdzona transpozycja)

Jedyne dwa wiersze `DO_IMPORTU` **niedopasowane po EAN** — oba mają SKU `GV-xxx`
i weszły do planu wyłącznie na podstawie Twojego potwierdzenia:

| lp 31 | |
|---|---|
| Cennik | `COLACAL X 60 KAPS.` (EAN `8594011210012`) |
| Produkt | wc_id **37**, SKU **`GV-40`**, „Colacal - kolagen z wapniem 60 kapsułek" |
| Netto cennika | 49,50 zł · VAT 8% |
| Brutto obecne → docelowe | 51,00 → **53,46** zł |
| Δ brutto | **+2,46 zł** |
| Metoda | `RĘCZNE` (dopasowanie po nazwie, score 0,770, margines **−0,054**) |

| lp 34 | |
|---|---|
| Cennik | `COLAHIAL KOLAGEN X 60 KAPS.` (EAN `5907636994817`) |
| Produkt | wc_id **43**, SKU **`GV-43`**, „Colahial 60 kapsułek" |
| Netto cennika | 47,50 zł · VAT 8% |
| Brutto obecne → docelowe | 53,00 → **51,30** zł |
| Δ brutto | **−1,70 zł** |
| Metoda | `RĘCZNE` (dopasowanie po nazwie, score 0,906, margines 0,082) |

Kierunek błędu jest jednoznaczny: cena Colacalu siedzi na Colahialu i odwrotnie, a obie
wartości oczekiwane trafiają dokładnie w drugą kartę. **Oba wiersze są w backlogu
`AMBIGUOUS`** (score 0,770 / 0,906) — czyli automat sam by ich nie przepuścił.
Wchodzą do planu **wyłącznie** z ręcznego potwierdzenia.

### 🟠 Obserwacja, która wymaga decyzji przed Etapem 3

Rozkład wartości bezwzględnej `delta_brutto` w 74 wierszach `DO_IMPORTU`:

| |Δ brutto| | Pozycji |
|---|---|
| ≥ 0,60 zł | **2** — wyłącznie Colacal i Colahial (ręczne) |
| 0,30–0,59 zł | 34 |
| < 0,30 zł | 38 |

**Wszystkie 72 importy dopasowane po EAN mają deltę ≤ 0,49 zł.** To nie są rozjazdy
cenowe — to artefakt zaokrąglenia ceny detalicznej do pełnych złotych (audyt: 119 ze 123).
Konsekwencja praktyczna: import przestawiłby 72 ceny z okrągłych (21,00 · 23,00 · 17,00)
na wartości groszowe (21,06 · 23,37 · 17,06), **nie naprawiając ani jednego realnego
błędu cenowego**. Cztery realne rozjazdy z audytu rozkładają się tak:

| Pozycja | Los w planie |
|---|---|
| Colacal, Colahial | `DO_IMPORTU` — ręcznie |
| Venal Żel (lp105), Maść Nagietkowa (lp64) | `WYKLUCZONE` na Twoje polecenie |

Czyli **cała wartość korekcyjna Etapu 2 siedzi w dwóch wierszach ręcznych**, a 72 wiersze
EAN to zmiana polityki zaokrąglania cen. To decyzja biznesowa (czy sklep ma trzymać ceny
w pełnych złotych), nie techniczna — dlatego zostaje do rozstrzygnięcia, a nie wykonania.

### Ceny promocyjne

Dwa produkty z aktywną `_sale_price` trafiły do `DO_IMPORTU` — plan **nie rozstrzyga**,
co się z promocją stanie:

| lp | SKU | Produkt | Regular | Sale | Cel |
|---|---|---|---|---|---|
| 76 | `5903317643241` | Mosqitos Zestaw Płyn 20ml i Żel 20ml | 16,00 | 14,62 | 16,24 |
| 81 | `5907636994329` | Olejek Pichtowy 100 ml | 15,00 | 13,51 | 15,01 |

Trzeci produkt promocyjny z audytu — ID 95 AloeVera Żel 150 ml (`GV-24`, sale 20,15) —
nie ma EAN, więc wypadł na `BRAK_EAN`. W obu przypadkach cena promocyjna zostaje poniżej
nowej regularnej, więc nie powstaje sytuacja „sale ≥ regular", ale **efektywny rabat się
zmienia** i wymaga decyzji.

### Pozycje niezaimportowane z braku dopasowania po EAN

**44 pozycje cennika** (`decyzja = BRAK_EAN`) — **34,9% cennika i 34,9% katalogu**
(oba mianowniki wynoszą 126).

Dodatkowo **wszystkie 6 pozycji `WYKLUCZONE` również nie ma dopasowania po EAN** —
gdyby nie były wykluczone decyzją, wpadłyby do tej samej kategorii. Licząc łącznie,
**50 ze 126 pozycji cennika (39,7%) nie ma dziś klucza EAN**, ale jako *powód
niezaimportowania* przypisano im wykluczenie, nie brak EAN.

Te 44 pozycje pokrywają się z kolejką z punktu A: to są produkty, które wciąż mają SKU
`GV-xxx`. Każdy EAN uzupełniony z backlogu przenosi jedną pozycję z `BRAK_EAN` do
`DO_IMPORTU`. Górna granica: gdyby uzupełnić wszystkie 50 wierszy backlogu, `BRAK_EAN`
spadłby do zera.

---

## D. Decyzje po Etapie 2 (2026-07-21)

### 1. Import częściowy odpada — 72 groszowe korekty to cel, nie artefakt

**Moja interpretacja z sekcji C była błędna i zostaje wycofana.** Nazwałem 72 delty
≤ 0,49 zł „artefaktem zaokrąglenia" i sugerowałem, że import nie naprawia niczego realnego.
To ocena z perspektywy detalu, a projekt ma inną oś odniesienia:

> Δ brutto 0,49 zł ≈ Δ netto 0,45 zł ≈ **~2,3% rozjazdu na fakturze B2B** względem cennika
> producenta.

W modelu B2B z rabatem −18% wliczonym w cenę (reguła 1760, wariant B) każda cena regularna
jest podstawą do wyliczenia ceny hurtowej i pozycji na fakturze. Rozjazd rzędu 2% na pozycji
nie jest kosmetyką — to rozjazd wobec cennika, który klient B2B ma u siebie. Groszowe
korekty **są celem projektu**, a nie kosztem ubocznym.

**Decyzja:** importujemy wszystko, ale **dopiero po pokryciu EAN-ami całego katalogu**.
Import częściowy (74 dziś / 44 później) jest odrzucony — sklep miałby przejściowo dwie
polityki cenowe naraz. Etap 3 startuje po uzupełnieniu backlogu.

### 2. Colacal i Colahial — potwierdzone

| lp | SKU | wc_id | Brutto docelowe |
|---|---|---|---|
| 31 | `GV-40` | 37 | **53,46 zł** |
| 34 | `GV-43` | 43 | **51,30 zł** |

Pozostają w `plan_cen.csv` jako `DO_IMPORTU` / `metoda = RĘCZNE`.

### 3. Promocje lp 76 i lp 81 — `_sale_price` bez zmian

**Import nie dotyka `_sale_price`.** Zmieni się wyłącznie cena regularna, więc efektywny
rabat na tych dwóch pozycjach przesunie się sam:

| lp | SKU | Produkt | Regular teraz → po | Sale (bez zmian) | Rabat teraz → po |
|---|---|---|---|---|---|
| 76 | `5903317643241` | Mosqitos Zestaw Płyn 20ml i Żel 20ml | 16,00 → **16,24** | 14,62 | −8,6% → **−10,0%** |
| 81 | `5907636994329` | Olejek Pichtowy 100 ml | 15,00 → **15,01** | 13,51 | −9,9% → **−10,0%** |

🔖 **Do świadomego przeglądu przed startem sklepu.** Obie ceny promocyjne zostają poniżej
nowej regularnej, więc nie powstaje stan „sale ≥ regular" i nic się nie zepsuje technicznie —
ale rabat przestaje być tym, co ktoś kiedyś ustawił, i nikt tego nie zatwierdzał. Trzecia
pozycja promocyjna, **ID 95 AloeVera Żel 150 ml** (`GV-24`, regular 22,00 / sale 20,15),
dziś nie ma EAN → `BRAK_EAN`; **wejdzie w ten sam problem, gdy tylko dostanie EAN z backlogu**,
więc należy ją przejrzeć razem z tamtymi dwiema.

---

## E. Backlog EAN do pracy ręcznej — `backlog_ean_PROD.xlsx`

**Plik:** `/opt/gorvita-shop/_audyt/backlog_ean_PROD.xlsx` — jeden arkusz „Backlog EAN PROD",
51 wierszy danych, nagłówek zamrożony (`A2`), szerokości kolumn ustawione, zawijanie tekstu.
**Wersja 2 (regeneracja 2026-07-21):** próg bloku A obniżony do 0,93, dołożone kolumny
`cennik_lp`, `kandydat_1_ean`, `kandydat_2_ean`.

Kolumny (14): jak w `backlog_ean_PROD.csv` + **`cennik_lp`** (zaraz przed
`kandydat_1_nazwa` — identyfikuje kandydata 1 i pozwala wrócić do cennika po numerze),
+ **`kandydat_1_ean`**, **`kandydat_2_ean`** (bezpośrednio przed `DECYZJA`),
+ **`DECYZJA`** (do wypełnienia: EAN albo `POMIŃ`, tło szare) + **`UWAGA`**.
Sortowanie malejąco po `kandydat_1_score` **wewnątrz każdego bloku**.

### Format EAN-ów — wymuszony tekstowy

`kandydat_1_ean`, `kandydat_2_ean` **oraz `DECYZJA`** mają `number_format = '@'` i są
zapisane jako łańcuchy znaków. Excel nie zamieni ich na `5,90764E+12` ani nie utnie
wiodącego zera — dotyczy to również EAN-u wpisanego ręcznie do `DECYZJA`.

**Asercja na zapisanym pliku** (plik jest po zapisie wczytywany ponownie i sprawdzany,
a nie weryfikowany na strukturze w pamięci): każdy `kandydat_1_ean` musi być typu `str`,
mieć dokładnie 13 znaków, składać się wyłącznie z cyfr i mieć format `@`.
Wynik: **51/51 ✅**. Dodatkowo `kandydat_2_ean` — jeśli niepusty — przechodzi ten sam test.

### `kandydat_2_ean` jest wypełniony w 31 z 51 wierszy — celowo

Runner-up jest liczony na dwóch osiach (patrz sekcja A):

- oś **`cennik:`** — konkuruje inna pozycja cennika, czyli **inny EAN mógłby trafić na ten
  produkt**. Kolumna wypełniona. **31 wierszy.**
- oś **`produkt:`** — konkuruje inny produkt WooCommerce o **ten sam** EAN. Innego EAN-u
  tu nie ma; wpisanie `kandydat_1_ean` po raz drugi sugerowałoby wybór, którego nie ma.
  Kolumna pusta. **20 wierszy.** Ryzyko w tych wierszach brzmi „czy EAN nie powinien pójść
  na tamten produkt", a nie „który EAN wpisać".

| Blok | Kryterium | Wierszy |
|---|---|---|
| **A. Wysokie zaufanie** | score ≥ **0,93** | **8** |
| **B. Do rozstrzygnięcia** | 0,80 ≤ score < 0,93 | **26** |
| **C. Sporne / warianty** | score < 0,80 **lub** `FLAGA` **lub** znany przypadek sporny | **17** |
| | razem | **51** |

> Poprzedni próg 1,05 dawał `A = 1 / B = 33 / C = 17` — blok „do szybkiego potwierdzenia"
> był praktycznie pusty, bo maksimum skali to 1,150. Po obniżeniu do 0,93 blok A obejmuje
> zakres 0,931–1,150.

> ℹ️ W bloku A jest **lp105 Venal Żel** (0,931), a w B **lp115 Żel łagodzący** (0,810) —
> obie pozycje są `WYKLUCZONE` w `plan_cen.csv`. To nie jest sprzeczność: wykluczenie
> dotyczy **ceny** (Etap 2), a backlog dotyczy **EAN-u** (Etap 1). Uzupełnienie EAN-u nie
> uruchamia importu ceny.

### 🔴 Odstępstwo od literalnej specyfikacji — świadome

**Do bloku C trafiły wiersze, które po samym score należałyby wyżej** — bo poleciłeś
wyróżnić w C znane przypadki sporne:

| wc_id | Score | Blok wg score | Powód wymuszenia C |
|---|---|---|---|
| 31 `GV-140` | 1,134 | A | warianty Chromu lp 27/28/29 (`FLAGA` — i tak trafiłby do C) |
| 1550 | 1,129 | A | para lp54/lp55 Magnez B6 + Cynk vs VEGAN |
| **1549** | 1,127 | — | **wiersza NIE MA w CSV** (patrz niżej) |
| **1486** | 1,126 | — | **wiersza NIE MA w CSV** (patrz niżej) |
| 65 `GV-112` | 0,825 | B | para lp54/lp55, margines ujemny (−0,130) |

Bez tego wymuszenia cztery z nich siedziałyby w bloku A „do szybkiego potwierdzenia" —
czyli dokładnie tam, gdzie najłatwiej je przeklepać bez zastanowienia.

### 🔴 Dwa wiersze DOPISANE — nie pochodzą z `backlog_ean_PROD.csv`

**1549 LUTEINA Vegan i 1486 LIBIDO Vegan mają na PROD decyzję `AUTO`**, więc z definicji
nie ma ich w backlogu (backlog = `DO_WERYFIKACJI` + `POMIŃ`/`AMBIGUOUS`+`FLAGA`).
Zostały **dopisane ręcznie do bloku C**, zgodnie z poleceniem, i są w arkuszu wyróżnione
czerwoną, pogrubioną czcionką w pierwszych czterech kolumnach. Dlatego arkusz ma
**51 wierszy, a CSV 49** — różnica jest zamierzona i udokumentowana w kolumnie `UWAGA`
każdego z tych dwóch wierszy.

Powód dopisania: oba produkty **nie mają SKU**, a ścieżka „zapis SKU do produktu bez SKU"
nie została przećwiczona na stagingu (tam obie pozycje wypadły na `AMBIGUOUS`) — sekcja B
tego raportu.

### ✅ Domknięte w wersji 2: EAN kandydata i numer pozycji cennika

Wersja 1 nie pokazywała EAN-u, więc wypełnienie `DECYZJA` wymagało 51 ręcznych zajrzeń do
`cennik_2025-06-01.csv`. Wersja 2 ma `cennik_lp` + `kandydat_1_ean` + `kandydat_2_ean` —
w typowym wierszu wystarczy przekopiować `kandydat_1_ean` do `DECYZJA` albo wpisać `POMIŃ`.

Bramka spójności przy generowaniu: dla każdego wiersza sprawdzane jest asercją, że `lp`
z odtworzonego potoku wskazuje w cenniku **tę samą nazwę**, co `kandydat_1_nazwa` —
inaczej `cennik_lp` i `kandydat_1_ean` mogłyby pochodzić z różnych wierszy cennika.

---

## Bezpieczeństwo wykonania

Wykonano wyłącznie `SELECT` przez `wp db query` (eksport 128 produktów: `_sku`,
`_regular_price`, `_sale_price`, `_tax_class`, `_tax_status`) oraz operacje na plikach
w `_audyt/`.

**Zero `UPDATE` / `INSERT` / `DELETE`. Nie zmieniono żadnej ceny, klasy podatkowej, SKU,
meta ani reguły B2BKing. Nie dotknięto `_gspb_post_css`, wariantów, opisów, obrazków,
kategorii, stanów magazynowych. Nie czyszczono cache — ani Redis, ani WP Rocket.
Produkcji nie modyfikowano** — z `/opt/gorvita-shop` czytano wyłącznie
`cennik_2025-06-01.csv`, `wc_produkty.csv` i `plan_ean_PROD.csv`, żeby wygenerować
`backlog_ean_PROD.csv`.

Nowe pliki:

| Plik | Właściciel |
|---|---|
| `/opt/gorvita-staging/_audyt/plan_cen.csv` | `deploy:deploy` |
| `/opt/gorvita-staging/_audyt/backlog_ean_STAGING.csv` | `deploy:deploy` |
| `/opt/gorvita-staging/_audyt/wc_produkty_po_1b.tsv` | `deploy:deploy` |
| `/opt/gorvita-shop/_audyt/backlog_ean_PROD.csv` | `deploy:deploy` |
| `/opt/gorvita-shop/_audyt/backlog_ean_PROD.xlsx` | `deploy:deploy` |

`backlog_ean_PROD.xlsx` powstał **wyłącznie z plików CSV** (`backlog_ean_PROD.csv` +
odtworzenie potoku `plan_ean.py` na `cennik_2025-06-01.csv` i `wc_produkty.csv`).
Bazy produkcyjnej nie odpytywano. Kontrola: `md5sum` `backlog_ean_PROD.csv` przed i po
regeneracji identyczny (`6124f533…`) — plik źródłowy nie został zmieniony.

**Zatrzymano się. Etap 3 czeka na uzupełniony backlog.**

---

## F. Etap 3 — KROK 1 (STAGING) — wykonany 2026-07-21

### F.1 Warunek bramkowy — ceny na PRODUKCJI
| wc_id | produkt | `_regular_price` (brutto) | oczekiwanie | werdykt |
|---|---|---|---|---|
| 31 | Chrom z zieloną herbatą 30 kaps. | **17,00** | ~17 zł → lp28 | lp28, EAN `5907636994794` ✅ |
| 65 | Magnez B6 60 kaps. | **12,00** | ~12 zł → lp54 | lp54, EAN `5907636994039` ✅ |

`woocommerce_prices_include_tax = yes`, więc powyższe to kwoty brutto. Konkurencyjne
warianty odpadają z dużym marginesem: lp29 → 25,92 (Δ 8,92), lp27 → 18,25 (Δ 1,25),
lp53 → 23,54 (Δ 11,54). Dopasowanie lp54 zgadza się co do 1 grosza (11,99 vs 12,00).

### F.2 Zapis
- Backup: `_audyt/backup_przed_ean2_20260721_2129.sql` — 37,7 MB, 179 tabel, „Dump completed".
- Przebieg próbny (`DRY=1`): 50 ok / 1 pominięty / 0 błędów. Dopiero potem zapis.
- Zapis: **50 zapisanych / 1 pominięty (`GV-128`, JUZ_USTAWIONY) / 0 błędów.**
- Metoda: `WC_Product::set_sku()` + `save()`. Żadnego `UPDATE` na `wp_postmeta`.
- Bramki per wiersz: SKU w bazie == `sku_obecny` z planu (dla 3 wierszy BEZ_SKU: puste),
  rozwiązany `wc_id` == `wc_id_srodowiska`, po zapisie ponowny odczyt `wc_get_product()`.

Weryfikacja: 50/50 `_sku` zgodne z planem, 50/50 lookup zsynchronizowany, 0 duplikatów
SKU w całej bazie, 128 produktów. Pokrycie EAN **74/128 → 124/128 (97%)**, cennik
dopasowany **124/126 (98%)**. Cache: Redis, potem WP Rocket.

### F.3 Rozjazd `_sku` ↔ `wp_wc_product_meta_lookup` — atrybucja (ROZSTRZYGNIĘTE)

Kontrola wykryła 14 produktów z rozjazdem na stagingu. **Żaden nie pochodzi z zapisu.**

| grupa | liczba | dowód |
|---|---|---|
| zapisane w Etapie 1b (37) | **0** | wszystkie 14 mają w `plan_ean_STAGING.csv` decyzję `POMIŃ` |
| miały EAN w SKU już przed Etapem 1b | **14** | 13× `powod_pominiecia=SKU_JUZ_EAN13`, 1× (#55 GOTU KOLA) `PODCIAG` |
| zapisane w Etapie 3 / KROK 1 (50) | **0** | 50/50 zweryfikowane jako zsynchronizowane |

Wniosek: raport Etapu 1b mówiący „lookup 37/37 zgodne" **był prawdziwy** — sprawdzał
swoje 37 zapisów i te faktycznie były spójne. Rozjazd siedzi w rozłącznym zbiorze,
którego tamta weryfikacja nie obejmowała, bo te produkty nie były przez nią dotykane.
Źródłem jest pierwotny import produktów, nie mechanizm zapisu.

**Mechanizm `set_sku()` + `save()` jest bezpieczny** — potwierdzone na 87 zapisach
(37 z Etapu 1b + 50 z KROKU 1), zero rozjazdów.

Naprawa 14 wierszy: `wp wc update_product_lookup_tables` **nie istnieje** w tej wersji WC.
`WC_Data_Store::load('product')->update_lookup_table($id, …)` **cicho nic nie robi** —
metoda jest `protected`, a `WC_Data_Store::__call()` dla niecallable metody zwraca `null`
bez błędu (pułapka: wygląda na sukces). Zadziałało dopiero wywołanie refleksją na
instancji data store'u. Po naprawie: **0 rozjazdów w całej bazie stagingu.**

### F.4 Ten sam test na PRODUKCJI (tylko odczyt) — 7 rozjazdów

Wcześniejsze twierdzenie „na prodzie tego nie ma, bo prod nie przeszedł Etapu 1b" było
**błędne** i zostaje wycofane. Zapytanie pokazuje 7 rozjazdów `_sku` ↔ lookup:

```
#25  postmeta 5907636994107  lookup GV-135   CARBOsal syrop 100ml
#29  postmeta 5907636994572  lookup GV-139   Chrom Forte
#47  postmeta 5907636994589  lookup GV-142   Energia 30 kapsułek
#103 postmeta 5907636994251  lookup GV-52    Balsam Koński 250 ml
#111 postmeta 5907636994503  lookup GV-58    Końska Maść 250 ml
#187 postmeta 5907636994534  lookup GV-68    Panthenol Żel 100 ml
#228 postmeta 5907636994763  lookup GV-34    Aurix 35 ml
```

To podzbiór stagingowych 14, wszystkie z grupy „miały EAN przed Etapem 1b" — czyli
dług z pierwotnego importu, obecny na produkcji od początku. Skutek: te 7 produktów
jest nieodnajdywalnych po EAN-ie w wyszukiwarce panelu i w części zapytań WooCommerce.
**Do naprawy w KROKU 2** tą samą metodą (refleksja), przed zapisem EAN-ów.

### F.5 Rozjazd cenowy postmeta ↔ lookup — Etap 4 rozszerzony (pkt 5)

Zakres Etapu 4 zostaje rozszerzony o kontrolę spójności cen:
`_regular_price` / `_price` w `wp_postmeta` vs `min_price` / `max_price` w
`wp_wc_product_meta_lookup`, dla **wszystkich** zaimportowanych pozycji.
**Rozjazd = czerwony wynik Etapu 4, nie ostrzeżenie.**

Sonda wykonana już teraz (tylko odczyt):
- STAGING: **0** rozjazdów cenowych.
- PRODUKCJA: **3** rozjazdy — i to nie groszowe:

| ID | produkt | `_price` | `lookup.min_price` | Δ |
|---|---|---|---|---|
| 103 | Balsam Koński 250 ml | 20,00 | 24,00 | **−4,00** |
| 111 | Końska Maść 250 ml | 21,00 | 20,00 | **+1,00** |
| 187 | Panthenol Żel 100 ml | 17,00 | 21,00 | **−4,00** |

Żaden z tych produktów nie ma `_sale_price`, więc to nie jest znany quirk on-sale.
Wszystkie trzy są jednocześnie w grupie 7 rozjazdów SKU — ten sam pierwotny import.
Znaczenie biznesowe: sortowanie i filtrowanie po cenie w sklepie czyta `min_price`,
więc klient widzi te produkty w złych przedziałach cenowych; przy 103 i 187 lookup
zawyża cenę o 4 zł. Do naprawy razem z SKU w KROKU 2.

### F.6 Produkty spoza cennika (kontrola kompletności)

Na stagingu po zapisie, 128 produktów vs 126 pozycji cennika:

| ID (staging / prod) | SKU | produkt | kwalifikacja |
|---|---|---|---|
| 14 / 14 | GV-133 | Apleplus C,E 30 kapsułek | duplikat (import wyłącznie na ID 2040) |
| 1058, 1059 | — | Offer, Credit | placeholdery B2BKing |
| 2304 / 11586 | GV-151 | Maść żywokostowa z gojnikiem i olejem CBD 140ml | **jedyny realny produkt spoza cennika** |

Kontrola odwrotna: **0** SKU-EAN-13 w katalogu, których nie ma w cenniku. Lista realnych
produktów bez pozycji w cenniku to dokładnie ten jeden gojnik — nic się nie chowa.

`GV-151`: `tax_class` = **(standard) = 23%** na PRODUKCJI (ID 11586) i na STAGINGU
(ID 2304), `tax_status = taxable`, `_regular_price = 24,72` brutto w obu środowiskach.
Wynikające **netto = 20,10 zł**. 23% jest spójne z całą rodziną maści CBD w WC (155, 165,
171, 175 — wszystkie standard) i z cennikiem, gdzie każda maść CBD to „Kosmetyk" 23%.
Uwaga: 24,72 = 20,10 × 1,23, a 20,10 to dokładnie netto lp78 (maść żywokostowa z olejem
CBD 140 ml) — cena gojnika została wyprowadzona z netto produktu siostrzanego.
Status w `plan_cen`: **BEZ_ZMIAN**, SKU bez zmian.

---

## G. Decyzja klienta 22.07.2026 — lp115 Żel łagodzący po ukąszeniach (VAT 23%)

**Autoryzacja: klient (Paweł Jurkowski, 22.07.2026).** Jedyny autoryzowany wyjątek od
zasady „na produkcji klas podatkowych nie ruszamy". Odnotowane osobno, bo jest to jedyna
zmiana `tax_class` w całym projekcie importu cen.

### G.1 Reguła VAT dla maści i żeli

Klient potwierdził regułę ogólną: wśród maści i żeli **8% mają wyłącznie** Żel Pneumovit,
Rabka Spa (spray i żel) oraz Olejek Pichtowy w żelu. Reszta 23%.

Weryfikacja wobec katalogu WC (PRODUKCJA, `_tax_class = reduced-rate`) — pozycje
kosmetyczne 8%:

| ID | produkt | pozycja w cenniku | po zmianie |
|---|---|---|---|
| 183 | Olejek Pichtowy 100 ml | lp81 OLEJEK PICHTOWY **W ŻELU** 100 ML | zostaje 8% ✅ |
| 189 | Pneumovit Żel 100 ml | — | zostaje 8% ✅ |
| 191 | Pneumovit Żel 200 ml | — | zostaje 8% ✅ |
| 195 | RABKA SPA MINERALE spray 200 ml | lp97 | zostaje 8% ✅ |
| 197 | RABKA SPA MINERALE żel 200 ml | — | zostaje 8% ✅ |
| **209** | **Żel łagodzący po ukąszeniach owadów 20ml** | **lp115** | **→ 23%** |

Po zmianie zbiór 8% wśród maści/żeli = **dokładnie lista klienta**, zero odchyleń.
Kontrola `vat_cennik` vs `klasa_wc` na wszystkich 126 wierszach `plan_cen.csv`:
**0 rozjazdów**.

### G.2 Zmiana dla lp115 / wc_id 209

Stan obecny (odczyt, oba środowiska — PROD ID 209 i STAGING ID 209, ten sam numer):

| pole | przed | po |
|---|---|---|
| `_sku` | PROD `GV-147` / STAGING `5903317643227` | bez zmian |
| `tax_class` | `reduced-rate` (8%) | **`(standard)` (23%)** |
| `_regular_price` (brutto) | `9,00` | **`10,95`** |
| netto odtworzone | 8,33 | **8,90** |
| netto po −18% B2B | 6,83 | **7,30** |
| status w `plan_cen` | `WYKLUCZONE` | **`DO_IMPORTU`** |

Kontrola arytmetyki: 8,90 × 1,23 = 10,947 → **10,95** ✅ · 8,90 × 0,82 = 7,298 → **7,30** ✅
Delta brutto: **+1,95**.

**Wykonanie — jedna operacja na produkcie:**

```php
$p = wc_get_product( 209 );
$p->set_tax_class( '' );          // '' = standard = 23%
$p->set_regular_price( '10.95' );
$p->save();
```

Rozdzielenie tych dwóch setterów jest zabronione: przy `woocommerce_prices_include_tax = yes`
`_regular_price` jest **brutto**, więc sama zmiana klasy z 8% na 23% przy cenie 9,00
przestawiłaby netto z 8,33 na 7,32 — produkt przez moment miałby złe netto, a każdy
przelicznik B2BKing/lookup uruchomiony w tym oknie utrwaliłby błąd.

### G.3 Etap 4 — osobna kontrola dla wc_id 209

Dodana do zakresu Etapu 4 jako pozycja punktowa; rozjazd = **czerwony wynik**, nie ostrzeżenie:

1. `tax_class` = `(standard)` — i tak samo w `wp_wc_product_meta_lookup.tax_class`
2. netto odtworzone z `_regular_price` = **8,90** (`10,95 / 1,23 = 8,9024` → 8,90)
3. netto po rabacie B2B −18% = **7,30**
4. `_regular_price` = `_price` = `lookup.min_price` = `lookup.max_price` = `10,95`
5. brak `_sale_price`

### G.4 plan_cen.csv — odświeżenie i nowe liczby

⚠ Przy okazji wyszło, że plik był **nieaktualny**: powstał 21.07 o 18:41, a KROK 1 dopisał
50 EAN-ów na stagingu o 21:29. Plan jest kluczowany po SKU = EAN-13, więc kolumna
`BRAK_EAN` liczyła produkty, które od 21:29 już istnieją. Sam flip lp115 dałby liczby
nieprawdziwe. Plan został odświeżony wobec katalogu po KROKU 1.

Kopia stanu sprzed: `plan_cen_przed_lp115_20260722.csv` (md5 `e8b18275ae3bf07bf3a76aa5e0e57105`).

| decyzja | przed (21.07 18:41) | **po (22.07)** | zmiana |
|---|---|---|---|
| `DO_IMPORTU` | 74 | **118** | +44 |
| `BEZ_ZMIAN` | 2 | **3** | +1 |
| `WYKLUCZONE` | 6 | **5** | −1 |
| `BRAK_EAN` | 44 | **0** | −44 |
| RAZEM | 126 | **126** | — |

45 zmian statusu: 44 × `BRAK_EAN → DO_IMPORTU` (odblokowane przez KROK 1; z tego lp57
Maść Borowinowa dalej na `BEZ_ZMIAN`, bo delta = 0,00) + 1 × `WYKLUCZONE → DO_IMPORTU`
(lp115, decyzja klienta).

Ponowne uruchomienie skryptu na już odświeżonym pliku dało **0 zmian statusu** —
przekształcenie jest idempotentne.

### G.5 WYKLUCZONE nie jest puste — zostało 5

| lp | wc_id | produkt w WC | powód wykluczenia |
|---|---|---|---|
| 27 | — | brak | wariant Chromu 30 TAB — brak produktu w WC |
| 28 | 31 | **jest** | wariant Chromu 30 KAPS — dopasowanie niepewne |
| 29 | — | brak | wariant Chromu 60 KAPS — brak produktu w WC |
| 64 | 157 | **jest** | Maść Nagietkowa — cennik bez gramatury, dopasowanie niepotwierdzone |
| 105 | 201 | **jest** | Venal Żel — cena bez zmian do decyzji |

Trzy z nich (28, 64, 105) mają już produkt dowiązany po EAN z `decyzje_ean.csv`, więc są
technicznie odblokowane — ale wykluczenie było **decyzją merytoryczną**, nie brakiem
dowiązania, i zostaje do osobnego rozstrzygnięcia. lp28 dodatkowo: brama cenowa
(ID 31 = 17,00) potwierdziła, że to właśnie lp28, więc „dopasowanie niepewne" jest już
nieaktualne — do decyzji klienta.

lp27 i lp29 celowo **nie** zostały przestemplowane na `BRAK_EAN`: to decyzja z
uzasadnieniem, a nie mechaniczny brak dowiązania.

---

## H. Decyzje klienta 22.07.2026 (cd.) — cofnięcie wykluczeń, rozkład planu

Zasada nadrzędna podana przez klienta: **„cennik jest głównym źródłem prawdy"**. Wykluczenia
lp28/64/105 z Etapu 2 zostały tą decyzją **zastąpione** — moja wcześniejsza ochrona tych
wierszy jako „decyzji merytorycznej" opierała się na nieaktualnym ustaleniu.

Kopia stanu sprzed: `plan_cen_przed_decyzjami2207.csv`.

### H.1 Naniesione

| lp | wc_id | zmiana | netto | VAT | brutto docelowy | obecna | delta |
|---|---|---|---|---|---|---|---|
| 28 | 31 | `WYKLUCZONE → DO_IMPORTU` | 15,80 | 8% | **17,06** | 17,00 | +0,06 |
| 64 | 157 | `WYKLUCZONE → DO_IMPORTU` | 18,00 | 23% | **22,14** | 23,00 | −0,86 |
| 105 | 201 | `WYKLUCZONE → DO_IMPORTU` | 19,00 | 23% | **23,37** | 22,00 | +1,37 |
| 20 | 23 | `DO_IMPORTU → BEZ_ZMIAN` | 15,90 | 8% | 17,00 (obecna) | 17,00 | pominięta +0,17 |
| 97 | 195 | `DO_IMPORTU → BEZ_ZMIAN` | 15,60 | 8% | 17,00 (obecna) | 17,00 | pominięta −0,15 |
| 27, 29 | — | `WYKLUCZONE` (bez zmian) | — | — | — | — | klient nie zakłada produktów |
| 14 | — | poza planem | — | — | — | — | duplikat Apleplus; import wyłącznie na ID 2040 (lp6) |

Wszystkie trzy brutto docelowe policzone z cennika **zgodziły się co do grosza** z liczbami
podanymi przez klienta (asercja w skrypcie, nie porównanie na oko).

`lp20` i `lp97` to **świadome odstępstwa od cennika**, nie zgodność cen. W kolumnie
`brutto_docelowy` wpisana została cena obecna (kolumna znaczy „cena po imporcie" i to ją
weryfikuje Etap 4), a wartość z cennika i pominięta delta są zapisane w `powod`.

### H.2 Rozkład — 119 / 5 / 2 / 0 / 0, nie 122 / 2 / 2

| decyzja | wynik | oczekiwane przez klienta |
|---|---|---|
| `DO_IMPORTU` | **119** | 122 |
| `BEZ_ZMIAN` | **5** | 2 |
| `WYKLUCZONE` | **2** | 2 ✅ |
| `BRAK_EAN` | **0** | 0 ✅ |
| `BLOKADA` | **0** | 0 ✅ |
| SUMA | **126** ✅ | 126 |

Różnica to dokładnie **3 wiersze**: lp57 (Maść Borowinowa, id 141), lp65 (Maść Niedźwiedzi
Ząb, id 159), lp67 (Maść Rokitnikowa, id 163). Wszystkie trzy: netto 18,70 × 1,23 = **23,00**,
cena obecna **23,00**, delta **0,00**.

Rozbieżność jest **definicyjna, nie liczbowa** — dwa różne znaczenia `BEZ_ZMIAN`:

- **kontrakt dotychczasowy pliku** (`BEZ_ZMIAN` = cena już zgodna z cennikiem co do grosza) → 5
- **kontrakt klienta** (`BEZ_ZMIAN` = pozycja wyłączona z importu decyzją; delta 0 to nadal
  import, tylko bezruchowy) → 2, `DO_IMPORTU` 122

Kontrakt klienta jest czystszy — `DO_IMPORTU` znaczy wtedy „cennik stosowany", niezależnie
od tego, czy cena drgnie. **Nie przełączono go samodzielnie**: to zmiana semantyki kolumny
w całym pliku, do decyzji klienta. Skutek na bazie jest identyczny w obu wariantach —
lp57/65/67 dostają zapis 23,00 na 23,00 albo nie dostają nic; różnica jest wyłącznie
w etykiecie i w tym, co Etap 4 policzy jako „zaimportowane".

---

## I. KROK 2 — PRODUKCJA, 22.07.2026 (wykonany)

Autoryzacja: klient potwierdził wszystkie 36 pozycji `AUTO` (zgodność EAN z lp w cenniku,
pary ryzykowne rozdzielone poprawnie). Zakres a) c) d) e) f) g).

### I.1 (a) Backup

`/opt/gorvita-shop/_audyt/backup_prod_przed_krok2_20260722_0650.sql`
— **147 984 054 B**, **179** `CREATE TABLE`, stopka `Dump completed on 2026-07-22 6:50:12`.
Wykonany `wp db export` do `/tmp` w kontenerze + `docker compose cp` (katalog `_audyt`
nie jest zamontowany w kontenerze), `chown deploy:deploy`.

### I.2 (c) 87 zapisów SKU

Plik wsadowy `/opt/gorvita-shop/_audyt/krok2_87.csv` — 51 z `uzupelnienie_ean_PROD.csv`
+ 36 `AUTO` z `plan_ean_PROD.csv`. Bramki przed zapisem (asercje, przerwanie przy naruszeniu):

| bramka | wynik |
|---|---|
| 87 wierszy, wszystkie `DO_ZAPISU` | ✅ |
| każdy EAN dokładnie 13 cyfr | ✅ |
| 0 duplikatów EAN, 0 duplikatów `wc_id` | ✅ |
| 0 kolizji z SKU istniejącym na innym produkcie | ✅ |
| każdy klucz rozwiązywalny; SKU wskazuje na ten sam ID co plan | ✅ |
| `AUTO`: SKU w bazie == `wc_sku_obecny` z planu | ✅ |

Źródła: `KANDYDAT_1` 48, `BEZ_SKU` 3 (kluczowane po `wc_id`: 1550, 1549, 1486), `AUTO` 36.
Plan miał 38 `AUTO`; dwa odpadły jako już objęte decyzjami (1549 LUTEINA, 1486 LIBIDO).

Metoda: `WC_Product::set_sku()` + `save()`, nigdy `UPDATE` na `wp_postmeta`. Każdy zapis
weryfikowany odczytem po zapisie (`get_sku()` po `wc_get_product()`).

**Próba na sucho: ok 87 / pominięte 0 / błędy 0. Zapis: ok 87 / pominięte 0 / błędy 0.**

### I.3 (d) Naprawa lookup

Pomiar **po** 87 zapisach: nadal dokładnie **7 rozjazdów SKU** i **3 cenowe** — te same, co
przed. **87 zapisów nie dołożyło ani jednego rozjazdu**, co potwierdza wniosek z F.3:
`set_sku()` + `save()` utrzymuje `wp_wc_product_meta_lookup` w spójności. Łączny dowód
mechanizmu: 37 (Etap 1b) + 50 (KROK 1) + 87 (KROK 2) = **174 zapisy, 0 rozjazdów**.

Naprawione refleksją (`update_lookup_table()` jest `protected`, a `WC_Data_Store::__call()`
zwraca `null` bez błędu — wywołanie wprost wygląda na udane i nic nie robi):

| ID | produkt | SKU lookup przed | cena `min_price` przed | po |
|---|---|---|---|---|
| 25 | CARBOsal syrop 100ml | `GV-135` | — | ✅ |
| 29 | Chrom Forte | `GV-139` | — | ✅ |
| 47 | Energia 30 kapsułek | `GV-142` | — | ✅ |
| 103 | Balsam Koński 250 ml | `GV-52` | 24,00 vs `_price` 20,00 | ✅ 20,00 |
| 111 | Końska Maść 250 ml | `GV-58` | 20,00 vs `_price` 21,00 | ✅ 21,00 |
| 187 | Panthenol Żel 100 ml | `GV-68` | 21,00 vs `_price` 17,00 | ✅ 17,00 |
| 228 | Aurix 35 ml | `GV-34` | — | ✅ |

`update_lookup_table()` przelicza cały wiersz, więc jedno wywołanie naprawiło SKU i cenę
naraz. **naprawione 7 / nadal rozjazd 0.**

Skutek biznesowy: te 7 produktów było nieodnajdywalnych po EAN w wyszukiwarce panelu,
a 103 i 187 pokazywały się w filtrach cenowych zawyżone o 4 zł.

### I.4 (e) Recheck całej bazy

Niezależnym zapytaniem po naprawie, na wszystkich produktach (nie tylko zapisanych):

| kontrola | wynik |
|---|---|
| produktów | **128** |
| rozjazdy SKU postmeta ↔ lookup | **0** ✅ |
| rozjazdy cenowe `_price` ↔ `min_price` | **0** ✅ |
| duplikaty SKU w całej bazie | **0** ✅ |
| pokrycie EAN-13 jako SKU | **124 / 128 (97%)** |

Cztery produkty bez EAN-13 — dokładnie znany zbiór, zero niespodzianek:
`#14` GV-133 (duplikat Apleplus), `#11586` GV-151 (gojnik spoza cennika),
`#1058`/`#1059` (placeholdery B2BKing, `draft`).

⚠ Zapytanie wykazało też, że `#1058` i `#1059` **nie mają wiersza w `wc_product_meta_lookup`**.
Nie jest to skutek KROKU 2: te same dwa placeholdery nie mają wiersza również na stagingu
(sprawdzone), a żaden z 87 zapisów ich nie dotyczył. To stan pierwotny B2BKing, bez wpływu
na sklep — oba są `draft` i bez SKU.

### I.5 (f) Cache

`wp cache flush` (Redis) → `rocket_clean_domain()` + `rocket_clean_minify()` (WP Rocket).
Oba OK. Kontrola po flushu — trzy zapisy odczytane na nowo: `#97` → 5907636994831,
`#1550` → 5903317643296, `#101` → 5907636994664.

### I.6 Pokrycie po KROKU 2

| moment | pokrycie EAN na PROD |
|---|---|
| przed Etapem 1 | 37 / 128 (29%) |
| po 51 decyzjach | 88 / 128 (69%) |
| **po KROKU 2 (87 zapisów)** | **124 / 128 (97%)** |

Bez pokrycia zostają wyłącznie pozycje bez odpowiednika: lp27 i lp29 (warianty Chromu,
klient nie zakłada produktów) oraz cztery produkty spoza cennika wymienione w I.4.

### I.7 Rollback

`wp db import /opt/gorvita-shop/_audyt/backup_prod_przed_krok2_20260722_0650.sql`
(po `docker compose cp` do kontenera), następnie flush Redis + WP Rocket.
Cofa łącznie 87 zapisów SKU i naprawę lookup 7 wierszy.

---

## J. ETAP 3 — import cen na STAGINGU, 22.07.2026

Poprzedzone przełączeniem semantyki `BEZ_ZMIAN` (decyzja klienta): `BEZ_ZMIAN` = pozycja
wyłączona z importu **decyzją**, nie „delta 0". lp57/65/67 → `DO_IMPORTU`.
Rozkład **122 / 2 / 2 / 0 / 0**, suma 126. Kopia sprzed: `plan_cen_przed_semantyka.csv`.

Backup: `backup_stg_przed_etap3_20260722_0703.sql` — 39 573 782 B, 179 tabel,
`Dump completed on 2026-07-22 7:03:33`.

Import: `WC_Product::set_regular_price()` + `save()`, kluczowanie po SKU (EAN-13).
Bramka per wiersz: SKU rozwiązywalny → `wc_id` zgodny z planem → **cena w bazie zgodna
z `brutto_obecny` z planu** (rozjazd = przerwanie). Weryfikacja odczytem po każdym zapisie:
cena docelowa, `_sale_price` niezmieniona, `tax_class` niezmieniona.

**Próba na sucho: 122 ok / 0 błędów. Import: 122 ok / 0 błędów / 119 realnych zmian ceny.**
(Trzy bez ruchu to lp57/65/67 — delta 0,00.)

`wc_id 209` — jedna operacja: `set_tax_class('')` + `set_regular_price('10.95')` + `save()`.
Bramka wstępna sprawdziła, że klasa przed zmianą to `reduced-rate` i że cel wynosi 10,95.

Lookup przeliczony refleksją dla wszystkich 122: **zgodnych 122 / rozjazdów 0**.
Cache: Redis → WP Rocket, oba OK.

`_gspb_post_css` przed i po: **5 wierszy, 48 966 B, md5 `664ce2b22039069c62ee391b08433d5e`
— identyczne.** Nietknięte.

### J.1 Ceny promocyjne — nietknięte, ale było ich 4, nie 2

Klient wymienił lp76 (Mosqitos) i lp81 (Olejek Pichtowy). Aktywnych promocji jest **cztery**:

| ID | lp | reg przed | reg po | `_sale_price` | promocja vs nowa reg |
|---|---|---|---|---|---|
| 95 | 3 | 22,00 | 22,39 | 20,15 | −10,0% |
| 165 | 68 | 25,00 | 13,90 | 12,51 | −10,0% |
| 179 | 76 | 16,00 | 16,24 | 14,62 | −10,0% |
| 183 | 81 | 15,00 | 15,01 | 13,51 | −10,0% |

`_sale_price` niezmienione — te same `meta_id`, te same wartości co w migawce planu sprzed
importu. **Żadna promocja nie przekroczyła ceny regularnej** (kontrola: 0 przypadków
`sale >= regular`), więc obniżka lp68 z 25,00 na 13,90 nie zepsuła promocji.

Obserwacja: wszystkie cztery promocje to dokładnie **−10% od ceny wyliczonej z cennika**,
a nie od ceny, która stała w WC. Ktoś ustawiał je wcześniej na podstawie cennika, podczas
gdy ceny regularne w WC były nieaktualne. To niezależne potwierdzenie, że cele z cennika
są poprawne.

Skutek uboczny wart odnotowania: dla #165 cena płacona przez klienta **nie zmieniła się**
(12,51 przed i po) — zmieniła się tylko cena przekreślona, z 25,00 na 13,90. Promocja
„−50%" była fikcją wynikającą z nieaktualnej ceny regularnej; teraz pokazuje prawdziwe −10%.

---

## K. ETAP 4 — weryfikacja (STAGING)

| sekcja | zakres | wynik |
|---|---|---|
| **A** | netto odtworzone (`_regular_price / (1+stawka)`) == netto z cennika, co do grosza, 122 poz. | **122/122 = 100%** ✅ |
| **B** | rabat −18%: netto odtworzone × 0,82 vs netto cennik × 0,82 | **122/122** ✅ |
| **C** | end-to-end na 5 produktach, gość i B2B, realny render WooCommerce | **5/5, obie role** ✅ |
| **D** | brak cen 0 / pustych / ujemnych | **0 naruszeń** ✅ |
| **E** | liczba produktów = 128 | **128** ✅ |
| **F** | postmeta ↔ lookup (`_regular_price`/`_price` vs `min_price`/`max_price`, brak wiersza = błąd) | **0 rozjazdów, 0 braków** ✅ |
| **G** | `wc_id 209` punktowo | **7/7** ✅ |

Sekcja A dała 100%, więc rollback nie był potrzebny.

### K.1 Sekcja C — metoda i wyniki

Test przez **HTTP na żywym stagingu**, nie przez `wp eval` — filtry rabatowe B2BKing nie
rejestrują się w kontekście WP-CLI (`user=0` przed `init`, `--user` nie pomaga), więc
przeliczenie w CLI byłoby niewiarygodne. Sesja B2B zbudowana ciasteczkiem
`wp_generate_auth_cookie()` dla użytkownika **#9 `test-b2b-18`, grupa B2BKing 1073**;
sesja potwierdzona odczytem `/moje-konto/` (widoczne „Kokpit", „Wyloguj", login).

| ID | dobór | GOŚĆ | B2B #9 (grupa 1073) | kontrola |
|---|---|---|---|---|
| 12 | 8% | 21,06 zł z VAT | **15,99 zł bez VAT** | 21,06/1,08 = 19,50 × 0,82 = 15,99 ✅ |
| 216 | 23% | 22,76 zł z VAT | **15,17 zł bez VAT** | 22,76/1,23 = 18,50 × 0,82 = 15,17 ✅ |
| 165 | największa delta + promocja | 13,90 → **12,51** | **9,27 zł bez VAT** | 13,90/1,23 = 11,30 × 0,82 = 9,27 ✅ |
| 209 | wyjątek klienta | 10,95 zł z VAT | **7,30 zł bez VAT** | 8,90 × 0,82 = 7,30 ✅ |
| 183 | aktywna promocja | 15,01 → **13,51** | **11,40 zł bez VAT** | 15,01/1,08 = 13,90 × 0,82 = 11,40 ✅ |

Wynik G potwierdzony niezależnie, end-to-end: **#209 gość 10,95 brutto, B2B 7,30 netto.**

Potwierdzone przy okazji zachowanie B2BKing: rabat „everywhere" liczy się od ceny
**regularnej i ignoruje promocję** (#165 i #183 — B2B dostaje 9,27 i 11,40 netto liczone
z regularnej, nie z promocyjnej). B2B i tak płaci mniej niż gość w obu przypadkach
(11,40 vs 12,51 brutto oraz 12,31 vs 13,51 brutto), więc nie ma inwersji cen.

### K.2 Zmiany cen

5 największych zmian ceny brutto:

| ID | lp | przed | po | delta | produkt |
|---|---|---|---|---|---|
| 165 | 68 | 25,00 | 13,90 | **−11,10** | Maść Rumiankowa z olejem CBD 5% 80 ml |
| 37 | 31 | 51,00 | 53,46 | **+2,46** | Colacal — kolagen z wapniem 60 kaps. |
| 209 | 115 | 9,00 | 10,95 | **+1,95** | Żel łagodzący po ukąszeniach owadów 20ml |
| 43 | 34 | 53,00 | 51,30 | **−1,70** | Colahial 60 kapsułek |
| 201 | 105 | 22,00 | 23,37 | **+1,37** | Venal Żel 150 ml |

**Suma bezwzględna zmian: 49,10 zł na 119 pozycjach** — średnio 0,41 zł na produkt.
Poza pierwszą pozycją cały import to korekta groszowa; 11,10 zł z 49,10 (23%) przypada
na jeden produkt.

### K.3 Rollback

`wp db import /opt/gorvita-staging/_audyt/backup_stg_przed_etap3_20260722_0703.sql`
+ flush Redis i WP Rocket. Cofa 122 zapisy cen, zmianę klasy #209 i przeliczenie lookup.

---

## L. Uzupełnienia przed akceptacją Etapu 4 (22.07.2026)

### L.1 Kontrola widoku gościa — poprawna

Świeża sesja HTTP, pusty słoik ciasteczek, cztery strony: `/`, `/sklep/`,
`/product/acerola-500mg-60-kapsulek/`, `/product/zel-lagodzacy-po-ukaszeniach-owadow-20ml/`.

| kontrola | wynik |
|---|---|
| wystąpień „bez VAT" | **0** na wszystkich stronach ✅ |
| wystąpień „z VAT" | 17 / 10 / 12 / 10 ✅ |
| znaczników B2BKing w HTML (`b2bking_price`, `Cena B2B`) | **0** ✅ |
| kwot renderowanych łącznie | 48 |
| kwot niebędących ceną brutto lub promocyjną z bazy | **0** ✅ |
| kwot równych cenie po rabacie B2B | **0** ✅ |

Gość widzi wyłącznie ceny brutto z VAT. Zero wycieku cen B2B, także na stronie sklepu
i głównej (gdzie ceny renderują się przez Greenshift/Blocksy, poza blokiem `.price`).
Uwaga metodyczna: symbol waluty jest kodowany encjami `&#122;&#322;`, więc naiwny wzorzec
`\d+,\d{2}\s*zł` nic nie znajduje na listingach — trzeba parsować
`woocommerce-Price-amount amount"><bdi>`.

### L.2 Sekcja B przeliczona na renderze — **29 rozjazdów, nie 23**

Poprzednia sekcja B rzeczywiście miała lukę (porównywała netto **zaokrąglone** × 0,82).
Pomiar zrobiony od nowa: pobrane **122 strony produktowe** jako użytkownik #9 `test-b2b-18`
(grupa 1073), sesja na `wp_generate_auth_cookie()`, wszystkie 122 potwierdzone jako render
B2B. Porównanie: **cena faktycznie wyrenderowana** vs `netto_cennik × 0,82`.

**Wynik: 93 zgodne, 29 rozjazdów. Kierunek: +1 gr — 12, −1 gr — 17, większych brak.**

#### Mechanizm jest o jeden krok głębszy, niż zakładała diagnoza

| model | trafień na 122 |
|---|---|
| A — `netto_cennik × 0,82` | 93 |
| B — `netto NIEZAOKRĄGLONE × 0,82` (diagnoza z wiadomości) | 106 |
| **C — `round(brutto × 0,82) / (1+stawka)`** | **122/122** ✅ |

B2BKing nakłada −18% na **cenę brutto**, zaokrągla wynik do grosza, i dopiero ten
zaokrąglony brutto przelicza na netto do wyświetlenia. Są więc **dwa** zaokrąglenia, nie
jedno — stąd 6 pozycji różnicy w saldzie.

Przykład rozstrzygający, lp9 ARTREVIT (brutto 34,13, 8%):
- model B: 34,13 / 1,08 = 31,60185 × 0,82 = 25,9135 → **25,91**
- model C: round(34,13 × 0,82) = 27,99 → 27,99 / 1,08 = 25,9167 → **25,92** ← tak renderuje sklep

Obie kontrole punktowe podane w zleceniu (lp70 → 9,67 zamiast 9,68; lp55 → 10,42 zamiast
10,41) wychodzą **tak samo w modelu B i C**, więc nie mogły tej różnicy ujawnić.

#### Rozliczenie 29 vs 23

| zbiór | liczność |
|---|---|
| rozjazdy zmierzone na renderze | **29** |
| rozjazdy przewidziane modelem B | 23 |
| część wspólna | 18 |
| **tylko render** — model przeoczył | **11** |
| **tylko model** — fałszywy alarm, render zgodny | **5** |

11 przeoczonych: lp8, lp9, lp14, lp44, lp49, lp66, lp75, lp86, lp100, lp104, lp110.
5 fałszywych alarmów: lp22, lp23, lp24, lp42, lp108 — tam render trafia w `netto_cennik × 0,82`
co do grosza, mimo że model B wieszczył odchyłkę.

Pełna lista z kierunkiem, cenami i znacznikiem widoczności w modelu B:
`/opt/gorvita-staging/_audyt/etap4_sekcjaB_rozjazdy.csv` (29 wierszy).

#### Ocena

Nie jest to powód do rollbacku — potwierdzam. Odchyłka wynika wyłącznie z podwójnego
zaokrąglenia po stronie B2BKing i wynosi maksymalnie **1 grosz** na pozycji; sekcja A
(netto odtworzone z ceny regularnej) pozostaje **100%**, więc same ceny w bazie są
bezbłędne. Skutek dotyczy wyłącznie kwoty **wyświetlanej** klientowi B2B i faktury.
Łączne saldo na 122 pozycjach: 12 × (+1 gr) i 17 × (−1 gr) = **−5 gr**.

Lista jest udokumentowana przed wdrożeniem na produkcję, zgodnie z poleceniem.
