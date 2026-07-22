# Audyt zgodności katalogu WooCommerce z cennikiem producenta

**Data:** 2026-07-21
**Zakres:** produkcja `sklep.gorvita.pl` (`/opt/gorvita-shop`) — **wyłącznie odczyt**.
**Cennik:** `cennik_2025-06-01.csv`, 126 pozycji, kolumna „Cena netto 100%" jako źródło prawdy.

> **Uwaga o źródle cennika:** oryginalny `cennik_2025-06-01.xls` nie istniał na serwerze
> (`find / -iname "*.xls"` → 0 wyników). Dane pochodzą z wersji CSV dostarczonej
> bezpośrednio w rozmowie i zapisanej jako `_audyt/cennik_2025-06-01.csv`. Przed importem
> cen warto potwierdzić, że to ta sama wersja co arkusz producenta.

---

## Podsumowanie wykonawcze

| Metryka | Wynik |
|---|---|
| Pozycji w cenniku | 126 |
| Produktów w WooCommerce (bez placeholderów B2BKing) | 126 |
| Dopasowane **pewnie** (EAN) | **37** |
| Dopasowane **niepewnie** (nazwa fuzzy) | **87** |
| Niedopasowane — cennik | **2** |
| Niedopasowane — WooCommerce | **2** |
| **Niezgodności VAT** | **0** potwierdzonych + **1 do wyjaśnienia** (lp115) |
| Ceny rozjechane > 1 gr (netto) | **121 ze 124** |
| …z tego **realne** rozjazdy (> 0,60 zł brutto) | **4** |
| …z tego artefakt zaokrąglenia do pełnych złotych | **119** |

**Trzy rzeczy warte uwagi przed decyzją o architekturze cenowej:**

1. 🔴 **Colacal ↔ Colahial mają zamienione ceny** (sekcja E) — jedyny błąd cenowy o dużej skali.
2. 🟠 **lp115 „Żel łagodzący po ukąszeniach" ma w WC VAT 8%, a wygląda na kosmetyk 23%** (sekcja D).
3. 🟠 **Apleplus C,E istnieje w WC dwa razy** — ID 14 i ID 2040 (sekcja C).

Poza tym katalog jest w bardzo dobrym stanie: zero niezgodności VAT wśród 123
porównywalnych pozycji, a 119 ze 123 cen zgadza się z cennikiem w granicach
zaokrąglenia do pełnego złotego.

---

## A. Gdzie mieszka EAN + jakość dopasowania

### Gdzie mieszka EAN

Sprawdzono 5 produktów (ID 11586, 2071, 2070, 2069, 2068) pełnym zrzutem `wp_postmeta`
oraz agregatem po wszystkich meta_key produktów:

```sql
SELECT meta_key, COUNT(*) FROM wp_postmeta pm JOIN wp_posts p ON p.ID=pm.post_id
WHERE p.post_type IN ('product','product_variation')
  AND (meta_key LIKE '%ean%' OR '%gtin%' OR '%unique_id%' OR '%bloz%' OR '%barcode%')
GROUP BY meta_key;   → 0 wierszy
```

**EAN nie ma własnego pola — jest wpisany bezpośrednio w `_sku`.**

| Sprawdzone pole | Wynik |
|---|---|
| `_sku` | ✅ **tutaj mieszka EAN** — np. `5907636994923`, `5903317643333` |
| `_global_unique_id` (natywny WooCommerce GTIN) | ❌ nie istnieje na żadnym produkcie |
| `_ean` | ❌ brak |
| `_wpm_gtin_code` | ❌ brak |
| `_bloz`, barcode, inne | ❌ brak |

**BLOZ nie jest przechowywany nigdzie w WooCommerce** — krok „dopasowanie po BLOZ"
jest niewykonalny. Cennik ma BLOZ dla wszystkich 126 pozycji; to gotowy klucz, gdyby
kiedyś trafił do bazy (istotne przy integracjach aptecznych).

