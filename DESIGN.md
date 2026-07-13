<!-- Direction resolved: p07 "Aurora" prototype adopted portal-wide (2026-07-12).
     Normative source: ~/dev/solamnia-prototypes/public/p07-aurora/ (aurora.css,
     aurora.js, guide/index.html). Tokens below are extracted from it verbatim.
     The portal code itself has NOT been ported yet (app.css is still starter-kit
     zinc); re-run /impeccable document in scan mode after the port to confirm. -->
---
name: Solamnia Member Portal
description: A members-only homelab portal under a living aurora sky — dark, personal, quietly luminous.
colors:
  night: "oklch(0.13 0.03 250)"
  night-deep: "oklch(0.10 0.03 250)"
  panel: "oklch(0.17 0.03 258 / 0.92)"
  ink: "oklch(0.96 0.01 250)"
  muted: "oklch(0.74 0.02 250)"
  violet: "oklch(0.62 0.21 300)"
  violet-text: "oklch(0.80 0.13 300)"
  aurora-green: "oklch(0.82 0.19 165)"
  aurora-rose: "oklch(0.72 0.16 350)"
  edge: "oklch(0.42 0.05 290 / 0.35)"
  edge-top: "oklch(0.70 0.06 290 / 0.28)"
typography:
  display:
    fontFamily: "Clash Display, Avenir Next, sans-serif"
    fontSize: "clamp(2.3rem, 1.2rem + 4.6vw, 4.4rem)"
    fontWeight: 600
    lineHeight: 1.12
    letterSpacing: "-0.01em"
  headline:
    fontFamily: "Clash Display, Avenir Next, sans-serif"
    fontSize: "clamp(1.5rem, 1.1rem + 1.6vw, 2.1rem)"
    fontWeight: 600
    lineHeight: 1.12
    letterSpacing: "-0.01em"
  title:
    fontFamily: "Clash Display, Avenir Next, sans-serif"
    fontSize: "1.2rem"
    fontWeight: 700
    lineHeight: 1.12
  body:
    fontFamily: "Satoshi, system-ui, sans-serif"
    fontSize: "1.0625rem"
    fontWeight: 400
    lineHeight: 1.6
  label:
    fontFamily: "Satoshi, system-ui, sans-serif"
    fontSize: "0.95rem"
    fontWeight: 500
rounded:
  focus: "2px"
  input: "9px"
  panel: "14px"
  pill: "999px"
components:
  button-primary:
    backgroundColor: "{colors.violet}"
    textColor: "{colors.night-deep}"
    typography: "{typography.body}"
    rounded: "{rounded.pill}"
    padding: "0.75rem 1.7rem"
  button-primary-hover:
    backgroundColor: "oklch(0.68 0.20 300)"
    textColor: "{colors.night-deep}"
  button-ghost:
    backgroundColor: "transparent"
    textColor: "{colors.ink}"
    rounded: "{rounded.pill}"
    padding: "0.75rem 1.7rem"
  input:
    backgroundColor: "oklch(0.11 0.03 252)"
    textColor: "{colors.ink}"
    rounded: "{rounded.input}"
    padding: "0.7rem 0.9rem"
  panel:
    backgroundColor: "{colors.panel}"
    rounded: "{rounded.panel}"
---

# Design System: Solamnia Member Portal

## 1. Overview

**Creative North Star: "The Living Sky"**

The portal is a sky phenomenon: curtains of aurora light breathing behind
glass-dark surfaces, running the whole session and never competing with the
content. Solamnia began as a Plex media server, and the cinematic undertone
survives — but the theater here is *light itself*, not a screening room:
darkness, atmosphere, and a slow, otherworldly glow that says someone built
this place with care for the few people invited into it. The sky is loud only
where there is nothing to read (the landing), and nearly subliminal where
reading matters (the Knowledge Base). UI chrome stays night-and-ink; one
violet is the only actionable color; the full aurora spectrum exists *only
inside the light*.

This system explicitly rejects the acquisition-funnel look of corporate SaaS
landing pages, the sterile gray of enterprise admin panels, and the loud,
gradient-heavy, over-friendly tone of consumer apps trying too hard. It also
rejects the "media server → Plex amber/gold" reflex — the wonder comes from
the polar sky, not from copying Plex. Voice: warm, dry, trusting. Members
already belong; nothing is sold, nothing shouts.

