#!/usr/bin/env python3
"""
Scraper for www.gorvita.pl (Webflow CMS).
Pulls full product descriptions, ingredients (INCI), active-ingredient breakdowns,
usage instructions, and warnings — content that the legacy offon scraper missed.

Output: data/webflow-products.json (keyed by Webflow slug)
HTML cache: data/webflow-cache/{slug}.html (skip re-fetch on rerun)
"""

import json
import re
import sys
import time
from pathlib import Path
from urllib.parse import urljoin

import requests
from bs4 import BeautifulSoup, Tag

BASE_URL = "https://www.gorvita.pl"
LIST_URL = f"{BASE_URL}/produkty"
HEADERS = {"User-Agent": "Mozilla/5.0 (gorvita-shop content sync)"}

ROOT = Path(__file__).resolve().parent.parent
CACHE_DIR = ROOT / "data" / "webflow-cache"
OUT_FILE = ROOT / "data" / "webflow-products.json"


def fetch(url: str, cache_path: Path | None = None) -> str:
    if cache_path and cache_path.exists():
        return cache_path.read_text(encoding="utf-8")
    resp = requests.get(url, headers=HEADERS, timeout=30)
    resp.raise_for_status()
    if cache_path:
        cache_path.write_text(resp.text, encoding="utf-8")
    time.sleep(0.4)
    return resp.text


def discover_slugs() -> list[str]:
    html = fetch(LIST_URL, CACHE_DIR / "_index.html")
    slugs = sorted({
        m.group(1)
        for m in re.finditer(r'href="(?:https://www\.gorvita\.pl)?/produkty/([a-z0-9][a-z0-9-]*)"', html)
        if m.group(1) not in {"", "produkty"}
    })
    return slugs


def first_layout54_pair(soup: BeautifulSoup, heading_match: str) -> Tag | None:
    """Find first parent div containing both layout54_content-left (with matching H2) and layout54_content-right."""
    for left in soup.find_all("div", class_="layout54_content-left"):
        h2 = left.find("h2")
        if not h2:
            continue
        if heading_match.replace(" ", "").lower() not in h2.get_text(strip=True).replace(" ", "").lower():
            continue
        parent = left.parent
        if not parent:
            continue
        right = parent.find("div", class_="layout54_content-right")
        if right:
            return right
    return None


def clean_text(node: Tag) -> str:
    """Strip nav/buttons/widgets, collapse whitespace, return text."""
    if node is None:
        return ""
    for noise in node.select("a.button, .button-group, nav, script, style, .w-condition-invisible"):
        noise.decompose()
    text = node.get_text(" ", strip=True)
    text = re.sub(r"\s+", " ", text).strip()
    text = text.replace("‍", "").strip()
    return text


def extract_richtext_html(node: Tag) -> str:
    """Extract inner HTML of richtext divs in a section, joined; trims wrappers."""
    if node is None:
        return ""
    rts = node.select("div.w-richtext")
    seen, parts = set(), []
    for rt in rts:
        h = "".join(str(c) for c in rt.children).strip()
        h = re.sub(r"\s+", " ", h)
        # Strip Webflow zero-width joiners (cosmetic noise that appears between blocks)
        h = h.replace("‍", "").replace("&zwnj;", "").replace("&#x200d;", "")
        # Strip empty <p>‍</p>-like blocks that remain after ZWJ removal
        h = re.sub(r"<p[^>]*>\s*</p>", "", h)
        if not h or h in seen:
            continue
        seen.add(h)
        parts.append(h)
    return "\n".join(parts)


def extract_description(soup: BeautifulSoup) -> dict:
    right = first_layout54_pair(soup, "Opis")
    if not right:
        return {"text": "", "html": ""}
    return {"text": clean_text(right), "html": extract_richtext_html(right)}


def extract_specs(soup: BeautifulSoup) -> dict:
    """Specyfikacja produktu — usually contains 'Sposób użycia' + 'Uwaga'."""
    right = first_layout54_pair(soup, "Specyfikacjaproduktu") or first_layout54_pair(soup, "Specyfikacja")
    if not right:
        return {"usage": "", "warning": "", "raw_text": ""}

    raw_text = clean_text(right)
    usage, warning = "", ""

    # Walk children in order; switch state on h4/h5/h6 markers
    state = None
    buckets = {"usage": [], "warning": []}
    for el in right.descendants:
        if not isinstance(el, Tag):
            continue
        if el.name in ("h2", "h3", "h4", "h5", "h6"):
            t = el.get_text(strip=True).lower()
            if "sposób" in t or "stosowanie" in t:
                state = "usage"
                continue
            if "uwaga" in t or "ostrzeż" in t or "przeciwwska" in t:
                state = "warning"
                continue
        if state and el.name == "p":
            txt = el.get_text(" ", strip=True)
            if txt:
                buckets[state].append(txt)

    usage = " ".join(buckets["usage"]).strip()
    warning = " ".join(buckets["warning"]).strip()

    if not usage and not warning:
        # Fallback: split raw text on keywords
        m_use = re.search(r"Sposób użycia\s*(.*?)(?:Uwaga|$)", raw_text, re.IGNORECASE | re.DOTALL)
        m_warn = re.search(r"Uwaga[:\s]*(.*?)$", raw_text, re.IGNORECASE | re.DOTALL)
        if m_use:
            usage = m_use.group(1).strip()
        if m_warn:
            warning = m_warn.group(1).strip()

    return {"usage": usage, "warning": warning, "raw_text": raw_text}


