<?php
/**
 * Gorvita — Product descriptions for 12 remaining products (without source descriptions in WC)
 * IDs: 14, 21, 25, 55, 73, 75, 107, 155, 165, 177, 179, 209
 * Run: docker exec gorvita-wordpress wp eval-file /tmp/gorvita_descriptions_missing.php --allow-root
 */

$products = [
    14 => [
        'name'            => 'Apleplus C, E 30 kapsułek',
        'focus_keyword'   => 'witamina C E suplement',
        'type'            => 'supplement',
        'main_component'  => 'witaminy C i E',
        'seo_title'       => 'Apleplus C, E — witaminy C i E + ocet | Gorvita',
        'seo_description' => 'Apleplus C, E — źródło octu jabłkowego i witamin C, E. Wspiera odporność i chroni przed stresem oksydacyjnym.',
        'html'            => <<<'HTML'
<p><strong>Apleplus C, E</strong> — suplement diety zawierający ocet jabłkowy oraz witaminy C i E. Witaminy pomagają w ochronie komórek przed stresem oksydacyjnym.</p>

<h2>Działanie i właściwości</h2>
<p>Witamina C i E pomaga w ochronie komórek przed stresem oksydacyjnym. Witamina C pomaga w prawidłowym funkcjonowaniu układu odpornościowego oraz przyczynia się do zmniejszenia uczucia zmęczenia i znużenia.</p>

<h2>Główne składniki czynne</h2>
<table>
<tr><th>Składnik</th><th>Na 2 kapsułki (porcja dzienna)</th><th>Działanie</th></tr>
<tr><td>Ocet jabłkowy</td><td>300 mg</td><td>Wsparcie metabolizmu</td></tr>
<tr><td>Witamina C</td><td>60 mg (75%)</td><td>Odporność, energia, antystres oksydacyjny</td></tr>
<tr><td>Witamina E</td><td>12 mg (100%)</td><td>Antyoksydant, ochrona komórek</td></tr>
</table>

<h2>Skład</h2>
<p>Ekstrakt octu jabłkowego, witamina C (kwas L-askorbinowy), witamina E (D-alfa-tokoferol), żelatyna (składnik kapsułki).</p>

<h2>Dawkowanie</h2>
<p>Przyjmować 2 kapsułki dziennie. Kapsułkę należy przełknąć i popić wodą.</p>

<h2>Dla kogo?</h2>
<p>Produkt dedykowany osobom szukającym wsparcia dla odporności i ochrony antyoksydacyjnej. Szczególnie rekomendowany w okresach zwiększonego zmęczenia lub obniżonej odporności.</p>

<p><em>Suplement diety nie zastępuje zróżnicowanej diety i zdrowego trybu życia. Przed zastosowaniem skonsultuj się z lekarzem lub farmaceutą. Nie przekraczaj zalecanej porcji do spożycia w ciągu dnia.</em></p>
HTML
    ],

    21 => [
        'name'            => 'BABKA PŁESZNIK mielona 150g',
        'focus_keyword'   => 'babka płesznik błonnik',
        'type'            => 'supplement',
        'main_component'  => 'babka płesznik',
        'seo_title'       => 'BABKA PŁESZNIK mielona — naturalny błonnik | Gorvita',
        'seo_description' => 'BABKA PŁESZNIK mielona — sproszkowane nasiona bogatą w naturalny błonnik. Wspiera regulację pracy przewodu pokarmowego.',
        'html'            => <<<'HTML'
<p><strong>BABKA PŁESZNIK mielona</strong> — sproszkowane nasiona babki płesznika zawierające naturalny błonnik i śluzy roślinne. Suplement wspierający prawidłową pracę przewodu pokarmowego.</p>

<h2>Działanie i właściwości</h2>
<p>Babka płesznik zawiera naturalne błonniki i śluzy roślinne, które wspomagają regulację pracy jelit i utrzymanie prawidłowego perystaltyki. Stanowi doskonałe źródło naturalnego błonnika dla osób szukających wsparcia w prawidłowym funkcjonowaniu przewodu pokarmowego.</p>

<h2>Główne składniki czynne</h2>
<table>
<tr><th>Składnik</th><th>Na porcję dzienną (3g)</th><th>Działanie</th></tr>
<tr><td>Mielone nasiona babki płesznika</td><td>3 g</td><td>Naturalny błonnik, wsparcie dla jelit</td></tr>
</table>

<h2>Skład</h2>
<p>Mielone nasiona babki płesznika (Plantago afra L.).</p>

<h2>Dawkowanie</h2>
<p>Przyjmować 1 miarkę (3g) dziennie. Miarką znajdującą się wewnątrz opakowania odmierzyć dzienną porcję i wymieszać z wodą, sokiem owocowym lub produktami mlecznymi (np. kefir) i wypić. Można spożywać bez rozpuszczania w płynach.</p>

<h2>Dla kogo?</h2>
<p>Produkt rekomendowany dla osób szukających naturalnego wsparcia dla zdrowia jelit i właściwego funkcjonowania przewodu pokarmowego. Szczególnie przydatny dla osób o niskiej podaży błonnika w diecie.</p>

<p><em>Suplement diety nie zastępuje zróżnicowanej diety i zdrowego trybu życia. Przed zastosowaniem skonsultuj się z lekarzem lub farmaceutą. Nie przekraczaj zalecanej porcji do spożycia w ciągu dnia.</em></p>
HTML
    ],

    25 => [
        'name'            => 'CARBOsal syrop 100ml',
        'focus_keyword'   => 'borówka czernica syrop witamina',
        'type'            => 'supplement',
        'main_component'  => 'borówka czernica',
        'seo_title'       => 'CARBOsal syrop — borówka czernica + witamina C | Gorvita',
        'seo_description' => 'CARBOsal syrop — ekstrakt z borówki czernicy + witamina C. Wspiera system trawienny i funkcjonowanie błon śluzowych.',
        'html'            => <<<'HTML'
<p><strong>CARBOsal syrop</strong> — suplement diety w postaci syropu o smaku coli, zawierający ekstrakt z borówki czernicy i witaminę C. Wspiera prawidłowe funkcjonowanie układu pokarmowego.</p>

<h2>Działanie i właściwości</h2>
<p>Borówka czernica wspomaga funkcjonowanie błon śluzowych w żołądku i jelicie cienkim, przyczynia się do prawidłowego metabolizmu węglowodanów, tłuszczów i białek oraz wspiera układ pokarmowy. Chroni śluz żołądkowy i spowalnia tranzyt jelitowy. Witamina C wspiera funkcjonowanie układu odpornościowego.</p>

<h2>Główne składniki czynne</h2>
<table>
<tr><th>Składnik</th><th>Na porcję dzienną (dorośli: 2 łyżki)</th><th>Działanie</th></tr>
<tr><td>Ekstrakt z borówki czernicy 4:1</td><td>200 mg</td><td>Wsparcie błon śluzowych, metabolizm, układ pokarmowy</td></tr>
<tr><td>Witamina C</td><td>30 mg (37,5%)</td><td>Odporność, energia</td></tr>
</table>

<h2>Skład</h2>
<p>Syrop cukrowy, ekstrakt z owoców borówki czernicy (czarnej jagody — Vaccinium myrtillus fructis) 4:1, witamina C (kwas L-askorbinowy), barwnik — węgiel roślinny E153, aromat coli.</p>

<h2>Dawkowanie</h2>
<p>Dzieci: 2 łyżeczki (10ml) dziennie. Dorośli: 2 łyżki (20ml) dziennie.</p>

<h2>Dla kogo?</h2>
<p>Produkt rekomendowany dla osób szukających naturalnego wsparcia dla zdrowia przewodu pokarmowego. Szczególnie przydatny dla osób z obciążonym żołądkiem oraz tych szukających wzmocnienia odporności.</p>

<p><em>Suplement diety nie zastępuje zróżnicowanej diety i zdrowego trybu życia. Przed zastosowaniem skonsultuj się z lekarzem lub farmaceutą. Nie przekraczaj zalecanej porcji do spożycia w ciągu dnia.</em></p>
HTML
    ],

    55 => [
        'name'            => 'GOTU KOLA x60 kapsułek',
        'focus_keyword'   => 'gotu kola centella asiatica',
        'type'            => 'supplement',
        'main_component'  => 'gotu kola',
        'seo_title'       => 'GOTU KOLA x60 — Centella Asiatica | Gorvita',
        'seo_description' => 'GOTU KOLA x60 — sproszkowana wąkrota azjatycka (Centella Asiatica). Wspiera krążenie i komfort trawienny.',
        'html'            => <<<'HTML'
<p><strong>GOTU KOLA x60 kapsułek</strong> — suplement diety zawierający sproszkowane młode pędy wąkroty azjatyckiej (Centella Asiatica). Tradycyjne zioło wspierające układ krążenia i funkcjonowanie przewodu pokarmowego.</p>

<h2>Działanie i właściwości</h2>
<p>GOTU KOLA (wąkrota azjatycka) przyczynia się do prawidłowego funkcjonowania układu krążenia oraz poprawia komfort trawienny. Zioło to stosowane jest od wieków jako naturalny środek wspierający zdrowotny stan skóry i witalność.</p>

<h2>Główne składniki czynne</h2>
<table>
<tr><th>Składnik</th><th>Na porcję dzienną (2 kapsułki)</th><th>Działanie</th></tr>
<tr><td>Sproszkowane młode pędy wąkroty azjatyckiej</td><td>840 mg</td><td>Wsparcie krążenia, trawienie</td></tr>
</table>

<h2>Skład</h2>
<p>Sproszkowane młode pędy wąkroty azjatyckiej (Centella Asiatica), żelatyna (składnik kapsułki).</p>

<h2>Dawkowanie</h2>
<p>Przyjmować 2 kapsułki na dobę. Kapsułkę połknąć i popić wodą.</p>

<h2>Dla kogo?</h2>
<p>Produkt dedykowany osobom szukającym naturalnego wsparcia dla układu krążenia i zdrowia przewodu pokarmowego. Rekomendowany dla osób aktywnych i tych prowadzących zdystansowany tryb życia.</p>

<p><em>Suplement diety nie zastępuje zróżnicowanej diety i zdrowego trybu życia. Przed zastosowaniem skonsultuj się z lekarzem lub farmaceutą. Nie przekraczaj zalecanej porcji do spożycia w ciągu dnia.</em></p>
HTML
    ],

    73 => [
        'name'            => 'OSTROPEST mielony 150g',
        'focus_keyword'   => 'ostropest wątroba detoksykacja',
        'type'            => 'supplement',
        'main_component'  => 'ostropest',
        'seo_title'       => 'OSTROPEST mielony — wsparcie wątroby | Gorvita',
        'seo_description' => 'OSTROPEST mielony — naturalne wsparcie dla funkcji wątroby, detoksykacji i zdrowia żołądka. Karczoch + ostropest.',
        'html'            => <<<'HTML'
<p><strong>OSTROPEST mielony 150g</strong> — suplement diety zawierający sproszkowany ostropest i karczoch. Tradycyjne zioła wspierające funkcję wątroby, detoksykację i prawidłowe funkcjonowanie przewodu pokarmowego.</p>

<h2>Działanie i właściwości</h2>
<p>Karczoch wspiera funkcję wydalniczą nerek, zawiera naturalnie występujące przeciwutleniacze, przyczynia się do utrzymania prawidłowego poziomu lipidów we krwi, pomaga w trawieniu oraz wspomaga detoksykację organizmu.</p>
<p>Ostropest pomaga w utrzymaniu prawidłowego poziomu glukozy we krwi, przyczynia się do ochrony wątroby i wspiera jej prawidłowe funkcjonowanie. Dodatkowo wspomaga trawienie i oczyszczanie organizmu.</p>

<h2>Główne składniki czynne</h2>
<table>
<tr><th>Składnik</th><th>Działanie</th></tr>
<tr><td>Ostropest</td><td>Ochrona wątroby, detoksykacja, funkcja hepatyczna</td></tr>
<tr><td>Karczoch</td><td>Wsparcie nerek, lipidy, metabolizm, trawienie</td></tr>
</table>

<h2>Skład</h2>
<p>Sproszkowany ostropest i karczoch.</p>

<h2>Dawkowanie</h2>
<p>Przyjmować 1–2 kapsułki dziennie po posiłku. Kapsułkę połknąć i popić wodą.</p>

<h2>Dla kogo?</h2>
<p>Produkt rekomendowany dla osób szukających naturalnego wsparcia dla wątroby, zdrowia przewodu pokarmowego i detoksykacji organizmu. Szczególnie przydatny dla osób z obciążoną pracą wątroby lub szukających wsparcia metabolicznego.</p>

<p><em>Suplement diety nie zastępuje zróżnicowanej diety i zdrowego trybu życia. Przed zastosowaniem skonsultuj się z lekarzem lub farmaceutą. Nie przekraczaj zalecanej porcji do spożycia w ciągu dnia.</em></p>
HTML
    ],

    75 => [
        'name'            => 'OSTROPEST x60 kapsułek',
        'focus_keyword'   => 'ostropest karczoch wątroba',
        'type'            => 'supplement',
        'main_component'  => 'ostropest',
        'seo_title'       => 'OSTROPEST x60 — karczoch + ostropest | Gorvita',
        'seo_description' => 'OSTROPEST x60 — naturalny suplement do wątroby. Karczoch i ostropest wspierają detoksykację i funkcję hepatyczną.',
        'html'            => <<<'HTML'
<p><strong>OSTROPEST x60 kapsułek</strong> — suplement diety zawierający ostropest i karczoch. Naturalne wsparcie dla funkcji wątroby, detoksykacji i zdrowia metabolicznego.</p>

<h2>Działanie i właściwości</h2>
<p>Karczoch wspiera funkcję wydalniczą nerek, zawiera naturalnie występujące przeciwutleniacze, przyczynia się do utrzymania prawidłowego poziomu lipidów we krwi, pomaga w trawieniu oraz wspomaga detoksykację organizmu. Wspomaga utratę wagi i utrzymanie prawidłowego poziomu cholesterolu.</p>
<p>Ostropest pomaga w utrzymaniu prawidłowego poziomu glukozy we krwi, przyczynia się do ochrony wątroby, wspomaga prawidłowe funkcjonowanie wątroby oraz wspiera trawienie i oczyszczanie organizmu.</p>

<h2>Główne składniki czynne</h2>
<table>
<tr><th>Składnik</th><th>Działanie</th></tr>
<tr><td>Ostropest</td><td>Ochrona wątroby, prawidłowy poziom glukozy, detoksykacja</td></tr>
<tr><td>Karczoch</td><td>Wsparcie nerek, lipidy, cholesterol, trawienie</td></tr>
</table>

<h2>Skład</h2>
<p>Kapsułki zawierające sproszkowany ostropest i karczoch.</p>

<h2>Dawkowanie</h2>
<p>Przyjmować 1–2 kapsułki dziennie po posiłku. Kapsułkę połknąć i popić wodą.</p>

<h2>Dla kogo?</h2>
<p>Produkt dedykowany osobom szukającym naturalnego wsparcia dla wątroby, zdrowia metabolicznego i detoksykacji. Szczególnie rekomendowany dla osób starających się utrzymać prawidłowe lipidy we krwi i cholesterol.</p>

<p><em>Suplement diety nie zastępuje zróżnicowanej diety i zdrowego trybu życia. Przed zastosowaniem skonsultuj się z lekarzem lub farmaceutą. Nie przekraczaj zalecanej porcji do spożycia w ciągu dnia.</em></p>
HTML
    ],

    107 => [
        'name'            => 'BLIZNA Maść na blizny 20ml',
        'focus_keyword'   => 'maść na blizny przebarwienia',
        'type'            => 'cosmetic',
        'main_component'  => 'kwas hialuronowy + ekstrakt z cebuli',
        'seo_title'       => 'BLIZNA maść na blizny — kwas hialuronowy | Gorvita',
        'seo_description' => 'BLIZNA — maść redukująca widoczność blizn i przebarwień. Kwas hialuronowy, diacetyl boldine, ekstrakt z cebuli.',
        'html'            => <<<'HTML'
<p><strong>BLIZNA Maść na blizny i przebarwienia</strong> — kosmetyczna maść zawierająca kwas hialuronowy, ekstrakt z cebuli, diacetyl boldine, alantolinę i panthenol. Skutecznie zmniejsza widoczność blizn i przebarwień różnego pochodzenia.</p>

<h2>Działanie i właściwości</h2>
<p>Zawarte w maści składniki skutecznie zmniejszają widoczność oraz wielkość blizn i przebarwień różnego pochodzenia na skórze. Diacetyl Boldine oraz wyciąg z cebuli pomagają zmniejszać blizny po trądzikowe, chirurgiczne i pourazowe. Kompozycja wszystkich składników przyspieszają proces regeneracji naskórka. Działają antybakteryjnie, tonizująco i utrzymują właściwy poziom nawilżenia. Maść stosowana regularnie przywraca skórze elastyczność i witalność.</p>

<h2>Główne składniki czynne</h2>
<table>
<tr><th>Składnik</th><th>Działanie</th></tr>
<tr><td>Kwas hialuronowy (Sodium Hyaluronate)</td><td>Nawilżające, wygładzające, poprawia elastyczność i redukcję blizn</td></tr>
<tr><td>Ekstrakt z kory drzewa Boldo (Diacetyl Boldine)</td><td>Rozjaśniające, regeneracyjne, redukcja przebarwień</td></tr>
<tr><td>Ekstrakt z cebuli (Allium Cepa Bulb Extract)</td><td>Regeneracyjne, rozjaśniające, redukcja blizn i plam pigmentacyjnych</td></tr>
<tr><td>Alantoina</td><td>Nawilżające, zmiękczające, wygładzające</td></tr>
<tr><td>Panthenol</td><td>Nawilżające, regeneracyjne, wzmacniające elastyczność</td></tr>
</table>

<h2>Skład</h2>
<p>Aqua, Glycerin, Glyceryl Stearate SE, Caprylic/Capric Trigliceride, Stearyl Alkohol, Cetyl Ricinoleate, Allium Cepa Bulb Extract, Sodium Hyaluronate, Diacetyl Boldine, Allantoin, Panthenol, Phenoxyethanol.</p>

<h2>Dawkowanie</h2>
<p>Stosować zewnętrznie kilka razy dziennie lub w zależności od potrzeb, wmasowując maść w skórę i pozostawiając do wchłonięcia.</p>

<h2>Dla kogo?</h2>
<p>Maść dedykowana osobom szukającym wsparcia w redukcji blizn i przebarwień. Może być stosowana do każdego rodzaju cery, szczególnie suchej i wrażliwej. Idealna dla osób po zabiegach chirurgicznych, urazach skóry lub zmagających się z bliznami trądzikowymi.</p>

<p><em>Kosmetyk. Przed zastosowaniem zapoznaj się z informacją zawartą na opakowaniu. Nie stosować w przypadku nadwrażliwości na którykolwiek składnik preparatu. W przypadku podrażnienia skóry zaprzestań używania i skonsultuj się z lekarzem.</em></p>
HTML
    ],

    155 => [
        'name'            => 'Maść Konopna z olejem CBD 5% 80ml',
        'focus_keyword'   => 'maść CBD konopna 5% regeneracja',
        'type'            => 'cosmetic',
        'main_component'  => 'olej konopi CBD 5%',
        'seo_title'       => 'Maść Konopna CBD 5% — regeneracja skóry | Gorvita',
        'seo_description' => 'Maść Konopna 5% CBD — olejek konopny 200mg + witaminy A, E, F. Regeneruje i nawilża suchą, atopową skórę.',
        'html'            => <<<'HTML'
<p><strong>Maść Konopna z olejem CBD 5%</strong> — kosmetyczna maść do pielęgnacji skóry zawierająca 200 mg/80 ml kannabidiolu (CBD) z oleju konopnego. Wzbogacona witaminami A, E, F oraz alantoliną i panthenolu.</p>

<h2>Działanie i właściwości</h2>
<p>Działanie maści jest związane z właściwościami oleju z konopi zawierającego Kannabidiol CBD 5%. Składniki aktywne przyspieszają proces odnowy naskórka. Regulują gospodarkę hydro-lipidową, przywracając skórze elastyczność i witalność. Łagodzą uczucie dyskomfortu wywołane różnymi czynnikami, w tym uczucie swędzenia skóry. Maść polecana jest do pielęgnacji skóry suchej i atopowej.</p>

<h2>Główne składniki czynne</h2>
<table>
<tr><th>Składnik</th><th>Działanie</th></tr>
<tr><td>Olej z konopi 5% CBD (Cannabis Sativa Seed Oil)</td><td>Regeneracja skóry, regulacja sebum, wsparcie przy trądziku</td></tr>
<tr><td>Witamina A (Retinol)</td><td>Odnowa komórkowa, produkcja kolagenu, rozjaśnianie</td></tr>
<tr><td>Witamina E (Tokoferol)</td><td>Antyoksydant, regeneracja, nawilżanie</td></tr>
<tr><td>Witamina F (NNKT)</td><td>Regeneracja bariery hydro-lipidowej, nawilżenie</td></tr>
<tr><td>Alantoina</td><td>Nawilżająca, zmiękczająca, wygładzająca</td></tr>
<tr><td>Panthenol</td><td>Nawilżająca, regeneracyjna, wzmacniająca elastyczność</td></tr>
</table>

<h2>Skład</h2>
<p>Aqua, Glycerin, Glyceryl Stearate SE, Caprylic/Capric Trigliceride, Cetyl Alkohol, Cetyl Ricinoleate, Cannabis Sativa Seed Oil, Tocopherol, Linoleic Acid, Retinyl Palmitate, Hydantoin, Allantoin, Panthenol, Ethylhexylglycerin, Benzyl Alcohol.</p>

<h2>Dawkowanie</h2>
<p>Stosować zewnętrznie kilka razy dziennie lub w zależności od potrzeb, delikatnie wmasowując maść w skórę aż do wchłonięcia. Maść dobrze się wchłania i nie pozostawia na skórze tłustej warstwy.</p>

<h2>Dla kogo?</h2>
<p>Maść dedykowana osobom szukającym naturalnego wsparcia dla skóry suchej i atopowej. Idealna dla osób zmagających się z podrażnieniami skóry, bliznami oraz tymi szukającymi intensywnej regeneracji i nawilżenia.</p>

<p><em>Kosmetyk. Przed zastosowaniem zapoznaj się z informacją zawartą na opakowaniu. Nie stosować w przypadku nadwrażliwości na którykolwiek składnik preparatu. W przypadku podrażnienia skóry zaprzestań używania i skonsultuj się z lekarzem.</em></p>
HTML
    ],

    165 => [
        'name'            => 'Maść Rumiankowa z olejem CBD 5% 80ml',
        'focus_keyword'   => 'maść rumiankowa CBD łagodząca',
        'type'            => 'cosmetic',
        'main_component'  => 'ekstrakt z rumianku + olej konopi CBD 5%',
        'seo_title'       => 'Maść Rumiankowa CBD 5% — łagodzenie i regeneracja | Gorvita',
        'seo_description' => 'Maść Rumiankowa 5% CBD — naturalny ekstrakt rumianku + olej konopny. Łagodzi podrażnienia i regeneruje skórę.',
        'html'            => <<<'HTML'
<p><strong>Maść Rumiankowa z olejem CBD 5%</strong> — kosmetyczna maść zawierająca naturalny ekstrakt z rumianku i olej konopny (CBD 5%). Szybko wchłaniająca się maść wspierająca zdrową skórę.</p>

<h2>Działanie i właściwości</h2>
<p>Działanie maści związane jest z naturalnymi właściwościami ekstraktu z rumianku i oleju konopnego (CBD 5%). Składniki te wykazują działanie antybakteryjne, łagodzące i regenerujące naskórek podrażniony różnymi czynnikami zewnętrznymi. Maść dobrze nawilża i wygładza suchą i podrażnioną skórę, witalizuje i widocznie poprawia kondycję oraz wyrównuje jej koloryt. Działanie wzbogaca zawarta w składzie lecznicza woda mineralna. Maść dobrze się wchłania, nie pozostawia na skórze tłustej warstwy.</p>

<h2>Główne składniki czynne</h2>
<table>
<tr><th>Składnik</th><th>Działanie</th></tr>
<tr><td>Ekstrakt z rumianku (Chamomilla Recutita Extract)</td><td>Łagodzące, regeneracyjne, nawilżające, antybakteryjne</td></tr>
<tr><td>Olej z konopi 5% CBD (Cannabis Sativa Seed Oil)</td><td>Regeneracja skóry, regulacja sebum, wsparcie przy trądziku</td></tr>
<tr><td>Alantoina</td><td>Nawilżająca, zmiękczająca, wygładzająca</td></tr>
<tr><td>Panthenol</td><td>Nawilżająca, regeneracyjna, wzmacniająca elastyczność</td></tr>
</table>

<h2>Skład</h2>
<p>Aqua, Propylene Glycol, Chamomilla Recutita Extract, Cocos Nucifera (Coconut) Oil, Glycerin, Paraffinum Liquidum, Etylhexyl Stearate, Polyglyceryl-3-Methylglucose Distearate, Cannabis Sativa Seed Oil, Cyclopentasiloxane, Cyclohexasiloxane, Carbomer, Triethanolamine, Allantoin, Panthenol, Phenoxyethanol.</p>

<h2>Dawkowanie</h2>
<p>Stosować zewnętrznie kilka razy dziennie lub w zależności od potrzeb, delikatnie wmasowując maść w skórę aż do wchłonięcia.</p>

<h2>Dla kogo?</h2>
<p>Maść dedykowana osobom szukającym naturalnego wsparcia dla skóry podrażnionej, suchej i wrażliwej. Idealna dla osób zmagających się z zapaleniami, rumienczyzną, alergią skórną (AZS) oraz tymi szukającymi łagodzącego i regenerującego działania.</p>

<p><em>Kosmetyk. Przed zastosowaniem zapoznaj się z informacją zawartą na opakowaniu. Nie stosować w przypadku nadwrażliwości na którykolwiek składnik preparatu. W przypadku podrażnienia skóry zaprzestań używania i skonsultuj się z lekarzem.</em></p>
HTML
    ],

    177 => [
        'name'            => 'Mosqitos 100ml (żel)',
        'focus_keyword'   => 'żel łagodzący ukąszenia owadów aloe',
        'type'            => 'cosmetic',
        'main_component'  => 'ekstrakt z aloesu + żywokost',
        'seo_title'       => 'Mosqitos żel 100ml — łagodzenie ukąszań | Gorvita',
        'seo_description' => 'Mosqitos żel — aloe vera + żywokost + mentol. Łagodzy podrażnienia po ukąszeniach owadów i przyspiesza regenerację.',
        'html'            => <<<'HTML'
<p><strong>Mosqitos 100ml — żel łagodzący po ukąszeniach owadów</strong> — kosmetyczne żele zawierające ekstrakt z aloesu, żywokostu, mentolu i panthenolu. Szybko działające wsparcie dla podrażnionej skóry.</p>

<h2>Działanie i właściwości</h2>
<p>Zawarty w żelu wyciąg z aloesu stosowany od lat jako naturalny i skuteczny środek pomocny przy problemach skórnych. Alantoina i panthenol wspomagają regenerację tkanki skórnej podrażnionej wieloma czynnikami zewnętrznymi. Ekstrakt z żywokostu wspomaga proces regeneracji i gojenia. Mentol daje uczucie świeżości i odprężenia, łagodzy napięcie skóry.</p>

<h2>Główne składniki czynne</h2>
<table>
<tr><th>Składnik</th><th>Działanie</th></tr>
<tr><td>Ekstrakt z Aloesu (Aloe Vera Extract)</td><td>Nawilżające, kojące, regenerujące, łagodzące podrażnienia</td></tr>
<tr><td>Ekstrakt z żywokostu (Symphytum Officinale Extract)</td><td>Regenerujące, wspomaga gojenie i regenerację</td></tr>
<tr><td>Mentol</td><td>Odświeżające, łagodzące napięcie skóry</td></tr>
<tr><td>Alantoina</td><td>Nawilżająca, wspomaga regenerację</td></tr>
<tr><td>Panthenol (D-panthenol)</td><td>Nawilżające, wspomaga regenerację tkanki skórnej</td></tr>
</table>

<h2>Skład</h2>
<p>Aloe Vera Extract, Propylene Glycol, Glycerin, Symphytum officinale Extract, Menthol, Panthenol, Allantoin, Carbomer, Triethanolamine, Phenoxyethanol.</p>

<h2>Dawkowanie</h2>
<p>Stosować zewnętrznie kilka razy dziennie lub w zależności od potrzeb, wmasowując żel w skórę aż do wchłonięcia.</p>

<h2>Dla kogo?</h2>
<p>Żel dedykowany osobom szukającym szybkiego i naturalnego wsparcia przy podrażnieniach skóry spowodowanych ukąszeniami owadów. Bezpieczny dla dzieci od 3. roku życia. Idealny do zabrania na urlop, pikniku i wszelkie aktywności na świeżym powietrzu.</p>

<p><em>Kosmetyk. Przed zastosowaniem zapoznaj się z informacją zawartą na opakowaniu. Nie stosować na skórę podrażnioną, błony śluzowe i oczy. Nie stosować w przypadku nadwrażliwości na którykolwiek składnik preparatu.</em></p>
HTML
    ],

    179 => [
        'name'            => 'Mosqitos Zestaw (Płyn 20ml + Żel 20ml)',
        'focus_keyword'   => 'mosqitos zestaw owady ochrona',
        'type'            => 'cosmetic',
        'main_component'  => 'aloe + lawendy + arnika',
        'seo_title'       => 'Mosqitos Zestaw — ochrona przed owadami | Gorvita',
        'seo_description' => 'Mosqitos Zestaw — płyn ochronny (UV, filtry) + żel łagodzący owady. Lawendy, arnika, mentol, panthenol.',
        'html'            => <<<'HTML'
<p><strong>Mosqitos Zestaw</strong> — kompletne wsparcie przed owadami i podrażnieniami skóry. Zawiera płyn ochronny na skórę (z filtrami UV) i żel łagodzący po ukąszeniach. Naturalne, skuteczne i bezpieczne dla rodziny.</p>

<h2>Działanie i właściwości</h2>
<p><strong>Żel:</strong> Ekstrakt z aloesu stosowany od lat jako naturalny środek pomocny przy problemach skórnych. Alantoina i panthenol wspomagają regenerację tkanki skórnej podrażnionej. Ekstrakt z żywokostu wspomaga proces regeneracji i gojenia. Mentol daje uczucie świeżości i odprężenia.</p>
<p><strong>Płyn:</strong> Kompozycja zapachowa zawarta w składzie zapewnia komfort przebywania na zewnątrz w okresach wiosenno-letnich. Filtry UV chronią skórę przy zastosowaniu w dzień. D-panthenol dodatkowo nawilża i chroni skórę. Ekstrakty z lawendy, arniki i olejkami (herbacianym, eukaliptusowym, cedrowym, goździkowym) dodają uczucia chłodu i świeżości, a ich zapach naturalnie odstraszać owady.</p>

<h2>Główne składniki czynne żelu</h2>
<table>
<tr><th>Składnik</th><th>Działanie</th></tr>
<tr><td>Ekstrakt z Aloesu</td><td>Nawilżające, kojące, regenerujące</td></tr>
<tr><td>Ekstrakt z żywokostu</td><td>Regenerujące, wspomaga gojenie</td></tr>
<tr><td>Mentol</td><td>Odświeżające, łagodzące</td></tr>
<tr><td>Panthenol</td><td>Nawilżające, regeneracyjne</td></tr>
</table>

<h2>Główne składniki czynne płynu</h2>
<table>
<tr><th>Składnik</th><th>Działanie</th></tr>
<tr><td>Ekstrakt z lawendy (Lavandula Extract)</td><td>Odświeżające, ochrona przed owadami</td></tr>
<tr><td>Ekstrakt z arniki (Arnica Montana Extract)</td><td>Regeneracyjne, regeneracja siniaków i obrzęków</td></tr>
<tr><td>Olejki eteryczne (herbaty, eukaliptus, cedr, goździk)</td><td>Rewitalizujące, odświeżające, ochrona przed owadami</td></tr>
<tr><td>Filtry UV (Ethylhexyl ethoxycinnamate, Butyl methoxydibenzoylmethane)</td><td>Ochrona przed promieniowaniem UVB</td></tr>
<tr><td>Panthenol</td><td>Nawilżające, ochrona skóry</td></tr>
</table>

<h2>Skład żelu</h2>
<p>Aloe Vera Extract, Propylene Glycol, Glycerin, Symphytum officinale Extract, Menthol, Panthenol, Allantoin, Carbomer, Triethanolamine, Phenoxyethanol.</p>

<h2>Skład płynu</h2>
<p>Aqua, Lavandula Extract, Arnica Montana Flower Extract, Panthenol, Tea Tree Oil, Eucalyptus Globulus Oil, Cupressus Funebris Wood Oil, Eugenia Caryophyllus Bud Oil, Polisorbate-20, Ethylhexyl ethoxycinnamate, Butylmethoxydibenzoylmethane, Ethylhexyl salicylate, Phenoxyethanol.</p>

<h2>Dawkowanie</h2>
<p><strong>Żel:</strong> Stosować zewnętrznie kilka razy dziennie lub w zależności od potrzeb, wmasowując żel w skórę aż do wchłonięcia.</p>
<p><strong>Płyn:</strong> Rozpylić preparat bezpośrednio na odkryte części ciała. Na twarz nanosić po aplikacji na dłonie, a następnie delikatnie wmasować płyn w skórę.</p>

<h2>Dla kogo?</h2>
<p>Zestaw dedykowany całej rodzinie szukającej naturalnego wsparcia przy aktywności na świeżym powietrzu. Żel idealny do zabrania na wakacje, pikniki i wędrówki. Płyn zapewnia ochronę i komfort. Bezpieczny dla dzieci od 3. roku życia.</p>

<p><em>Kosmetyk. Przed zastosowaniem zapoznaj się z informacją zawartą na opakowaniu. Nie stosować w przypadku nadwrażliwości na którykolwiek składnik preparatu. Na twarz nanosić po aplikacji na dłonie. W przypadku podrażnienia skóry zaprzestań używania i skonsultuj się z lekarzem.</em></p>
HTML
    ],

    209 => [
        'name'            => 'Żel łagodzący po ukąszeniach owadów 20ml',
        'focus_keyword'   => 'żel łagodzący ukąszenia aloe panthenol',
        'type'            => 'cosmetic',
        'main_component'  => 'aloe vera + panthenol',
        'seo_title'       => 'Żel łagodzący po ukąszeniach — aloe + alantoina | Gorvita',
        'seo_description' => 'Żel łagodzący owady — naturalny ekstrakt aloesu, panthenol, alantoina. Łagodzi podrażnienia i wspomaga regenerację.',
        'html'            => <<<'HTML'
<p><strong>Żel łagodzący po ukąszeniach owadów 20ml</strong> — kosmetyczne żele zawierające naturalny ekstrakt z aloesu, alantolinę i panthenol. Szybkie naturalne wsparcie dla podrażnionej skóry.</p>

<h2>Działanie i właściwości</h2>
<p>Zawarty w żelu wyciąg z aloesu stosowany od lat jako naturalny i skuteczny środek pomocny przy problemach skórnych oraz podrażnieniach po ukąszeniach owadów. Alantoina i panthenol wspomagają intensywnie regenerację tkanki skórnej podrażnionej wieloma czynnikami zewnętrznymi. Żel szybko chłodzi i łagodzi podrażnienia, wspomaga naturalny proces gojenia skóry.</p>

<h2>Główne składniki czynne</h2>
<table>
<tr><th>Składnik</th><th>Działanie</th></tr>
<tr><td>Ekstrakt z Aloesu (Aloe Vera Extract)</td><td>Nawilżające, kojące, regenerujące podrażnienia</td></tr>
<tr><td>Alantoina</td><td>Wspomaga regenerację, nawilża, zmiękczająca</td></tr>
<tr><td>Panthenol (D-panthenol)</td><td>Intensywnie nawilża, wspomaga regenerację tkanki skórnej</td></tr>
</table>

<h2>Skład</h2>
<p>Aloe Vera Extract, Propylene Glycol, Glycerin, Allantoin, Panthenol, Carbomer, Triethanolamine, Phenoxyethanol.</p>

<h2>Dawkowanie</h2>
<p>Stosować zewnętrznie kilka razy dziennie lub w zależności od potrzeb, wmasowując żel w skórę aż do wchłonięcia.</p>

<h2>Dla kogo?</h2>
<p>Żel dedykowany osobom szukającym szybkiego i naturalnego wsparcia przy podrażnieniach skóry spowodowanych ukąszeniami owadów. Idealny do zabrania na wakacje, piknicki i aktywności na świeżym powietrzu. Bezpieczny dla całej rodziny, w tym dla dzieci.</p>

<p><em>Kosmetyk. Przed zastosowaniem zapoznaj się z informacją zawartą na opakowaniu. Nie stosować w przypadku nadwrażliwości na którykolwiek składnik preparatu. W przypadku podrażnienia skóry zaprzestań używania i skonsultuj się z lekarzem.</em></p>
HTML
    ],
];

$updated = 0;
$failed = 0;

foreach ( $products as $product_id => $data ) {
    $product = wc_get_product( $product_id );

    if ( ! $product ) {
        WP_CLI::warning( "Product ID $product_id not found" );
        $failed++;
        continue;
    }

    // Update description
    $product->set_description( $data['html'] );
    $product->save();

    // Update Rank Math SEO meta
    update_post_meta( $product_id, 'rank_math_focus_keyword', $data['focus_keyword'] );
    update_post_meta( $product_id, 'rank_math_title', $data['seo_title'] );
    update_post_meta( $product_id, 'rank_math_description', $data['seo_description'] );

    // Clear product transients
    wc_delete_product_transients( $product_id );

    WP_CLI::line( "✅ ID $product_id ({$data['name']}) — zaktualizowany" );
    $updated++;
}

// Flush cache
wp_cache_flush();

WP_CLI::success( "Missing products: $updated updated, $failed failed" );
