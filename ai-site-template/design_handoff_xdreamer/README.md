# Handoff: X-DREAMER — AI Generation Platform

## Overview
X-DREAMER is a Thai-language AI generation platform for images, video, audio, and 3D. The user "weaves dreams from threads of thought" — prompts flow through a visual metaphor of luminous fibers. This bundle contains 8 screens (Home, Studio, Dashboard, Gallery, Docs, About, Login, Signup) plus reusable components, an animated hero, and a feature banner slider.

## About the Design Files
The files in this bundle are **design references created in HTML/JSX** — working prototypes that show the intended look and behavior. They are **not production code to copy directly**. The task is to **recreate these designs in the target codebase's existing environment** (React/Next.js, Vue, Svelte, native, etc.), following the target project's established patterns, component libraries, and design tokens. If no environment exists yet, pick the most appropriate framework (React + Tailwind or a CSS-in-JS solution is a natural fit given the inline-style approach used here).

## Fidelity
**High-fidelity (hifi).** Final colors, typography scale, spacing, animations, and interactions are all pinned. Recreate pixel-perfect using your stack's idioms (Tailwind classes, CSS modules, styled-components — whatever your codebase uses). The inline `style={{…}}` objects in the prototypes are there for iteration speed — migrate them to your system.

---

## Design System

### Color Tokens
Everything is driven by **HSL with a `hueShift` user-controllable offset** (default 70°). All brand-accent colors derive from 3-4 base hues rotated by `hueShift`:

| Role | Base HSL | Notes |
|---|---|---|
| Background root | `#030612` | Near-black with blue undertone |
| Surface / card | `rgba(255,255,255,0.04)` | Glass over root |
| Border subtle | `rgba(255,255,255,0.06)` | Dividers |
| Border hairline | `rgba(255,255,255,0.1)` | Buttons, inputs |
| Text primary | `#fff` | |
| Text secondary | `rgba(203,213,225,0.75)` | Body copy |
| Text tertiary | `#94a3b8` | Captions, labels |
| Text muted | `#64748b` | Meta |
| Accent cyan | `#a5f3fc` | Eyebrow labels, links |
| Accent violet (italic) | `#c4b5fd` | Italic emphasis |
| Accent mint (italic) | `#6ee7b7` | Alt italic emphasis |
| Accent indigo (italic) | `#a5b4fc` | Alt italic emphasis |
| Error | `#fca5a5` | Logout etc. |
| Primary gradient | `linear-gradient(135deg, #10b981 0%, #06b6d4 50%, #8b5cf6 100%)` | Primary CTA |
| Thread hues (rotate) | `160, 200, 230, 270, 290` | +`hueShift` for all fiber animations |

### Typography
- **Font stack**: `Inter, sans-serif` (UI). Thai system font cascade used inline; no custom Thai font is loaded.
- **Display** (h1 hero): `clamp(48px, 7vw, 96px)` · weight 200 · letter-spacing `-0.03em` · line-height 1
- **H1 page**: `clamp(48px, 6vw, 80px)` · weight 300 · letter-spacing `-0.02em` · line-height 1.05
- **H2 section**: `clamp(40px, 5vw, 64px)` · weight 300 · letter-spacing `-0.02em` · line-height 1.05
- **H3**: 24px · weight 500
- **Body**: 16–18px · weight 300 · color `rgba(203,213,225,0.75–0.8)` · line-height 1.55
- **Eyebrow/Label**: 10–12px · letter-spacing 0.14–0.24em · uppercase · color `#a5f3fc` or `#94a3b8` · often prefixed with `·`
- **Italic emphasis**: `fontStyle: italic` with `fontWeight: 200` on `<span>` inside headings. **IMPORTANT for Thai**: italic spans need `padding-bottom: 0.15em; display: inline-block` to prevent vowel marks and tone marks from being clipped. See the global CSS in `AI Loom.html`.

### Spacing
- Section padding: `80–140px` top/bottom, `48px` horizontal (desktop)
- Container max-width: `1280px` (main), `860px` (articles/manifesto), `1100px` (pricing)
- Card padding: `18–32px`
- Grid gaps: `12 / 16 / 20 / 24 / 32px`

### Radii
- Inputs / small buttons: `10–12px`
- Cards: `16–20px`
- Banner / large surfaces: `20–28px`
- Pill / badge: `999px`
- Logo mark: `10px` (nav) / `28px` (hero)

### Shadows
- Card hover: `0 25px 50px -15px hsla(<hue>,80%,55%,0.4)`
- Card base: `0 10px 30px -10px rgba(0,0,0,0.6)`
- CTA primary: `0 8px 24px -8px rgba(139,92,246,0.6)` or `0 12px 30px -10px hsla(<h>,80%,50%,0.7)`
- Banner: `0 40px 80px -30px hsla(<h>,70%,30%,0.6), 0 0 0 1px rgba(255,255,255,0.04)`
- Glow (logo / dots): `0 0 20px hsla(<h>,90%,65%,0.5)`

