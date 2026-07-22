# PLAN — przełączenie PRODUKCJI na ceny netto

**Status: PLAN. Zero zapisów.** Sporządzony 2026-07-22 z katalogu produkcyjnego
(`sklep.gorvita.pl`, `/opt/gorvita-shop`). Zbudowany od nowa — ze stagingu przeniesiona
wyłącznie metoda.

Stan wyjściowy produkcji: `prices_include_tax=yes`, `_regular_price` to **brutto
zaokrąglone do pełnych złotych**. Jedna operacja robi więc dwie rzeczy naraz: **korektę
cen z cennika** ORAZ **przełączenie podstawy**. Plus jedną trzecią — patrz niżej.

Dane szczegółowe: `plan_netto_PROD.csv` (126 wierszy `regular` + 4 `sale`).

---

## ⚠ Dwie różnice wobec stagingu, wykryte przy budowie planu

### 1. `#209` — na produkcji trzeba TAKŻE zmienić klasę podatkową

Porównanie klas podatkowych po SKU (126 produktów) dało **dokładnie jedną rozbieżność**:

| SKU | PROD | STAGING |
|---|---|---|
| 5903317643227 Żel łagodzący | `reduced-rate` (8%) | `(standard)` (23%) |

Decyzja klienta z 22.07 (VAT 23%, cena 10,95) została wykonana na stagingu, na produkcji
nie. Bez tej zmiany netto 8,90 przy 8% dałoby brutto **9,61** zamiast **10,95**.

**Operacja produkcyjna ma więc trzy elementy, nie dwa:** `prices_include_tax`, ceny,
oraz `set_tax_class('')` dla `#209` — wszystko w jednym `save()` na tym produkcie.

### 2. `#1059 „Credit"` ma na produkcji cenę, na stagingu nie

Placeholder B2BKing (`b2bking_credit_product_id_setting`), `draft`, `hidden`,
`_regular_price = 1`, `tax_class = zero-rate`. Przy 0% VAT konwersja jest **no-op**
(1 netto = 1 brutto), więc pozycja zostaje **poza zakresem** — świadomie, nie przez
przeoczenie. `#1058 „Offer"` nie ma `_regular_price`.

---

## a) Ceny promocyjne na produkcji — 4 pozycje

**Zestaw jest inny niż na stagingu.** Nie założyłem czterech ze stagingu — wyliczyłem z bazy produkcyjnej.

| ID | SKU | produkt | VAT | sale brutto | sale → netto | nowa reg. netto | sale < reg |
|---|---|---|---|---|---|---|---|
| 95 | 5907636994367 | AloeVera Żel 150 ml | 23% | 20.15 | **16.38** | 18.20 | ✅ |
| 179 | 5903317643241 | Mosqitos Zestaw Płyn 20ml i Żel 20ml | 23% | 14.62 | **11.89** | 13.20 | ✅ |
| 183 | 5907636994329 | Olejek Pichtowy 100 ml | 8% | 13.51 | **12.51** | 13.90 | ✅ |
| 11586 | GV-151 | Maść żywokostowa z gojnikiem i olejem CB | 23% | 22.25 | **18.09** | 20.10 | ✅ |

Promocje STAGING: `95, 165, 179, 183` · Promocje PROD: `95, 179, 183, 11586`
→ `#165` ma promocję tylko na stagingu, `#11586` (gojnik) tylko na produkcji.

**Kontrola `sale >= nowa cena regularna`: 0 przypadków.** Warunek zatrzymania nie zaszedł.

---

## b) 15 największych zmian ceny brutto

| wc_id | SKU | produkt | VAT | obecna | nowa | delta |
|---|---|---|---|---|---|---|
| 37 | 8594011210012 | Colacal - kolagen z wapniem 60 kapsułek | 8% | 51 | **53.46** | **+2.46** |
| 209 | 5903317643227 | Żel łagodzący po ukąszeniach owadów 20ml | 23% | 9 | **10.95** | **+1.95** |
| 43 | 5907636994817 | Colahial 60 kapsułek | 8% | 53 | **51.30** | **-1.70** |
| 201 | 5907636994336 | Venal Żel 150 ml | 23% | 22 | **23.37** | **+1.37** |
| 157 | 5907636994398 | Maść Nagietkowa 130ml | 23% | 23 | **22.14** | **-0.86** |
| 234 | 5907636994787 | Pneumovit Zatoki 50 ml | 8% | 17 | **17.50** | **+0.50** |
| 218 | 5907636994640 | Żel ze świetlikiem i kolagenem 20 ml | 23% | 15 | **15.50** | **+0.50** |
| 2069 | 5907636994879 | Maść Witaminowa 50ml | 23% | 15 | **14.51** | **-0.49** |
| 181 | 5907636994930 | Olej Kokosowy w żelu 200 ml | 23% | 22 | **21.53** | **-0.47** |
| 2054 | 5903317643210 | Magnez B6 + Ashwagandha 60 kapsułek | 8% | 24 | **23.54** | **-0.46** |
| 111 | 5907636994503 | Końska Maść 250 ml | 23% | 21 | **20.54** | **-0.46** |
| 101 | 5907636994664 | Balsam do ust z witaminami A,E,F 20 ml | 23% | 13 | **12.55** | **-0.45** |
| 2048 | 5907636994060 | Carbocaps 30 kapsułek | 8% | 12 | **11.56** | **-0.44** |
| 75 | 5903317643067 | OSTROPEST 60 kapsułek | 8% | 15 | **15.44** | **+0.44** |
| 81 | 5907636994015 | Silicum + H ( KRZEM+ BIOTYNA ) 30 tabletek | 8% | 19 | **19.44** | **+0.44** |

