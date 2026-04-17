#!/usr/bin/env python3
"""
Fuzzy-match scraped product image filenames to local image files.

Input:  data/products.json (from scraper) + data/images/ directory (local files)
Output: data/image-mapping.json — maps product SKU/id → local filename (or null)

Strategy (stacked; first match wins):
1. Exact filename match
2. Normalized stem match (strip suffixes like -wiz, -mini, _fin, -new, numbers)
3. Token overlap: ≥70% of name tokens must appear in the candidate filename
4. Longest common subsequence of tokens when size info is present (e.g., "150ml", "60kaps")

Preference rules:
- Prefer JPG/PNG over TIF (web-ready format)
- Prefer shorter filename (usually cleaner, "final" variant)
- Prefer non-"mini" over "-mini" (higher resolution for web)
"""
import json
import os
import re
import sys
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent
PRODUCTS_JSON = Path("/opt/gorvita-shop/data/products.json")
IMAGES_DIR = Path("/opt/gorvita-shop/data/images")
OUTPUT = Path("/opt/gorvita-shop/data/image-mapping.json")

# Common "noise" tokens that clutter filenames but don't identify product
NOISE_TOKENS = {
    "wiz", "mini", "fin", "new", "nowe", "nowy", "kopia", "wizualizacja",
    "opak", "cmyk", "rgb", "c", "1c", "2c", "3c", "v1", "v2", "v3", "v4", "v5",
    "ok", "tmp", "tab", "full",
}

# Synonyms / variant spellings seen in product names and filenames
SYNONYMS = {
    "zel": "zel", "żel": "zel",
    "masc": "masc", "maść": "masc",
    "konopii": "konopi", "konopi": "konopi", "konopna": "konopi",
    "zywokost": "zywokost", "żywokost": "zywokost", "żywokostowy": "zywokost", "zywokostowy": "zywokost", "żywokostem": "zywokost",
    "witaminy": "witamina", "witamin": "witamina",
    "witaminami": "witamina",
    "kapsulek": "kaps", "kapsułek": "kaps", "kaps": "kaps", "kapsułki": "kaps", "kapsulki": "kaps",
    "gram": "g", "g": "g",
    "ml": "ml",
    "tabletek": "tab", "tab": "tab",
    "kasztanowy": "kasztanow", "kasztanowa": "kasztanow", "kasztanowcem": "kasztanow", "kasztanowiec": "kasztanow",
    "rokitnik": "rokitnik", "rokitnikowa": "rokitnik", "rokitnikiem": "rokitnik",
    "czerwona": "czerwona", "chlodzaca": "chlodzaca", "chłodząca": "chlodzaca",
    "konska": "konsk", "końska": "konsk", "konski": "konsk", "koński": "konsk",
}


def normalize(text: str) -> str:
    """Lowercase, remove Polish diacritics, remove punctuation."""
    text = text.lower()
    mapping = {"ą": "a", "ć": "c", "ę": "e", "ł": "l", "ń": "n",
               "ó": "o", "ś": "s", "ź": "z", "ż": "z"}
    for k, v in mapping.items():
        text = text.replace(k, v)
    # Replace non-alphanumeric with space, collapse
    text = re.sub(r"[^a-z0-9]+", " ", text)
    return text.strip()


def tokenize(text: str) -> list[str]:
    """Normalize + split + apply synonyms + drop noise.

    Merges size info like "60 kaps" → "60kaps" or "100 ml" → "100ml"
    so it matches filenames where those are jammed together.
    Also splits "x60" → "60", "60kaps" → ["60kaps", "60"] (keep both forms).
    """
    text = normalize(text)

    # Merge number + unit into single token: "100 ml" → "100ml", "60 kaps" → "60kaps"
    text = re.sub(
        r"(\d+)\s+(ml|g|kaps|kap|kapsulek|kapsułek|tab|tabletek|sztuk|szt)\b",
        lambda m: m.group(1) + (SYNONYMS.get(m.group(2), m.group(2))),
        text,
    )
    # Strip leading "x" in "x60": "x60kaps" → "60kaps"
    text = re.sub(r"\bx(\d+)", r"\1", text)

    tokens = text.split()
    out = []
    for t in tokens:
        t = SYNONYMS.get(t, t)
        if t in NOISE_TOKENS:
            continue
        if len(t) == 1 and not t.isdigit():
            continue
        if t == "x":
            continue
        out.append(t)
        # Also add a "digits-only" variant for size tokens: "60kaps" adds "60"
        m = re.match(r"^(\d+)(\w+)$", t)
        if m:
            out.append(m.group(1))
    return out