### Motion
- Transitions: `cubic-bezier(0.4,0,0.2,1)` over `200–400ms`
- Button hover: `transform: translateY(-1px); filter: brightness(1.1)` (250ms)
- Card hover: lift `-4px` + colored shadow (400ms)
- Page enter: `pageIn` — opacity 0→1 + translateY 8px→0 (400ms)
- Banner slide enter: `bannerIn` — opacity 0→1 + translateX 20px→0 (600ms)
- Floating logo: `floatY` — vertical ±12px (6s ease-in-out infinite)

### Global effects
- **Noise overlay**: SVG turbulence, opacity 0.03, `mix-blend-mode: overlay`, fixed, z-index 100
- **Fiber threads canvas**: fixed full-viewport behind everything, base opacity 0.28, boosts to 0.83 on hero
- **Backdrop blur**: `blur(14–18px) saturate(1.3)` on nav + glass cards

---

## Screens

### 1. Home (`#home` — default)
Long-scroll landing with these sections in order:
1. **Nav** (fixed, blurred)
2. **Hero** — huge display heading "ทอความฝัน / ด้วยเส้นใยแห่งจินตนาการ" (Weave dreams with fibers of imagination), floating X-DREAMER logo top-right with glow + `floatY` anim, eyebrow `· AI Loom v4`, animated prompt typer cycling 4 samples, 4 mode tabs (Image/Video/Audio/3D), 4 live "generation" preview frames
3. **Banner Slider** (NEW) — 420px-tall rounded card, 5 auto-rotating slides (6s each, pause on hover, progress bar, dot pager). Slides: Seedance 2.0, Voxel Forge, Loom Live, Muse Audio v3, Workflow Nodes. Each slide has: animated SVG pattern background (waves/voxel/threads/audio bars/nodes), badge, eyebrow subtitle, gradient title, description, primary+secondary CTA, stats side-panel. `<video>` element is stubbed — swap `videoSrc` in `BANNER_SLIDES` array.
4. **Features** — 3-column grid of feature cards
5. **Gallery** — 4-column grid of 8 sample works (GalleryCard component)
6. **How It Works** — 4-step process with connecting SVG thread
7. **Pricing** — 3-tier (free/pro/studio) centered
8. **FooterCTA** — large "เริ่มทอความฝัน" call to action

### 2. Studio (`#studio`)
3-column workspace: left sidebar (prompt + controls, 320px), center canvas (2×2 grid of generation frames + 16-item history strip), right sidebar (layers/credits panel, 340px). At tablet (≤1024px) the right sidebar hides; at mobile (≤720px) everything stacks.

### 3. Dashboard (`#dashboard`)
User home. Welcome header, 4-up stat cards (works count, credits, avg generation time, completion rate), 1.6fr/1fr split with recent works grid + usage mini-chart, collections grid (3-up, each with 3 preview thumbs).

### 4. Gallery Detail (`#gallery`)
Community browse. Filter chips row + sort dropdown. Masonry layout via `columnCount: 4` (→3 tablet, →2 mobile). 24 items.

### 5. Docs (`#docs`)
3-column: sidebar TOC (260px), content (prose + code blocks), on-this-page nav (240px). Covers getting started, concepts (Thread/Prompt/Weave/Loom), API examples.

### 6. About (`#about`)
Narrow article layout (860px). Manifesto headline, body paragraphs, pull-quote with left-border accent. Team section below — 3-column member cards.

### 7. Login / Signup (`#login` / `#signup`)
Centered auth card on full-bleed fiber-threads background. Logo at top, form fields, OAuth buttons (Google / GitHub / Apple), toggle to opposite mode. Submit calls `onAuth(profile)` — parent stores in localStorage + redirects to Dashboard.

---

## Components (reusable)

| Component | File | Purpose |
|---|---|---|
| `Nav` | sections.jsx | Fixed top nav. Props: brand, page, onNav, loggedIn, user, onLogout |
| `UserMenu` | sections.jsx | Avatar dropdown when logged in |
| `Hero` | sections.jsx | Landing hero with prompt typer |
| `GenFrame` | sections.jsx | Single generation preview tile with fiber animation |
| `BannerSlider` | banner-slider.jsx | Auto-rotating feature banner (5 slides, progress bar, dots) |
| `Gallery` / `GalleryCard` | sections-b.jsx | Home gallery grid |
| `Features` | sections-b.jsx | 3-column feature grid |
| `HowItWorks` | sections-b.jsx | 4-step process with SVG thread connector |
| `Pricing` | sections-b.jsx | 3-tier pricing |
| `FooterCTA` | sections-b.jsx | Bottom CTA section |
| `FiberThreads` | fiber-threads.jsx | Canvas animation — bezier light threads with cursor-react |
| `FiberGlyph` | fiber-threads.jsx | Small SVG fiber ornament |
| All `*Page` | pages.jsx | StudioPage, DashboardPage, GalleryDetailPage, AboutPage, AuthPage, DocsPage |

---

## Interactions & Behavior

### Navigation
- Hash-based routing (`window.location.hash`). Set `hash = 'dashboard'` to navigate.
- `hashchange` listener resets scroll to top on page change.
- Nav links + logo click call `onNav(id)`.