### Rozkład formatów SKU (126 realnych produktów)

| Format SKU | Liczba | Przykład |
|---|---|---|
| EAN-13 | **37** | `5907636994923` |
| Wewnętrzny `GV-xxx` | **86** | `GV-22`, `GV-151` |
| Brak SKU | **3** | ID 1486, 1549, 1550 |

### Skuteczność dopasowania

| Metoda | Liczba | Pewność |
|---|---|---|
| **EAN** (`cennik.ean` = `_sku`) | **37** | ✅ PEWNE |
| **SKU** | 0 dodatkowych | ta sama kolumna co EAN |
| **BLOZ** | 0 | pole nie istnieje w WC |
| **Nazwa (fuzzy)** | **87** | ⚠️ **NIEPEWNE** |
| Niedopasowane | 2 (cennik) / 2 (WC) | — |

Algorytm dopasowania po nazwie: normalizacja (bez diakrytyków, lowercase, ujednolicenie
`kapsułek/kaps./kapsułki` → `kaps`, usunięcie interpunkcji i stop-słów), następnie
0.5 × podobieństwo Jaccarda tokenów + 0.5 × `SequenceMatcher`, plus **kontrola zgodności
gramatury** (ml / g / liczba kapsułek / %): zgodność +0,15, sprzeczność −0,30.
Dopasowanie zachłanne 1:1, próg 0,42.

**Weryfikacja jakości:** dla wszystkich 37 par dopasowanych po EAN porównano też nazwy —
rozjazdy wynikają wyłącznie ze skracania nazw w sklepie (`AURIX KROPLE DO USZU 35ML` →
`Aurix 35 ml`, `VENAL SPRAY NA ZMĘCZONE NOGI, ŻYLAKI 115ml` → `Venal Spray 115 ml`).
Żadna para EAN nie wskazuje na błędne przypisanie. To potwierdza, że EAN-y w `_sku`
są wiarygodne.

### ⚠️ Dopasowania po nazwie wymagające ręcznego potwierdzenia

Te pary przeszły próg, ale mają sprzeczny sygnał — **nie importować na nich cen bez decyzji człowieka**:

| Score | Cennik | WooCommerce | Problem |
|---|---|---|---|
| 0.478 | `CALCIUM NATURAL 60 KAPS` | ID 23 `Calcium Natural 70 kapsułek` | 🔴 **60 vs 70 kapsułek** — inna gramatura |
| 0.525 | `RABKA SPA MINERALE SPRAY 215 ML` | ID 195 `RABKA SPA MINERALE spray 200 ml` | 🔴 **215 vs 200 ml** |
| 0.626 | `MAŚĆ RUMIANKOWA 80ML` | ID 165 `Maść Rumiankowa z olejem CBD 5% 80 ml` | 🟠 cennik bez CBD, WC z CBD |
| 0.825 | `MAGNEZ B6 + CYNK x60 KAPS.` | ID 65 `Magnez B6 60 kapsułek` | 🟠 cennik z cynkiem, WC bez |
| 0.770 | `COLACAL X 60 KAPS.` | ID 37 `Colacal - kolagen z wapniem` | 🟡 prawdopodobnie OK |
| 0.718–0.721 | `OLEK Z KONOPI 5%/10%` (×4) | ID 69–72 `Olej z konopii CBD …` | 🟡 „OLEK" to literówka w cenniku — OK |

Pozostałe 81 dopasowań po nazwie ma spójną gramaturę i wysokie podobieństwo.

