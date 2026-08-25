# Initial passwords are set through Authelia's reset flow

Invite acceptance provisions a Member into LLDAP but never sets a password.
The acceptance success page shows the invitee the username they just chose and
sends them to Authelia's public reset-password page; they type that username,
and Authelia emails the set-password link through its Resend-backed SMTP
notifier. The portal stays a strict zero-password zone — for provisioning as
well as login — at the cost of one manual step, because Authelia's reset page
cannot be pre-filled.

Part of this is forced, not chosen: LLDAP's GraphQL API (the provisioning
surface) has **no password mutation at all** — a freshly created user is
passwordless by construction, and a passwordless user simply cannot bind, so
Authelia refuses them until the reset completes. Fail-safe without any code.

## Considered and rejected

**LLDAP's own reset flow** (`POST /auth/reset/step1`, auto-triggerable after
provisioning — no typing for the invitee). Rejected because the emailed link
lands on LLDAP's web UI, which is not publicly reachable and should stay that
way: exposing the directory's admin-capable interface to the internet, plus
configuring LLDAP's separate SMTP and `http_url`, is a bad trade for saving
one typed field.

**Transient password handling in the portal** (invitee types a password on the
acceptance page; the portal writes it via LDAP PasswordModify and forgets it).
Rejected: it breaks the zero-password principle established in Phase 2 for a
UX gain the Authelia flow mostly delivers anyway.

**Triggering Authelia's reset email programmatically.** Not possible: there is
no admin API for it (open feature request, authelia/authelia#10177), and the
frontend endpoint the UI uses is unsupported, rate-limited, and
anti-enumeration by design.

## Consequences

- Acceptance sends **two emails** from two senders: the portal's Invite
  (Resend, portal domain) and Authelia's set-password link. The acceptance
  success page must bridge them — displaying the username prominently and
  saying what to expect.
- The first-run experience doubles as training: setting the initial password
  *is* the flow a Member will use for every future reset.
- No new infrastructure: no public LLDAP exposure, no LLDAP SMTP, no new
  proxy routes. The only new credential is the portal's LLDAP service account
  for provisioning, which is unrelated to passwords.
