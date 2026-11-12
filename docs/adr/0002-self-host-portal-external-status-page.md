# Self-host the portal on the homelab; only the status page is external

## Amendment (2026-10-30)

"Solely" below is softened. The external status page is still the
**authoritative** record of outages and the one place to look when the portal
is down. While the portal is up, the Services hub may also show each Service's
current state, read from the homelab's own Uptime Kuma over the internal Docker
network.

- **Why.** One Service can be down while the portal is up, and a Member already
  on the portal shouldn't have to leave it to find out. Kuma already monitors
  most Member-facing Services, sits on the same network as the portal, and needs
  no API key, so this adds no external dependency.
- **Limits (revised 2026-11-11 below).** Kuma probes internal addresses, so a
  Service can look up in the hub while its public route is broken; UptimeRobot,
  probing from outside, still catches that. If Kuma doesn't answer, the hub
  shows "status unknown" and renders anyway.
- **Unchanged.** No homelab-hosted *replacement* for the status page, and the
  portal never shows private or infrastructure monitors.

## Amendment (2026-11-11): recent Service uptime history

The approved Services hub design (DESIGN.md, variant B; #99/#115) replaces
the earlier live-state-only limit with **current state plus recent hourly
history where practical**. Static history replaces the decorative shimmer.

- **Preferred scale.** Show 24 marks for the last 24 completed hours, oldest
  to newest, with current state shown separately. Each mark represents an
  hour, not a heartbeat. Daily history may be considered later; minute
  samples must never be relabelled as hours or days.
- **Data integrity.** Verify the installed Kuma version supplies sufficient
  timestamped observations and that retrieval and aggregation have reasonable
  resource and bandwidth cost. A recent-heartbeat response alone does not
  establish a full day's coverage. Mark an hour up only when its expected
  observations are complete and successful; a known failure marks it down.
  Incomplete coverage without a known failure is unknown, never presumed up.
- **Fallback.** If reliable hourly history is unavailable or impractical,
  omit the history strip and retain current status. If Kuma fails, or a
  Service has no monitor, show **Status unknown** and still render the hub
  within the short timeout. Keep Service links usable in every state.
- **Presentation.** Current state uses text and a glyph. Static history uses
  distinct shapes as well as colour, an accessible text equivalent, and a
  compact time scale. Do not restore the animated shimmer.
- **Boundaries.** Only configured Member-facing monitors are exposed. Keep
  reads on the internal Docker network, with no API key, a short timeout,
  and no portal-side cache; Kuma may cache its own endpoint responses. Do not
  introduce a new collector or monitoring store merely to supply the strip.
  Internal Kuma observations do not prove the public route is reachable.

The external UptimeRobot Status page remains authoritative for externally
observed outages and available when the portal is down. Link to
`https://status.solamnia.tv` using the prominent **Service status ↗** footer
action (#102), separate from private Service access.

## Original decision

The portal runs **on the Solamnia homelab** (a Dockge stack behind the existing
Cloudflare Tunnel, at the apex `solamnia.tv`), co-located with Authelia, LLDAP,
and the member services. This eliminates any cross-network back-channel from the
portal into the internal identity directory. Outage visibility is provided
**solely by an external, public status page** — UptimeRobot's free tier at
`status.solamnia.tv`.

This reverses the original "host the portal externally for independent uptime"
plan, which conflated *app hosting* with *outage visibility*. Only the status
page needs to survive a Solamnia outage; the portal does not. A members-only,
SSO-gated status page would also be self-defeating (Authelia is down during the
outage it reports), so the status page is public and off-site.

## Consequences

- The KB and portal are unreachable during a **total** Solamnia outage —
  accepted: such outages are rare, most member problems are client-side while
  Solamnia is up, and the status page covers the "we're down" case.
- The "MySQL because the managed hosts offer it, not MariaDB" rationale no
  longer applies (nothing is managed-hosted). MySQL is retained anyway to avoid
  churning the scaffold.
