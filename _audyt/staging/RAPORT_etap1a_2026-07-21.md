# Etap 1 — naprawa VAT na stagingu + Etap 1a (plan uzupełnienia EAN)

**Data:** 2026-07-21
**Charakter:** punkt 1 = zapis (5 klas podatkowych na stagingu). Etap 1a = wyłącznie odczyt.

---

## Punkt 1 — wyrównanie klas podatkowych stagingu do produkcji ✅

Backup przed zmianą: `_audyt/backup_przed_vat_staging_20260721_1510.sql`
(39,7 MB, 179 tabel, stopka `Dump completed` obecna).

| ID | SKU | Produkt | było (staging) | jest | prod |
|---|---|---|---|---|---|
| 209 | `GV-147` | Żel łagodzący po ukąszeniach owadów 20ml | `zero-rate` 0% | **`reduced-rate` 8%** | `reduced-rate` ✅ |
| 2056 | `5907636994558` | Maść Końska Rozgrzewająca 130ml | `reduced-rate` 8% | **`standard` 23%** | `standard` ✅ |
| 2058 | `5903317643258` | Mosqitos Płyn 20ml | `reduced-rate` 8% | **`standard` 23%** | `standard` ✅ |
| 2059 | `5907636994596` | Pneumovit Pastylki x16 | `reduced-rate` 8% | **`standard` 23%** | `standard` ✅ |
| 2060 | `5907636994565` | Pneumovit Pastylki x8 | `reduced-rate` 8% | **`standard` 23%** | `standard` ✅ |

### Dlaczego przez API, a nie `UPDATE` na `postmeta`

`wp_wc_product_meta_lookup` **również przechowuje `tax_class`** (potwierdzone odczytem —
przed zmianą tabela miała te same błędne wartości). Sam `UPDATE` na `wp_postmeta`
zostawiłby tabelę lookup nieaktualną, a WooCommerce czyta z niej przy filtrowaniu
i liczeniu podatku. Użyto `WC_Product::set_tax_class()` + `save()`, co kaskadowo
przelicza lookup.

**Weryfikacja:** `postmeta` i `wp_wc_product_meta_lookup` zgodne dla wszystkich 5 pozycji,
`tax_status = taxable`, `_regular_price` nietknięte. Kluczowano po SKU (decyzja 3).
Cache: Redis → WP Rocket (page + minify), w tej kolejności.

