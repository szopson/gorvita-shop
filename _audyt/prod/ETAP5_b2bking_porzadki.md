# ETAP 5 — porządki w B2BKing (PRODUKCJA)

**Rozpoznanie (sekcje poniżej): wyłącznie odczyt.** 2026-07-22.
**Wykonanie: patrz sekcja „WYKONANIE" na końcu** — usunięto `b2bking_rule_currency=AED`
z reguły 1760 na produkcji; reguły 2310/2311/2395 ⏸ wstrzymane, nietknięte.

## Inwentarz reguł

| ID | status | utworzona | modyfikowana | autor | tytuł |
|---|---|---|---|---|---|
| 1760 | publish | 2026-05-05 13:36 | 2026-07-10 10:35 | admin | Percentage Discount |
| 2310 | **draft** | **2026-06-24 21:23** | 2026-06-24 21:24 | **admin** | Zniżka B2B – 17 |
| 2311 | **draft** | **2026-06-24 21:23** | 2026-06-24 21:23 | **admin** | Zniżka B2B – 20 |
| 2367 | publish | 2026-07-09 13:32 | 2026-07-09 13:32 | admin | Zwolnienie z podatku |
| 2395 | **draft** | **2026-07-10 10:37** | 2026-07-16 19:11 | **admin** | Zniżka B2B – 18 |
| 13811 | publish | 2026-07-15 10:56 | 2026-07-15 11:00 | admin | Zniżka -10% nowy produkt |

Wszystkie sześć założone przez `admin` — jedynego autora reguł w bazie.

## ⚠ Dwie korekty do opisu stanu zastanego

**1. `2310` i `2311` NIE mają pustego `howmuch`.** Mają wartości i pełną konfigurację:

| ID | `howmuch` | `who` | `applies` | `show_everywhere` |
|---|---|---|---|---|
| 2310 | **16.98** | `group_2308` (Zniżka B2B – 17%) | cart_total | 1 |
| 2311 | **20** | `group_2309` (Zniżka B2B – 20%) | cart_total | 1 |
| 2395 | 18 | `group_2394` (Żniżka B2B – 18%) | cart_total | **0** (nie 1) |

To nie są puste szkielety, tylko **kompletne reguły dla innych grup rabatowych**, wstrzymane
w `draft`. Obie grupy docelowe (2308, 2309) istnieją i mają **0 użytkowników**.

**2. `2395` nie jest ścisłym duplikatem `1760`** — ma `everywhere=0` i celuje w grupę 2394,
nie we wszystkich B2B. Jest natomiast **funkcjonalnie zbędna**, bo:

- `1760` ma `who = everyone_registered_b2b`, czyli obejmuje **wszystkich** użytkowników B2B
  niezależnie od grupy;
- grupa 2394 ma **2 użytkowników** (`#28` (kontrahent A), `#6` (kontrahent B)), oba
  `approved=yes`;
- **pomiar:** konto spoza grupy 1073 widzi Acerolę **15,99 bez VAT** = 19,50 × 0,82 —
  czyli dostaje pełne 18% z reguły 1760. Publikacja 2395 nic by nie zmieniła (ten sam
  procent, a B2BKing bierze największy rabat, nie sumuje).

## ⏸ DECYZJA 2026-07-22: 2310, 2311, 2395 — **WSTRZYMANE, NIE ODRZUCONE**

Klient **nie odrzucił** tych reguł. Wstrzymał decyzję do czasu rozstrzygnięcia pytania,
czy planowane są kolejne progi rabatowe B2B. Do tego czasu **cała trójka zostaje w `draft`
bez zmian**, razem z grupami 2308 / 2309 / 2394.