def stem_filename(filename: str) -> str:
    """Strip extension and all noise tokens."""
    stem = Path(filename).stem
    return " ".join(tokenize(stem))


def extension_rank(filename: str) -> int:
    """Lower = better (web-friendly)."""
    ext = Path(filename).suffix.lower()
    return {".jpg": 0, ".jpeg": 0, ".png": 1, ".webp": 2, ".tif": 10, ".tiff": 10}.get(ext, 5)


def is_mini(filename: str) -> bool:
    return "mini" in filename.lower()


def score_match(product_tokens: list[str], product_title: str, filename: str) -> tuple[float, str]:
    """Return (score, reason). Higher score = better. 0 = no match."""
    fname_tokens = tokenize(Path(filename).stem)
    if not product_tokens or not fname_tokens:
        return 0, ""

    product_set = set(product_tokens)
    fname_set = set(fname_tokens)

    # Hard requirement: at least the *first significant* product token must be in filename.
    # Hero = pure alphabetic tokens ≥4 chars (NOT size/quantity descriptors like "60kaps").
    hero_tokens = [t for t in product_tokens if len(t) >= 4 and t.isalpha()][:2]
    stem_norm = normalize(Path(filename).stem)

    def hero_present(h: str) -> bool:
        # Exact match
        if h in fname_set or h in stem_norm:
            return True
        # Prefix match: first 5 chars of hero appear in filename (tolerates Polish spelling variants)
        if len(h) >= 5 and h[:5] in stem_norm:
            return True
        return False

    hero_match_count = sum(1 for h in hero_tokens if hero_present(h))
    if hero_tokens and hero_match_count == 0:
        return 0, ""

    overlap = product_set & fname_set
    if not overlap:
        return 0, ""

    # Base score: proportion of product tokens matched
    base = len(overlap) / max(len(product_set), 1)

    # Strong bonus: hero tokens matched (product identity)
    hero_bonus = 0.4 * hero_match_count

    # Bonus: size/volume tokens matched (e.g., "200ml", "60kaps")
    size_tokens = {t for t in product_set if re.search(r"\d", t) and len(t) <= 6}
    size_match_bonus = 0.2 * len(size_tokens & fname_set) if size_tokens else 0

    # Bonus: percent indicator (5, 10 for CBD %) matched when title mentions %
    percent_bonus = 0
    if "%" in product_title or any(t in product_tokens for t in ["5", "10"]):
        pct_tokens = {t for t in product_tokens if t in ("5", "10", "15", "20", "25")}
        percent_bonus = 0.15 * len(pct_tokens & fname_set)

    # Penalty: extra random tokens in filename (probably a different product)
    noise_in_fname = len(fname_set - product_set)
    noise_penalty = 0.03 * noise_in_fname

    score = base + hero_bonus + size_match_bonus + percent_bonus - noise_penalty
    reason = f"hero={hero_match_count}/{len(hero_tokens)} overlap={len(overlap)}/{len(product_set)} size={len(size_tokens & fname_set)}"
    return score, reason


def find_best_match(product: dict, candidates: list[str]) -> tuple[str | None, float, str]:
    title_tokens = tokenize(product["title"])

    best = None
    best_score = 0
    best_reason = ""

    for cand in candidates:
        score, reason = score_match(title_tokens, product["title"], cand)
        if score > best_score:
            best = cand
            best_score = score
            best_reason = reason
        elif score == best_score and best is not None:
            # Tie-break by format preference and mini/non-mini
            if (extension_rank(cand), is_mini(cand), len(cand)) < (extension_rank(best), is_mini(best), len(best)):
                best = cand
                best_reason = reason

    # Require minimum score to accept
    MIN_SCORE = 0.5
    if best_score < MIN_SCORE:
        return None, best_score, best_reason
    return best, best_score, best_reason


