---
status: accepted
---

# The portal is the Member's self-service front for their LLDAP identity

A Member changes their own password, email address, and display name, and can
leave Solamnia, from the portal's settings. The portal writes each change to
LLDAP through its existing `lldap_admin` service account: LDAP Password Modify
for passwords (as ADR-0007 already does at acceptance), GraphQL for everything
else. LLDAP stays the identity's single home (ADR-0001); the portal is the
screen the Member uses to manage it. Forgotten passwords still go through
Authelia's reset flow, because the portal cannot check a password the Member
no longer knows.

Chosen because the alternatives send a Member somewhere unfamiliar for routine
changes: Authelia's settings, LLDAP's UI (internal only, and it should stay that
way), or an email to the Admin. With about a dozen Members these changes are
rare, but the operator judged an "ask the Admin" dead end to be too much
friction for something every comparable site lets you do yourself.

## Guards

- **Password, email, and Leaving need the current password**, checked by
  binding to LDAP as the Member. A hijacked session alone cannot take over or
  destroy an identity.
- **An email change is confirmed from the new address** (a signed, expiring
  link) before LLDAP is touched, and the old address is told it happened.
- **The display name needs no confirmation.** It is written to LLDAP first,
  because SSO copies it from LLDAP at every login, so a portal-only change
  would be undone on the next login.

## Leaving deletes from LLDAP first

Leaving deletes the LLDAP user **before** any local record. If that fails,
nothing else changes. This is the reverse of Invite acceptance, which writes
local records first and treats the directory as the step that may need a manual
retry. The order matters here because SSO recreates a portal record for any
directory identity in `members`: delete locally first, fail at LLDAP, and the
Member's next login quietly brings them back. Removing media-server access
stays manual for now (the media-server seam can only share); the Admin is told
via Pushover to do it.

The Admin cannot leave, since that would lock the only operator out of the
panel.
