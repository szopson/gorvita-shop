# SMTP — Status

## ✅ DONE
- FluentSMTP zainstalowany i aktywny
- Provider: Resend (smtp.resend.com:465 SSL)
- From: contact@nexoperandi.cloud (TEST)
- Test mail: DELIVERED ✅
- Root cause fix: provider_settings musi być wypełnione (nie tylko top-level keys)

## ⚠️ PRZED LAUNCHEM
- Zmienić From Email na: sklep@gorvita.pl
- Zweryfikować domenę gorvita.pl w Resend (dodać DNS records)
- Przetestować maile WooCommerce: potwierdzenie zamówienia, faktura

## Fix który zadziałał (2026-04-23)
FluentSMTP czyta SMTP config z connections[smtp][provider_settings],
nie z top-level keys. Musi być:
connections.smtp.provider_settings = {host, port, username, password, encryption}
