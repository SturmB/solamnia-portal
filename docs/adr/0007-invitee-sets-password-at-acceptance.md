---
status: accepted
supersedes: ADR-0006
---

# The invitee sets their password at acceptance; the portal writes it to LLDAP

The acceptance page asks for a username, display name, and **password** in one
form. The portal creates the LLDAP user, sets the password through LDAP Password
Modify (RFC 3062) bound as its existing `lldap_admin` service account, adds the
user to `members`, consumes the Invite, and redirects straight into SSO. One
email from Solamnia (the Invite), one form, one sign-in, then the dashboard.

This reverses ADR-0006, which kept the portal a zero-password zone and sent the
invitee through Authelia's reset flow for their first password. Live testing
showed that flow cost three emails from Solamnia and six-plus pages. The reset
page's "Reset password" heading over a "Username" field led the operator to type
a password into it, and the default 5-minute link expired in a slow inbox. The
zero-password principle existed to keep onboarding safe and simple; it had
become the main source of friction.

## What the portal accepts, and what it doesn't

- The password is in the portal's memory for the length of one request. It is
  never stored, logged, flashed back into the form, or re-populated after a
  failure; a retry means retyping it.
- It travels as plain LDAP over the internal Docker network to `lldap:3890`, the
  same path Authelia already uses to check every password on every login.
- No new privilege: the service account is already in `lldap_admin` for
  `createUser` / `addUserToGroup`, and admins can already set passwords.
- The portal now owns password strength (minimum 12 characters, breached
  passwords rejected), because neither Authelia nor LLDAP has a policy set.
- Login stays SSO-only. Forgotten passwords still go through Authelia's reset
  flow; the portal never offers password changes.

## Considered and rejected

**Keep ADR-0006, soften the reset page.** Done anyway: an Authelia locale
override retitles the page "Enter your username to get a password link", and
reset links now last 30 minutes. It removes the worst trap but not the extra
email or pages.

**Log the invitee straight into the portal after acceptance, skipping
Authelia.** Zero extra pages, but Authelia asks them to sign in on their first
click into any other service, so the step only moves. Signing in once via SSO
right away, while the browser still holds the new password, gives them a session
across every service and binds `oidc_sub` immediately.

**LLDAP's own reset flow, or triggering Authelia's reset programmatically.**
Still rejected for ADR-0006's reasons: the first exposes LLDAP's admin-capable
UI publicly, and the second has no supported API.