### Auth state
- Stored in `localStorage` under key `aether_user` (JSON). Shape: `{ name, email }`.
- On login/signup submit or OAuth click → call `login(profile)` → sets state + localStorage + redirects to `#dashboard`.
- `logout()` clears state + localStorage + returns to `#home`.
- Nav swaps CTAs ↔ UserMenu based on `loggedIn`.

### Banner slider
- Auto-advance every 6000ms, progress updates every 40ms.
- Hover over banner → pauses auto-advance.
- Dot click → jumps to index, resets timer.
- 5 SVG animation patterns driven by a shared time counter `t` (rAF-ticked +0.4/frame).

### Hero prompt typer
- Cycles 4 sample prompts every 5200ms.
- Typing effect: 28ms/char intervals.
- "Generating" pulse flashes every 5200ms.

### Tweaks panel (design-only)
Floating panel with user controls: `hueShift` slider (0–360), `threadsDensity` (10–100), `threadsSpeed` (0.1–2), `brandName` text, Navigation shortcuts. When Tweaks mode is off, panel hides entirely — production app should either omit this or convert specific tweaks into real user preferences.

### Fiber threads (background)
- Global fixed canvas under content, opacity = `baseOpacity (0.28) + heroBoost (0 to 0.55)` where `heroBoost` fades as user scrolls past 600px on home.
- Cursor attracts threads — track `mousemove` on window.
- Performance: `devicePixelRatio` capped at 1.5, FPS capped at 30, `IntersectionObserver` pauses when off-screen.

---

## Responsive breakpoints
Defined in global `<style>` in `AI Loom.html`.

- **Desktop** (>1024px): default layouts
- **Tablet** (≤1024px): nav shrinks, LIVE badge hides, nav CTAs shorten, hero logo → 80px inline, Studio hides right panel, Docs hides right panel, 4-col grids → 2-col, masonry 4→3
- **Mobile** (≤720px): nav links hide entirely (no hamburger yet — add one in production), hero logo moves above heading, Studio/Docs stack to 1 column, all grids → 1 col, masonry 4→2, banner stacks content above stats

**Production TODO**: implement a hamburger menu for mobile nav.

---

## State Management
Minimal — the prototype uses `React.useState` + `localStorage`. In production:
- **Auth**: persist session via your auth provider (NextAuth, Supabase, Firebase, etc.), not localStorage
- **Tweaks**: user preferences → server-side profile or cookie
- **Routing**: replace hash-routing with your framework's router (Next.js App Router, React Router, etc.)
- **Generation state**: real API calls + job polling/streaming (the "generating" flashes in the prototype are purely visual)

---

## Assets
- `xdreamer-logo.png` — brand mark (user-supplied). Used in nav (38×38 @ 10px radius) and hero (180×180 @ 28px radius with glow + float).
- All other visuals are **procedural** — SVG + Canvas. No image files beyond the logo.

---

## Files in this bundle
- `AI Loom.html` — entry HTML with global styles, media queries, React roots, script loading order. Start here.
- `tokens.css` — minimal token layer (mostly unused; inline styles dominate — consider migrating)
- `tweaks-panel.jsx` — reusable Tweaks panel primitives (TweaksPanel, TweakSlider, TweakText, useTweaks)
- `fiber-threads.jsx` — Canvas fiber animation + SVG glyph
- `sections.jsx` — Nav, UserMenu, Hero, GenFrame
- `banner-slider.jsx` — BannerSlider + 5 SVG pattern renderers
- `sections-b.jsx` — Gallery, Features, HowItWorks, Pricing, FooterCTA
- `pages.jsx` — StudioPage, DashboardPage, GalleryDetailPage, AboutPage, AuthPage, DocsPage
- `xdreamer-logo.png` — brand asset

**Script load order** (required — components attach to `window`):
```
tweaks-panel.jsx → fiber-threads.jsx → sections.jsx → banner-slider.jsx → sections-b.jsx → pages.jsx
```

---

## Implementation notes for Claude Code

1. **Don't ship the prototype** — recreate each screen in your chosen framework. The inline-style objects are reference values, not code to import.
2. **Extract the design tokens first** — build a tokens file (Tailwind config, CSS variables, or design-system package) with the colors/spacing/radii/shadows listed above. Everything else flows from this.
3. **`hueShift` is a product feature, not a dev tool** — users love it. Preserve it by driving all brand colors through an HSL function parameterized on `hueShift`.
4. **Thai italic clipping** — any `<span>` with `font-style: italic` inside a Thai heading needs `padding-bottom: 0.15em` and `display: inline-block` to avoid clipping tone marks. Bake this into your typography utilities.
5. **Banner video slots** — the slider is designed to host real `<video>` tags. Each slide in `BANNER_SLIDES` has a `videoSrc` field (currently `null`). When you plug in real Seedance/Voxel/etc. demo videos, the SVG patterns become fallback posters.
6. **Performance-sensitive animations** — the fiber canvas caps DPR at 1.5, FPS at 30, and uses IntersectionObserver. Replicate these guards; don't naively animate at 60fps DPR 2.
7. **Mobile hamburger** — not built. Add one.
