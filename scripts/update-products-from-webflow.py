#!/usr/bin/env python3
"""
Match WP products to gorvita.pl Webflow data, then enrich post_content.

Strategy:
  1. Load data/webflow-products.json (output of scraper-webflow.py).
  2. Dump DB products (ID, post_name, post_title) via wp-cli.
  3. Match by normalized title; report unmatched.
  4. For each match, build new post_content from template:
       <Description>
       <Sposób użycia>
       <Uwagi>
       <Główne składniki czynne>   (richtext per ingredient)
       <Pełny skład / INCI>
     If the existing description is longer than Webflow's, keep the existing one
     and only APPEND the structured INCI + active-ingredient sections.
  5. Write meta `_gorvita_webflow_slug` so the link is durable.

Usage:
  ./update-products-from-webflow.py --dry-run                # report only
  ./update-products-from-webflow.py --limit 5 --dry-run      # small preview
  ./update-products-from-webflow.py --apply                  # actually UPDATE
  ./update-products-from-webflow.py --apply --slug venal-zel # one product
"""

import argparse
import json
import re
import subprocess
import sys
import unicodedata
from html import escape
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
WF_FILE = ROOT / "data" / "webflow-products.json"
MAPPING_FILE = ROOT / "data" / "webflow-mapping.json"
OVERRIDES_FILE = ROOT / "data" / "webflow-mapping-overrides.json"
PREVIEW_DIR = ROOT / "data" / "update-preview"

WP_CONTAINER = "gorvita-wordpress"


def wp(*args, capture=True):
    cmd = ["docker", "exec", WP_CONTAINER, "wp", *args, "--allow-root"]
    res = subprocess.run(cmd, capture_output=capture, text=True)
    if res.returncode != 0:
        sys.stderr.write(f"wp-cli failed: {' '.join(cmd)}\n{res.stderr}\n")
        sys.exit(1)
    return res.stdout


_POLISH_FOLD = str.maketrans({
    "ł": "l", "Ł": "l", "ą": "a", "ć": "c", "ę": "e",
    "ń": "n", "ó": "o", "ś": "s", "ź": "z", "ż": "z",
})


def normalize_title(s: str) -> str:
    s = s.lower().translate(_POLISH_FOLD)
    s = unicodedata.normalize("NFKD", s)
    s = "".join(c for c in s if not unicodedata.combining(c))
    # Strip leading "x" from counts ("x60 kaps")
    s = re.sub(r"\bx(\d+)", r"\1", s)
    # Strip volume/count suffixes BEFORE collapsing punctuation, while units are still intact
    s = re.sub(
        r"\b\d+\s*(?:ml|mg|kapsulek|kapsulki|kapsulka|kaps\.?|szt\.?|"
        r"tabletek|tabl\.?|tab|kostek|gram|g)\b\.?",
        "", s
    )
    s = re.sub(r"[^a-z0-9]+", " ", s)
    s = re.sub(r"\s+", " ", s).strip()
    return s


def title_tokens(s: str) -> list[str]:
    """Distinctive tokens from a normalized title (drops stop-words & numbers)."""
    stop = {"i", "z", "ze", "do", "na", "w", "we", "po", "od", "dla", "o",
            "the", "and", "or", "of", "60", "30", "20", "100", "150", "200", "250",
            "500", "70", "120", "10", "5", "35", "40", "1", "2", "3"}
    return [t for t in normalize_title(s).split() if t not in stop and len(t) > 1]


def load_db_products() -> list[dict]:
    out = wp("db", "query", """
SELECT p.ID, p.post_name, p.post_title, COALESCE(m.meta_value,'') AS legacy_url, LENGTH(p.post_content) AS content_len
FROM wp_posts p
LEFT JOIN wp_postmeta m ON p.ID = m.post_id AND m.meta_key='_gorvita_legacy_url'
WHERE p.post_type='product' AND p.post_status='publish'
ORDER BY p.ID;""")
    products = []
    for line in out.strip().splitlines()[1:]:  # skip header
        parts = line.split("\t")
        if len(parts) < 5:
            continue
        products.append({
            "id": int(parts[0]),
            "post_name": parts[1],
            "post_title": parts[2],
            "legacy_url": parts[3],
            "content_len": int(parts[4]),
        })
    return products


