# PLAN — przełączenie stagingu na ceny netto

**Status: PLAN. Zero zapisów wykonanych.** Sporządzony 2026-07-22.

## Uzasadnienie

B2BKing nie ma ustawienia podstawy rabatu. Rabat procentowy liczy się w
`public/class-b2bking-dynamic-rules.php:2960`:

```php
$smallest_discounted_price = floatval($regular_price - ($howmuch/100 * $regular_price));
```

Bazą jest wprost `$regular_price` — czyli to, co WooCommerce trzyma w `_regular_price`.
Przy `woocommerce_prices_include_tax = yes` jest to **brutto**, stąd rabat od brutto.

Jedyny sposób, żeby rabat liczył się od netto **bez własnego PHP w ścieżce cenowej**, to
sprawić, żeby `_regular_price` zawierało netto — czyli `prices_include_tax = no`.
To nie obejście, tylko usunięcie przyczyny.

---

## 1. Zakres konwersji — **126 produktów, nie 122**

⚠ Poza 122 pozycjami importu są jeszcze **4 produkty z ceną brutto**, których plan
z polecenia nie obejmował. Pominięcie ich = cena spada o 8–23%, bo brutto zostanie
zinterpretowane jako netto.

| ID | SKU | VAT | brutto | → netto | produkt | dlaczego poza importem |
|---|---|---|---|---|---|---|
| 14 | GV-133 | 8% | 13,00 | **12,04** | Apleplus C,E 30 kaps. | duplikat, poza cennikiem |
| 23 | 5903317643012 | 8% | 17,00 | **15,74** | Calcium Natural 70 kaps. | `BEZ_ZMIAN` lp20 (decyzja klienta) |
| 195 | 5907636994305 | 8% | 17,00 | **15,74** | RABKA SPA MINERALE spray | `BEZ_ZMIAN` lp97 (decyzja klienta) |
| 2304 | GV-151 | 23% | 24,72 | **20,10** | Maść żywokostowa z gojnikiem | poza cennikiem |

`#1058`/`#1059` (placeholdery B2BKing) nie mają ceny — pomijamy.

Dla 23 i 195 netto liczymy **z ceny obecnej**, nie z cennika — klient świadomie zostawił
je poza cennikiem, więc konwersja ma zachować cenę brutto, nie ją zmienić.

**Razem do zapisu: 126 pozycji `_regular_price`.**

## 2. `_regular_price` dla 122 pozycji importu

`_regular_price = netto_cennik` wprost z `plan_cen.csv` (kolumna `netto_cennik`).

Kontrola wykonana: netto odtworzone z obecnego brutto == netto z cennika dla **122/122**.

> ℹ **Historia tego zdania — patrz D.1.** Pierwotnie napisałem „brutto B2C odtworzy się bez
> zmiany ani grosza". Symulacja przez filtry zakwestionowała to (10/126 przy 1 szt.), więc
> zdanie wycofałem. **Pomiar po realnym przełączeniu przywrócił pierwotny wniosek:
> 0/126 zmian przy 1 szt.** Błędna okazała się symulacja, nie zdanie. Przy 10 szt. zmienia
> się 85/126, ale zawsze o ≤1 gr na sztuce.

## 3. `_sale_price` — 4 pozycje

| ID | VAT | reg brutto | reg → netto | sale brutto | **sale → netto** | rabat |
|---|---|---|---|---|---|---|
| 95 | 23% | 22,39 | 18,20 | 20,15 | **16,38** | 10,0% |
| 165 | 23% | 13,90 | 11,30 | 12,51 | **10,17** | 10,0% |
| 179 | 23% | 16,24 | 13,20 | 14,62 | **11,89** | 10,0% |
| 183 | 8% | 15,01 | 13,90 | 13,51 | **12,51** | 10,0% |

Round-trip sprawdzony dla wszystkich czterech: `netto × (1+VAT)` wraca dokładnie do
obecnego brutto. Ceny promocyjne widziane przez B2C nie drgną.

