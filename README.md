# Gorvita Shop

WordPress + WooCommerce sklep dla Gorvita.

## Quick Start

```bash
cp .env.example .env
# edytuj .env
docker compose up -d
docker compose exec wordpress bash /var/scripts/setup.sh
```

## Stack

- WordPress + WooCommerce
- MariaDB 11
- Redis 7
- Traefik (SSL)