> **Rekomendacja:** uzupełnić EAN-y z cennika na 86 produktach `GV-xxx` **przed**
> jakimkolwiek importem cen. Docelowo w natywnym polu WooCommerce `_global_unique_id`
> („GTIN, UPC, EAN lub ISBN", WooCommerce ≥ 9.2), zostawiając `_sku` jako kod wewnętrzny —
> to rozdzieli dwie role, które dziś kolidują w jednym polu. Cennik dostarcza EAN dla
> wszystkich 126 pozycji, więc materiał jest kompletny.

---

## Ustawienia globalne podatków (krok 5)

| Opcja | Wartość | Znaczenie |
|---|---|---|
| `woocommerce_calc_taxes` | `yes` | podatki włączone |
| `woocommerce_prices_include_tax` | **`yes`** | 🔴 **`_regular_price` to cena BRUTTO** |
| `woocommerce_tax_display_shop` | `incl` | w sklepie ceny z VAT |
| `woocommerce_tax_display_cart` | `incl` | w koszyku ceny z VAT |
| `woocommerce_tax_round_at_subtotal` | `no` | zaokrąglanie per pozycja |
| `woocommerce_default_customer_address` | `base` | adres bazowy sklepu (PL) |

### Stawki (`wp_woocommerce_tax_rates`)

| ID | Kraj | Stawka | Nazwa | Klasa | Wysyłka |
|---|---|---|---|---|---|
| 2 | PL | 8.0000 | VAT | `reduced-rate` | tak |
| 3 | PL | 23.0000 | VAT | *(standard — pusta)* | tak |
| 4 | PL | 0.0000 | VAT 0% | `zero-rate` | tak |

Zastosowany wzór: **`netto_wc = _regular_price / (1 + stawka)`**, gdzie stawka wynika
z `_tax_class`: `reduced-rate` → 0,08 · *(pusta)* → 0,23 · `zero-rate` → 0,00.

### Rozkład klas podatkowych

| Klasa | VAT | Produktów |
|---|---|---|
| `reduced-rate` | 8% | 72 |
| *(standard)* | 23% | 55 |
| `zero-rate` | 0% | 1 (placeholder „Credit", draft) |

---

## B. Produkty w cenniku, których NIE MA w WooCommerce

**2 pozycje — obie to warianty produktu, który w sklepie istnieje tylko w jednej wersji.**

| lp | EAN | BLOZ | Nazwa | Netto 100% | VAT |
|---|---|---|---|---|---|
| 27 | `5907636994046` | 6794141 | CHROM Z ZIELONĄ HERBATĄ X 30 **TAB.** | 16,90 zł | 8% |
| 29 | `5907636994824` | 8471404 | CHROM Z ZIELONĄ HERBATĄ X **60 KAPS.** | 24,00 zł | 8% |

Cennik ma trzy warianty „Chrom z zieloną herbatą" (30 tabletek, 30 kapsułek, 60 kapsułek).
WooCommerce ma **tylko jeden**: ID 31 `Chrom z zieloną herbatą 30 kapsułek` (SKU `GV-140`),
dopasowany do lp28. Brakuje wersji 30 tabletek i 60 kapsułek.

> ⚠️ Dopasowanie ID 31 → lp28 jest **niepewne** (trzej kandydaci, brak EAN w WC).
> Jeśli ID 31 to w rzeczywistości wariant 60-kapsułkowy, cena netto powinna wynosić
> 24,00 zł zamiast 15,80 zł — czyli rozjazd o ~52%, nie o 6 groszy. **Do potwierdzenia
> u producenta przed importem.**

---

## C. Produkty w WooCommerce, których NIE MA w cenniku

**2 pozycje — o zupełnie różnym charakterze.**

| ID | SKU | Status | Nazwa | Cena brutto | Ocena |
|---|---|---|---|---|---|
| 14 | `GV-133` | publish | Apleplus C,E 30 kapsułek | 13,00 zł | 🔴 **DUPLIKAT** |
| 11586 | `GV-151` | publish | Maść żywokostowa z gojnikiem i olejem CBD 140ml | — | 🟢 nowy produkt |

### 🔴 ID 14 — duplikat, kandydat do wycofania

```
ID   14  GV-133         Apleplus C,E 30 kapsułek                  13.00 zł
ID 2040  5907636994121  Apleplus C,E – Ocet Jabłkowy 30 kapsułek  13.00 zł
```

To **ten sam produkt** (cennik lp6: „APLEPLUS C, E – OCET JABŁKOWY + WIT. C x30 kaps.",
EAN `5907636994121`). ID 2040 ma poprawny EAN i pełną nazwę; ID 14 ma tylko kod
wewnętrzny i skróconą nazwę. **Ta sama cena na obu**, więc klient nie zobaczy
rozbieżności — ale ma dwie karty tego samego produktu w katalogu, co szkodzi SEO
(kanibalizacja) i rozbija statystyki sprzedaży.

**Rekomendacja:** ID 14 → trash, przekierowanie 301 na ID 2040. **Nie wykonano —
wymaga zapisu i Twojej decyzji.**

### 🟢 ID 11586 — nowy produkt, brak w cenniku to norma

Objęty regułą B2BKing 13811 („Zniżka −10% nowy produkt"), dodany po dacie cennika
(2025-06-01). Nie jest kandydatem do wycofania — raczej **do zgłoszenia producentowi,
żeby trafił do następnej wersji cennika**.

---

## D. Rozjazd VAT — sekcja krytyczna

### ✅ Zero niezgodności wśród 123 porównywalnych pozycji

Dla wszystkich dopasowanych produktów, gdzie cennik podaje stawkę VAT, klasa podatkowa
w WooCommerce **zgadza się co do jednego**:

| VAT w cenniku | Klasa w WC | Pozycji | Zgodne |
|---|---|---|---|
| 8% | `reduced-rate` | 72 | ✅ T |
| 23% | *(standard)* | 51 | ✅ T |

Obejmuje to również dwie pozycje, które łatwo byłoby pomylić — **PNEUMOVIT PASTYLKI
(lp91, lp92) mają w cenniku VAT 23% mimo kodu CN `2106 90 92`** (typowego dla
suplementów z 8%). WooCommerce ma je poprawnie na klasie standardowej 23%
(ID 2059, 2060). To dobry znak: ktoś ustawiał te stawki świadomie, nie hurtem po kategorii.

### 🟠 Jedna pozycja do wyjaśnienia — lp115

| Pole | Wartość |
|---|---|
| Cennik lp | 115 |
| Nazwa | ŻEL ŁAGODZĄCY PO UKĄSZENIACH 20 ML |
| EAN | `5903317643227` |
| BLOZ | 7091949 |
| Kod CN | `3304 99 00` |
| **VAT w cenniku** | 🕳️ **PUSTY** (jedyna taka pozycja w całym cenniku) |
| Kategoria w cenniku | 🕳️ pusta |
| Produkt w WC | ID 209, `GV-147`, „Żel łagodzący po ukąszeniach owadów 20ml" |
| **Klasa podatkowa w WC** | `reduced-rate` → **8%** |
| **Zgodne?** | ❓ **NIE DA SIĘ ROZSTRZYGNĄĆ Z CENNIKA** |

**Dlaczego to prawdopodobnie błąd 8% → 23%:**

Rozkład kod CN → VAT w całym cenniku jest w 100% spójny:

| Kod CN | VAT | Pozycji |
|---|---|---|
| `3304 99 00` (kosmetyki) | 23% | **51** |
| `3304 99 00` | *(pusty)* | **1** ← lp115 |
| `2106 90 92` (suplementy) | 8% | 62 |
| `2106 90 92` | 23% | 2 (pastylki Pneumovit) |
| `3006 70 00` (wyroby med.) | 8% | 10 |

**Wszystkie 51 pozostałych pozycji z kodem CN `3304 99 00` mają VAT 23%.** lp115 ma
ten sam kod CN, ale pustą komórkę VAT — a WooCommerce trzyma ten produkt na 8%.

Jeśli to faktycznie kosmetyk 23%, sklep **zaniża VAT należny** na tej pozycji. Przy
cenie brutto 9,00 zł różnica na sztuce to ~1,10 zł podatku, ale ryzyko jest jakościowe,
nie kwotowe.

> **Działanie:** potwierdzić stawkę u producenta / księgowości (lim-tax) **zanim**
> cokolwiek się zmieni. Pusta komórka w cenniku to brak danych, nie zgoda na 8% —
> nie zmieniam klasy podatkowej bez decyzji.

**Żadnej innej niezgodności VAT nie wykryto.**

---

## E. Rozjazd cen netto

### Jak czytać te liczby

`_regular_price` w WooCommerce to **brutto** i jest ustawiony na **pełne złote**
(21,00 · 23,00 · 17,00 …). Cennik podaje netto z dokładnością do grosza. Po przeliczeniu
`netto = brutto / (1 + VAT)` prawie zawsze wychodzi kwota różniąca się o kilkanaście
do kilkudziesięciu groszy — **to artefakt zaokrąglenia ceny detalicznej, nie rozjazd
cennika.**

Dlatego poniżej podano obie miary. Rozstrzygająca jest **delta brutto** — porównanie
`netto_cennik × (1 + VAT)` z faktyczną ceną w sklepie:

| Miara | Wynik |
|---|---|
| Ceny netto różniące się > 1 gr | **121 ze 124** |
| Delta brutto **< 0,60 zł** (zaokrąglenie do pełnych zł) | **119** |
| Delta brutto **≥ 0,60 zł** (realny rozjazd) | **4** |

### 🔴 Realne rozjazdy — 4 pozycje

| ID | SKU | Produkt | Netto WC | Netto cennik | Różnica | % | Brutto oczek. | Brutto w WC | Δ brutto |
|---|---|---|---|---|---|---|---|---|---|
| 37 | `GV-40` | Colacal – kolagen z wapniem 60 kaps. | 47,22 | **49,50** | **−2,28** | **−4,6%** | 53,46 | 51,00 | **−2,46** |
| 43 | `GV-43` | Colahial 60 kapsułek | 49,07 | **47,50** | **+1,57** | **+3,3%** | 51,30 | 53,00 | **+1,70** |
| 201 | `GV-78` | Venal Żel 150 ml | 17,89 | **19,00** | **−1,11** | **−5,9%** | 23,37 | 22,00 | **−1,37** |
| 157 | `GV-109` | Maść Nagietkowa 130ml | 18,70 | **18,00** | **+0,70** | **+3,9%** | 22,14 | 23,00 | **+0,86** |

### 🔴 Colacal ↔ Colahial: ceny są zamienione miejscami

To nie są dwa niezależne błędy, tylko jedna transpozycja:

```
Colacal  (ID 37): cennik 49,50 netto → 53,46 brutto → powinno być 53 zł, jest 51 zł
Colahial (ID 43): cennik 47,50 netto → 51,30 brutto → powinno być 51 zł, jest 53 zł
                                                                        ↑↓ zamienione
```

Cena Colacalu siedzi na Colahialu i odwrotnie. Oba produkty mają SKU `GV-40` / `GV-43`
(brak EAN), dopasowane po nazwie — ale kierunek błędu jest jednoznaczny, bo obie
oczekiwane wartości trafiają dokładnie w drugą kartę.

**Skutek biznesowy:** Colacal sprzedaje się o 2 zł za tanio, Colahial o 2 zł za drogo.
Przy rabacie B2B −18% błąd propaguje się dalej w dół.

### 🟡 Venal Żel i Maść Nagietkowa — pojedyncze rozjazdy

- **ID 201 Venal Żel 150 ml** — najgłębszy procentowo (−5,9%). Sklep sprzedaje o 1,37 zł
  brutto poniżej cennika. Dopasowanie po nazwie (`VENAL ŻEL CHŁODZĄCY 150 ML` →
  `Venal Żel 150 ml`, score 0,931) — gramatura się zgadza, dopasowanie wiarygodne.
- **ID 157 Maść Nagietkowa** — +0,86 zł brutto powyżej cennika. Uwaga: pozycja w cenniku
  (lp64 `MAŚĆ NAGIETKOWA`) **nie podaje gramatury**, a produkt w WC to `130ml`.
  Dopasowanie prawdopodobne, ale bez potwierdzenia pojemności zostawiam jako niepewne.

### ✅ Pozycje idealnie zgodne (do grosza)

| ID | SKU | Netto WC | Netto cennik | Produkt |
|---|---|---|---|---|
| 159 | `GV-60` | 18,70 | 18,70 | Maść Niedźwiedzi Ząb 200 ml |
| 163 | `GV-111` | 18,70 | 18,70 | Maść Rokitnikowa 140ml |
| 141 | `GV-55` | 18,70 | 18,70 | Maść Borowinowa 130 ml |

### Ceny promocyjne (`_sale_price`)

Trzy produkty mają aktywną promocję — **przy imporcie nowych cen regularnych trzeba
zdecydować, co się z nimi stanie** (nadpisać, zachować, przeliczyć proporcjonalnie):

| ID | Produkt | Regular | Sale |
|---|---|---|---|
| 95 | AloeVera Żel 150 ml | 22,00 | 20,15 |
| 179 | Mosqitos Zestaw Płyn 20ml i Żel 20ml | 16,00 | 14,62 |
| 183 | Olejek Pichtowy 100 ml | 15,00 | 13,51 |

**Pełna tabela wszystkich 124 dopasowań**, posortowana malejąco po wartości bezwzględnej
różnicy: `_audyt/dopasowanie.csv`.

---

## F. Produkty wariantowe

✅ **Brak problemu.**

```sql
product_type → simple: 128 (100%)
SELECT ... WHERE post_type='product_variation' → 0 wierszy
```

**Wszystkie produkty są typu `simple`. W bazie nie ma ani jednego wiersza
`product_variation`.** Żaden SKU z cennika nie trafia na wariant — alternatywna
ścieżka importu nie jest potrzebna.

> Warto odnotować w kontekście sekcji B: cennik traktuje warianty gramaturowe
> (Chrom 30 tab / 30 kaps / 60 kaps, Olej konopny 5%/10% × 10/20 ml) jako **osobne
> pozycje z własnym EAN** — i tak samo robi WooCommerce. To spójne podejście;
> nie ma powodu migrować ich na produkty wariantowe.

---

## G. Reguły B2BKing (`b2bking_rule`)

Wszystkie reguły poza koszem. Wartość rabatu siedzi w meta **`b2bking_rule_howmuch`**
(pole `b2bking_rule_discount_value` nie istnieje).

| ID | Tytuł | Status | Kto (grupa) | Typ | Wartość | Stosuje się do | `show_everywhere` |
|---|---|---|---|---|---|---|---|
| **1760** | Percentage Discount | 🟢 **publish** | `everyone_registered_b2b` | `discount_percentage` | **18%** | `cart_total` | **`1`** |
| **2367** | Zwolnienie z podatku | 🟢 **publish** | `everyone_registered_b2b` | `tax_exemption_user` | — (`showtax=yes`) | — | `1` |
| **13811** | Zniżka −10% nowy produkt | 🟢 **publish** | `everyone_registered_b2c`, `user_0` | `discount_percentage` | **10%** | produkt **11586** | `1` |
| 2310 | Zniżka B2B - 17 | ⚪ draft | `group_2308` | `discount_percentage` | *(brak `howmuch`)* | `cart_total` | `1` |
| 2311 | Zniżka B2B - 20 | ⚪ draft | `group_2309` | `discount_percentage` | *(brak `howmuch`)* | `cart_total` | `1` |
| **2395** | Zniżka B2B - 18 | ⚪ draft | `group_2394` | `discount_percentage` | **18%** | `cart_total` | `0` |

### Duplikaty i pułapki

**🔶 1760 vs 2395 — duplikat, obecnie nieszkodliwy.**
Obie dają 18% od `cart_total`. Różnice: 1760 celuje we *wszystkich* B2B, jest `publish`
i ma `show_everywhere=1`; 2395 celuje w grupę 2394, jest **draft**, `show_everywhere=0`.
Konfliktu dziś nie ma — 2395 została zdegradowana do draftu przy wdrożeniu wariantu B
(2026-07-16). **Gdyby ktoś ją przywrócił do publish**, B2BKing wziąłby regułę o większej
wartości (nie sumuje), ale rozjazd `show_everywhere` (1 vs 0) dałby niespójność: cena
na karcie produktu vs rabat w koszyku.

**🔴 1760 — trzy pola wyglądające na pozostałości po starter-site:**
- `b2bking_rule_currency = AED` — dirham ZEA w sklepie rozliczanym w PLN. Nieaktywne
  przy braku multicurrency, ale **do wyczyszczenia** — przy włączeniu wielowalutowości
  może uzbroić się nieoczekiwanie.
- `b2bking_rule_paymentmethod = woocommerce_payments` — brama, której sklep nie używa
  (planowane PayU / Przelewy24).
- `b2bking_rule_shippingmethod = free_shipping:1`.

Żadne z nich nie jest polem warunkującym przy `applies=cart_total`, więc dziś nie
wpływają na rabat — ale to śmieci konfiguracyjne, które mylą przy audycie.

**🔴 2367 „Zwolnienie z podatku" — NIE RUSZAĆ.**
Mimo nazwy `tax_exemption_user` reguła ma `showtax=yes`, co **tylko wyświetla ceny netto**
klientom B2B. **Odznaczenie `showtax` zwolniłoby wszystkich B2B z VAT** — poważny błąd
podatkowy. Zgodne z notatką w `CLAUDE.md`.

**🟡 13811 — to promocja B2C, nie B2B.**
Kieruje do `everyone_registered_b2c` + `user_0` (goście), dotyczy wyłącznie produktu
11586 (jedynego produktu spoza cennika — sekcja C). Nie wchodzi w interakcję z cennikiem B2B.

**⚪ 2310 / 2311 — reguły niekompletne.**
Tytuły sugerują 17% i 20%, ale `b2bking_rule_howmuch` **jest puste**. Publikacja dałaby
rabat 0% albo błąd. Kandydaci do usunięcia.

### Interakcja z cennikiem

Reguła 1760 działa w trybie `show_everywhere=1` (wariant B), czyli **rabat 18% jest
wliczony w prezentowaną cenę produktu**, nie doliczany w koszyku. Każda zmiana ceny
regularnej automatycznie przeskaluje cenę B2B. Zgodnie z notatką projektową: po każdej
zmianie meta reguł konieczne jest
`wp eval 'B2bking_Admin::b2bking_calculate_rule_numbers_database();'` + cache flush —
**przy zmianie samych cen produktów to nie jest potrzebne.**

---

## Załączniki

| Plik | Zawartość |
|---|---|
| `_audyt/dopasowanie.csv` | 124 dopasowania: metoda, pewność, score, EAN, BLOZ, ceny obu stron, delty (`;`-separated) |
| `_audyt/wc_produkty.csv` | eksport 128 produktów z WooCommerce |
| `_audyt/cennik_2025-06-01.csv` | cennik producenta (źródło) |

## Metodyka i bezpieczeństwo

Wykonano **wyłącznie** `wp option get`, `wp post list` oraz `wp db query` z `SELECT`.

**Nie zmieniono żadnej ceny, klasy podatkowej, meta ani reguły B2BKing. Nie dotknięto
`_gspb_post_css`. Nie czyszczono cache (ani Redis, ani WP Rocket). Zero UPDATE / INSERT /
DELETE.** Pliki zapisano tylko w `_audyt/`.
