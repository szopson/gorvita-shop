#!/usr/bin/env python3
"""
Import 32 missing Gorvita products into WooCommerce.

Reads:
  - data/missing-products-cennik.json  (price list: EAN, prices, category, webflow slug)
  - data/webflow-products.json         (descriptions + CDN image URLs from gorvita.pl)

For each product:
  1. Finds existing product by EAN (_sku meta) or by normalized title.
  2. If found (wc_id hardcoded or title match) → update prices + B2B meta + EAN SKU.
  3. If not found → create new product with full description, image, pricing.
  4. Sets b2bking_regular_product_price_group_1073 = round(b2b_netto * (1 + vat), 2).
  5. Downloads image from webflow CDN and imports via wp media import.

Idempotent — safe to run multiple times.

Run from /opt/gorvita-shop:
    python3 scripts/import-missing-32.py [--dry-run] [--limit N] [--skip-images]
"""

import argparse
import json
import os
import re
import subprocess
import sys
import tempfile
import unicodedata
import urllib.request
from pathlib import Path

BASE = Path("/opt/gorvita-shop")
CENNIK_JSON = BASE / "data" / "missing-products-cennik.json"
WEBFLOW_JSON = BASE / "data" / "webflow-products.json"
REPORT_JSON = BASE / "data" / "import-missing-32-report.json"
IMG_TMP_DIR = Path("/tmp/gorvita-import-32")

B2B_GROUP = "1073"

CMD_PREFIX = [
    "docker", "compose", "exec", "-T", "wordpress",
    "wp", "--allow-root", "--path=/var/www/html",
]


# ── helpers ──────────────────────────────────────────────────────────────────

def wp(args: list[str], check: bool = True) -> str:
    cmd = CMD_PREFIX + args
    try:
        r = subprocess.run(cmd, capture_output=True, text=True, check=check, cwd=str(BASE))
        return r.stdout.strip()
    except subprocess.CalledProcessError as e:
        print(f"  ✗ WP error: {e.stderr.strip()[:200]}", file=sys.stderr)
        raise


def wp_silent(args: list[str]) -> tuple[int, str, str]:
    cmd = CMD_PREFIX + args
    r = subprocess.run(cmd, capture_output=True, text=True, cwd=str(BASE))
    return r.returncode, r.stdout.strip(), r.stderr.strip()


def normalize(text: str) -> str:
    """Lowercase, strip diacritics, keep only alnum and spaces."""
    text = text.lower()
    nfkd = unicodedata.normalize("NFKD", text)
    ascii_text = "".join(c for c in nfkd if not unicodedata.combining(c))
    return re.sub(r"[^a-z0-9 ]", " ", ascii_text).strip()


def make_slug(name: str) -> str:
    norm = normalize(name)
    return re.sub(r"\s+", "-", norm)


def build_post_content(wf: dict) -> tuple[str, str]:
    """Return (post_content HTML, post_excerpt text) from webflow entry."""
    if not wf:
        return "", ""

    desc_html = wf.get("description", {}).get("html", "")
    desc_text = wf.get("description", {}).get("text", "")
    usage = wf.get("specs", {}).get("usage", "")
    warning = wf.get("specs", {}).get("warning", "")
    inci = wf.get("skladniki", {}).get("inci", "")

    parts = [desc_html]
    if usage:
        parts.append(f"<h4>Sposób użycia</h4><p>{usage}</p>")
    if warning:
        parts.append(f"<h4>Uwaga</h4><p>{warning}</p>")
    if inci:
        parts.append(f"<h4>Skład</h4><p>{inci}</p>")

    content = "\n".join(p for p in parts if p)
    excerpt = desc_text[:300].strip() if desc_text else ""
    return content, excerpt


def find_by_ean(ean: str) -> int | None:
    rc, out, _ = wp_silent(["post", "list", "--post_type=product",
                             "--meta_key=_sku", f"--meta_value={ean}",
                             "--format=ids"])
    ids = [x for x in out.split() if x.isdigit()]
    return int(ids[0]) if ids else None


def find_by_title(name: str) -> int | None:
    norm_target = normalize(name)
    rc, out, _ = wp_silent(["post", "list", "--post_type=product",
                             "--fields=ID,post_title",
                             "--posts_per_page=300",
                             "--format=csv"])
    if rc != 0:
        return None
    for line in out.splitlines()[1:]:
        parts = line.split(",", 1)
        if len(parts) == 2 and parts[0].isdigit():
            pid, title = int(parts[0]), parts[1].strip('"')
            if normalize(title) == norm_target:
                return pid
    return None