**Key Characteristics:**
- A live WebGL aurora behind every page, throttled per-page from full theater
  (landing, 1.0) to a subliminal whisper (KB, 0.1)
- Glass-dark, near-opaque panels with 1px lit edges — "glass, earned"
- Night + ink chrome; violet as the single actionable voice
- Personal, not corporate; warm, not saccharine; polish over decoration

## 2. Colors: The Solar Wind Palette

Night and ink carry the interface; violet carries every action; green and
rose exist only inside the light.

### Primary
- **Aurora Violet** (`oklch(0.62 0.21 300)`, token `violet`): THE action
  color. Primary button fills (with night-deep text — white-on-violet fails
  AA), the nav's current-page tick, the dashboard shimmer, selection
  (`oklch(0.62 0.21 300 / 0.45)`).
- **Lifted Violet** (`oklch(0.80 0.13 300)`, token `violet-text`): violet
  raised to AA for text — links, actionable text, and every focus ring
  (2px outline, 3px offset).

### Secondary — inside the light only
- **Aurora Green** (`oklch(0.82 0.19 165)`) and **Aurora Rose**
  (`oklch(0.72 0.16 350)`): they appear in exactly two places — the shader's
  hue drift and the pre-baked email gradients. Never UI chrome, never status,
  never text.

### Neutral
- **Night** (`oklch(0.13 0.03 250)`): the page ground; a blue-black polar
  sky. **Night Deep** (`oklch(0.10 0.03 250)`): text on violet fills.
- **Panel** (`oklch(0.17 0.03 258 / 0.92)`): the near-opaque surface for
  cards, lists, and forms — dark glass, never blurred.
- **Ink / Muted** (`oklch(0.96 0.01 250)` / `oklch(0.74 0.02 250)`): the text
  ramp — ≈15:1 and ≈7:1 on night. Muted is for secondary text only, never
  body prose.
- **Edge / Edge-Top** (`oklch(0.42 0.05 290 / 0.35)` /
  `oklch(0.70 0.06 290 / 0.28)`): 1px lit panel borders; edge-top is the
  brighter rim where light grazes the top of the glass.

### Named Rules
**The One Voice Rule.** Violet is the only chromatic color in UI chrome. If a
screen seems to need a second color, that's a deliberate design decision to
escalate — never an ad-hoc hue.

**The Light-Only Rule.** Green and rose belong to the aurora and the email
gradients. The moment either appears on a button, badge, or label, it's a bug.

**The Colorblind-Safe State Rule.** At least one Member has red-green color
deficiency. State (success / warning / error) is **never** encoded by
red-vs-green hue alone — status is a plain-ink text label (with an icon or
glyph, e.g. "☾ Maintenance — back Sunday"). The violet shimmer is texture on
top of the label, never the message.

## 3. Typography

**Display Font:** **Clash Display** (600/700; fallback Avenir Next,
sans-serif) — geometric, slightly otherworldly; the voice of the sky.
**Body Font:** **Satoshi** (400/500/700; fallback system-ui, sans-serif) —
everything else: body, labels, buttons, data. 500 for labels/nav, 700 for
CTAs and emphasis.
**Label/Mono Font:** ui-monospace / JetBrains Mono for code in KB articles.

Both families load from Fontshare in one link tag
(`clash-display@600,700` + `satoshi@400,500,700`); self-host in production.

**Character:** A geometric display voice over a clean grotesk body — contrast
by construction, not by weight alone. Clash Display appears at headings, the
nav brand, service names, and email mastheads; Satoshi disappears into the
interface below it.

### Hierarchy
- **Display** (600, `clamp(2.3rem, 1.2rem + 4.6vw, 4.4rem)`, 1.12): the
  landing h1 only. Inner pages cap at ≈2.6rem.
- **Headline** (600, `clamp(1.5rem, 1.1rem + 1.6vw, 2.1rem)`, 1.12): h2 /
  page section headers. `text-wrap: balance`, letter-spacing −0.01em.
- **Title** (700, 1.2rem): h3, panel headings, service names.
- **Body** (400, 1.0625rem, 1.6): Satoshi; prose measure ≤70ch
  (`p { max-width: 68ch }`).
- **Label** (500, 0.95rem): nav items, form labels, table headers.

