# Membership is an LLDAP group; portal rows are shadows bound by `oidc_sub`

LLDAP is the roster of people and Authelia authenticates them, but **existence
in the directory is not membership**. LLDAP is shared by every federated service
on the homelab and also holds non-people (`authelia`, the bind service account;
`admin`, the directory superuser). Membership is therefore expressed as
presence in a dedicated LLDAP group, **`members`** — the group *is* the Admin's
grant, which is what `CONTEXT.md` has always said a Member is.

The portal's local `users` row is a **shadow** of that identity, not a second
roster. On a successful login carrying the `members` group, the portal creates
the row if absent (JIT). The row is bound to the Authelia identity by the OIDC
**subject** claim, stored as `oidc_sub` — never as bare `sub`, which reads as
"subscription" against this project's `Subscriber` vocabulary. The very first
login for an existing row matches on **email** and then writes `oidc_sub`; every
login thereafter matches on `oidc_sub` alone, so a member may change their email
without losing their account.

Authentication comes from Authelia; **authorization stays local**. `is_admin` is
a portal column, never derived from a group claim, so an LLDAP compromise cannot
escalate into Campaign-sending or Member management. ADR-0001 left the source of
truth for membership open; this pins it.

## Consequences

- **Revocation is one action.** Removing someone from `members` (or from LLDAP)
  ends their portal access without a second step in the portal.
- **The local roster fills in lazily** — a person appears in `users` only after
  their first login, so it reflects who has logged in, not who is a Member.
  Phase 3's invite-accept writes the row eagerly, at which point JIT becomes a
  healer for pre-portal accounts and a safety net, rather than the main path.
- **JIT-created rows default to `is_admin = false`** and carry a stamped
  `email_verified_at` — the address was asserted by the Admin in LLDAP, which is
  a stronger claim than a self-service confirmation loop. Stamping it now keeps
  the switch to `MustVerifyEmail` (Phase 3) from locking existing members out.
- **Email matching runs exactly once per row**, only while `oidc_sub` is null.
  In the general case this is an account-takeover vector; here the identity
  provider is self-administered on self-owned hardware, so the attack requires
  the operator to attack themselves.
- `password` becomes nullable, so `whereNotNull('password')` is the honest test
  for "can bypass SSO." It should return exactly one row — the break-glass
  Admin. Two would be a finding.
- The `groups` scope is **required** on the portal's OIDC client. Without it the
  gate cannot be evaluated and every login fails closed.

## Considered and rejected

**Strict roster (no JIT).** Refuse any login without a pre-existing local row.
Rejected because it inverts the source of truth: someone already using Immich,
Mealie, and Audiobookshelf via Authelia would be told they had not been invited,
which is both counter-intuitive and wrong. It would also force the local table
and the directory to be maintained in parallel.

**Ungated JIT.** Create a row for any Authelia-authenticated principal.
Rejected because it silently redefines a Member as "anything in the directory" —
and the directory demonstrably contains a service account. The security risk is
low (those credentials are the operator's own), but the domain model would
contradict `CONTEXT.md`, which is the drift that gets expensive in Phase 3.

**`is_admin` from a group claim.** Rejected: it makes LLDAP group membership an
admin-granting surface, and builds a role system for a population of one.