---

## c) Suma i średnia

| miara | wartość |
|---|---|
| pozycji w planie | **126** |
| realnie zmieniających cenę | **119** |
| **suma bezwzględna zmian** | **38.10 zł** |
| średnia na produkt (126) | **0.30 zł** |
| średnia na produkt zmieniany | 0.32 zł |
| w górę | 63 poz., +20.83 zł |
| w dół | 56 poz., -17.27 zł |
| saldo netto | **+3.56 zł** |

---

## d) Zmiana brutto powyżej 1 zł

**4 produkty na 126** (3%). Powyżej 5 zł: **0**.

| wc_id | produkt | obecna | nowa | delta |
|---|---|---|---|---|
| 37 | Colacal - kolagen z wapniem 60 kapsułek | 51 | 53.46 | **+2.46** |
| 209 | Żel łagodzący po ukąszeniach owadów 20ml | 9 | 10.95 | **+1.95** |
| 43 | Colahial 60 kapsułek | 53 | 51.30 | **-1.70** |
| 201 | Venal Żel 150 ml | 22 | 23.37 | **+1.37** |

Rozkład wielkości zmiany: bez zmian **7** · 0,01–0,50 zł **114** · 0,51–1,00 zł **1** ·
1,01–5,00 zł **4**. Czyli 90% katalogu to korekta groszowa.

---

## e) Porównanie zakresu PROD vs STAGING

| kategoria | STAGING | PROD | |
|---|---|---|---|
| produktów w katalogu | 128 | 128 | ✅ |
| `_regular_price` do konwersji | 126 | **126** | ✅ |
| — netto z cennika 1:1 | 122 | **122** | ✅ |
| — netto z ceny obecnej | 4 | **4** | ✅ |
| `_sale_price` do konwersji | 4 | **4** | ✅ (inny skład) |
| zmiana `tax_class` w tej samej operacji | 0 | **1** | ⚠ `#209` |
| bez ceny / poza zakresem | 2 | **1** | ⚠ `#1059` ma cenę |
| suma bezwzględna zmian | 49,10 zł | **38.10 zł** | — |
| pozycji zmieniających cenę | 119 | **119** | ✅ |

Liczby w każdej kategorii się zgadzają **poza dwiema**, obie opisane wyżej i obie ujęte
w planie. Niższa suma zmian na produkcji (38.10 vs 49,10 zł) bierze się stąd, że
staging przeszedł już wcześniej import cen — na produkcji część różnic to inne pozycje,
m.in. `#165` (na stagingu −11,10 zł, na produkcji bez promocji i z inną ceną wyjściową).

---

## Zakres operacji produkcyjnej — podsumowanie

