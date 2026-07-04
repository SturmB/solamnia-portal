# Product

## Register

product

## Users

**Members** — friends and family of the homelab operator, invited by the single
Admin. They are not customers and not strangers being converted; they already
belong. Context of use is casual and infrequent: accepting an invite once,
logging in occasionally to reach a service, reading a quarterly newsletter, or
looking something up in the Knowledge Base. Their job is to get a single
federated identity across Solamnia's services and to self-serve without needing
to ask the Admin.

**Admin** — the single operator, using the Filament panel to manage Members,
Invites, Campaigns, and Knowledge Base articles. One person, not a team.

## Product Purpose

The member-facing front-end for the Solamnia homelab. It provides self-service
signup that provisions one federated identity (LLDAP, via Authelia) across every
opened service, plus a newsletter (replacing Mailchimp) and a members-only
Knowledge Base. Success is a Member who accepts an invite and reaches the
services they were invited to with no hand-holding from the Admin, and an Admin
who can run invites and campaigns from one panel without operational friction.

## Brand Personality

Sleek, personal, inviting. The voice is warm but never saccharine — these are
real friends and family, so it addresses people, not "users," yet it stays out
of their way. Underneath the warmth is quiet competence: the polish should read
as care, not showmanship. A subtle cinematic undertone is welcome as a nod to
the homelab's origin as a Plex media server — a hint of theater, not a marquee.

Emotional goal: a newly-invited Member accepting their invite for the first time
should feel *impressed, with a dash of delight* — "oh, this is nice" — without
the interface ever trying too hard.

## Anti-references

- **Too friendly / saccharine** — no cutesy mascots, exclamation-point copy, or
  forced whimsy. Warmth comes from tone and craft, not decoration.
- **Sterile & corporate** — not enterprise-admin gray, not a SaaS conversion
  funnel, not stock-photo landing pages.
- **Pedantic** — don't over-explain or lecture; trust the reader.
- **Cluttered** — no dense option-walls or competing focal points.
- **Loud** — no aggressive gradients, no shouting hero type, no crypto-bright
  saturation.

## Design Principles

- **Earned intimacy.** This is a club of people who already belong. Speak to
  members as people; skip the acquisition-funnel patterns aimed at strangers.
- **Cinematic restraint.** Honor the Plex/media-server origin with a dark,
  considered, faintly theatrical feel — expressed through depth, imagery, and
  focus, never through noise or spectacle.
- **Delight through polish, not gimmicks.** The "dash of delight" comes from
  precise motion, timing, and detail — not from novelty affordances.
- **Get out of the way.** Members visit rarely; clarity on return beats
  power-user density. The tool should disappear into the task.
- **One-operator honesty.** The admin surface serves exactly one person — favor
  the obvious path over the configurable one.

## Accessibility & Inclusion

- Target **WCAG 2.2 AA**: body text ≥4.5:1 contrast, keyboard-navigable
  throughout, visible focus states, `prefers-reduced-motion` honored on every
  animation.
- **At least one Member has mild red-green color blindness.** Never encode state
  (success / error / warning) by red-vs-green hue alone — always pair color with
  an icon, shape, or text label. Edge-case reds and greens are the risk, so
  differentiate on more than hue.