def main():
    if not PRODUCTS_JSON.exists():
        print(f"✗ Missing {PRODUCTS_JSON}", file=sys.stderr)
        sys.exit(1)
    if not IMAGES_DIR.exists():
        print(f"✗ Missing {IMAGES_DIR}", file=sys.stderr)
        sys.exit(1)

    with open(PRODUCTS_JSON, encoding="utf-8") as f:
        products = json.load(f)

    # Gather all candidate images recursively.
    # Include extensionless files — client's export has some JPEGs without extension.
    image_exts = {".jpg", ".jpeg", ".png", ".webp", ".tif", ".tiff"}
    candidates = []
    for root, _, files in os.walk(IMAGES_DIR):
        for f in files:
            suffix = Path(f).suffix.lower()
            # Skip non-image known types
            if suffix in {".pdf", ".ods", ".doc", ".docx", ".xls", ".xlsx", ".txt"}:
                continue
            # Accept: known image extension, or extensionless file that looks like a product image
            if suffix in image_exts:
                rel = Path(root).relative_to(IMAGES_DIR) / f
                candidates.append(str(rel))
            elif suffix == "" and len(f) > 5 and any(c.isalpha() for c in f):
                # Verify it's actually an image by magic-byte check (fast)
                full = Path(root) / f
                try:
                    with open(full, "rb") as fp:
                        head = fp.read(12)
                    if head.startswith(b"\xff\xd8\xff") or head.startswith(b"\x89PNG") or head.startswith(b"GIF") or head[8:12] == b"WEBP":
                        rel = Path(root).relative_to(IMAGES_DIR) / f
                        candidates.append(str(rel))
                except OSError:
                    pass

    print(f"→ {len(products)} products, {len(candidates)} image files")

    mapping = []
    matched = 0
    unmatched = []

    for p in products:
        best, score, reason = find_best_match(p, candidates)
        entry = {
            "sku": p["sku"],
            "id": p["id"],
            "title": p["title"],
            "scraped_filename": p["image_filename"],
            "local_file": best,
            "score": round(score, 3),
            "reason": reason,
        }
        mapping.append(entry)
        if best:
            matched += 1
        else:
            unmatched.append(entry)

    # Apply manual overrides if present
    overrides_path = Path("/opt/gorvita-shop/data/image-mapping-overrides.json")
    overrides_applied = 0
    if overrides_path.exists():
        try:
            with open(overrides_path, encoding="utf-8") as f:
                overrides_data = json.load(f)
            overrides = {o["sku"]: o for o in overrides_data.get("overrides", [])}
            for entry in mapping:
                if entry["sku"] in overrides:
                    ov = overrides[entry["sku"]]
                    entry["local_file"] = ov.get("local_file")
                    entry["score"] = 1.0 if ov.get("local_file") else 0.0
                    entry["reason"] = f"manual override: {ov.get('note', '')}"
                    overrides_applied += 1
        except (json.JSONDecodeError, KeyError) as e:
            print(f"⚠ Could not apply overrides: {e}", file=sys.stderr)

    matched = sum(1 for e in mapping if e["local_file"])
    unmatched = [e for e in mapping if not e["local_file"]]

    with open(OUTPUT, "w", encoding="utf-8") as f:
        json.dump(mapping, f, ensure_ascii=False, indent=2)

    # Also mirror to repo's data folder
    repo_output = REPO_ROOT / "data" / "image-mapping.json"
    repo_output.parent.mkdir(parents=True, exist_ok=True)
    with open(repo_output, "w", encoding="utf-8") as f:
        json.dump(mapping, f, ensure_ascii=False, indent=2)

    print(f"\n✓ Matched:  {matched}/{len(products)}")
    if overrides_applied:
        print(f"  (of which {overrides_applied} via manual override)")
    print(f"  Saved → {OUTPUT}")
    if unmatched:
        print(f"\n⚠ Unmatched ({len(unmatched)}):")
        for u in unmatched[:20]:
            print(f"  · {u['title'][:50]:50}  scraped: {u['scraped_filename']}")
        if len(unmatched) > 20:
            print(f"  ... and {len(unmatched) - 20} more")


if __name__ == "__main__":
    main()