def get_post_content(post_id: int) -> str:
    return wp("post", "get", str(post_id), "--field=post_content")


def build_match_index(webflow: dict) -> dict:
    """Map normalized_title -> webflow slug."""
    idx = {}
    for slug, data in webflow.items():
        if "error" in data:
            continue
        norm = normalize_title(data["title"])
        if not norm:
            continue
        # Don't allow ambiguous keys
        if norm in idx and idx[norm] != slug:
            print(f"  [warn] ambiguous title '{norm}': {idx[norm]} vs {slug}")
            continue
        idx[norm] = slug
    return idx


def match_db_to_webflow(db_products: list[dict], wf_index: dict, webflow: dict) -> dict:
    """Return mapping {db_id: webflow_slug} + report unmatched."""
    # Load manual overrides first — they win unconditionally
    overrides = {}
    if OVERRIDES_FILE.exists():
        raw = json.loads(OVERRIDES_FILE.read_text(encoding="utf-8"))
        # accept either {"id_str": "slug"} or {"id_str": {"slug": "..."}} or skip-flag
        for k, v in raw.items():
            try:
                pid = int(k)
            except ValueError:
                continue
            if isinstance(v, str):
                overrides[pid] = v
            elif isinstance(v, dict) and "slug" in v:
                overrides[pid] = v["slug"]
        print(f"[*] Loaded {len(overrides)} manual overrides from {OVERRIDES_FILE.name}")

    # Pre-build token sets for fuzzy fallback
    wf_token_sets = {
        slug: set(title_tokens(data["title"]))
        for slug, data in webflow.items() if "error" not in data
    }

    mapping = {}
    used_slugs = set()
    unmatched = []
    fuzzy_log = []

    # Pass 0: apply overrides (overrides may legitimately map multiple DB products
    # to the same Webflow source when Webflow combines variants on one page)
    by_id = {p["id"]: p for p in db_products}
    for pid, slug in overrides.items():
        if pid not in by_id:
            print(f"  [warn] override for missing DB id #{pid} -> '{slug}'")
            continue
        if slug == "SKIP":
            continue
        if slug not in webflow or "error" in webflow.get(slug, {}):
            print(f"  [warn] override #{pid} -> '{slug}' but slug not in Webflow data")
            continue
        mapping[pid] = slug
        # NOTE: do not add to used_slugs — overrides allow shared sources.
    db_products = [p for p in db_products if p["id"] not in mapping and overrides.get(p["id"]) != "SKIP"]

    # Pass 1: exact normalized match — also allow shared source (e.g. Webflow combo pages)
    for p in db_products:
        norm = normalize_title(p["post_title"])
        slug = wf_index.get(norm)
        if slug:
            mapping[p["id"]] = slug
            used_slugs.add(slug)
        else:
            unmatched.append(p)

    # Pass 2: token-overlap fuzzy fallback for still-unmatched
    # Accept only when tokens are in a subset relationship (one title is a longer
    # version of the other) OR symmetric difference is at most 1 token total.
    # Reject when both sides have UNIQUE distinctive tokens (= different products).
    still_unmatched = []
    candidates_per_product = {}
    for p in unmatched:
        db_tokens = set(title_tokens(p["post_title"]))
        if not db_tokens:
            still_unmatched.append(p)
            continue
        scored = []
        for slug, wf_tokens in wf_token_sets.items():
            if slug in used_slugs or not wf_tokens:
                continue
            inter = db_tokens & wf_tokens
            if not inter:
                continue
            db_first = next(iter(title_tokens(p["post_title"])), "")
            wf_first = next(iter(title_tokens(webflow[slug]["title"])), "")
            if db_first != wf_first or len(db_first) < 4:
                continue
            db_only = db_tokens - wf_tokens
            wf_only = wf_tokens - db_tokens
            # Accept only if titles differ by at most 1 distinctive token total.
            # Anything more = likely a different product variant; flag for manual review.
            ok = (len(db_only) + len(wf_only) <= 1)
            score = len(inter) / max(1, len(db_tokens | wf_tokens))
            scored.append((score, slug, ok, db_only, wf_only))
        scored.sort(reverse=True)
        candidates_per_product[p["id"]] = scored[:3]

        # Pick best STRICT-OK candidate; otherwise leave unmatched for manual review
        chosen = next(((s, slug) for s, slug, ok, _, _ in scored if ok and s >= 0.5), None)
        if chosen:
            mapping[p["id"]] = chosen[1]
            used_slugs.add(chosen[1])
            fuzzy_log.append((p["id"], p["post_title"], chosen[1], chosen[0]))
        else:
            still_unmatched.append(p)
    unmatched = still_unmatched

    matched_titles = list(mapping.items())

    print(f"\n=== MATCH REPORT ===")
    print(f"  Matched:    {len(mapping)}/{len(db_products)}")
    print(f"    of which fuzzy: {len(fuzzy_log)}")
    print(f"  Unmatched:  {len(unmatched)}")
    if fuzzy_log:
        print("\n  Fuzzy matches (review!):")
        for pid, title, slug, score in fuzzy_log:
            print(f"    [#{pid:>4}] '{title}' -> {slug}  (score={score:.2f})")
    if unmatched:
        print("\n  Unmatched DB products (need manual mapping or skip):")
        for p in unmatched:
            print(f"    [#{p['id']:>4}] '{p['post_title']}' (slug: {p['post_name']})")
            cands = candidates_per_product.get(p["id"], [])
            if not cands:
                # No token-overlap candidates; show top 3 webflow products by first-token starts-with
                db_first = next(iter(title_tokens(p["post_title"])), "")
                similar = [
                    (slug, webflow[slug]["title"])
                    for slug in webflow if "error" not in webflow[slug]
                    and slug not in used_slugs
                    and (slug.startswith(db_first) or normalize_title(webflow[slug]["title"]).startswith(db_first[:4]))
                ][:3]
                for slug, title in similar:
                    print(f"           ? '{title}' ({slug})")
            else:
                for s, slug, ok, db_only, wf_only in cands:
                    flag = "" if ok else "  [tokens diverge]"
                    extras = []
                    if db_only:
                        extras.append(f"db+{','.join(db_only)}")
                    if wf_only:
                        extras.append(f"wf+{','.join(wf_only)}")
                    print(f"           cand {s:.2f} -> {slug} ('{webflow[slug]['title']}'){flag} [{' '.join(extras)}]")
        # Also write a stub overrides file so user can fill it in
        stub_path = OVERRIDES_FILE.with_suffix(".stub.json")
        stub = {
            str(p["id"]): {
                "_db_title": p["post_title"],
                "_db_slug": p["post_name"],
                "slug": "FILL_ME_OR_SET_TO_SKIP",
                "_candidates": [
                    {"slug": slug, "title": webflow[slug]["title"]}
                    for s, slug, _, _, _ in (candidates_per_product.get(p["id"]) or [])
                ],
            } for p in unmatched
        }
        stub_path.write_text(json.dumps(stub, indent=2, ensure_ascii=False), encoding="utf-8")
        print(f"\n  -> Stub for manual review written to {stub_path.relative_to(ROOT)}")
        print(f"     Edit it, save as {OVERRIDES_FILE.name} (drop the '.stub'), then re-run.")

    # Webflow products not used (could be new products to import)
    used = set(mapping.values())
    unused_wf = [s for s in webflow if s not in used and "error" not in webflow[s]]
    if unused_wf:
        print(f"\n  Webflow products NOT matched to any DB product ({len(unused_wf)}):")
        for s in unused_wf[:30]:
            print(f"    {s} -> '{webflow[s]['title']}'")
        if len(unused_wf) > 30:
            print(f"    ... and {len(unused_wf)-30} more")
    return mapping


