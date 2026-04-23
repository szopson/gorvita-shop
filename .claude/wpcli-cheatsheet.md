# WP-CLI Cheatsheet — Gorvita

## Prefix do każdej komendy
cd /opt/gorvita-shop
docker compose exec wordpress wp --allow-root

## Skrót (ustaw alias w sesji)
alias wpcli='docker compose exec wordpress wp --allow-root'

## Produkty
wpcli post list --post_type=product --format=count
wpcli post list --post_type=product --format=table --fields=ID,post_title,post_status

## Strony
wpcli post list --post_type=page --format=table --fields=ID,post_title,post_name
wpcli post update [ID] --post_content="..." --post_status=publish

## Pluginy
wpcli plugin list --format=table
wpcli plugin activate [slug]
wpcli plugin deactivate [slug]

## Opcje WooCommerce
wpcli option get woocommerce_currency
wpcli option set woocommerce_currency PLN

## Cache
wpcli cache flush
wpcli rewrite flush

## Shipping zones
wpcli wc shipping_zone list --user=1
wpcli wc shipping_zone create --name="Polska" --user=1

## Użytkownicy
wpcli user list --format=table
wpcli user create b2b_test test@example.com --role=b2b_customer

## Baza danych (przez MariaDB)
docker compose exec mariadb mysql -uroot -p"$(grep MYSQL_ROOT_PASSWORD .env | cut -d= -f2)" gorvita

## Search-replace (przed launchem)
wpcli search-replace 'gorvita.srv1594477.hstgr.cloud' 'sklep.gorvita.pl' --all-tables --dry-run