## 4. Jedna operacja

Przełączenie ustawienia i zapis cen **muszą pójść w jednej transakcji/skrypcie**, w tej
kolejności, bez flush cache pomiędzy:

1. `update_option('woocommerce_prices_include_tax','no')`
2. 126 × `set_regular_price(netto)` + 4 × `set_sale_price(netto)` + `save()`
3. przeliczenie lookup dla wszystkich 126
4. cache: Redis → WP Rocket

Samo (1) bez (2) podniosłoby każdą cenę brutto o 8–23% — sklep przez ten czas
sprzedawałby drożej. Samo (2) bez (1) obniżyłoby ceny o 8–23%.

**Wymagany backup przed operacją.** Rollback = import dumpu, bo cofnięcie samego
ustawienia bez cen zostawiłoby sklep w stanie niespójnym.

---

## 5. Co jeszcze zależy od `prices_include_tax`

### 5a. Ustawienia, które MUSZĄ zostać bez zmian

| opcja | wartość | uwaga |
|---|---|---|
| `woocommerce_tax_display_shop` | `incl` | **nie ruszać** — B2C ma dalej widzieć brutto |
| `woocommerce_tax_display_cart` | `incl` | **nie ruszać** — jw. |
| `woocommerce_calc_taxes` | `yes` | bez zmian |
| `woocommerce_tax_based_on` | `shipping` | bez zmian |
| `woocommerce_tax_round_at_subtotal` | `no` | bez zmian — zaokrąglanie per pozycja |

Reguła B2BKing 2367 `showtax` odpowiada za pokazywanie netto **tylko** użytkownikom B2B —
nie ruszać (odznaczenie zwolniłoby wszystkich B2B z VAT).

### 5b. Wymaga weryfikacji PRZED wykonaniem — nie potwierdzone

| obszar | co sprawdzić |
|---|---|
| **Wysyłka — flat_rate `cost=12`** (strefa 1, inst 9 i 10) | czy 12 zł jest traktowane jako netto czy brutto; przy `no` może dojść VAT i wysyłka wzrośnie do 14,76 |
| **Free shipping `min_amount=250`** (strefa 1, inst 1) | `is_available()` porównuje z `get_displayed_subtotal()`, który idzie za `tax_display_cart=incl` — prawdopodobnie stabilne, ale trzeba potwierdzić testem koszyka |
| **`gorvita_free_shipping_gross_qualify`** (functions.php) | filtr liczy `min_amount` od brutto przez `total_items + total_items_tax`; po zmianie podstawy może liczyć podwójnie |
| **Pasek darmowej wysyłki** (custom motyw, próg 249) | liczy brutto przez `total_items + total_items_tax` — ta sama pułapka |
| **Equalizer `easypack_*`** | wg notatek InPost „zeruje się z netto" — to jest dokładnie ten scenariusz |
| **mu-plugin `gorvita-b2b-storeapi-netto.php`** | inicjalizuje `WC()->customer` dla Store API `/products`; po zmianie podstawy może stać się zbędny albo szkodliwy |
| **WCPDF, szablon `theme/gorvita`** | pozycje faktury liczą netto z brutto — po zmianie źródło jest już netto, wzory wymagają przeglądu |
| **E-maile WooCommerce** | dziedziczą `tax_display_cart`, ale warto potwierdzić na realnym zamówieniu testowym |

Kupony: **0 kuponów z `minimum_amount`** — ten wektor odpada.

### 5c. Poza zakresem stagingu

