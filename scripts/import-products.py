#!/usr/bin/env python3
"""
Import 99 Gorvita products into WooCommerce.

Reads:
  - data/products.json       (from scraper.py)
  - data/image-mapping.json  (from match-images.py + overrides)

For each product:
  1. Determines Potrzeby categories via keyword matching on title/description.
  2. Determines ingredient tags (CBD, kolagen, żywokost, rokitnik, …).
  3. Creates/updates product in WooCommerce via WP-CLI:
       - SKU = GV-{id} (from scraper)
       - Regular price, description, short description
       - Category memberships (can belong to multiple)
       - Tags
       - Stock management disabled (ręczne stany)
       - Old URL saved as post meta _gorvita_legacy_url (for 301 redirect)
  4. Imports the matched local image and sets it as featured image.

Idempotent — finds existing products by SKU and updates instead of creating duplicates.

Run inside WordPress container or on host where `docker compose exec` works:
    python3 /opt/gorvita-shop/scripts/import-products.py [--limit N] [--dry-run]
"""

import argparse
import json
import re
import subprocess
import sys
from pathlib import Path

BASE = Path("/opt/gorvita-shop")
PRODUCTS_JSON = BASE / "data" / "products.json"
IMAGE_MAPPING_JSON = BASE / "data" / "image-mapping.json"
IMAGES_DIR = BASE / "data" / "images"

# ============================================================================
# Category mapping: keyword → category slug
# ============================================================================
# A product can belong to multiple categories. Ordered from specific to generic.
CATEGORY_RULES = [
    # Specific first: CBD overrides others (oils, salves marked as CBD)
    ("cbd-konopie", ["cbd", "konopi", "konopna", "konopii"]),

    # Supplements by condition
    ("stawy-miesnie", ["artrofit", "artrevit", "artrożel", "artrozel", "kolagen", "colafit", "colacal", "colahial", "żywokost", "zywokost", "żywokostem", "żywokostowy", "zywokostowy", "kasztanow", "końska", "konska", "końsk", "konsk", "sportowców", "sportowcow", "diabelski", "czarci", "kurkuma", "arnika", "arnikow", "świerkow", "swierkow", "borowin", "nagietk", "rumiank", "pazur"]),
    ("watroba-trawienie", ["ostropest", "hepasal", "babka płesznik", "babka plesznik", "carbosal", "kudzu", "jarmuż", "jarmuz", "spirulina", "zielony jęczmień", "zielony jeczmien", "zielona kawa"]),
    ("odpornosc", ["acerola", "apleplus", "witamin", "propolis", "rokitnik", "citrogreft"]),
    ("krazenie", ["chrom", "venal", "cynk", "magnez", "żel kasztanow", "zel kasztanow", "kasztanowcem", "miłorząb", "milorzab", "babką lancetowatą", "babka lancetowata"]),
    ("energia-stres", ["energia", "geriafix", "gotu kola", "erotic", "afrodyzjak"]),
    ("nos-gardlo-jama-ustna", ["aphtihelp", "aurix", "pneumovit"]),

    # Skin & body — catch-all for topical products without specific condition
    ("skora-cialo", ["aloevera", "aloe vera", "alantoin", "balsam do ust", "panthenol", "antiseptic", "arcacet", "blizna", "venal", "stop", "żel ze świetlikiem", "zel ze swietlikiem", "celluitis", "anticelluitis", "antycelulit", "mosqitos", "owadów", "owadow", "ukąszeni", "ukaszeni", "olejek pichtowy", "olej kokosowy", "rabka spa", "puder", "niedźwiedzi", "niedzwiedzi"]),

    # Silicum/silicium special — supplementation for skin, hair
    ("skora-cialo", ["silicum", "silicium", "krzem", "biotyna"]),
]

# Second pass — generic topical products (any with maść/masc/balsam/żel/zel/krem/spray/pianka)
# go to skora-cialo unless already categorized
GENERIC_TOPICAL = ["maść", "masc", "balsam", "żel", "zel", "krem", "spray", "pianka", "olejek"]