# ─── Content composition ──────────────────────────────────────────────

def html_paragraph(text: str) -> str:
    text = (text or "").strip()
    if not text:
        return ""
    # Light cleanup: drop leading "Opis produktu:" since we already have a heading
    text = re.sub(r"^Opis produktu:\s*", "", text)
    return f"<p>{escape(text)}</p>"


def html_list_from_text(text: str) -> str:
    """Convert lines starting with '-' or ':' into a <ul>."""
    items = []
    for chunk in re.split(r"\s+-\s+", " " + text):
        chunk = chunk.strip(" -:;,.")
        if 6 < len(chunk) < 500:
            items.append(f"<li>{escape(chunk)}</li>")
    if len(items) >= 2:
        return "<ul>" + "".join(items) + "</ul>"
    return f"<p>{escape(text)}</p>"


def build_post_content(existing_content: str, wf: dict) -> tuple[str, list[str]]:
    """
    Compose new post_content. Returns (html, [section_names_added]).
    If existing description is longer than Webflow's, keep it; otherwise replace.
    Always append INCI + active-ingredient sections (these are the value-add).
    """
    sections = []
    parts = []

    # ── Description ──
    wf_desc = wf["description"]["text"]
    wf_desc_clean = re.sub(r"^Opis produktu:\s*", "", wf_desc).strip()

    # If existing post_content already contains the same description in HTML, keep existing top portion;
    # otherwise replace with the cleaner Webflow text.
    if len(existing_content) > len(wf_desc_clean) * 1.3 and "<p>" in existing_content:
        # Keep existing intro: take everything up to the first "##" or first <h2>
        m = re.search(r"(<h[1-6]|^##\s)", existing_content, re.MULTILINE)
        if m and m.start() > 0:
            parts.append(existing_content[: m.start()].strip())
            sections.append("description (kept from existing)")
        else:
            parts.append(html_paragraph(wf_desc_clean))
            sections.append("description (webflow)")
    else:
        parts.append(html_paragraph(wf_desc_clean))
        sections.append("description (webflow)")

    # ── Sposób użycia ──
    usage = wf["specs"]["usage"].strip()
    if usage:
        parts.append("<h2>Sposób użycia</h2>")
        parts.append(html_paragraph(usage))
        sections.append("usage")

    # ── Uwagi / ostrzeżenia ──
    warning = wf["specs"]["warning"].strip()
    if warning:
        parts.append("<h2>Uwagi</h2>")
        parts.append(html_paragraph(warning))
        sections.append("warning")

    # ── Główne składniki czynne ──
    active = wf["skladniki"]["active_ingredients"]
    if active:
        parts.append("<h2>Główne składniki czynne</h2>")
        for ing in active:
            parts.append(f"<h3>{escape(ing['name'])}</h3>")
            # Use richtext HTML if present (already structured <p>/<strong>); else fallback to text
            html = ing.get("properties_html") or ""
            if html and "<p" in html:
                parts.append(html)
            else:
                parts.append(html_list_from_text(ing["properties_text"]))
        sections.append(f"active_ingredients ({len(active)})")

    # ── Pełny skład / INCI ──
    inci = wf["skladniki"]["inci"].strip()
    if inci:
        parts.append("<h2>Skład</h2>")
        parts.append(f"<p>{escape(inci)}</p>")
        sections.append("inci")

    return "\n\n".join(p for p in parts if p), sections