1. `prices_include_tax`: `yes` → `no`
2. `_regular_price` = netto dla **126** produktów
3. `_sale_price` = netto dla **4** promocji (#95, #179, #183, #11586)
4. `set_tax_class('')` dla **`#209`** — w tym samym `save()` co jego cena
5. przeliczenie lookup dla 126
6. cache: Redis → WP Rocket

Poza zakresem: `#1058` (brak ceny), `#1059` (zero-rate, konwersja no-op),
lp27 i lp29 (brak produktu w WC).

Kroki 1–4 muszą być atomiczne. Backup przed. Błąd w połowie → rollback z dumpu.

---

# FAZA 2 — WYKONANIE, 2026-07-22, PRODUKCJA

Backup: `_audyt/backup_przed_netto_PROD_20260722_0829.sql` — 149 000 004 B, 179 tabel.

## ⚠ Pierwsza próba przerwana i wycofana

Operacja padła w połowie: ustawienie i 130 cen zapisane, **zmiana klasy `#209` nie doszła**.
Strażnik `zmian klasy 0 != 1` przerwał wykonanie przed przeliczeniem lookup.

**Przyczyna — błąd w moim generatorze pliku celów:**

```python
zmien_klase and kl or '-'      # 'TAK' and '' or '-'  →  '-'
```

Klasą docelową dla `#209` jest **pusty string** (= standard). Idiom `a and b or c` traktuje
pusty string jako fałsz i podstawia `c`, więc do pliku trafiło „nie zmieniaj klasy".

**Rollback wykonany z dumpu** (zgodnie z zasadą: nie łatać pojedynczych pozycji).
Weryfikacja rollbacku: katalog produkcyjny **bajt w bajt identyczny** ze zrzutem sprzed
operacji (128 produktów, ceny i klasy), `prices_include_tax=yes`, 0 rozjazdów lookup,
13 919 zamówień nienaruszonych, gość przez HTTP: Acerola 21,00 zł z VAT.

**Dwie poprawki przed powtórzeniem:**
1. marker `ZMIEN:<klasa>` zamiast wartości mogącej być pustym stringiem, plus samokontrola
   pliku po zapisie (odczyt z powrotem i asercja: dokładnie 1 marker, na `#209`, cel `''`);
2. **bramka przeniesiona przed zapis** — liczba deklarowanych zmian klasy weryfikowana
   z pliku, zanim cokolwiek pójdzie do bazy. Poprzednio licznik sprawdzał się dopiero
   po pętli, czyli po zapisie 130 wartości.

## Przebieg właściwy

```
bramka wstepna: 130/130 zgodne | zmian klasy w pliku: 1 | #1058/#1059 nieobecne
1. prices_include_tax -> no
3. #209 tax_class -> (standard) (w tym samym save() co cena)
2. zapisano 130 wartosci na 126 produktach | zmian klasy: 1
4. lookup przeliczony: 126 | rozjazdy: 0
```

Cache: Redis → WP Rocket.

## Weryfikacja

Pierwsza kontrola: gość HTTP, świeża sesja — **Acerola 21,06 zł z VAT** ✅, 0 wystąpień
„bez VAT". Rollback niepotrzebny.

| sekcja | wynik |
|---|---|
| A — netto w bazie == netto z cennika | **122/122 = 100%** ✅ |
| **B — render dla grupy 1073 == netto_cennik × 0,82** | **122/122, 0 rozjazdów** ✅ **KRYTERIUM SPEŁNIONE** |
| C — end-to-end, gość i B2B, HTTP | 5/5 ✅ |
| D — ceny 0 / puste / ujemne | 0 ✅ |
| E — liczba produktów | 128 ✅ |
| F — postmeta ↔ lookup | 0 naruszeń, 0 braków poza #1058/#1059 ✅ |
| G — `#209` | 6/6 ✅ |

### Sekcja C

| ID | dobór | GOŚĆ | B2B (grupa 1073, user #21) |
|---|---|---|---|
| 12 | 8% | 21,06 z VAT | 15,99 bez VAT |
| 216 | 23% | 22,76 z VAT | 15,17 bez VAT |
| **209** | **zmiana klasy 8%→23%** | **10,95 z VAT** | **7,30 bez VAT** |
| 183 | promocja 8% | 15,01 → 13,51 | 11,40 bez VAT |
| 11586 | promocja 23% | 24,72 → 22,25 | 16,48 bez VAT |

### Promocje — `sale < regular`

| ID | sale netto | reg netto | brutto sale | |
|---|---|---|---|---|
| 95 | 16,38 | 18,20 | 20,15 | ✅ |
| 179 | 11,89 | 13,20 | 14,62 | ✅ |
| 183 | 12,51 | 13,90 | 13,51 | ✅ |
| 11586 | 18,09 | 20,10 | 22,25 | ✅ |

### Niezmienniki

`tax_display_shop=incl` · `tax_display_cart=incl` · reguła 2367 `showtax=yes` ·
reguła 1760 `howmuch=18`, `everywhere=1` · `_gspb_post_css` 5 wierszy / 48 952 B /
md5 `691149b435813d9b4d1ba234e8df1ec2` · 13 919 zamówień.

**Klasy podatkowe — porównanie 128 produktów przed vs po: zmieniona dokładnie jedna,
`#209 reduced-rate → standard`**, zgodnie z decyzją klienta. Żadna inna nie drgnęła.

`#1058` (brak ceny) i `#1059` (`reg=1`, `zero-rate`) nietknięte — poza zakresem.

---

# TEST KOSZYKA I FAKTURY NA PRODUKCJI — 2026-07-22 (wykonany i posprzątany)

## Rozpoznanie przed testem

| | ustalenie |
|---|---|
| **a) licznik faktur** | `wp_wcpdf_invoice_number`, 42 wiersze, `AUTO_INCREMENT=43`. **Numer faktury == `id` wiersza** (zweryfikowane: #16236→34, #16242→36). Przywracalny przez `DELETE` + `ALTER TABLE … AUTO_INCREMENT`. |
| **b) e-maile** | 4 aktywne, `New_Order` → **sklep@gorvita.pl**, FluentSMTP/Resend. Wyłączone tymczasowym mu-pluginem na `pre_wp_mail`, **bramkowanym opcją** `gorvita_blokada_poczty` — bez flagi plik bezczynny. |
| **c) płatności** | 7 bramek PayU + `bacs` (Przelew bankowy) + `cod`. Użyto **`bacs`** — offline, dodatkowo ustawia `on-hold`. |
| **d) InPost** | `easypack_create_shipment_automatically = no`, ale środowisko `production` z żywym tokenem. |

