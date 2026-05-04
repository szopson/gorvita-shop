#!/usr/bin/env python3
"""
Generate RankMath SEO meta (title, description, focus_keyword) for products
based on rich post_content (post-Webflow update). Includes GEO signal (Rabka).

Strategy per product:
  - rank_math_focus_keyword: distinctive product name tokens
    e.g. "Żel z Kasztanowcem 200 ml" -> "żel z kasztanowcem"
  - rank_math_title (max ~60 chars):
    "{Product Name} — naturalna pielęgnacja z Rabki | Gorvita"
    falls back to "{Product Name} — Gorvita Rabka" if too long
  - rank_math_description (~150 chars):
    First sentence(s) of post_content (the Webflow description) trimmed to ≤155 chars,
    with " Naturalna marka z Rabki." appended when there's room.

Usage:
  ./update-seo-meta.py --dry-run             # show what would change
  ./update-seo-meta.py --apply               # actually update meta
  ./update-seo-meta.py --apply --id 212      # single product
"""

import argparse
import re
import subprocess
import sys
import unicodedata
from html import unescape
from pathlib import Path

WP_CONTAINER = "gorvita-wordpress"
ROOT = Path(__file__).resolve().parent.parent

# Brand line per product. GEO default = Gorce (where ingredients come from).
# Rabka used ONLY for products that actually contain Rabka mineral water as
# an active ingredient (detected via water_keywords in post_content). Reason:
# the factory is in Szczawa, source ingredients from Gorce mountains; water
# from Rabka is just one component used in some formulas.
BRAND_TAGLINE_DEFAULT = "naturalna pielęgnacja z Gorców"
BRAND_TAGLINE_WATER = "naturalna pielęgnacja z Rabki"
BRAND_SUFFIX_DEFAULT = "Gorvita z Gorców."
BRAND_SUFFIX_WATER = "Gorvita z Rabki."
BRAND_LONG = "Gorvita"

# If post_content contains any of these phrases, the product genuinely uses
# Rabka mineral water — meta may legitimately reference Rabka.
WATER_KEYWORDS = (
    "woda lecznicza", "wody leczniczej", "wodzie leczniczej",
    "woda mineralna", "wody mineralnej", "mineralna z",
    "fizjologiczny roztwór", "hydrochlorowo", "wodorowęglanowo",
    "z Rabki", "z rabki",
)


def uses_rabka_water(post_content: str) -> bool:
    text = post_content.lower()
    return any(k.lower() in text for k in WATER_KEYWORDS)


def wp(*args):
    cmd = ["docker", "exec", WP_CONTAINER, "wp", *args, "--allow-root"]
    res = subprocess.run(cmd, capture_output=True, text=True)
    if res.returncode != 0:
        sys.stderr.write(f"wp-cli failed: {' '.join(cmd)}\n{res.stderr}\n")
        sys.exit(1)
    return res.stdout


def strip_html(s: str) -> str:
    s = re.sub(r"<[^>]+>", " ", s)
    s = unescape(s)
    s = re.sub(r"\s+", " ", s).strip()
    return s


def first_sentences(text: str, max_chars: int) -> str:
    """Return as many full sentences as fit in max_chars."""
    text = text.strip()
    if not text:
        return ""
    if len(text) <= max_chars:
        return text
    # Try to split on sentence boundaries: . ! ? followed by space + capital
    parts = re.split(r"(?<=[.!?])\s+", text)
    out = ""
    for p in parts:
        candidate = (out + " " + p).strip() if out else p
        if len(candidate) > max_chars:
            break
        out = candidate
    if not out:
        # No sentence boundary fit — hard truncate at last word
        out = text[: max_chars - 1].rsplit(" ", 1)[0] + "…"
    return out


def normalize_keyword(title: str) -> str:
    """
    Distill product name into a concise focus keyword (lowercase, no volume).
    'Żel z Kasztanowcem 200 ml' -> 'żel z kasztanowcem'
    'Acerola 500mg 60 kapsułek' -> 'acerola'
    """
    s = title.lower()
    # Remove volume + count + unit suffixes
    s = re.sub(
        r"\b\d+\s*(?:ml|mg|kapsulek|kapsułek|kapsulki|kapsułki|kaps\.?|szt\.?|"
        r"tabletek|tabl\.?|tab|kostek|gram|g)\b\.?",
        "", s, flags=re.IGNORECASE
    )
    # Strip x60-style prefixes
    s = re.sub(r"\bx\s*\d+\b", "", s)
    # Drop trailing percentages and stray numbers
    s = re.sub(r"\b\d+%\b", "", s)
    s = re.sub(r"[,;]+", " ", s)
    s = re.sub(r"\s+", " ", s).strip(" -–—")
    return s