# ============================================================================
# Tag mapping: keyword → tag slug  (for ingredients)
# ============================================================================
TAG_RULES = {
    "cbd": ["cbd", "konopi"],
    "kolagen": ["kolagen", "colafit", "colacal", "colahial"],
    "zywokost": ["żywokost", "zywokost", "żywokostowy", "zywokostowy", "żywokostem"],
    "rokitnik": ["rokitnik"],
    "kasztanowiec": ["kasztanow", "kasztanowcem"],
    "propolis": ["propolis"],
    "kurkuma": ["kurkuma"],
    "aloe-vera": ["aloevera", "aloe vera"],
    "arnika": ["arnika", "arnikow"],
    "rumianek": ["rumiank"],
    "witamina-c": ["acerola", "apleplus"],
    "ostropest": ["ostropest"],
    "cbd-5": ["5% cbd", "cbd 5%", "cbd 5", "5%"],
    "cbd-10": ["10% cbd", "cbd 10%", "cbd 10", "10%"],
}

CMD_PREFIX = ["docker", "compose", "exec", "-T", "wordpress", "wp", "--allow-root", "--path=/var/www/html", "--user=1"]


def normalize(text: str) -> str:
    text = text.lower()
    mapping = {"ą": "a", "ć": "c", "ę": "e", "ł": "l", "ń": "n",
               "ó": "o", "ś": "s", "ź": "z", "ż": "z"}
    for k, v in mapping.items():
        text = text.replace(k, v)
    return text


def determine_categories(product: dict) -> list[str]:
    """Return list of category slugs for a product based on title + description."""
    haystack = (product.get("title", "") + " " + product.get("short_description", "")).lower()
    haystack_norm = normalize(haystack)
    matched = []

    for cat_slug, keywords in CATEGORY_RULES:
        for kw in keywords:
            kw_norm = normalize(kw)
            if kw in haystack or kw_norm in haystack_norm:
                if cat_slug not in matched:
                    matched.append(cat_slug)
                break

    # If no specific category matched, fall back to generic topical → skora-cialo
    if not matched:
        for topical_kw in GENERIC_TOPICAL:
            if topical_kw in haystack or normalize(topical_kw) in haystack_norm:
                matched = ["skora-cialo"]
                break

    # Final fallback: odpornosc (safer default for supplements)
    if not matched:
        matched = ["odpornosc"]

    return matched


def determine_tags(product: dict) -> list[str]:
    haystack = (product.get("title", "") + " " + product.get("short_description", "")).lower()
    haystack_norm = normalize(haystack)
    tags = []
    for tag_slug, keywords in TAG_RULES.items():
        for kw in keywords:
            if kw in haystack or normalize(kw) in haystack_norm:
                tags.append(tag_slug)
                break
    return tags


def wp(args: list[str], stdin: str | None = None, check: bool = True) -> str:
    """Run a WP-CLI command via docker compose exec. Returns stdout."""
    cmd = CMD_PREFIX + args
    try:
        result = subprocess.run(
            cmd, capture_output=True, text=True, input=stdin,
            check=check, cwd=str(BASE),
        )
        return result.stdout.strip()
    except subprocess.CalledProcessError as e:
        print(f"✗ WP error: {' '.join(cmd)}", file=sys.stderr)
        print(f"   stderr: {e.stderr.strip()[:300]}", file=sys.stderr)
        raise


def wp_silent(args: list[str]) -> tuple[int, str, str]:
    """Run WP-CLI without raising on errors. Returns (returncode, stdout, stderr)."""
    cmd = CMD_PREFIX + args
    r = subprocess.run(cmd, capture_output=True, text=True, cwd=str(BASE))
    return r.returncode, r.stdout.strip(), r.stderr.strip()


def get_category_id(slug: str) -> int | None:
    rc, out, _ = wp_silent(["term", "get", "product_cat", slug, "--by=slug", "--field=term_id"])
    if rc == 0 and out.isdigit():
        return int(out)
    return None