def find_skladniki_container(soup: BeautifulSoup) -> Tag | None:
    h2 = soup.find("h2", string=lambda s: s and "Składniki" in s)
    if not h2:
        return None
    node = h2
    for _ in range(8):
        node = node.parent
        if node is None:
            break
        cls = node.get("class") or []
        if "container-large" in cls:
            return node
    return node


def extract_skladniki(soup: BeautifulSoup) -> dict:
    """
    Returns:
      inci: full INCI string (the 'Składniki / Ingredients' richtext)
      active_ingredients: [{name, properties_html, properties_text}, ...]
    """
    container = find_skladniki_container(soup)
    if not container:
        return {"inci": "", "active_ingredients": []}

    # Find INCI: it's the FIRST .text-size-small.w-richtext after a label "Składniki / Ingredients"
    inci = ""
    inci_label = container.find(
        lambda t: isinstance(t, Tag)
        and "text-weight-semibold" in (t.get("class") or [])
        and "Składniki" in t.get_text()
        and "ngredients" in t.get_text()
    )
    if inci_label:
        nxt = inci_label.find_next("div", class_="w-richtext")
        if nxt:
            inci = clean_text(nxt)

    # Active ingredients: each is `<div class="text-size-small">{Name}</div>` immediately
    # followed by a `<div class="w-richtext">{props}</div>` (sibling-or-near sibling).
    active = []
    seen_names = set()
    for rt in container.find_all("div", class_="w-richtext"):
        # Skip the INCI block itself (it has class text-size-small)
        if "text-size-small" in (rt.get("class") or []):
            continue
        # Walk previous siblings in DOM until we find a non-empty `text-size-small` (not semibold) label
        prev = rt.find_previous(
            lambda t: isinstance(t, Tag)
            and t.name == "div"
            and "text-size-small" in (t.get("class") or [])
            and "text-weight-semibold" not in (t.get("class") or [])
            and "w-richtext" not in (t.get("class") or [])
        )
        if not prev:
            continue
        name = prev.get_text(" ", strip=True)
        if not name or len(name) > 200 or name in seen_names:
            continue
        seen_names.add(name)
        props_html = "".join(str(c) for c in rt.children).strip()
        props_text = clean_text(rt)
        if not props_text:
            continue
        active.append({"name": name, "properties_html": props_html, "properties_text": props_text})

    return {"inci": inci, "active_ingredients": active}


def extract_main_image(soup: BeautifulSoup) -> str:
    """Best-effort hero image URL."""
    og = soup.find("meta", property="og:image")
    if og and og.get("content"):
        return og["content"]
    img = soup.find("img", class_=re.compile("product|hero", re.I))
    if img and img.get("src"):
        return urljoin(BASE_URL, img["src"])
    return ""


def scrape_product(slug: str) -> dict:
    url = f"{BASE_URL}/produkty/{slug}"
    cache = CACHE_DIR / f"{slug}.html"
    html = fetch(url, cache)
    soup = BeautifulSoup(html, "html.parser")

    h1 = soup.find("h1")
    title = h1.get_text(" ", strip=True) if h1 else slug
    title = re.sub(r"\s+", " ", title).strip()

    return {
        "slug": slug,
        "url": url,
        "title": title,
        "description": extract_description(soup),
        "specs": extract_specs(soup),
        "skladniki": extract_skladniki(soup),
        "main_image": extract_main_image(soup),
    }


def main():
    only = None
    if len(sys.argv) > 1:
        only = sys.argv[1:]
        print(f"[*] Limited mode: scraping only {only}")

    print(f"[*] Discovering slugs from {LIST_URL}")
    slugs = discover_slugs()
    print(f"[*] Found {len(slugs)} product slugs on gorvita.pl")

    if only:
        slugs = [s for s in slugs if s in only] + [s for s in only if s not in slugs]

    out = {}
    for i, slug in enumerate(slugs, 1):
        print(f"  [{i}/{len(slugs)}] {slug}", end=" ", flush=True)
        try:
            data = scrape_product(slug)
            out[slug] = data
            d_len = len(data["description"]["text"])
            n_active = len(data["skladniki"]["active_ingredients"])
            inci_len = len(data["skladniki"]["inci"])
            print(f"-> desc={d_len}B inci={inci_len}B active={n_active}")
        except Exception as exc:
            print(f"-> ERROR: {exc}")
            out[slug] = {"slug": slug, "error": str(exc)}

    OUT_FILE.write_text(json.dumps(out, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"\n[*] Wrote {len(out)} products to {OUT_FILE.relative_to(ROOT)}")

    successes = [v for v in out.values() if "error" not in v]
    if successes:
        avg_desc = sum(len(p["description"]["text"]) for p in successes) / len(successes)
        with_active = sum(1 for p in successes if p["skladniki"]["active_ingredients"])
        with_inci = sum(1 for p in successes if p["skladniki"]["inci"])
        print(f"    avg description: {avg_desc:.0f} B")
        print(f"    with INCI: {with_inci}/{len(successes)}")
        print(f"    with active ingredients: {with_active}/{len(successes)}")


if __name__ == "__main__":
    main()
