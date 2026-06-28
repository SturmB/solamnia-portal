# Solamnia Member Portal

The member-facing front-end for the Solamnia homelab: self-service signup that
provisions a single federated identity across all opened services, plus a
newsletter and a members-only knowledge base. It is **not** a self-contained
auth island — it federates identity to external SSO infrastructure.

## Language

**Member**:
A person granted access to Solamnia's services by an Admin invitation.
Membership is provider-agnostic — it means "the Admin invited you", not "you
have a Plex account" — so it survives a media-server switch (Plex → Jellyfin).
A Member's identity lives in LLDAP; login federates through Authelia.
_Avoid_: user, friend, account.

**Subscriber**:
An enrollment in the Newsletter, keyed by email — **one Subscriber per email
address**. Every Member is **auto-enrolled** at signup and may opt out at any
time (opt-out, not opt-in — these are personal friends and family, not a cold
list). A Subscriber may exist without being a Member (legacy contacts imported
from Mailchimp); when such a person later becomes a Member, signup **adopts**
the existing Subscriber rather than creating a second one. Auto-enrollment is
create-if-absent: it never overrides a prior opt-out.
_Avoid_: mailing-list contact, recipient.

**Invite**:
An Admin-issued, single-use authorization for one person to become a Member.
Accepting it provisions the Member into LLDAP, auto-enrolls them as a
Subscriber, and auto-invites them to Plex.
_Avoid_: signup link, registration code, voucher.

**Newsletter**:
The infrequent (quarterly at most) email update sent to Subscribers. Replaces
Mailchimp.

**Campaign**:
A single issue of the Newsletter — composed, previewed, then sent to all
opted-in Subscribers.
_Avoid_: blast, email, issue.

**Admin**:
A Member with the administrative role (`is_admin`) — the single operator who
manages Members, Invites, Campaigns, and Knowledge Base articles through the
Filament panel. Normally logs in via SSO like any Member; a local password
login is retained as break-glass for when Authelia is unavailable.

**Knowledge Base**:
Admin-authored troubleshooting articles, visible to Members only.
_Avoid_: KB (in prose), docs, help center.