Podstawą wstrzymania jest ustalenie z tego audytu: to nie są śmieci po nieudanej
konfiguracji, tylko **spójny, nieuruchomiony cennik progowy 17% / 18% / 20%** — trzy reguły
z wypełnionym `howmuch`, trzy odpowiadające im grupy, wszystko założone przez `admin`
w krótkim odstępie. Pierwotny opis („draft, puste howmuch") był nieścisły.

**Nie usuwać bez powrotu do tej decyzji.** Jeśli klient potwierdzi, że progów nie będzie —
usuwać reguły **razem z grupami**, żeby nie zostały osierocone.

## Lista rozpoznania — kandydaci (status: WSTRZYMANE)

| # | ID | co to jest | ryzyko usunięcia | status |
|---|---|---|---|---|
| 1 | **2395** | 18% dla `group_2394`, draft | **żadne** — 1760 pokrywa tych 2 użytkowników, zweryfikowane pomiarem | ⏸ **wstrzymane** |
| 2 | **2310** | 16,98% dla `group_2308`, draft, grupa pusta | niskie — gotowa reguła na próg 17% | ⏸ **wstrzymane** |
| 3 | **2311** | 20% dla `group_2309`, draft, grupa pusta | jw. — gotowy próg 20% | ⏸ **wstrzymane** |

**Nic nie usunięto.** Wszystkie trzy pozostają w `draft`, w stanie zastanym.

## `b2bking_rule_currency = AED` na regule 1760 — bez wpływu

Cztery niezależne przesłanki:

1. **Arytmetyka rabatu nie zna waluty.** `class-b2bking-dynamic-rules.php:2960`:
   `$smallest_discounted_price = floatval($regular_price - ($howmuch/100 * $regular_price));`
   — tylko cena i procent.
2. **Pole czyta wyłącznie mechanizm reguł walutowych**, a `1760` ma
   `what = discount_percentage`, nie `currency`. Reguł typu `currency` w bazie: **0**.
3. **Filtry walutowe nie są nawet zarejestrowane.** Warunek w `class-b2bking.php:441`
   przechodzi (opcja `b2bking_have_currency_rules` nie istnieje, a jej **domyślną wartością
   jest `'yes'`** — to mogłoby zmylić), ale drugi gate `b2bking_have_currency_rules_list`
   ma wartość **`'no'`**, więc `b2bking_user_is_in_list()` odrzuca. Sprawdzone w runtime:
   `has_filter('option_woocommerce_currency', …)` → **nie**.
4. **Pomiar:** `get_woocommerce_currency() = PLN`, ceny renderują się w zł na wszystkich
   122 stronach sprawdzonych w Etapie 4.

**Wniosek: AED to martwy artefakt w meta.** Można wyczyścić przy okazji, ale nie ma pilności
i nie ma wpływu na rabat.

## `13811` — czy duplikuje `_sale_price` gojnika

Konfiguracja reguły:

```
applies = multiple_options -> product_11586
who     = multiple_options -> everyone_registered_b2c, user_0   (B2C + GOŚCIE)
howmuch = 10   show_everywhere = 1   status = publish
```

Stan produktu `#11586` po przełączeniu na netto:

| | netto | brutto |
|---|---|---|
| `_regular_price` | 20,10 | 24,72 |
| `_sale_price` | 18,09 | **22,25** |
| reguła 13811 (−10% od regularnej) | 18,09 | **22,25** |
| gdyby się SKŁADAŁY (`sale × 0,9`) | 16,28 | 20,03 |

**Pomiar na żywym sklepie, koszyk gościa przez Store API:**

```
#11586  regular=24.72  price=22.25  sale=22.25
linia:  netto=18.09  VAT=4.16  brutto=22.25
```

**Nie składają się.** Gość płaci 22,25, nie 20,03. ✅

### Ale duplikacja jest realna i krucha

Oba mechanizmy kodują **ten sam rabat −10%** dwiema niezależnymi drogami i dają identyczną
liczbę, więc dziś są nierozróżnialne. Problem pojawi się przy **następnej zmianie ceny**:

- reguła liczy −10% **dynamicznie od aktualnej ceny regularnej** — pojedzie za każdą zmianą;
- `_sale_price` jest **wartością zamrożoną** — zostanie na 18,09.

Po zmianie ceny regularnej obie wartości się rozjadą. Który mechanizm wygra: reguła
„everywhere" nadpisuje istniejącą `_sale_price` — potwierdzone niezależnym pomiarem na
`#183` (Olejek Pichtowy: regular 13,90, sale 12,51, B2B dostaje **11,40** = 13,90 × 0,82,
czyli z regularnej, z pominięciem promocji). Czyli klient zobaczy wartość **z reguły**,
a `_sale_price` stanie się mylącym śmieciem w bazie — widocznym w panelu i w eksportach,
ale nie w cenie.

> ⚠ **REKOMENDACJA ODWRÓCONA — patrz „Punkt 3" w sekcji WYKONANIE.** Pisałem tu, że lepiej
> zachować regułę 13811 i usunąć `_sale_price`. Pomiar na stagingu to obalił: sama reguła
> **nie zapisuje `_price` ani nie odświeża lookup**, przez co produkt w filtrach cenowych
> figuruje jako 24,72 zamiast 22,25 i ma `onsale=0`. Obowiązuje wniosek odwrotny —
> zostawić `_sale_price`, usunąć regułę.

---

# WYKONANIE — 2026-07-22

## Punkt 2: AED wyczyszczone (PRODUKCJA)

Backup: `_audyt/backup_przed_aed_20260722_0924.sql` — 149 705 663 B, 179 tabel.

Usunięto **wyłącznie** `b2bking_rule_currency` z reguły 1760 (`delete_post_meta` → `true`,
klucz nie istnieje). Diff całej meta reguły: **39 → 38 kluczy, dokładnie jedna usunięta
linia**. Pola krytyczne potwierdzone odczytem: `howmuch=18.000000`,
`what=discount_percentage`, `who=everyone_registered_b2b`, `show_everywhere=1`,
status `publish` — wszystkie nietknięte.

Test HTTP: gość Acerola **21,06 z VAT**, B2B **15,99 bez VAT**; Żel z Żywkostem 22,76 / 15,17.
Waluta `PLN`, reguł typu `currency`: 0. Cache Redis → WP Rocket.

Świadomie **nie uruchomiono** `B2bking_Admin::b2bking_calculate_rule_numbers_database()` —
przebudowuje listy `b2bking_have_*_rules_list_ids`, na które pole walutowe reguły rabatowej
nie wpływa; zakres był minimalny z polecenia. Test HTTP potwierdza działanie rabatu.

## Punkt 3: pomiar gojnika (STAGING, przywrócony)

Backup: `/opt/gorvita-staging/_audyt/backup_przed_gojnik_20260722_0926.sql`.
Na stagingu odtworzono stan produkcyjny: `#2304` `_sale_price = 18,09` + reguła testowa
`#2321` (klon 13811 wskazujący `product_2304`, −10%, `everyone_registered_b2c,user_0`,
`everywhere=1`), po czym `b2bking_calculate_rule_numbers_database()` + cache.

### Odpowiedź na pytanie: przekreślenie ZOSTAJE

| stan | markup ceny dla gościa | `<del>` | badge `.onsale` |
|---|---|---|---|
| **A** — `_sale_price` + reguła (jak na prodzie) | `<del>24,72</del> <ins>22,25</ins>` | **JEST** | JEST |
| **B** — sama reguła, bez `_sale_price` | `<del>24,72</del> <ins>22,25</ins>` | **JEST** | JEST |

Markup **identyczny co do znaku**. Sama reguła w pełni odtwarza przekreślenie, cenę „przed",
tekst dla czytników ekranu i badge promocji. Kryterium „promocja ma być widoczna jako
promocja" spełnia **oba** warianty.

### ⚠ Ale pomiar wykazał coś, czego nie było w hipotezach

Reguła działa **wyłącznie na warstwie wyświetlania**. Nie zapisuje `_price` ani nie
odświeża `wp_wc_product_meta_lookup`:

| | `_price` w bazie | `lookup.min_price` | `lookup.onsale` | co widzi sortowanie/filtry |
|---|---|---|---|---|
| **A** — z `_sale_price` (produkcja `#11586`) | **18,09** | **18,0900** | **1** | **22,25 brutto** ✅ zgodne z ceną widoczną |
| **B** — sama reguła (staging `#2304`) | 20,10 | 20,1000 | **0** | **24,72 brutto** 🔴 rozjazd 2,47 zł |

W wariancie B produkt:
- w filtrach i sortowaniu po cenie figuruje jako **24,72**, choć klient płaci 22,25;
- ma `onsale = 0`, więc **nie pojawi się** w zapytaniach i shortcode'ach „produkty w promocji";
- jest to dokładnie ten sam typ rozjazdu postmeta ↔ lookup, który Etap 4 traktuje jako
  **czerwony wynik**.

### Rekomendacja — odwracam swoją poprzednią

Wcześniej rekomendowałem zostawienie reguły 13811 i usunięcie `_sale_price`. **Ten pomiar
tę rekomendację obala.** Przekreślenie nie jest kryterium rozstrzygającym, bo zostaje
w obu wariantach — rozstrzyga spójność z lookup.

**Zostawić `_sale_price`, usunąć regułę 13811.** Uzasadnienie pokrywa się z Twoim:
promocja jako **kwota zamrożona**, dodatkowo poprawnie zaindeksowana w lookup, widoczna
w filtrach i w listingach promocji. Reguła to pływający procent, który dodatkowo
rozjeżdża indeks cenowy.

Koszt: `_sale_price` nie pójdzie automatycznie za zmianą ceny regularnej — przy następnym
imporcie cennika trzeba ją przeliczyć ręcznie (tak jak zrobiono dla 4 promocji przy
przełączeniu na netto).

### Staging przywrócony

Import z dumpu, cache Redis → WP Rocket. Kontrola: `#2304` bez `_sale_price` (klucz nie
istnieje), reguła testowa `#2321` nie istnieje, reguł B2BKing **2** (2306, 1760 — jak przed
testem), 128 produktów, `prices_include_tax=no`, render gojnika `24,72 zł z VAT` bez `<del>`.

## Punkt 4: reguła 13811 usunięta (PRODUKCJA)

Backup: `_audyt/backup_przed_13811_20260722_0935.sql` — 149 803 651 B, 179 tabel.

`wp_delete_post(13811, true)`. Reguła **była** w listach B2BKing
(`b2bking_have_discount_everywhere_rules_list_ids = 13811,1760`), więc po usunięciu
uruchomiono `B2bking_Admin::b2bking_calculate_rule_numbers_database()` — inaczej
zostałoby wiszące ID wskazujące na nieistniejący post. (W operacji AED ta funkcja była
zbędna i świadomie pominięta — tu jest konieczna.)

| kontrola | wynik |
|---|---|
| reguły opublikowane | **#1760, #2367** ✅ |
| drafty 2310 / 2311 / 2395 | nietknięte ✅ |
| wystąpień `13811` w listach reguł | **0** ✅ |
| `have_discount_everywhere_rules_list_ids` | `1760` ✅ |
| `#11586` `_regular_price` / `_sale_price` / `_price` | 20,10 / **18,09** / 18,09 ✅ |
| `#11586` lookup `min_price` / `onsale` | **18,0900** / **1** ✅ |
| GOŚĆ przez HTTP | **24,72 przekreślone → 22,25**, `<del>` obecny ✅ |
| B2B przez HTTP | **16,48 bez VAT** = 20,10 × 0,82 ✅ |

Rabat B2B liczy się od ceny **regularnej**, z pominięciem promocji — tak samo jak przy
`#183`. Cache: Redis → WP Rocket.

## 🔴 DŁUG: `_sale_price` nie idzie za ceną regularną

Świadoma konsekwencja wyboru „promocja jako kwota zamrożona". Import cennika zmienia
wyłącznie `_regular_price`; ceny promocyjne zostają na starych wartościach.

**Przy każdym przyszłym imporcie cennika trzeba ręcznie przeliczyć wszystkie aktywne
promocje.** Bez tego rabat procentowy po cichu się rozjedzie, a w skrajnym przypadku
`sale >= regular` (kontrola tego warunku jest już w procedurze importu).

Na produkcji aktywne promocje — **4**:

| ID | produkt | `_regular_price` | `_sale_price` | rabat |
|---|---|---|---|---|
| 95 | AloeVera Żel 150 ml | 18,20 | 16,38 | −10% |
| 179 | Mosqitos Zestaw | 13,20 | 11,89 | −10% |
| 183 | Olejek Pichtowy 100 ml | 13,90 | 12,51 | −10% |
| 11586 | Maść żywokostowa z gojnikiem | 20,10 | 18,09 | −10% |

Lista może się zmienić — przed każdym importem sprawdzać aktualną:
`SELECT post_id FROM wp_postmeta WHERE meta_key='_sale_price' AND meta_value<>''`.

**Wpisane także do `CLAUDE.md`**, nie tylko tutaj — bo dotyczy każdej przyszłej pracy
z cenami, nie tylko tego audytu.