def make_title(product_name: str, post_content: str, max_chars: int = 60) -> str:
    """Build the SEO title. GEO suffix depends on whether product uses Rabka water."""
    geo = "z Rabki" if uses_rabka_water(post_content) else "z Gorców"
    tagline = BRAND_TAGLINE_WATER if geo == "z Rabki" else BRAND_TAGLINE_DEFAULT
    candidates = [
        f"{product_name} — {tagline} | {BRAND_LONG}",
        f"{product_name} — {geo} | {BRAND_LONG}",
        f"{product_name} | {BRAND_LONG} {geo}",
        f"{product_name} | {BRAND_LONG}",
        product_name,
    ]
    for c in candidates:
        if len(c) <= max_chars:
            return c
    return candidates[-1][:max_chars]


def make_description(post_content: str, product_name: str, max_chars: int = 160) -> str:
    """Build the SEO description from rich post_content."""
    is_water_product = uses_rabka_water(post_content)
    # wp-cli TSV output escapes real newlines as literal "\n" — convert to spaces
    post_content = post_content.replace("\\n", " ").replace("\\t", " ").replace("\\r", " ")
    # Strip everything after first <h2> — we want the lead paragraph only.
    intro = re.split(r"<h[1-6][^>]*>", post_content, maxsplit=1)[0]
    text = strip_html(intro)
    # Drop "Opis produktu:" prefix if present
    text = re.sub(r"^Opis produktu:?\s*", "", text, flags=re.IGNORECASE)
    # Drop generic-stub leading sentence so we don't echo it back as SEO copy
    text = re.sub(
        r"^[\w\s]*?\bto produkt przeznaczony do wsparcia zdrowia i regeneracji organizmu\.?\s*",
        "", text, flags=re.IGNORECASE,
    )
    # Convert bullet markers into sentence breaks so health-claim lists become prose
    text = text.replace(" • ", ". ").replace("•", ".")
    text = re.sub(r"\.{2,}", ".", text)
    # Strip stray punctuation noise from converted bullet lists (",.", " ,", " .")
    text = re.sub(r"\s*,\s*\.", ".", text)
    text = re.sub(r"\s+([,.;:])", r"\1", text)
    # Strip list-numbering at start AND after sentence breaks
    # ("1 Dzika Róża...", ". 2 Dzika..." -> drop the digits)
    text = re.sub(r"^\d+\s+(?=[A-ZŻŹĆŚŁĘĄÓŃ])", "", text)
    text = re.sub(r"(?<=[.!?])\s+\d+\s+(?=[A-ZŻŹĆŚŁĘĄÓŃ])", " ", text)
    text = re.sub(r"\s+", " ", text).strip()

    suffix_core = BRAND_SUFFIX_WATER if is_water_product else BRAND_SUFFIX_DEFAULT
    suffix = " " + suffix_core
    tagline = BRAND_TAGLINE_WATER if is_water_product else BRAND_TAGLINE_DEFAULT

    # Pick whichever option is most informative without exceeding the budget.
    body_full = first_sentences(text, max_chars)
    body_with_room = first_sentences(text, max_chars - len(suffix))
    candidates = []
    if body_with_room:
        with_brand = (body_with_room + suffix).strip()
        if len(with_brand) <= max_chars:
            candidates.append(with_brand)
    if body_full:
        candidates.append(body_full[:max_chars])
    if candidates:
        return max(candidates, key=len)
    # Fall back: name + tagline
    return f"{product_name} — {tagline}.{suffix}"[:max_chars]


