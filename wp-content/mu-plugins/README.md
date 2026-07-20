# mu-plugins — UWAGA o deploymencie

> **KRYTYCZNE:** ten katalog **NIE jest** objęty standardowym git-deploy.
> Pliki tu są wersjonowane w gitcie, ale **nie trafiają do działającego
> kontenera** automatycznie. Bez ręcznego kroku znikają przy rebuildzie.

## Dlaczego

`docker-compose.yml` montuje do kontenera WordPress tylko:

```yaml
volumes:
  - wordpress_data:/var/www/html                                   # named volume
  - ./wp-content/themes/gorvita-child:/var/www/html/wp-content/themes/gorvita-child  # bind
```

- **Bind-mount jest TYLKO dla `gorvita-child`** → zmiany motywu z repo/rsync są
  natychmiast żywe w kontenerze.
- `wp-content/mu-plugins/` leży wewnątrz **named volume `wordpress_data`**, NIE
  jest bind-mountowany. Pipeline deploy (rsync do `/opt/gorvita-shop`) zapisuje
  pliki na hoście, ale kontener ich nie widzi — czyta z wolumenu.

**Skutek:** plik mu-plugin dodany do repo i zdeployowany do `/opt` **nie zostaje
załadowany** przez WordPress. Musi zostać **ręcznie skopiowany do wolumenu
kontenera**.

## Jak wdrożyć / odtworzyć mu-plugin w kontenerze (prod i staging)

```bash
cd /opt/gorvita-shop            # (lub /opt/gorvita-staging)
docker compose exec -T wordpress mkdir -p /var/www/html/wp-content/mu-plugins
docker compose cp wp-content/mu-plugins/<plik>.php \
    wordpress:/var/www/html/wp-content/mu-plugins/<plik>.php
docker compose exec -T wordpress php -l /var/www/html/wp-content/mu-plugins/<plik>.php
docker compose exec -T wordpress wp --allow-root eval 'var_dump(wp_get_mu_plugins());'  # weryfikacja
```

> Krok trzeba **powtórzyć po każdym `docker compose down`/rebuildzie** kontenera
> (named volume przeżywa restart, ale nie odtworzenie wolumenu).

## Pliki w tym katalogu

| Plik | Rola | Wersjonowany | Aktywny w kontenerze PROD (2026-07-20) |
|---|---|---|---|
| `gorvita-store-open.php` | wymusza otwarcie sklepu (wyłącza „Coming Soon") | tak | **NIE** — martwy (kontener mu-plugins pusty). Obecnie nieszkodliwy, bo sklep otwarty przez opcję WC, ale latentne ryzyko. |
| `gorvita-b2b-storeapi-netto.php` | tryb netto B2B w Store API `/products` (fix dropdownu wyszukiwarki) | tak | nie wdrożony na prod (aktywny na STAGING w wolumenie) |

## Docelowa naprawa infrastruktury (rekomendacja, poza zakresem fixu)

Dodać bind-mount dla całego `mu-plugins` w `docker-compose.yml`, analogicznie do
motywu, aby pliki z repo były żywe bez ręcznego kopiowania:

```yaml
  - ./wp-content/mu-plugins:/var/www/html/wp-content/mu-plugins
```

Do rozważenia osobno (zmiana compose = restart kontenera).