def get_or_create_tag_id(slug: str) -> int | None:
    rc, out, _ = wp_silent(["term", "get", "product_tag", slug, "--by=slug", "--field=term_id"])
    if rc == 0 and out.isdigit():
        return int(out)
    # Create
    rc, out, _ = wp_silent(["term", "create", "product_tag", slug.replace("-", " ").title(),
                            f"--slug={slug}", "--porcelain"])
    if rc == 0 and out.isdigit():
        return int(out)
    return None


def find_existing_product(sku: str) -> int | None:
    rc, out, _ = wp_silent(["post", "list", "--post_type=product",
                            f"--meta_key=_sku", f"--meta_value={sku}",
                            "--format=ids"])
    if rc == 0 and out.strip() and out.strip().split()[0].isdigit():
        return int(out.strip().split()[0])
    return None


def import_image(product_id: int, image_filename: str, title: str) -> int | None:
    """Copy image into container, run wp media import, return attachment_id."""
    src = IMAGES_DIR / image_filename
    if not src.exists():
        return None
    # Copy into container temp path
    container_path = f"/tmp/gorvita-import-{product_id}{src.suffix}"
    cp_cmd = ["docker", "compose", "cp", str(src),
              f"wordpress:{container_path}"]
    r = subprocess.run(cp_cmd, capture_output=True, text=True, cwd=str(BASE))
    if r.returncode != 0:
        return None
    rc, out, _ = wp_silent([
        "media", "import", container_path,
        f"--post_id={product_id}",
        f"--title={title}",
        "--porcelain",
    ])
    # Cleanup temp
    subprocess.run(["docker", "compose", "exec", "-T", "wordpress", "rm", "-f", container_path],
                   capture_output=True, cwd=str(BASE))
    if rc == 0 and out.isdigit():
        return int(out)
    return None