def load_products() -> list[dict]:
    out = wp("db", "query", """
SELECT p.ID, p.post_title, p.post_content
FROM wp_posts p
WHERE p.post_type='product' AND p.post_status='publish'
ORDER BY p.ID;""")
    products = []
    lines = out.splitlines()
    if not lines:
        return products
    # Skip header; rows may contain newlines inside post_content (TSV escapes them with \n literal)
    # wp-cli emits TSV; tab is column separator. Parse by counting tabs.
    header = lines[0].split("\t")
    expected_cols = len(header)
    buf = []
    for line in lines[1:]:
        if line.count("\t") >= expected_cols - 1 and buf:
            # Process previous row
            joined = "\n".join(buf)
            parts = joined.split("\t", expected_cols - 1)
            if len(parts) == expected_cols:
                products.append({"id": int(parts[0]), "post_title": parts[1], "post_content": parts[2]})
            buf = [line]
        else:
            buf.append(line)
    if buf:
        joined = "\n".join(buf)
        parts = joined.split("\t", expected_cols - 1)
        if len(parts) == expected_cols and parts[0].isdigit():
            products.append({"id": int(parts[0]), "post_title": parts[1], "post_content": parts[2]})
    return products


def batch_set_meta(updates: list[tuple[int, str, str]]):
    """
    Apply many meta updates in a single mysql session (avoids PHP boot per call).
    `updates` is a list of (post_id, meta_key, meta_value) tuples.
    Uses INSERT ... ON DUPLICATE KEY UPDATE to handle both insert and update cases,
    relying on the unique key (post_id, meta_key) — but wp_postmeta does NOT have a
    unique key on those by default, so we DELETE+INSERT in a transaction instead.
    """
    if not updates:
        return
    sql_lines = ["START TRANSACTION;"]
    for post_id, key, value in updates:
        # Escape single quotes by doubling, and escape backslashes
        v = value.replace("\\", "\\\\").replace("'", "''")
        k = key.replace("'", "''")
        sql_lines.append(f"DELETE FROM wp_postmeta WHERE post_id={post_id} AND meta_key='{k}';")
        sql_lines.append(f"INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ({post_id}, '{k}', '{v}');")
    sql_lines.append("COMMIT;")
    sql = "\n".join(sql_lines)

    proc = subprocess.run(
        ["docker", "exec", "-i", "gorvita-mariadb", "bash", "-c",
         'mariadb -uroot -p"$MYSQL_ROOT_PASSWORD" gorvita'],
        input=sql, capture_output=True, text=True,
    )
    if proc.returncode != 0:
        raise RuntimeError(f"batch SQL meta update failed: {proc.stderr[:500]}")


def main():
    ap = argparse.ArgumentParser()
    g = ap.add_mutually_exclusive_group(required=True)
    g.add_argument("--dry-run", action="store_true")
    g.add_argument("--apply", action="store_true")
    ap.add_argument("--id", type=int, help="process only this DB id")
    ap.add_argument("--limit", type=int, default=0)
    args = ap.parse_args()

    products = load_products()
    if args.id:
        products = [p for p in products if p["id"] == args.id]
    if args.limit:
        products = products[: args.limit]

    print(f"[*] Loaded {len(products)} products")
    print(f"[*] Mode: {'DRY-RUN' if args.dry_run else 'APPLY'}\n")

    summary = {"updated": 0, "skipped": 0, "errors": 0}
    batch_updates: list[tuple[int, str, str]] = []
    for p in products:
        title = p["post_title"]
        kw = normalize_keyword(title)
        seo_title = make_title(title, p["post_content"])
        seo_desc = make_description(p["post_content"], title)

        print(f"[#{p['id']:>4}] {title}")
        print(f"   keyword:    {kw}")
        print(f"   title ({len(seo_title):>3}): {seo_title}")
        print(f"   descr ({len(seo_desc):>3}): {seo_desc}")

        if args.apply:
            batch_updates.append((p["id"], "rank_math_focus_keyword", kw))
            batch_updates.append((p["id"], "rank_math_title", seo_title))
            batch_updates.append((p["id"], "rank_math_description", seo_desc))
            summary["updated"] += 1
        else:
            summary["skipped"] += 1
        print()

    if args.apply and batch_updates:
        try:
            batch_set_meta(batch_updates)
            print(f"[*] Batch SQL committed: {len(batch_updates)} meta rows for {summary['updated']} products")
        except Exception as e:
            print(f"[!] Batch SQL FAILED: {e}")
            summary["errors"] = summary["updated"]
            summary["updated"] = 0

    print("─" * 60)
    print(f"Done. updated={summary['updated']}, skipped={summary['skipped']}, errors={summary['errors']}")


if __name__ == "__main__":
    main()
