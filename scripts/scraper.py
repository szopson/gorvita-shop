#!/usr/bin/env python3
"""
Scraper for sklep.gorvita.com.pl (legacy offon.pl platform).

Extracts all products → data/products.json.
Images are NOT downloaded — only filename is recorded (user has them locally).

Run: python3 scripts/scraper.py [--dry-run] [--limit N]
"""

import argparse
import json
import re
import sys
import time
from pathlib import Path
from urllib.parse import urljoin, urlparse

try:
    import requests
    from bs4 import BeautifulSoup
except ImportError:
    print("Install deps: apt-get install python3-requests python3-bs4 python3-lxml", file=sys.stderr)
    sys.exit(1)


BASE_URL = "https://sklep.gorvita.com.pl"
REPO_ROOT = Path(__file__).resolve().parent.parent
DATA_DIR = REPO_ROOT / "data"
HEADERS = {"User-Agent": "Mozilla/5.0 (compatible; GorvitaMigrationBot/1.0; +contact@nexoperandi.cloud)"}
REQUEST_DELAY = 0.4  # polite pause between requests

# Known category slugs from site navigation
CATEGORIES = [
    ("suplementy-diety", "Suplementy diety"),
    ("do-zastosowania-zewnetrznego", "Do zastosowania zewnętrznego"),
    ("pozostale-nos-gardlo-ucho", "Pozostałe: Nos, Gardło, Ucho"),
]


def polish_slug(text: str) -> str:
    """Normalize Polish text to URL slug."""
    text = text.lower()
    mapping = {"ą": "a", "ć": "c", "ę": "e", "ł": "l", "ń": "n",
               "ó": "o", "ś": "s", "ź": "z", "ż": "z"}
    for pl, en in mapping.items():
        text = text.replace(pl, en)
    text = re.sub(r"[^\w\s-]", "", text)
    text = re.sub(r"[\s_-]+", "-", text).strip("-")
    return text


def fetch(url: str, session: requests.Session) -> BeautifulSoup:
    resp = session.get(url, headers=HEADERS, timeout=30)
    resp.raise_for_status()
    resp.encoding = "utf-8"
    time.sleep(REQUEST_DELAY)
    return BeautifulSoup(resp.text, "lxml")


def extract_product_urls(session: requests.Session) -> dict[str, list[str]]:
    """Returns {category_slug: [product_url, ...]}."""
    result = {}
    for cat_slug, _cat_name in CATEGORIES:
        url = f"{BASE_URL}/produkty/{cat_slug}?sort=name_asc&limit=999&page=1"
        try:
            soup = fetch(url, session)
        except requests.RequestException as e:
            print(f"  ✗ category {cat_slug}: {e}", file=sys.stderr)
            result[cat_slug] = []
            continue

        links = set()
        for a in soup.find_all("a", href=True):
            href = a["href"]
            if f"/produkty/{cat_slug}/" in href:
                clean = href.split("?")[0].split("#")[0].rstrip("/")
                # Must be a product page (ends with -<digits>)
                if re.search(r"-\d+$", clean):
                    links.add(clean if clean.startswith("http") else urljoin(BASE_URL, clean))

        result[cat_slug] = sorted(links)
        print(f"  · {cat_slug}: {len(links)} products")
    return result


def extract_image_filename(img_url: str) -> str:
    """Extract the main image filename from a thumbnail URL.

    Thumb URL: /thumbs/w300h390q90/img/products/big/acerola-500mg.jpg?v=...
    → filename: acerola-500mg.jpg
    Also handles direct URLs like /img/products/big/foo.jpg
    """
    # Remove query string
    path = urlparse(img_url).path
    filename = Path(path).name
    return filename