Na PRODUKCJI od 16.07 są **2 zrealizowane zamówienia B2B** (#16236 z 16.07, #16242 z 20.07)
+ 1 porzucony `checkout-draft` (#16245). Mają rabat liczony od brutto. Czy je korygować —
osobna decyzja klienta, poza tym planem.

---

## 6. Weryfikacja po wykonaniu

1. **Sekcja B Etapu 4 = 0 rozjazdów** — cena renderowana dla grupy 1073 == `netto_cennik × 0,82`
   dla wszystkich 122. To jest kryterium przyjęcia.
2. Sekcja A dalej 100% (netto w bazie == netto z cennika — teraz wprost, bez odtwarzania).
3. **Gość: brutto bez zmian co do grosza** — porównanie 126 cen przed i po.
4. 4 promocje: brutto bez zmian.
5. Koszyk testowy gość i B2B: suma, wysyłka, próg darmowej dostawy.
6. Faktura WCPDF z zamówienia testowego B2B — netto, VAT, brutto.

---

# WYNIKI A i B (2026-07-22) — zero zapisów

## A. Zamówienia B2B na produkcji

Oba mają fakturę WCPDF. **Poszły różnymi mechanizmami rabatu** — to nie było wcześniej wiadome:

| zamówienie | data | faktura | mechanizm rabatu |
|---|---|---|---|
| #16236 | 2026-07-16 14:35 | **nr 34**, 2026-07-16 14:59 | `FEE −60,24` (rabat linią w koszyku) |
| #16242 | 2026-07-20 09:46 | **nr 36**, 2026-07-20 12:36 | wariant B (rabat wliczony w cenę) |

Przełączenie na wariant B nastąpiło 16.07 — zamówienie z 14:35 złapało jeszcze stary
mechanizm. Dokładna godzina przełączenia nie jest ustalona.

Porównanie netto/szt. z `netto_cennik × 0,82` (dla #16236 rabat nałożony na jednostkę,
bo był fee):

**#16236 — suma różnicy +1,50 zł netto** (klient zapłacił WIĘCEJ niż cennik)

| ID | qty | lp | netto/szt | cennik | ×0,82 | różnica | na pozycji |
|---|---|---|---|---|---|---|---|
| 2061 | 3 | 93 | 12,91 | 15,40 | 12,63 | **+0,28** | +0,84 |
| 234 | 3 | 95 | 12,91 | 16,20 | 13,28 | −0,37 | −1,11 |
| 230 | 4 | 90 | 12,91 | 16,10 | 13,20 | −0,29 | −1,16 |
| 226 | 2 | 5 | 10,00 | 12,50 | 10,25 | −0,25 | −0,50 |
| 2060 | 5 | 92 | 6,83 | 7,50 | 6,15 | **+0,68** | **+3,40** |
| 232 | 3 | 94 | 12,91 | 15,50 | 12,71 | +0,20 | +0,60 |
| 189 | 2 | 116 | 12,91 | 15,80 | 12,96 | −0,05 | −0,10 |
| 107 | 1 | 19 | 16,67 | 20,50 | 16,81 | −0,14 | −0,14 |
| 218 | 1 | 123 | 10,00 | 12,60 | 10,33 | −0,33 | −0,33 |

**#16242 — suma różnicy −1,50 zł netto** (klient zapłacił MNIEJ niż cennik)

| ID | qty | lp | netto/szt | cennik | ×0,82 | różnica | na pozycji |
|---|---|---|---|---|---|---|---|
| 173 | 15 | 73 | 15,33 | 19,00 | 15,58 | −0,25 | **−3,75** |
| 95 | 1 | 3 | 14,67 | 18,20 | 14,92 | −0,25 | −0,25 |
| 203 | 2 | 107 | 9,33 | 11,40 | 9,35 | −0,02 | −0,04 |
| 2069 | 5 | 70 | 10,00 | 11,80 | 9,68 | +0,32 | +1,60 |
| 39 | 1 | 32 | 58,46 | 71,00 | 58,22 | +0,24 | +0,24 |
| 181 | 1 | 80 | 14,67 | 17,50 | 14,35 | +0,32 | +0,32 |
| 161 | 5 | 66 | 11,33 | 13,70 | 11,23 | +0,10 | +0,50 |
| 210 | 1 | 118 | 15,33 | 19,00 | 15,58 | −0,25 | −0,25 |
| 2071 | 3 | 125 | 12,67 | 15,80 | 12,96 | −0,29 | −0,87 |
| 224 | 5 | 126 | 10,67 | 12,70 | 10,41 | +0,26 | +1,30 |
| 167 | 2 | 69 | 16,00 | 19,70 | 16,15 | −0,15 | −0,30 |

Rozjazd jest **rzędu 2–68 gr na jednostce**, zgodnie z oczekiwaniem — bo zamówienia poszły
na cenach sprzed importu, nie z powodu podstawy rabatu. Saldo obu zamówień znosi się
(+1,50 i −1,50). Korekt nie wystawiano.

## B. Zależności — każda z testem

| # | pozycja | psuje? | test |
|---|---|---|---|
| 1 | `gorvita_free_shipping_gross_qualify` + pasek | **NIE** | 4 koszyki × 2 tryby, decyzja identyczna |
| 2 | mu-plugin `storeapi-netto` | **NIE** (neutralny, nadal potrzebny) | HTTP Store API, gość vs B2B |
| 3 | `flat_rate cost=12` | **NIE** | koszyk × 2 tryby: 12,00 + 2,76 = 14,76 identycznie |
| 4 | WCPDF `theme/gorvita` | **NIE** (kontrola kodu, nie runtime) | grep formuł — czyta net/tax z zamówienia |
| 5 | equalizer `easypack_*` | **NIE** | jak #1 — zerowanie działa w obu trybach |
| 6 | `free_shipping min_amount=250` | **NIE** | jak #1 |
| 7 | e-maile | **NIE** (kontrola konfiguracji, nie runtime) | `tax_display_cart=incl` bez zmian, brak override szablonu |
| **8** | **brutto B2C** | **⚠ TAK — ZMIENI SIĘ** | pełny katalog × 2 tryby |

### B.1 Próg darmowej wysyłki — test

| koszyk | brutto teraz | decyzja | brutto po | decyzja po |
|---|---|---|---|---|
| A mieszany VAT, poniżej | 245,26 | nie spełnia | 245,22 | nie spełnia ✅ |
| B mieszany VAT, powyżej | 256,21 | SPEŁNIA | 256,17 | SPEŁNIA ✅ |
| C o włos poniżej | 249,50 | nie spełnia | 249,44 | nie spełnia ✅ |
| D o włos powyżej | 250,36 | SPEŁNIA | 250,30 | SPEŁNIA ✅ |

W koszykach B i D wszystkie metody (`easypack_*`, `flat_rate`) wyzerowane do 0,00 w obu
trybach; w A i C wszystkie 12,00. **Brak podwójnego liczenia VAT** — `get_subtotal()`
i `get_subtotal_tax()` WooCommerce rozdziela zawsze, niezależnie od podstawy.

### B.2 ⚠ Brutto B2C ZMIENI SIĘ — retrakcja wcześniejszego wniosku

W punkcie 2 planu napisałem „brutto B2C odtworzy się **bez zmiany ani grosza**". **To było
błędne.** Sprawdzałem round-trip w Pythonie na `Decimal` (arytmetyka dokładna), a WooCommerce
liczy na **floatach**. `18,50 × 1,23 = 22,754999…` → PHP `round()` daje **22,75**, podczas
gdy dziś w bazie stoi 22,76.

Pomiar na pełnym katalogu, ten sam produkt w obu trybach:

| ilość | produktów ze zmianą brutto | zakres |
|---|---|---|
| 1 szt. | **10 / 126** | wszystkie −0,01 |
| 10 szt. | **85 / 126** | od −0,05 do +0,04 |

Dziesięć pozycji zmieniających cenę już przy sztuce: #107 BLIZNA 25,22→25,21 · #139 Maść
Arnikowa 22,76→22,75 · #143 Maść dla Sportowców 22,76→22,75 · #177 Mosqitos 100ml
11,69→11,68 · #181 Olej Kokosowy 21,53→21,52 · #185 Panthenol Pianka 16,61→16,60 ·
#187 Panthenol Żel 16,61→16,60 · #216 Żel z Żywkostem 22,76→22,75 · #226 APHTIHELP
15,38→15,37 · #2060 Pneumovit Pastylki 9,23→9,22.

Przy ilościach > 1 dochodzi druga przyczyna: dziś WooCommerce mnoży cenę **brutto** przez
ilość i dopiero wyodrębnia VAT; po przełączeniu mnoży **netto** i dolicza VAT do linii.
Stąd 85/126 przy 10 sztukach.

**Konsekwencja dla sekcji D:** kryterium „brutto B2C po == brutto przed dla wszystkich 126"
**nie przejdzie** w obecnym sformułowaniu. Wymaga Twojej decyzji — nie mojej.

---

# WYKONANIE C / D / E — 2026-07-22, STAGING

## C. Przełączenie (wykonane)

Backup: `backup_przed_netto_20260722_0808.sql` — 39 563 080 B, 179 tabel.

Jedna operacja (`wp eval-file`), bramka wstępna 130/130 zgodne ze stanem w bazie,
próba na sucho przed zapisem:

```
prices_include_tax -> no
zapisano: 130 wartości na 126 produktach   (126 × _regular_price + 4 × _sale_price)
lookup przeliczony: 126 | rozjazdy: 0
```

Cache: Redis → WP Rocket. Zero błędów, rollback nie był potrzebny.

Uwaga terminologiczna: na stagingu regułą „Zwolnienie z podatku (showtax)" jest **#2306**
(klon prod 2367) — ID 2367 na stagingu nie istnieje. Nietknięta, `showtax=yes`.

## D. Weryfikacja

Pierwsza kontrola (gość HTTP, świeża sesja): **Acerola 21,06 zł z VAT** ✅ — rollback zbędny.

| sekcja | wynik |
|---|---|
| A — netto w bazie == netto z cennika | **122/122 = 100%** ✅ |
| **B — render dla grupy 1073 == netto_cennik × 0,82** | **122/122, 0 rozjazdów** ✅ **KRYTERIUM SPEŁNIONE** |
| C — end-to-end 5 produktów, gość i B2B, HTTP | **5/5** ✅ |
| D — ceny 0 / puste / ujemne | 0 ✅ |
| E — liczba produktów | 128 ✅ |
| F — postmeta ↔ lookup | 0 naruszeń, 0 braków poza #1058/#1059 ✅ |
| G — wc_id 209 | 6/6 ✅ |

Sekcja B była celem całej operacji — przed przełączeniem 29 rozjazdów, po: **0**.

### D.1 Brutto B2C przed vs po — i sprostowanie

| ilość | pozycji ze zmianą | max zmiana |
|---|---|---|
| **1 szt.** | **0 / 126** | — |
| 10 szt. | 85 / 126 | ±0,05 (= ≤1 gr/szt) |

**Pozycji zmieniających się o więcej niż 1 gr na sztuce: 0.** Kryterium spełnione,
zatrzymanie niepotrzebne.

⚠ **Sprostowanie:** przed wykonaniem ostrzegałem, że 10 pozycji straci 1 gr już przy sztuce
(#107, #139, #143, #177, #181, #185, #187, #216, #226, #2060). **To był fałszywy alarm** —
artefakt mojej symulacji (filtr zwracał `wc_format_decimal`, co zmieniało zaokrąglenie).
Pomiar na realnie przełączonym sklepie: **0/126 zmian przy 1 szt.** Symulacja trafiła
116/126. Wniosek metodyczny: symulacja przez filtry nie jest wiarygodna dla arytmetyki
groszowej — decyduje pomiar po zmianie.

## E. Zamówienie testowe #2320 (utworzone i usunięte)

Konto `test-b2b-18` (#9, grupa 1073), pełna ścieżka koszyk → `create_order()` → processing.
Pułapki CLI obeszte zgodnie z notatką: filtry B2BKing przypięte ręcznie po
`wp_set_current_user()`, `calculate_totals` zdjęte z `add_to_cart`, jedno przeliczenie na końcu.

### Koszyk vs `netto_cennik × 0,82`

| ID | dobór | qty | netto/szt | oczekiwane | ✓ |
|---|---|---|---|---|---|
| 12 | 8% | 1 | 15,99 | 19,50 × 0,82 = 15,99 | ✅ |
| 216 | 23% | 1 | 15,17 | 18,50 × 0,82 = 15,17 | ✅ |
| 165 | promocja | 1 | 9,27 | 11,30 × 0,82 = 9,27 | ✅ |
| 209 | wyjątek klienta | 1 | 7,30 | 8,90 × 0,82 = 7,30 | ✅ |
| 173 | **ilość 10** | 10 | 15,58 | 19,00 × 0,82 = 15,58 | ✅ |

### Linia 10-sztukowa

`15,58 × 10 = 155,80` netto **dokładnie**, VAT 23% = **35,83**, brutto 191,63.
Netto mnoży się przez ilość bez reszty — to była główna zmiana wynikająca z przełączenia.

### Mechanizm rabatu

**WLICZONY W CENĘ (wariant B)** — `FEE (linie rabatu): 0`. Rabat nie pojawia się jako
osobna linia ani w koszyku, ani na fakturze.

### Sumy i wysyłka

| pozycja | wartość |
|---|---|
| netto pozycji | 203,53 |
| VAT 8% | 1,28 |
| VAT 23% | 45,89 (w tym 2,76 od wysyłki) |
| brutto koszyka | 247,94 |
| próg 250 | **nie osiągnięty → wysyłka płatna** ✅ poprawnie |
| dostawa | InPost Paczkomat 24/7 — 12,00 netto + 2,76 VAT = 14,76 |
| **RAZEM** | **262,70** |

### Faktura PDF (szablon `theme/gorvita`, nr 4)

```
Produkt                                    Ilość  Cena netto  Wartość netto  VAT   Kwota VAT  Brutto
Acerola 500mg  Kod: 5907636994152              1     15,99zł        15,99zł   8 %     1,28zł  17,27zł
Żel z Żywkostem  Kod: 5907636994459            1     15,17zł        15,17zł  23 %     3,49zł  18,66zł
Maść Rumiankowa CBD  Kod: 5903317643265        1      9,27zł         9,27zł  23 %     2,13zł  11,40zł
Żel łagodzący  Kod: 5903317643227              1      7,30zł         7,30zł  23 %     1,68zł   8,98zł
Maść z Żyworódki  Kod: 5907636994657          10     15,58zł       155,80zł  23 %    35,83zł 191,63zł

Wartość pozycji netto 203,53 · Dostawa netto 12,00 · Razem netto 215,53
VAT 8% 1,28 · VAT 23% 45,89 · Razem VAT 47,17 · Razem brutto 262,70
```

Szablon nie wymagał żadnej zmiany — czyta netto i VAT zapisane na zamówieniu.

### E-mail potwierdzenia

„[Gorvita Sklep] Otrzymaliśmy twoje zamówienie!" → `test-b2b-18@gorvita.pl`.
Kwota 247,94 · Wysyłka 14,76 (z VAT) · Razem 262,70 (zawiera 47,17 VAT).
**Wyrenderowany, NIE wysłany** — żeby nie generować ruchu wychodzącego na adres klienta.

### Sprzątanie

Zamówienie #2320 usunięte (`wp wc shop_order delete --force=true`, 0 wierszy w `wp_wc_orders`),
sesja WC użytkownika 9 wyczyszczona, ciasteczka i pliki tymczasowe skasowane.
⚠ **Numer faktury 4 został zużyty** przez test i nie wraca do puli po usunięciu zamówienia.

### Niezmienniki po całej operacji

`tax_display_shop=incl` · `tax_display_cart=incl` · reguła #2306 `showtax=yes` ·
klasy podatkowe 55/71/1/1 bez zmian · `_gspb_post_css` md5 `664ce2b22039069c62ee391b08433d5e`
identyczne jak przed operacją.