def update_product(post_id: int, slug: str, new_content: str, dry_run: bool):
    if dry_run:
        return
    # Direct SQL UPDATE — much faster than wp-cli (no PHP boot per call) and
    # avoids wp-cli stdin gotchas that interpret '-' as a literal value.
    # Use Python tempfile + docker cp to avoid shell-escaping issues with the HTML.
    import tempfile, os
    with tempfile.NamedTemporaryFile(mode="w", suffix=".html", delete=False, encoding="utf-8") as tf:
        tf.write(new_content)
        tmp_path = tf.name
    try:
        # Copy file into container then load via mysql LOAD_FILE-equivalent.
        # Simplest: use wp eval with file content (PHP escapes properly).
        # But cheapest: pass content via stdin to mysql with prepared statement.
        # Actually simplest correct approach: use wp post update with a real file path.
        dst = f"/tmp/post-content-{post_id}.html"
        subprocess.run(["docker", "cp", tmp_path, f"{WP_CONTAINER}:{dst}"], check=True, capture_output=True)
        proc = subprocess.run(
            ["docker", "exec", WP_CONTAINER, "wp", "post", "update", str(post_id), dst, "--allow-root"],
            capture_output=True, text=True,
        )
        if proc.returncode != 0:
            raise RuntimeError(f"wp post update {post_id} failed: {proc.stderr}")
        subprocess.run(["docker", "exec", WP_CONTAINER, "rm", "-f", dst], capture_output=True)
        # Set durable mapping meta
        wp("post", "meta", "update", str(post_id), "_gorvita_webflow_slug", slug)
    finally:
        os.unlink(tmp_path)