### Named Rules
**The Sky-Voice Rule.** Clash Display never sets body copy, buttons, inputs,
or data — it marks *names and moments* (headings, the brand, a service's
name). Satoshi carries everything a Member actually reads or operates.

## 4. Elevation

Depth on the night base is conveyed by **light, not shadow**: panels are
near-opaque night glass (`panel`, 0.92 alpha) separated from the ground by a
1px `edge` border and an inset `edge-top` top-rim highlight — light grazing
the top of dark glass. One deep, soft ambient shadow
(`0 18px 48px oklch(0.05 0.02 250 / 0.5)`) grounds panels without ever
reading as a "2014 card." Text contrast over the live sky is guaranteed by a
two-part scrim: a fixed body gradient (night at 2% up top → 75% at the foot)
plus a local radial pool of night behind any text that sits *inside* the sky
(hero ident, invite card). Aurora luminance behind text stays under ~12%.

### Shadow Vocabulary
- **Panel ambient** (`inset 0 1px 0 var(--edge-top), 0 18px 48px
  oklch(0.05 0.02 250 / 0.5)`): the standard panel treatment.
- **Violet glow** (`0 0 32px oklch(0.62 0.21 300 / 0.45)`): primary button
  hover only — elevation as light.

### Named Rules
**The One Blur Rule.** `backdrop-filter: blur(14px)` exists in exactly one
place: the sticky nav. Panels are never blurred; glass is earned, not
sprinkled.

**The Scrim Rule.** Ink is never asked to fight the light. If text sits over
the sky, it gets a scrim (global gradient or local pool) before it gets a
contrast complaint.

## 5. Components

### The Aurora Shader (signature — inside the house)
A fullscreen fixed WebGL2 canvas at `z-index:-2`, rendered at half
resolution: three curl-warped simplex-noise curtains, hue drifting
green→violet→rose (~55s cycle, ~70s master breath). Dependency-free
(`aurora.js`); mount once in the Blade layout, **outside any
Livewire-morphed region**, and set intensity per route via
`<body data-aurora>`: app pages **0.25** · KB/prose **0.1**. Pages without
the attribute get no sky (auth utility pages stay plain). Fallback chain:
no WebGL2 → static CSS frozen-aurora gradient (same palette);
`prefers-reduced-motion` → one static frame (t = 34.5s), re-rendered on
resize only; hidden tab → rAF loop cancelled. Canvas is `aria-hidden`.
Interactive elements may carry `data-pull` to aim a gentle exposure boost
at themselves on hover/focus (eased ~0.06/frame — deliberately languid).

### The Velvet House (signature — brand surfaces)
The two `brand`-register surfaces (public landing, invite acceptance) do
not run the aurora; **outside is velvet, inside is sky.** Their ground is
a seamless ~7s video loop of deep-violet stage drapes swaying gently
(`public/media/velvet-loop.mp4`, poster `velvet-hero.webp` beneath, night
scrim over), with a cursor-following spotlight — an 80vmin radial pool in
warm rose lamplight (`oklch(0.84 0.10 340 / 0.11)`, sampled from the light
falling on the drapes) lerping after the pointer (~140ms lag). Hero text
sits in a local radial pool of night (the hero-scrim), never on a bare
fold. Reduced-motion: still poster, no spotlight. Data-saver: video never
downloads. Regenerating the loop: image-to-video from the poster frame,
then crossfade the tail into the head (ffmpeg xfade) so the loop closes
mathematically.

### Buttons
- **Shape:** pill (999px radius), Satoshi 1rem.
- **Primary:** Aurora Violet fill, **night-deep text** (≈7:1), weight 700,
  padding 0.75rem 1.7rem. Hover: lighter violet (`oklch(0.68 0.20 300)`),
  1px lift, violet glow, 0.25s.
- **Ghost:** transparent, ink text, 1px `edge-top` border; hover swaps the
  border to `violet-text`.
- **Focus:** global `:focus-visible` — 2px `violet-text` outline, 3px offset.
- Reduced motion: no lift, no transition.

### Cards / Containers
- **Corner Style:** 14px radius.
- **Background:** `panel` — near-opaque, never blurred.
- **Shadow Strategy:** the panel ambient treatment (see Elevation).
- **Border:** 1px `edge`, inset `edge-top` top rim.
- **Internal Padding:** ≈1.4rem ± clamp; forms use a `display:grid` +
  ~1.15rem gap rhythm.