def create_or_update_product(product: dict, img_mapping: dict, dry_run: bool = False) -> dict:
    sku = product["sku"]
    title = product["title"]
    categories = determine_categories(product)
    tags = determine_tags(product)
    image_filename = img_mapping.get(sku)

    result = {
        "sku": sku, "title": title, "categories": categories, "tags": tags,
        "image": image_filename, "status": "pending",
    }

    if dry_run:
        result["status"] = "dry-run"
        return result

    # Prepare description (clean up scraped HTML slightly)
    description = product.get("description", "")
    short = product.get("short_description", "")
    price = product.get("price")
    if price is None:
        result["status"] = "skip-no-price"
        return result

    existing_id = find_existing_product(sku)

    if existing_id:
        # Update
        args = [
            "post", "update", str(existing_id),
            f"--post_title={title}",
            f"--post_content={description}",
            f"--post_excerpt={short}",
            f"--post_name={product['slug']}",
        ]
        rc, _, err = wp_silent(args)
        if rc != 0:
            result["status"] = f"update-fail: {err[:100]}"
            return result
        # Update meta
        wp_silent(["post", "meta", "update", str(existing_id), "_regular_price", str(price)])
        wp_silent(["post", "meta", "update", str(existing_id), "_price", str(price)])
        wp_silent(["post", "meta", "update", str(existing_id), "_gorvita_legacy_url", product["old_url"]])
        product_id = existing_id
        result["status"] = "updated"
    else:
        # Create
        args = [
            "post", "create",
            "--post_type=product",
            "--post_status=publish",
            f"--post_title={title}",
            f"--post_content={description}",
            f"--post_excerpt={short}",
            f"--post_name={product['slug']}",
            "--porcelain",
        ]
        rc, out, err = wp_silent(args)
        if rc != 0 or not out.isdigit():
            result["status"] = f"create-fail: {err[:100]}"
            return result
        product_id = int(out)
        # Set meta
        wp_silent(["post", "meta", "update", str(product_id), "_sku", sku])
        wp_silent(["post", "meta", "update", str(product_id), "_regular_price", str(price)])
        wp_silent(["post", "meta", "update", str(product_id), "_price", str(price)])
        wp_silent(["post", "meta", "update", str(product_id), "_manage_stock", "no"])
        wp_silent(["post", "meta", "update", str(product_id), "_stock_status", "instock"])
        wp_silent(["post", "meta", "update", str(product_id), "_visibility", "visible"])
        wp_silent(["post", "meta", "update", str(product_id), "_virtual", "no"])
        wp_silent(["post", "meta", "update", str(product_id), "_downloadable", "no"])
        wp_silent(["post", "meta", "update", str(product_id), "_gorvita_legacy_url", product["old_url"]])
        # Product type
        wp_silent(["wp_set_object_terms", str(product_id)] if False else
                  ["term", "add", str(product_id), "product_type", "simple"])  # may fail harmlessly
        result["status"] = "created"

    # Apply categories (replace set)
    cat_ids = [cid for slug in categories if (cid := get_category_id(slug))]
    if cat_ids:
        cat_ids_csv = ",".join(str(c) for c in cat_ids)
        wp_silent(["post", "term", "set", str(product_id), "product_cat", cat_ids_csv, "--by=id"])

    # Apply tags (replace set)
    tag_ids = [tid for slug in tags if (tid := get_or_create_tag_id(slug))]
    if tag_ids:
        tag_ids_csv = ",".join(str(t) for t in tag_ids)
        wp_silent(["post", "term", "set", str(product_id), "product_tag", tag_ids_csv, "--by=id"])

    # Image import (only if no thumbnail already)
    if image_filename:
        rc, has_thumb, _ = wp_silent(["post", "meta", "get", str(product_id), "_thumbnail_id"])
        if not has_thumb or not has_thumb.isdigit():
            attach_id = import_image(product_id, image_filename, title)
            if attach_id:
                wp_silent(["post", "meta", "update", str(product_id), "_thumbnail_id", str(attach_id)])
                result["image_attached"] = attach_id

    result["product_id"] = product_id
    return result


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--limit", type=int, default=None)
    parser.add_argument("--dry-run", action="store_true")
    parser.add_argument("--skip-images", action="store_true")
    args = parser.parse_args()

    with open(PRODUCTS_JSON, encoding="utf-8") as f:
        products = json.load(f)

    with open(IMAGE_MAPPING_JSON, encoding="utf-8") as f:
        mapping_raw = json.load(f)
    img_mapping = {e["sku"]: e["local_file"] for e in mapping_raw if e.get("local_file")}

    if args.limit:
        products = products[:args.limit]

    print(f"→ Importing {len(products)} products (dry-run={args.dry_run})")

    stats = {"created": 0, "updated": 0, "dry-run": 0, "failed": 0, "no-image": 0}
    results = []

    for i, product in enumerate(products, 1):
        if args.skip_images:
            img_mapping_arg = {}
        else:
            img_mapping_arg = img_mapping
        try:
            res = create_or_update_product(product, img_mapping_arg, dry_run=args.dry_run)
        except Exception as e:
            res = {"sku": product.get("sku"), "title": product.get("title"), "status": f"exception: {e}"}

        results.append(res)
        status = res.get("status", "?")
        status_key = status.split(":")[0].strip() if ":" in status else status
        stats[status_key] = stats.get(status_key, 0) + 1

        if not res.get("image") and not args.skip_images:
            stats["no-image"] += 1

        cat_str = ",".join(res.get("categories", []))[:40]
        tag_str = ",".join(res.get("tags", []))[:30]
        img_str = res.get("image", "—") or "—"
        print(f"  [{i:3d}/{len(products)}] {status:10} {res['title'][:40]:40} cat=[{cat_str:40}] tag=[{tag_str:30}] img={img_str[:30]}")

    print("\n=== STATS ===")
    for k, v in stats.items():
        print(f"  {k}: {v}")

    # Write a report
    out_path = BASE / "data" / "import-report.json"
    with open(out_path, "w", encoding="utf-8") as f:
        json.dump({"stats": stats, "results": results}, f, ensure_ascii=False, indent=2)
    print(f"\n✓ Report → {out_path}")


if __name__ == "__main__":
    main()
