# Gorvita Design System

Premium natural wellness brand — inspired by Weleda, Apteka Meduz, Lush Apothecary.
Ekstrahowany z gorvita.pl oraz dostosowany do sklepu B2C+B2B.

## Kolory

### Primary
| Token | Hex | Użycie |
|-------|-----|--------|
| `--gorvita-green` | `#2D5016` | Primary CTA, linki, logo accent |
| `--gorvita-green-dark` | `#1F3A0F` | Hover state, focus ring |
| `--gorvita-sage` | `#6B8E5F` | Secondary buttons, badges, highlights |
| `--gorvita-sage-light` | `#A8BFA0` | Backgrounds subtle, dividers |

### Neutral
| Token | Hex | Użycie |
|-------|-----|--------|
| `--gorvita-ink` | `#1A1A1A` | Body text |
| `--gorvita-gray-700` | `#404040` | Secondary text |
| `--gorvita-gray-500` | `#737373` | Muted text, captions |
| `--gorvita-gray-300` | `#D4D4D4` | Borders, dividers |
| `--gorvita-cream` | `#F5F3F0` | Background sekcji, cards |
| `--gorvita-white` | `#FFFFFF` | Main background |

### Accent
| Token | Hex | Użycie |
|-------|-----|--------|
| `--gorvita-earth` | `#8B7355` | Category badges, decorative |
| `--gorvita-rose` | `#C97B63` | Sale badges, urgency |
| `--gorvita-gold` | `#C9A961` | Premium products, B2B tier |

### Feedback
| Token | Hex | Użycie |
|-------|-----|--------|
| `--gorvita-success` | `#16A34A` | Success messages, in-stock |
| `--gorvita-warning` | `#D97706` | Warnings, low stock |
| `--gorvita-error` | `#DC2626` | Errors, out of stock |

## Typografia

### Rodziny
- **Headings**: `Fraunces` (Google Fonts) — serif, display, eleganckie, naturalne
- **Body/UI**: `Inter` (Google Fonts) — sans-serif, czytelne, nowoczesne

### Skala (mobile-first, desktop w nawiasach)
| Token | Mobile | Desktop | Użycie |
|-------|--------|---------|--------|
| `--fs-hero` | 2.5rem (40px) | 4rem (64px) | Hero headlines |
| `--fs-h1` | 2rem (32px) | 3rem (48px) | Page titles |
| `--fs-h2` | 1.5rem (24px) | 2rem (32px) | Section headers |
| `--fs-h3` | 1.25rem (20px) | 1.5rem (24px) | Subsections |
| `--fs-body-lg` | 1.125rem (18px) | 1.125rem | Lead paragraphs |
| `--fs-body` | 1rem (16px) | 1rem | Default body |
| `--fs-small` | 0.875rem (14px) | 0.875rem | Captions, meta |
| `--fs-xs` | 0.75rem (12px) | 0.75rem | Badges, tags |

### Wagi
- Fraunces: 400, 500, 600
- Inter: 400, 500, 600, 700

## Spacing (8pt grid)

| Token | Value | Pixels |
|-------|-------|--------|
| `--space-1` | 0.25rem | 4 |
| `--space-2` | 0.5rem | 8 |
| `--space-3` | 0.75rem | 12 |
| `--space-4` | 1rem | 16 |
| `--space-6` | 1.5rem | 24 |
| `--space-8` | 2rem | 32 |
| `--space-12` | 3rem | 48 |
| `--space-16` | 4rem | 64 |
| `--space-24` | 6rem | 96 |

## Border radius

| Token | Value | Użycie |
|-------|-------|--------|
| `--radius-sm` | 0.375rem | Badges, tags |
| `--radius` | 0.5rem | Buttons, inputs |
| `--radius-lg` | 0.75rem | Cards |
| `--radius-xl` | 1rem | Large cards, modals |
| `--radius-full` | 9999px | Pills, avatars |

## Shadows

```css
--shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
--shadow: 0 4px 6px -1px rgba(0,0,0,0.08), 0 2px 4px -2px rgba(0,0,0,0.05);
--shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.05);
--shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.10), 0 8px 10px -6px rgba(0,0,0,0.05);
```

## Breakpoints

| Token | Value | Urządzenie |
|-------|-------|-----------|
| `sm` | 640px | Large phone |
| `md` | 768px | Tablet |
| `lg` | 1024px | Laptop |
| `xl` | 1280px | Desktop |
| `2xl` | 1536px | Large desktop |

Mobile-first: default styles dla mobile, `@media (min-width: X)` dla większych.

## Komponenty (kluczowe)

### Button primary
- `background: var(--gorvita-green)`
- `color: white`
- `padding: 0.875rem 1.75rem` (14/28px)
- `border-radius: var(--radius)` (8px)
- `font-weight: 600`, `font-size: 1rem`
- `transition: all 150ms ease`
- Hover: `background: var(--gorvita-green-dark)`, `transform: translateY(-1px)`, `shadow-lg`

### Product card
- `background: white`, `border: 1px solid var(--gorvita-gray-300)`
- `border-radius: var(--radius-lg)`
- `overflow: hidden`
- Image aspect ratio 1:1 lub 4:5
- Hover: `shadow-xl`, `transform: translateY(-2px)`

### Input
- `border: 1.5px solid var(--gorvita-gray-300)`
- `border-radius: var(--radius)`
- `padding: 0.75rem 1rem`
- Focus: `border-color: var(--gorvita-sage)`, `outline: 3px solid rgba(107,142,95,0.15)`

## Layout

- Container max-width: `1280px`
- Gutter: `var(--space-6)` (24px) mobile, `var(--space-8)` (32px) desktop
- Grid: 12-col desktop, 4-col mobile
- Section vertical padding: `var(--space-16)` mobile, `var(--space-24)` desktop

## Imagery

- **Produktowe**: białe tło lub naturalne (drewno, tkanina lniana), miękkie cienie, świeże, kontrast niski
- **Lifestyle**: naturalne światło, odcienie ziemi, woda/zioła/drewno
- **Ikony**: line icons 1.5px stroke, zaokrąglone końce (Lucide / Phosphor style)

## Accessibility

- Kontrast min WCAG AA: text 4.5:1, large text 3:1
- Focus visible zawsze: `outline: 3px solid var(--gorvita-sage)` + `outline-offset: 2px`
- Targets dotyku min 44x44px
- Prefer `rem` nad `px` (user zoom friendly)
