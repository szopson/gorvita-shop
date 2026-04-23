# Decyzje architektoniczne — Gorvita Shop

## Motyw: Blocksy + gorvita-child
Blocksy jako parent (nie FSE/blocks), gorvita-child dla customizacji.
Nie używamy Page Buildera (Elementor, Bricks) — zbyt ciężkie dla tego projektu.

## B2B: B2BKing (nie WooCommerce Memberships, nie własny kod)
B2BKing $179/rok obsługuje: grupy cenowe, hidden pricing, formularz rejestracji, min. zamówienia.
Własny kod b2b.php to tymczasowy placeholder do czasu zakupu licencji.
Nie sugeruj WooCommerce Memberships — za drogie i brak hidden pricing out-of-box.

## SMTP: FluentSMTP + Resend
Resend wybrany za darmowy tier (3000 maili/mies.) i prostą konfigurację.
Nie WP Mail SMTP, nie Postmark — Resend już skonfigurowany i przetestowany.

## Płatności: PayU jako primary gateway
Gorvita ma już konto PayU (sklep@gorvita.com.pl).
Przelewy24 jako opcja jeśli PayU nie zadziała — brak konta, trzeba założyć.
Stripe — nie (brak popularności w PL B2B).

## Wysyłka: InPost + FedEx
InPost: paczkomaty (plugin aktywny, czeka na API key).
FedEx: umowa podpisana, konfiguracja po spotkaniu z Pawłem.
Nie DHL, nie DPD — Gorvita ma umowy z InPost i FedEx.

## Analityka: GA4 przez GTM
GTM jako kontener (łatwiejsza zmiana dla Pawła bez dewelopera).
Facebook Pixel też przez GTM.
PostHog do product analytics (tańszy niż Hotjar).

## KSeF i NIP: WP Desk Pole NIP
Wymagane od 04.2026. Walidacja NIP przez GUS API, autofill danych firmy.
Nie własna implementacja — zbyt skomplikowane (GUS API, walidacja algorytmu NIP).

## Cache: Redis Object Cache
Redis już w stacku docker-compose.
Nie W3 Total Cache, nie WP Rocket — Redis Object Cache wystarcza dla tego projektu.

## SEO: Rank Math
Licencja w .env (RANKMATH_LICENSE).
Nie Yoast — Rank Math ma lepsze schema dla produktów WooCommerce.