**Rozkład klas podatkowych jest teraz identyczny po obu stronach:**
72 `reduced-rate` / 55 `standard` / 1 `zero-rate` (placeholder „Credit").
Wymiar VAT stagingu = produkcja.

---

## Etap 1a — plany uzupełnienia EAN

Pliki (oba wygenerowane, **żaden nie wykonany**):

| Plik | Źródło | Status |
|---|---|---|
| `/opt/gorvita-staging/_audyt/plan_ean_STAGING.csv` | `dopasowanie.csv` stagingu | do wykonania w Etapie 1b **po akceptacji** |
| `/opt/gorvita-shop/_audyt/plan_ean_PROD.csv` | `dopasowanie.csv` produkcji (tylko odczyt) | **nie wykonywany**, czeka na osobną akceptację |

Kolumny: `sku` (klucz główny), `wc_id`, `wc_sku_obecny`, `wc_nazwa`, `cennik_lp`,
`cennik_nazwa`, `cennik_ean`, `score`, `runner_up`, `metoda`, `decyzja`,
`powod_pominiecia`, `powod`.

### Wyniki

| Decyzja | STAGING | PROD |
|---|---|---|
| **AUTO** | **39** | **40** |
| **DO_WERYFIKACJI** | **35** | **38** |
| **POMIŃ** | **50** | **46** |
| razem | 124 | 124 |

### Rozkład `powod_pominiecia`

| Wartość | STAGING | PROD |
|---|---|---|
| `SKU_JUZ_EAN13` | 33 | 33 |
| `AMBIGUOUS` | 9 | 8 |
| `PODCIAG` | 4 | 4 |
| `FLAGA` | 4 | 1 |

> **Odstępstwo od specyfikacji:** poproszono o cztery wartości
> (`PODCIAG` / `FLAGA` / `SCORE` / `AMBIGUOUS`). Dodano piątą — `SKU_JUZ_EAN13` —
> bo 33 wiersze to produkty, które **już mają EAN-13 w SKU**, czyli operacje puste
> (nie ma czego wpisywać). Wciśnięcie ich w którąkolwiek z czterech kategorii sugerowałoby
> wykryty problem, którego nie ma. `SCORE` nie wystąpiło ani razu — wszystkie wiersze
> poniżej 0,70 zostały wcześniej złapane przez `AMBIGUOUS`, `PODCIAG` lub `FLAGA`.

---

## Reguła podciągu — zaimplementowana, ale nie robi tego, po co powstała

### Implementacja

Normalizacja: lowercase → usunięcie znaków innych niż alfanumeryczne i spacje →
redukcja wielokrotnych spacji → trim. Relacja liczona na **wszystkich 126 nazwach
cennika**, niezależnie od środowiska. Zastosowana identycznie do obu plików.
Ma priorytet nad wszystkim innym: score, flagą, progiem.

### Co złapała — 2 pary, 4 pozycje (identycznie w STAGING i PROD)

| Para | Nazwy znormalizowane |
|---|---|
| lp12 ⊂ lp53 | `ashwagandha x60 kaps` ⊂ `magnez b6 ashwagandha x60 kaps` |
| lp43 ⊂ lp13 | `gotu kola` ⊂ `ashwagandha gotu kola x60 kaps` |

| lp | Nazwa z cennika | → produkt | wcześniej | teraz |
|---|---|---|---|---|
| 12 | ASHWAGANDHA x60 kaps. | 2044 `5903317643043` | POMIŃ | POMIŃ |
| 13 | ASHWAGANDHA + GOTU KOLA x60 KAPS.. | 2046 `5903317643050` | POMIŃ | POMIŃ |
| 43 | GOTU KOLA | 55 `5907636994404` | POMIŃ | POMIŃ |
| 53 | MAGNEZ B6 + ASHWAGANDHA x60 KAPS. | 2054 `5903317643210` | POMIŃ | POMIŃ |

### 🔴 Ile realnych błędów by przeszło: **0**

**Wszystkie 4 złapane pozycje były już POMIŃ**, bo ich produkty mają EAN-13 w SKU
(`SKU_JUZ_EAN13`). Żadna nie miała wcześniej AUTO. Liczby AUTO / DO_WERYFIKACJI / POMIŃ
**nie zmieniły się w żadnym z plików** (+0 / +0 / +0).

Reguła jest poprawna i nieszkodliwa, ale na tym zbiorze danych nie zapobiegła
ani jednemu błędowi.

### 🔴 Reguła NIE łapie pary, która ją zmotywowała

```
lp54 "MAGNEZ B6 + CYNK x60 KAPS."  -> "magnez b6 cynk x60 kaps"
lp55 "MAGNEZ B6 VEGAN X 60 KAPS."  -> "magnez b6 vegan x 60 kaps"

"magnez b6 cynk x60 kaps"  in "magnez b6 vegan x 60 kaps"  -> False
"magnez b6 vegan x 60 kaps" in "magnez b6 cynk x60 kaps"   -> False
```

Nazwy różnią się **w środku** (`cynk` vs `vegan`), a nie przez rozszerzenie — więc relacja
podciągu nie zachodzi w żadną stronę. Wniosek: **ręczne zabezpieczenie jest nadal
nośne**, a założenie, że reguła podciągu je zastąpi, nie jest spełnione.

Stan lp54/55 dziś:

| Środowisko | lp | score | decyzja | skąd |
|---|---|---|---|---|
| STAGING | 55 | 0,955 | POMIŃ | `FLAGA` z Etapu 0 — **ręczna** |
| STAGING | 54 | 0,456 | POMIŃ | `FLAGA` z Etapu 0 — **ręczna** |
| PROD | 55 | 1,129 | DO_WERYFIKACJI | hardkod „para niestabilna" — **ręczny** |
| PROD | 54 | 0,825 | POMIŃ | `AMBIGUOUS` — automatyczny ✅ |

Na produkcji lp55 jest wstrzymane **wyłącznie dzięki mojemu hardkodowi** — czyli
dokładnie temu rodzajowi zabezpieczenia, które miało zniknąć.

### Propozycja domknięcia luki (do decyzji, NIE zaimplementowana)

Deterministyczną alternatywą jest **podniesienie marginesu `AMBIGUOUS`**. Dziś wynosi
0,05 (odrzuć, gdy konkurent mieści się w 0,05 od zwycięzcy). Zmierzony margines dla
PROD lp55: score 1,129, runner-up 0,955 → **0,174**.

| Margines | AUTO na PROD (z 42 kandydatów) | lp55 złapane? |
|---|---|---|
| 0,05 *(dziś)* | 42 | ❌ |
| 0,15 | 42 | ❌ |
| **0,20** | **39** | ✅ |
| 0,25 | 34 | ✅ |
| 0,30 | 32 | ✅ |

**Margines 0,20 kosztuje 3 pozycje AUTO (42 → 39) i domyka lukę lp55** bez żadnej listy
ręcznej. To wygląda na najlepszy stosunek bezpieczeństwa do kosztu, ale jest to zmiana
progu, więc zostawiam ją do Twojej decyzji.

---

## 🔴 Konflikt decyzji 3 z danymi — 2 wiersze AUTO na PROD bez klucza

Decyzja 3 mówi: kluczować wyłącznie po SKU, nigdy po `wc_id`. **Trzy produkty nie mają
SKU w ogóle**, więc nie da się ich zaadresować tym kluczem:

| Środowisko | wc_id | Produkt | SKU | decyzja | Problem |
|---|---|---|---|---|---|
| PROD | 1486 | LIBIDO Vegan 60 kapsułek | *(brak)* | **AUTO** | 🔴 brak klucza |
| PROD | 1549 | LUTEINA Vegan 60 kapsułek | *(brak)* | **AUTO** | 🔴 brak klucza |
| PROD | 1550 | Magnez B6 VEGAN 60 kapsułek | *(brak)* | DO_WERYFIKACJI | — |
| STAGING | 1486 / 1549 / 1550 | j.w. (krótsze nazwy) | *(brak)* | POMIŃ | ✅ bez wpływu |

**Na stagingu problem nie występuje** — wszystkie trzy wypadły na `AMBIGUOUS` przez
krótsze nazwy, więc **Etap 1b na stagingu jest wykonalny bez wyjątków**.

Na produkcji trzeba będzie rozstrzygnąć jedno z dwóch: dopuścić `wc_id` jako klucz
awaryjny wyłącznie dla produktów bez SKU, albo przenieść te wiersze do
DO_WERYFIKACJI i nadać im SKU ręcznie. Decyzja potrzebna dopiero przed wykonaniem
wersji PROD.

---

## Kontrole bezpieczeństwa listy AUTO — obie wersje

| Kontrola | STAGING | PROD |
|---|---|---|
| AUTO z SKU już w formacie EAN-13 (nadpisanie istniejącego EAN) | brak ✅ | brak ✅ |
| Duplikaty EAN wewnątrz AUTO | brak ✅ | brak ✅ |
| Duplikaty SKU wewnątrz AUTO | brak ✅ | 2 × pusty SKU ⚠️ (patrz wyżej) |
| Kolizja: nowy EAN = SKU innego istniejącego produktu | brak ✅ | brak ✅ |
| Pozycje flagowane w Etapie 0 (lp 27/28/54/55) w AUTO | brak ✅ | n/d |

### AUTO z bliskim konkurentem (runner-up w 0,25) — do obejrzenia okiem

Identyczne w obu plikach, decyzji nie zmieniałem:

| score | runner-up | produkt | z cennika |
|---|---|---|---|
| 1,150 | 0,993 | 175 `GV-131` Maść żywokostowa z olejem CBD 140ml | MAŚĆ ŻYWOKOSTOWA Z OLEJEM CBD 140ml |
| 1,150 | 0,940 | 197 `GV-107` RABKA SPA MINERALE żel 200 ml | RABKA SPA MINERALE ŻEL 200 ML |
| 1,050 | 0,814 | 191 `GV-88` Pneumovit Żel 200 ml | ŻEL PNEUMOVIT 200 ML |
| 0,985 | 0,774 | 51 `GV-46` Erotic dla mężczyzn 20 kaps. | EROTIC DLA MĘŻCZYZN X 20 KAPS. |
| 0,979 | 0,774 | 49 `GV-47` Erotic dla kobiet 20 kaps. | EROTIC DLA KOBIET X 20 KAPS. |
| 0,972 | 0,812 | 18 `GV-33` Artrofit 3w1 60 kapsułek | ARTROFIT X 60 KAPS. |

Pierwszy wiersz jest wart uwagi: konkurentem dla `GV-131` jest `GV-151`
*„Maść żywokostowa **z gojnikiem** i olejem CBD 140ml"* — inny produkt, spoza cennika.
Dopasowanie jest poprawne, ale margines 0,157 to najciaśniejszy przypadek na liście AUTO.

---

## Wymuszone reguły zastosowane w obu plikach

| Reguła | Efekt |
|---|---|
| `PODCIAG` — nazwa cennika zawiera się w innej nazwie cennika | POMIŃ, priorytet nad wszystkim |
| Flaga z Etapu 0 (STAGING: lp 27/28/54/55) | POMIŃ |
| SKU `GV-133` (duplikat Apleplus, ID 14) | POMIŃ |
| lp 27 / 28 / 29 (warianty Chromu) | POMIŃ |
| SKU już EAN-13 | POMIŃ (operacja pusta) |
| Sprzeczna gramatura/pojemność | DO_WERYFIKACJI wymuszone |
| Runner-up w 0,05 od zwycięzcy | POMIŃ (`AMBIGUOUS`) |
| score ≥ 0,95 | AUTO |
| score 0,70–0,94 | DO_WERYFIKACJI |
| score < 0,70 | POMIŃ |

**Wymuszone DO_WERYFIKACJI (sprzeczna gramatura), identyczne w obu plikach:**

| score | Cennik | Produkt | Sprzeczność |
|---|---|---|---|
| 0,525 | RABKA SPA MINERALE SPRAY 215 ML | 195 `GV-108` spray 200 ml | 215 vs 200 ml |
| 0,478 | CALCIUM NATURAL 60 KAPS | 23 `GV-114` 70 kapsułek | 60 vs 70 kaps. |

---

## Błąd w moim narzędziu, wykryty i naprawiony

Pierwsza implementacja kolumny `powod_pominiecia` dodała ją do nagłówka, ale **nie
wypełniała wartości w wierszach**. `csv.DictWriter` przy brakującym kluczu cicho wstawia
pustą wartość (`restval`), więc nie było żadnego błędu — plik wyglądał poprawnie, a cała
kolumna była pusta i raport pokazywał „PODCIAG: 0 pozycji".

Naprawione. Dołożono dwie asercje przed zapisem:

```python
for _r in rows:
    assert not (set(COLS) - set(_r)), f'BRAK KLUCZY W WIERSZU: {...}'
assert any(r['powod_pominiecia'] for r in rows), 'powod_pominiecia pusty we wszystkich wierszach'
```

Liczby w tym raporcie pochodzą z przebiegu **po** naprawie.

---

## Bezpieczeństwo

Etap 1a: wyłącznie odczyt plików CSV — **zero zapytań zapisujących do bazy**.
Punkt 1: 5 wywołań `WC_Product::save()` na stagingu, poprzedzonych pełnym dumpem.
**Produkcji nie modyfikowano** — odczyty z prod służyły wyłącznie wygenerowaniu
`plan_ean_PROD.csv` i porównaniom. Nie dotknięto `_gspb_post_css`, wariantów, opisów,
obrazków, kategorii ani stanów magazynowych.