def download_image(url: str, ean: str) -> Path | None:
    IMG_TMP_DIR.mkdir(parents=True, exist_ok=True)
    ext = ".avif"
    url_lower = url.lower()
    for candidate in [".jpg", ".jpeg", ".png", ".webp", ".avif"]:
        if candidate in url_lower:
            ext = candidate
            break
    dest = IMG_TMP_DIR / f"{ean}{ext}"
    if dest.exists():
        return dest
    try:
        req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
        with urllib.request.urlopen(req, timeout=15) as resp:
            dest.write_bytes(resp.read())
        return dest
    except Exception as e:
        print(f"  ⚠ Image download failed ({url[:60]}…): {e}", file=sys.stderr)
        return None


def import_image(product_id: int, local_path: Path, title: str) -> int | None:
    container_path = f"/tmp/gorvita-import-{local_path.name}"
    cp = subprocess.run(
        ["docker", "compose", "cp", str(local_path), f"wordpress:{container_path}"],
        capture_output=True, text=True, cwd=str(BASE),
    )
    if cp.returncode != 0:
        print(f"  ⚠ docker cp failed: {cp.stderr[:120]}", file=sys.stderr)
        return None
    rc, out, err = wp_silent([
        "media", "import", container_path,
        f"--post_id={product_id}",
        f"--title={title}",
        "--porcelain",
    ])
    wp_silent(["eval", f'@unlink("{container_path}");'])
    if rc == 0 and out.isdigit():
        return int(out)
    print(f"  ⚠ wp media import failed: {err[:120]}", file=sys.stderr)
    return None


# ── per-product logic ─────────────────────────────────────────────────────────

def update_product_meta(product_id: int, p: dict, attach_id: int | None = None):
    metas = {
        "_sku": p["ean"],
        "_regular_price": str(p["retail_brutto"]),
        "_price": str(p["retail_brutto"]),
        "_manage_stock": "no",
        "_stock_status": "instock",
        "_visibility": "visible",
        "_tax_status": "taxable",
        "_tax_class": "reduced-rate",
        f"b2bking_regular_product_price_group_{B2B_GROUP}": str(p["b2b_netto"]),
    }
    if attach_id:
        metas["_thumbnail_id"] = str(attach_id)
    for key, val in metas.items():
        wp_silent(["post", "meta", "update", str(product_id), key, val])


def process_product(p: dict, webflow: dict, dry_run: bool, skip_images: bool) -> dict:
    name = p["name"]
    ean = p["ean"]

    result: dict = {
        "name": name, "ean": ean,
        "retail_brutto": p["retail_brutto"],
        "b2b_netto": p["b2b_netto"],
        "status": "pending",
    }

    if dry_run:
        wf = webflow.get(p["webflow_slug"]) if p.get("webflow_slug") else None
        result["status"] = "dry-run"
        result["has_description"] = bool(wf)
        result["has_image"] = bool(wf and wf.get("main_image"))
        hardcoded_id = p.get("wc_id")
        existing_id = hardcoded_id or find_by_ean(ean) or find_by_title(name)
        result["action"] = "update" if existing_id else "create"
        result["wc_id"] = existing_id
        result["b2b_netto"] = p["b2b_netto"]
        return result

    # ── find existing ──
    hardcoded_id = p.get("wc_id")
    existing_id = hardcoded_id or find_by_ean(ean) or find_by_title(name)

    wf = webflow.get(p["webflow_slug"]) if p.get("webflow_slug") else None
    post_content, post_excerpt = build_post_content(wf)
    img_url = wf.get("main_image") if wf else None

    if existing_id:
        # ── UPDATE existing product ──
        wp_silent(["post", "meta", "update", str(existing_id),
                   "_regular_price", str(p["retail_brutto"])])
        wp_silent(["post", "meta", "update", str(existing_id),
                   "_price", str(p["retail_brutto"])])
        wp_silent(["post", "meta", "update", str(existing_id),
                   "_sku", ean])
        wp_silent(["post", "meta", "update", str(existing_id),
                   f"b2bking_regular_product_price_group_{B2B_GROUP}", str(p["b2b_netto"])])
        # Add description if product has none
        rc, cur_content, _ = wp_silent(["post", "get", str(existing_id),
                                         "--field=post_content"])
        if rc == 0 and not cur_content.strip() and post_content:
            wp_silent(["post", "update", str(existing_id),
                       f"--post_content={post_content}",
                       f"--post_excerpt={post_excerpt}"])
        # Add image if product has none
        if not skip_images and img_url:
            rc2, cur_thumb, _ = wp_silent(["post", "meta", "get",
                                            str(existing_id), "_thumbnail_id"])
            if rc2 != 0 or not cur_thumb.strip().isdigit():
                local_img = download_image(img_url, ean)
                if local_img:
                    attach_id = import_image(existing_id, local_img, name)
                    if attach_id:
                        wp_silent(["post", "meta", "update", str(existing_id),
                                   "_thumbnail_id", str(attach_id)])
                        result["image_attached"] = attach_id
        result["status"] = "updated"
        result["wc_id"] = existing_id
        return result

    # ── CREATE new product ──
    slug = make_slug(name)
    create_args = [
        "post", "create",
        "--post_type=product",
        "--post_status=publish",
        f"--post_title={name}",
        f"--post_name={slug}",
        "--porcelain",
    ]
    if post_content:
        create_args.append(f"--post_content={post_content}")
    if post_excerpt:
        create_args.append(f"--post_excerpt={post_excerpt}")

    rc, out, err = wp_silent(create_args)
    if rc != 0 or not out.isdigit():
        result["status"] = f"create-fail: {err[:150]}"
        return result

    product_id = int(out)
    result["wc_id"] = product_id

    # Set simple product type
    wp_silent(["term", "add", str(product_id), "product_type", "simple"])

    # Image
    attach_id = None
    if not skip_images and img_url:
        local_img = download_image(img_url, ean)
        if local_img:
            attach_id = import_image(product_id, local_img, name)
            if attach_id:
                result["image_attached"] = attach_id

    # Meta
    update_product_meta(product_id, p, attach_id)
    if p.get("webflow_slug"):
        wp_silent(["post", "meta", "update", str(product_id),
                   "_gorvita_webflow_slug", p["webflow_slug"]])

    # Category
    cat_id = p.get("category_id")
    if cat_id:
        wp_silent(["post", "term", "set", str(product_id),
                   "product_cat", str(cat_id), "--by=id"])

    result["status"] = "created"
    result["has_description"] = bool(post_content)
    result["has_image"] = bool(attach_id)
    return result