### Inputs / Fields
- **Style:** darker-than-night well (`oklch(0.11 0.03 252)`), 1px `edge`
  border, 9px radius, Satoshi 1rem, padding 0.7rem 0.9rem.
- **Focus:** 2px `violet-text` outline at 1px offset; border goes
  transparent.
- **States:** `readonly` renders muted; hints are muted 0.85rem with ink
  `<strong>`; live hints (password strength) update as *text*, announced via
  `aria-live="polite"` — never color alone.

### Navigation
- Sticky top bar: 66%-alpha night + the one 14px backdrop blur, 1px `edge`
  bottom border. Brand in Clash Display 600 with the 26px emblem. Items:
  Satoshi 500 0.95rem, muted → ink on hover; current page gets ink text + a
  2px violet underline via `aria-current="page"` (section pages use
  `aria-current="location"`). Footer: a flat "constellation map" of muted
  links above a fine-print line, `edge` top border.

### The Frequency Band (services list)
Services render as a vertical band of rows inside one panel — **deliberately
not a card grid**: name (Clash 600) / description (muted) / status label /
shimmer, `edge` hairlines between rows, collapsing to a stacked grid under
720px. The shimmer (`.pulse`) is a 64×14px masked violet gradient sliding on
a 4.5–6.2s loop (desynced per row) when Online; static neutral when idle;
frozen under reduced motion. Status is always also a text label.

### Chips / Pills (KB most-read, breadcrumb search affordance)
Panel background, 1px `edge` border, pill radius, 0.92rem; hover lifts border
to `edge-top`. Not-yet-published items are visibly inert (muted, "· soon"
suffix) — never a dead end.

### Motion vocabulary
Ambient motion is the shader plus the dashboard shimmer — nothing else moves
continuously. Headings may carry a one-time `data-reveal` (8px rise +
exposure bloom from brightness 1.9 → 1, 0.6–1.4s,
`cubic-bezier(0.2, 0.7, 0.2, 1)`), gated on an `html.js` class so nothing
hides without JavaScript, unobserved after firing. State transitions run
150–250ms. Every animation has a `prefers-reduced-motion` alternative — one
media block per concern.

## 6. Do's and Don'ts

### Do:
- **Do** keep UI chrome night + ink with Aurora Violet as the only actionable
  color; let green and rose live exclusively inside the shader and the email
  gradients.
- **Do** set primary buttons as night-deep text on violet (≈7:1) —
  white-on-violet fails AA.
- **Do** throttle the sky per page: 1.0 landing, 0.7 invite, 0.25 app, 0.1
  KB — loud only where there's nothing to read.
- **Do** ship the full shader fallback chain every time: no-WebGL static
  gradient, reduced-motion single frame, hidden-tab pause.
- **Do** guarantee text contrast with the scrim system (global gradient +
  local pools); body text clears 4.5:1 everywhere, always.
- **Do** pair state with a text label (and icon/glyph) so red-green-colorblind
  Members are never left guessing — the shimmer is texture, not the message.
- **Do** keep panels near-opaque with 1px lit edges; reserve backdrop blur
  for the sticky nav alone.

### Don't:
- **Don't** use green or rose in UI chrome, badges, status, or text — the
  Light-Only Rule. And **don't** encode success/error as green-vs-red, ever.
- **Don't** default to the "media server → Plex amber/gold" palette; the
  wonder is the polar sky, not Plex's brand.
- **Don't** let it read as corporate SaaS, sterile enterprise-admin gray, or
  an acquisition funnel aimed at strangers. These are Members, not leads.
- **Don't** be too friendly, pedantic, cluttered, or loud — no cutesy
  mascots, exclamation-point copy, gradient shouting, or option-walls.
- **Don't** put Clash Display on body copy, buttons, inputs, or data — the
  Sky-Voice Rule.
- **Don't** blur panels, stack decorative glass, or add continuous motion
  beyond the shader and the shimmer; no orchestrated page-load sequences
  inside the app.
- **Don't** run the shader in email — the aurora band there is the pre-baked
  CSS gradient, table-safe.
- **Don't** use muted (`oklch(0.74 0.02 250)`) for body prose; it's for
  secondary text only.
