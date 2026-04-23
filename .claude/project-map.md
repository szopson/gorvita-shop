# Gorvita Shop — Mapa plików projektu

## Struktura na VPS (/opt/gorvita-shop/)
```
/opt/gorvita-shop/
├── docker-compose.yml      # Stack: Traefik, WordPress, MariaDB, Redis
├── .env                    # Credentials (NIE w repo)
├── CLAUDE.md               # Status projektu
├── .claude/
│   ├── project-map.md      # Ten plik — mapa struktury
│   ├── wpcli-cheatsheet.md # Gotowe komendy WP-CLI
│   └── tasks/              # Pliki z aktualnymi taskami
│       ├── smtp.md
│       ├── b2b.md
│       ├── payments.md
│       └── launch-checklist.md
└── wp-content/             # Montowany do kontenera WordPress
    └── themes/
        └── gorvita-child/  # Własny motyw
            ├── style.css
            ├── functions.php
            ├── inc/        # Komponenty PHP
            └── assets/     # CSS/JS/img
```

## Kontener WordPress
```
/var/www/html/              # Root WordPress
├── wp-config.php           # Config (z .env)
└── wp-content/
    ├── themes/gorvita-child/
    ├── plugins/            # NIE edytuj bezpośrednio
    └── uploads/            # Media (NIE w repo)
```

## GitHub repo
```
gorvita-shop/
├── wp-content/themes/gorvita-child/    # W REPO ✅
├── wp-content/plugins/gorvita-chatbot/ # W REPO ✅
├── .github/workflows/                  # CI/CD
└── CLAUDE.md
```

## Co NIE jest w repo
- wp-content/uploads/ (media)
- wp-content/plugins/* (poza gorvita-chatbot)
- wp-config.php
- .env
