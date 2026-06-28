# Self-host the portal on the homelab; only the status page is external

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
