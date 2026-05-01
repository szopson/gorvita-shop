# Product

## Register

brand

## Users

**Primary (new): B2C retail customer in Poland.**
- 30–60, often a parent or caregiver, looking for natural supplements and herbal cosmetics for everyday health, immunity, joints, skin.
- Cautious. Has been burned by dropshipping stores and "AI-generated" health brands before. Wants to *trust* the producer.
- Visits via search ("herbatka na stawy", "naturalne kosmetyki Rabka", "krople CBD"), category browsing, or word-of-mouth referral. Rarely lands knowing the brand.
- Mobile-first. Reads slowly. Takes screenshots to send to family. Will compare with apteka.gemini.pl, ziolaibalsamy.com, hexeline.

**Secondary (retained): B2B buyer.**
- Pharmacies, herbal shops, online-shop resellers, distributors. Already buys from Gorvita.
- The site must keep its existing B2B login flow (`b2b_customer` role, hidden prices for guests on B2B SKUs, registration at `/b2b-rejestracja` with NIP/REGON/address).
- B2B is **out of scope for design changes in this round** — it stays functionally working but the design pass focuses on the B2C consumer surface.

## Product Purpose

A WooCommerce store at `sklep.gorvita.pl` that lets the **PPUH Gorvita Sp. z o.o.** family business sell herbal supplements and natural cosmetics directly to Polish households for the first time. Gorvita has manufactured these products since 1989 using water from Rabka (Gorce mountains) but has historically distributed only through B2B channels. The shop's job is to make a first-time consumer feel: *this is a real Polish family business, not another dropshipper, not a pharmacy chain — these are people I can trust with my family's health.*

Success looks like:
- A B2C visitor lands on the homepage, scrolls once, and *believes* the 35-year family story before they see a single product.
- That visitor adds a product to cart and checks out without bouncing to a competitor to "verify legitimacy."
- The B2B side keeps working with no regression.

## Brand Personality

Three words: **natural, trustworthy, traditional.**

- **Natural** — water from Gorce mountains, herbal ingredients, minimal-intervention sourcing. Not "all-natural buzzword" — actual Polish mountain herbalism.
- **Trustworthy** — backed by certificates (BIO, GMP, dermatological testing — concrete proofs, not claims), 35 years of continuous operation, and visible humans behind the brand.
- **Traditional** — a family-run business since 1989, second-generation Polish manufacturer, recipes refined over decades. Heritage, not nostalgia kitsch.

Voice: confident, calm, plainspoken Polish. Speaks like a knowledgeable family pharmacist (not a marketer, not a wellness influencer). Uses real product names and ingredients, not slogans.

## Anti-references

What this shop must explicitly **not** look or feel like:

- **Dropshipping store.** Generic Shopify-style product cards on white, no story, no place, no people. No "Lorem ipsum brand" energy.
- **Clinical pharmacy.** Pharma-blue + white, fluorescent lighting feel, regulatory-warning typography. Cold, sterile, anonymous.
- **Wellness influencer / Instagram aesthetic.** Pastel gradients, hand-drawn fonts, "your wellness journey" copy, AI-generated lavender-field photography.
- **Folksy craft-market kitsch.** Distressed wood textures, doily borders, cursive-script grandma fonts. Tradition is communicated through *substance*, not costume.
- **Generic SaaS hero.** Big-number-small-label trust strip, gradient-text headlines, identical icon-tile-stack feature cards. The store is not a B2B tool; design accordingly.

If a stranger sees the homepage and can't tell whether this is a 35-year Polish family business or a generic e-commerce template, the design has failed.

## Design Principles

1. **The family is visible, not hidden.** A first-time visitor sees a human (or a tangible reference to one — name, place, photo, signature) above the fold on the homepage and again in the brand-story section. Stock people don't count.
2. **Certificates are anchors, not afterthoughts.** Trust marks (BIO, GMP, dermatological, 30-year operation) are integrated into the primary visual hierarchy — at least as prominent as the CTA — not buried in the footer.
3. **Place over abstraction.** Rabka, Gorce, the stream — when there's a choice between a generic "natural" image and one that ties to the actual mountain village, choose the place. Specificity beats vibes.
4. **Soul over slick.** Asymmetric layouts, intentional negative space, real photographs over stock illustration. If a section reads as "templated," it's wrong even if the polish is high.
5. **Polish brand voice in Polish.** Headlines and microcopy are written *in Polish first*, not translated from English marketing patterns. No "wellness" if "zdrowie" works. No "premium" — earn the impression through craft, don't claim it as an adjective.

## Accessibility & Inclusion

- Target: **WCAG 2.2 AA** as a baseline. Focus rings on every interactive element (some already in the v6.1 CSS via `:focus-visible` + `--gor-focus`).
- Polish is the only UI language. The store is not localized to other markets.
- Older visitors (45+) are a real share of B2C audience — body text ≥16px, line-height ≥1.6, high contrast (the cream `#F9F9F9` + `#1A1A18` body pair already passes).
- Reduced-motion respected: hero parallax + Ken Burns + scroll reveals must check `prefers-reduced-motion: reduce` and skip animation. (Not yet implemented in `animations.js` — flagged for a later pass.)
- Mobile is the primary viewport. Tap targets ≥44×44px (the existing mobile bottom-nav already meets this).

## Operational notes (for design/AI agents)

- **Live design system source**: the `--gor-*` token set in WP Customizer "Additional CSS" on staging (`gorvita.srv1594477.hstgr.cloud`). This is the source of truth, *not* the older `--gorvita-*` tokens in `wp-content/themes/gorvita-child/style.css`. See `DESIGN.md` for the captured token set.
- **Staging URL**: `https://gorvita.srv1594477.hstgr.cloud`
- **Homepage**: WordPress Page ID 255, Gutenberg block content (edit via `mcp__wordpress__update_page` or the WP admin).
- **Product images**: live in WP Media library; reference by attachment ID where possible (e.g., hero mountain image is ID 268).