def main():
    ap = argparse.ArgumentParser()
    g = ap.add_mutually_exclusive_group(required=True)
    g.add_argument("--dry-run", action="store_true", help="match + preview only, no DB writes")
    g.add_argument("--apply", action="store_true", help="actually update post_content in DB")
    ap.add_argument("--limit", type=int, default=0, help="process only first N matched products")
    ap.add_argument("--slug", help="process only this specific Webflow slug")
    ap.add_argument("--id", type=int, help="process only this specific DB post ID")
    args = ap.parse_args()

    if not WF_FILE.exists():
        sys.exit(f"Missing {WF_FILE}. Run scripts/scraper-webflow.py first.")

    PREVIEW_DIR.mkdir(parents=True, exist_ok=True)

    webflow = json.loads(WF_FILE.read_text(encoding="utf-8"))
    wf_index = build_match_index(webflow)

    db_products = load_db_products()
    print(f"[*] Loaded {len(db_products)} published products from DB")

    mapping = match_db_to_webflow(db_products, wf_index, webflow)

    # Persist mapping for inspection
    MAPPING_FILE.write_text(json.dumps(mapping, indent=2, ensure_ascii=False), encoding="utf-8")
    print(f"[*] Mapping written to {MAPPING_FILE.relative_to(ROOT)}")

    # Build update list
    by_id = {p["id"]: p for p in db_products}
    work = []
    for post_id, slug in mapping.items():
        if args.slug and slug != args.slug:
            continue
        if args.id and post_id != args.id:
            continue
        work.append((post_id, slug))
    if args.limit:
        work = work[: args.limit]

    print(f"\n[*] Will process {len(work)} products ({'DRY-RUN' if args.dry_run else 'APPLY'})")

    summary = []
    for post_id, slug in work:
        p = by_id[post_id]
        wf = webflow[slug]
        existing = get_post_content(post_id)
        new_content, sections = build_post_content(existing, wf)
        old_len, new_len = len(existing), len(new_content)
        delta = new_len - old_len
        marker = "+" if delta > 0 else ("=" if delta == 0 else "-")
        print(f"  [#{post_id:>4}] '{p['post_title'][:50]:50s}' -> {slug}")
        print(f"           old={old_len}B  new={new_len}B  Δ{marker}{abs(delta)}B  sections={','.join(sections)}")

        # Save preview file regardless (helpful for QA in dry-run)
        (PREVIEW_DIR / f"{post_id}-{slug}.html").write_text(
            f"<!-- product #{post_id}: {p['post_title']} (slug: {slug}) -->\n\n"
            f"<!-- ===== EXISTING ({old_len}B) ===== -->\n{existing}\n\n"
            f"<!-- ===== NEW ({new_len}B) ===== -->\n{new_content}\n",
            encoding="utf-8",
        )

        if args.apply:
            try:
                update_product(post_id, slug, new_content, dry_run=False)
                print(f"           ✓ updated")
            except Exception as e:
                print(f"           ✗ FAILED: {e}")
                summary.append((post_id, slug, "fail", str(e)))
                continue
        summary.append((post_id, slug, "ok", f"{old_len}->{new_len}"))

    print(f"\n[*] Done. {len(summary)} products processed.")
    print(f"    Previews saved to {PREVIEW_DIR.relative_to(ROOT)}/")
    if args.apply:
        print(f"    Run: docker exec {WP_CONTAINER} wp cache flush --allow-root")


if __name__ == "__main__":
    main()