def parse_product(url: str, category_name: str, session: requests.Session) -> dict | None:
    try:
        soup = fetch(url, session)
    except requests.RequestException as e:
        print(f"  ✗ {url}: {e}", file=sys.stderr)
        return None

    # Title
    title_el = soup.select_one("h1")
    if not title_el:
        print(f"  ✗ no h1: {url}", file=sys.stderr)
        return None
    title = re.sub(r"\s+", " ", title_el.get_text(strip=True))

    # Price — prefer itemprop=price (numeric), fallback to .price with "PLN"
    price = None
    price_el = soup.select_one("[itemprop='price']")
    if price_el:
        price_str = price_el.get("content") or price_el.get_text(strip=True)
        m = re.search(r"(\d+[.,]?\d*)", price_str)
        if m:
            price = float(m.group(1).replace(",", "."))

    if price is None:
        # Fallback: find a .price element with "PLN"
        for el in soup.select(".price"):
            text = el.get_text(" ", strip=True)
            m = re.search(r"(\d+[.,]\d{2})\s*PLN", text)
            if m:
                price = float(m.group(1).replace(",", "."))
                break

    # Description
    description = ""
    desc_el = soup.select_one(".description")
    if desc_el:
        # Keep HTML to preserve paragraphs and lists
        description = str(desc_el).strip()
        # Clean redundant whitespace in text nodes
        description = re.sub(r"\n\s*\n", "\n", description)

    # Plain-text short description (first sentence or first 200 chars)
    short_desc = ""
    if desc_el:
        text = desc_el.get_text(" ", strip=True)
        # Use first sentence
        first = re.split(r"(?<=[.!?])\s+", text, maxsplit=1)
        short_desc = first[0][:200] if first else ""

    # Category — parse from URL (breadcrumbs are polluted by session cookie on legacy shop)
    # URL shape: /produkty/{category-slug}/{product-slug}-{id}
    categories = [category_name]
    path = urlparse(url).path.strip("/").split("/")
    if len(path) >= 3 and path[0] == "produkty":
        url_cat_slug = path[1]
        # Map to pretty category name
        cat_map = {
            "suplementy-diety": "Suplementy diety",
            "do-zastosowania-zewnetrznego": "Do zastosowania zewnętrznego",
            "pozostale-nos-gardlo-ucho": "Pozostałe: Nos, Gardło, Ucho",
        }
        categories = [cat_map.get(url_cat_slug, category_name)]

    # Product ID from URL (e.g., ...-22)
    id_match = re.search(r"-(\d+)$", url)
    product_id = id_match.group(1) if id_match else None
    slug = url.rstrip("/").split("/")[-1]
    # Remove trailing -ID from slug for a clean name-slug
    clean_slug = re.sub(r"-\d+$", "", slug) if product_id else slug

    # Image filename — look for main product image
    # Pattern: /thumbs/.../img/products/big/FILENAME or /img/products/big/FILENAME
    image_filename = None
    # Prefer images inside main product area
    for img in soup.find_all("img"):
        src = img.get("src") or img.get("data-src") or ""
        if "/img/products/" in src or "/products/big/" in src:
            image_filename = extract_image_filename(src)
            break

    # Fallback: any image with alt matching the title
    if not image_filename:
        title_lower = title.lower()
        for img in soup.find_all("img"):
            alt = (img.get("alt") or "").lower()
            src = img.get("src") or ""
            if alt and alt in title_lower and src:
                image_filename = extract_image_filename(src)
                break

    # SKU — not shown on page; use product ID from URL as reliable identifier
    sku = f"GV-{product_id}" if product_id else clean_slug

    return {
        "id": product_id,
        "sku": sku,
        "title": title,
        "slug": clean_slug,
        "price": price,
        "short_description": short_desc,
        "description": description,
        "categories": categories,
        "old_url": url,
        "image_filename": image_filename,
    }


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--limit", type=int, default=None, help="Limit products (testing)")
    parser.add_argument("--dry-run", action="store_true", help="Don't write files")
    parser.add_argument("--output", default=str(DATA_DIR / "products.json"))
    args = parser.parse_args()

    DATA_DIR.mkdir(parents=True, exist_ok=True)
    session = requests.Session()

    print("→ Discovering product URLs across categories...")
    cat_urls = extract_product_urls(session)

    # Build flat list with category name preserved
    all_urls = []
    cat_name_map = dict(CATEGORIES)
    for cat_slug, urls in cat_urls.items():
        for u in urls:
            all_urls.append((u, cat_name_map[cat_slug]))

    # Deduplicate (a product may appear in multiple categories — keep first)
    seen = set()
    unique_urls = []
    for u, c in all_urls:
        if u not in seen:
            seen.add(u)
            unique_urls.append((u, c))

    total = len(unique_urls)
    print(f"  → {total} unique products\n")

    if args.limit:
        unique_urls = unique_urls[:args.limit]
        print(f"  (limited to {len(unique_urls)})\n")

    products = []
    failed = []
    for i, (url, cat_name) in enumerate(unique_urls, 1):
        # Fresh session per product — legacy shop uses session cookies to track
        # "last visited category" which pollutes breadcrumbs and related server-side state.
        product_session = requests.Session()
        p = parse_product(url, cat_name, product_session)
        if p:
            products.append(p)
            price_str = f"{p['price']:.2f} PLN" if p['price'] is not None else "—"
            img_str = p['image_filename'] or "(brak obrazka)"
            print(f"  [{i:3d}/{total}] ✓ {p['title'][:50]:50} {price_str:12} {img_str}")
        else:
            failed.append(url)
            print(f"  [{i:3d}/{total}] ✗ {url}")

    # Collect categories
    all_cats = sorted({c for p in products for c in p["categories"]})
    categories_out = [{"name": c, "slug": polish_slug(c)} for c in all_cats]

    if args.dry_run:
        print(f"\n(dry-run) {len(products)} products, {len(all_cats)} categories, {len(failed)} failures")
        return

    output_path = Path(args.output)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    with open(output_path, "w", encoding="utf-8") as f:
        json.dump(products, f, ensure_ascii=False, indent=2)

    categories_path = output_path.parent / "categories.json"
    with open(categories_path, "w", encoding="utf-8") as f:
        json.dump(categories_out, f, ensure_ascii=False, indent=2)

    redirects_path = output_path.parent / "redirects.json"
    redirects = [{"from": urlparse(p["old_url"]).path, "to": f"/produkt/{p['slug']}/"} for p in products]
    with open(redirects_path, "w", encoding="utf-8") as f:
        json.dump(redirects, f, ensure_ascii=False, indent=2)

    print(f"\n=== DONE ===")
    print(f"Products: {len(products)} → {output_path}")
    print(f"Categories: {len(all_cats)} → {categories_path}")
    print(f"Redirects: {len(redirects)} → {redirects_path}")
    if failed:
        print(f"Failed: {len(failed)}")
        for u in failed:
            print(f"  · {u}")


if __name__ == "__main__":
    main()
