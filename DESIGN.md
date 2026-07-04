<!-- Color & type resolved (Solamnia Violet + Marcellus/Instrument Sans). Components still pending: re-run /impeccable document in scan mode once real components exist. -->
---
name: Solamnia Member Portal
description: A members-only portal for a homelab — sleek, personal, faintly cinematic.
---

# Design System: Solamnia Member Portal

## 1. Overview

**Creative North Star: "The Private Screening Room"**

Solamnia began as a Plex media server, so its portal should feel like being
handed a ticket to a small, private theater — dark, considered, and warm toward
the few people who belong there. The surface is a dark base with near-neutral
panels; a single projector-glow violet — drawn from the brand mark — is the one
chromatic voice, doing the work of identity, focus, and action. Nothing shouts.
The delight is in the polish and the timing, not in decoration.

This system explicitly rejects the acquisition-funnel look of corporate SaaS
landing pages, the sterile gray of enterprise admin panels, and the loud,
gradient-heavy, over-friendly tone of consumer apps trying too hard. Warmth is
carried by tone, typography, and a faint cinematic atmosphere — never by cutesy
copy or clutter. It also deliberately avoids the obvious "media server → Plex
amber/gold" reflex; the cinematic feeling comes from darkness, focus, and light,
not from copying Plex's palette.

**Key Characteristics:**
- Dark, screening-room base with a single committed accent
- Sleek and quiet; competence read as care
- One reserved moment of theater (first login), calm everywhere else
- Personal, not corporate; warm, not saccharine

## 2. Colors

A committed strategy: one saturated accent on a dark neutral base.

### Primary — Solamnia Violet
The sole chromatic voice, drawn from the brand mark (`#400080`). One hue (~296),
used as two tones so it can both fill and stay readable on the dark base:
- **Deep Violet** — `oklch(0.32 0.176 296)` (the logo/emblem color). Solid fills,
  primary buttons (white text, echoing the white-on-purple emblem), and the
  "curtain" / drenched moments.
- **Lit Violet** *(the projector glow)* — `oklch(0.72 0.15 296)`. Links, focus
  rings, current selection, actionable text, and the signature cursor-following
  glow on brand surfaces. Reads as *light* on the dark base and clears 4.5:1.

Its rarity is what gives it meaning.

### Neutral
Tinted a whisper toward the brand hue (~296), never toward warm-by-default.
- **Screening-Room Base** — `oklch(0.15 0.022 296)`: the dark body background —
  the darkened theater.
- **Panel Surface** — `oklch(0.20 0.028 296)`: a slightly lifted dark neutral for
  cards, toolbars, and the app shell; distinguished from the base by tone, not
  by heavy borders. Next tonal step / hairline border: `oklch(0.30 0.035 296)`.
- **Ink / Muted Ink** — `oklch(0.96 0.01 296)` / `oklch(0.72 0.02 296)`: text
  ramp. Both clear 4.5:1 on base and surface; no light-gray-for-elegance.

### Named Rules
**The One Voice Rule.** Solamnia Violet is the only chromatic color in the
system. If a screen needs a second color to make sense, that's a signal to
promote to a full palette deliberately (a `colorize` pass) — not to sprinkle in
ad-hoc hues.

**The Colorblind-Safe State Rule.** At least one Member has red-green color
deficiency. State (success / warning / error) is **never** encoded by red-vs-
green hue alone — always pair it with an icon, shape, or text label. Reds and
greens must differ on more than hue.

## 3. Typography

**Display Font:** **Marcellus** (with **Marcellus SC** for small-caps) — an
inscriptional roman that reads like lettering engraved on a brass plaque beside a
private screening-room door. Distinctive and quietly cinematic, not the Inter/Geist
default. One weight (400); its scale and the small-caps cut carry the contrast.
Marcellus SC echoes the logo's small-caps wordmark and suits kickers and the
tagline.
**Body Font:** **Instrument Sans** — the starter kit's humanist sans, kept for app
UI, labels, and data. (It's a common default; consistency and the fact that the
distinctive voice already lives in Marcellus + the logo make it the right, quiet
choice for body — the personality is deliberately up top, not in the paragraphs.)
**Label/Mono Font:** *(none yet; add only if data/tabular UI needs it)*

**Character:** A touch of personality up top, disappearing calm below — a
serif + sans contrast axis (Marcellus display over Instrument Sans body), never
two near-identical families. Marcellus is the brass plaque; the real logo is the
brand mark; Instrument Sans is the interface getting out of the way.

### Hierarchy
*(sizes to be set at implementation; product register wants a fixed rem scale,
tight ratio ~1.125–1.2, not fluid clamp headings inside the app)*
- **Display**: the distinctive face; reserved for the landing and first-login
  moments.
- **Headline / Title**: display or body-bold for section and page headers.
- **Body**: humanist sans, capped at 65–75ch for prose (Knowledge Base, emails).
- **Label**: sans, for UI controls and data.

### Named Rules
**The Quiet-Below-the-Fold Rule.** Display personality lives on brand surfaces
and page titles. Inside the app, one well-tuned sans carries everything;
display faces never appear on buttons, inputs, or data.

## 4. Elevation

Depth is conveyed on a dark surface primarily through **tonal layering** —
panels sit above the base by being a step lighter, not by heavy drop shadows.
Motion energy is Responsive, so surfaces are calm at rest. The one luminous
material is the **projector glow**: a soft, diffuse light bloom in Lit Violet,
used sparingly (the cursor-following glow, focus states, the first-login
reveal). Shadows, when used, are ambient and soft — never the hard, dark
"2014 card" shadow.

### Named Rules
**The Light-Not-Shadow Rule.** On the dark base, elevation reads as *light*
(glow, a lighter tonal step) rather than as a cast shadow. If a surface needs a
heavy drop shadow to separate from its background, the tonal step is wrong.

## 5. Components

*Omitted in seed — no components exist yet beyond the Livewire starter kit
(Flux + zinc/Instrument Sans defaults). Re-run `/impeccable document` in scan
mode once real components are built to capture their tokens and states.*

## 6. Do's and Don'ts

### Do:
- **Do** keep Solamnia Violet as the single chromatic voice — Deep Violet for
  fills, Lit Violet for interaction and glow; let its rarity carry meaning.
- **Do** build on a dark, screening-room base and convey depth with tonal
  layering and soft accent glow.
- **Do** reserve display-type personality and choreographed motion for the
  landing and first-login moments; keep the logged-in app quick and quiet
  (150–250ms state transitions).
- **Do** scope the cursor-following projector glow to brand surfaces (landing,
  invite acceptance), and disable it — and all motion — under
  `prefers-reduced-motion`.
- **Do** pair state color with icon/shape/text so red-green-colorblind Members
  are never left guessing.

### Don't:
- **Don't** default to the "media server → Plex amber/gold" palette. The cinema
  feeling comes from darkness and light, not from copying Plex.
- **Don't** let it read as corporate SaaS, sterile enterprise-admin gray, or an
  acquisition funnel aimed at strangers. These are Members, not leads.
- **Don't** be too friendly, pedantic, cluttered, or loud — no cutesy mascots,
  exclamation-point copy, gradient shouting, or option-walls.
- **Don't** use decorative click ripples or any motion that doesn't convey
  state; a crisp fast press response (~120ms) beats a material ripple.
- **Don't** encode success/error/warning by red-vs-green hue alone.
- **Don't** use light-gray body text on the dark base "for elegance"; body text
  clears 4.5:1 or it changes.