# ── main ──────────────────────────────────────────────────────────────────────

def main():
    parser = argparse.ArgumentParser(description="Import 32 missing Gorvita products")
    parser.add_argument("--dry-run", action="store_true",
                        help="Preview only — no writes to WooCommerce")
    parser.add_argument("--limit", type=int, default=None,
                        help="Process only first N products")
    parser.add_argument("--skip-images", action="store_true",
                        help="Skip image download and import")
    args = parser.parse_args()

    with open(CENNIK_JSON, encoding="utf-8") as f:
        products = json.load(f)
    with open(WEBFLOW_JSON, encoding="utf-8") as f:
        webflow = json.load(f)

    if args.limit:
        products = products[:args.limit]

    print(f"→ Processing {len(products)} products  "
          f"(dry_run={args.dry_run}, skip_images={args.skip_images})")
    print()

    stats: dict[str, int] = {}
    results = []

    for i, p in enumerate(products, 1):
        try:
            res = process_product(p, webflow, dry_run=args.dry_run,
                                  skip_images=args.skip_images)
        except Exception as exc:
            res = {"name": p.get("name"), "ean": p.get("ean"),
                   "status": f"exception: {exc}"}

        results.append(res)
        status = res.get("status", "?")
        key = status.split(":")[0].strip()
        stats[key] = stats.get(key, 0) + 1

        action = res.get("action", status)
        img_flag = "🖼" if res.get("image_attached") else ("📷" if res.get("has_image") else "—")
        desc_flag = "📝" if res.get("has_description") else "—"
        wc_str = f"WC#{res['wc_id']}" if res.get("wc_id") else "new"
        print(f"  [{i:2d}/{len(products)}] {status:14} {p['name'][:45]:45}  "
              f"{wc_str:8}  desc={desc_flag} img={img_flag}  "
              f"B2B={p['b2b_netto']}")


    print()
    print("=== STATS ===")
    for k, v in sorted(stats.items()):
        print(f"  {k}: {v}")

    REPORT_JSON.parent.mkdir(parents=True, exist_ok=True)
    with open(REPORT_JSON, "w", encoding="utf-8") as f:
        json.dump({"stats": stats, "results": results}, f, ensure_ascii=False, indent=2)
    print(f"\n✓ Report → {REPORT_JSON}")

    if not args.dry_run:
        print("\n→ Flushing caches…")
        wp_silent(["cache", "flush"])
        wp_silent(["eval", "rocket_clean_domain(); rocket_clean_minify();", "--user=1"])
        print("  ✓ Cache cleared")


if __name__ == "__main__":
    main()