⚠ **Trop, który mógł zmylić przy (d):** w `EasyPack.php:1826` bezpośrednie wywołanie jest
zakomentowane — ale tuż pod nim stoi `wp_schedule_single_event(..., 'send_shipment_automatically')`,
robiące to samo przez cron. **Jedyną realną barierą jest opcja.** Nie ruszano jej, a zamówienie
trzymano w `on-hold` (poza `processing`/`completed`), więc warunek i tak nie zachodził.

⚠ **Na produkcji nie ma konta testowego.** Wszystkie 7 kont w grupie 1073 to realni kontrahenci.
Zamiast podpinać test pod historię prawdziwego klienta, założono tymczasowe konto
`gorvita-test-netto` (uid 42) z identyczną konfiguracją B2BKing — i usunięto po teście.

## Przebieg — HTTP, Store API

Checkout na produkcji jest **blokowy** (`wp:woocommerce/checkout`), nie klasyczny —
`[woocommerce_checkout]` z CLAUDE.md jest nieaktualny. Ścieżką HTTP jest Store API.
Wysyłkę przestawiono na `flat_rate:10` (Poczta Polska), żeby całkowicie ominąć InPost.
NIP jest polem wymaganym (B2BKing 1759) — podano poprawny mod-11 `1234563218`.

**Zamówienie #16272, status `on-hold`, płatność Przelew bankowy.**

## Wyniki

| ID | qty | netto/szt | oczekiwane `netto_cennik × 0,82` | netto linii | VAT linii | brutto |
|---|---|---|---|---|---|---|
| 12 (8%) | 1 | **15,99** | 19,50 × 0,82 ✅ | 15,99 | 1,28 | 17,27 |
| 216 (23%) | 1 | **15,17** | 18,50 × 0,82 ✅ | 15,17 | 3,49 | 18,66 |
| 183 (promocja 8%) | 1 | **11,40** | 13,90 × 0,82 ✅ | 11,40 | 0,91 | 12,31 |
| 209 | 1 | **7,30** | 8,90 × 0,82 ✅ | 7,30 | 1,68 | 8,98 |
| **173** | **10** | **15,58** | 19,00 × 0,82 ✅ | **155,80** | **35,83** | 191,63 |

**Linia 10-sztukowa: `15,58 × 10 = 155,80` netto bez reszty**, VAT 23% = 35,83, brutto 191,63.

**Mechanizm rabatu: WLICZONY W CENĘ (wariant B)** — `FEE: 0`.

VAT per stawka: 8% → 2,19 · 23% → 43,76 (w tym 2,76 od wysyłki) · razem **45,95**.
Brutto pozycji **248,85** < 250 → **wysyłka płatna** 12,00 + 2,76, próg zadziałał poprawnie.
Razem **263,61**.

Faktura nr **43**, szablon `theme/gorvita`: Razem netto 217,66 · VAT 8% 2,19 · VAT 23% 43,76 ·
Razem VAT 45,95 · **Razem brutto 263,61**.

**InPost: 0 zaplanowanych zadań `*shipment*`, brak meta `_shipx_shipment_object`.** ✅

**Zablokowane e-maile (3):** test blokady, `[Gorvita Sklep]: Masz nowe zamówienie: #16272`
→ **sklep@gorvita.pl**, oraz potwierdzenie do klienta. Żaden nie wyszedł na zewnątrz.

## Sprzątanie i kontrola końcowa

| | |
|---|---|
| zamówienie #16272 | ✅ usunięte |
| licznik faktur | ✅ `AUTO_INCREMENT=43` (jak przed testem), numery 1–42 komplet, **luk brak** |
| konto `gorvita-test-netto` | ✅ usunięte |
| mu-plugin blokady poczty | ✅ flaga i log skasowane, plik usunięty |
| zamówień w bazie | **13 919** ✅ |
| ceny / klasy / lookup | ✅ **bajt w bajt identyczne** ze stanem po FAZIE 2 |
| gość HTTP | Acerola **21,06 zł z VAT** ✅ |
