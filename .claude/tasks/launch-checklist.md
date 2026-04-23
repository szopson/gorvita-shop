# Checklist przed launchem

## Technika
- [ ] PayU skonfigurowany i przetestowany (sandbox → prod)
- [ ] InPost API skonfigurowany
- [ ] B2BKing zainstalowany i skonfigurowany
- [ ] WP Desk Pole NIP zainstalowany
- [ ] SMTP: zmienić na sklep@gorvita.pl + Resend z gorvita.pl
- [ ] DNS: sklep.gorvita.pl → 76.13.156.173
- [ ] SSL na domenie produkcyjnej
- [ ] search-replace: staging URL → produkcja URL
- [ ] Usunąć shipping zone "Poland" (ID=1), zostaje "Polska" (ID=2)
- [ ] Wordfence: dokończyć konfigurację
- [ ] Redirection plugin: setup wizard

## Content
- [ ] Regulamin: przejrzeć i zatwierdzić z Pawłem
- [ ] Polityka prywatności: zaktualizować o GA4, PostHog, FluentCRM, Facebook Pixel
- [ ] Zdjęcia produktów: 9 produktów bez HQ foto (import 16,49,81,87,95,205,226,232,234)
- [ ] Próg darmowej wysyłki: potwierdzić z Pawłem (sugestia: 199 zł)

## Analityka
- [ ] GA4 + GTM: dodać do wp-head
- [ ] Facebook Pixel: dodać przez GTM
- [ ] Google Search Console: weryfikacja domeny
- [ ] Sitemap: submit do GSC

## B2B
- [ ] Lista klientów B2B od Pawła (nazwa + adres + NIP)
- [ ] Import kont B2B do WooCommerce
- [ ] Test pełnego flow B2B: rejestracja → zatwierdzenie → zakup

## Testy
- [ ] Pełny flow zakupu B2C: produkt → koszyk → PayU → potwierdzenie email
- [ ] Pełny flow B2B: rejestracja → approval → logowanie → zakup
- [ ] Mobile: Chrome/Safari na iOS i Android
- [ ] PageSpeed: target ≥85 mobile

## Launch
- [ ] Backup bazy przed launchem
- [ ] DNS switch
- [ ] 48h monitoring (GSC, GA4, logi błędów)
- [ ] Szkolenie Pawła (2h Zoom + nagranie)
