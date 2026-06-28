# Federated member identity (Authelia + LLDAP), not self-contained auth

The portal does not own member authentication. A Member's identity lives in
**LLDAP** and login federates through **Authelia** via OIDC: the portal is an
OIDC *client* (login) and, eventually, a *provisioner* (signup). The scaffolded
Fortify member auth — registration, member password / 2FA / passkeys — is
therefore **unused for members**. Chosen to give one member identity across all
opened Solamnia services (provider-agnostic — survives a Plex → Jellyfin
switch), over self-contained Fortify auth, WorkOS, or Plex OAuth.

## Consequences

- The SSO infrastructure (LLDAP + Authelia) is stood up **before** the portal's
  member features are built. The portal is built against live infra, once.
- **Member provisioning into LLDAP is automated.** On invite-accept the portal
  creates the LLDAP user via its GraphQL API over the local network — possible
  only because the portal is co-located on the homelab (see ADR-0002); this
  reverses an earlier "manual provisioning" stance that existed solely to avoid
  a back-channel from an externally-hosted app into the internal directory. The
  portal never sets or emails a password; the member sets their own via
  Authelia's self-service reset flow (the SSO doc's sidestep for the LLDAP
  password-API wrinkle). The Plex invite (outbound to plex.tv) is automated too.
- **Manual provisioning is the fallback, not the primary path.** The local
  Member + Subscriber records are written atomically before any external call,
  so if the LLDAP create fails the portal notifies the Admin (Pushover) to
  provision by hand; signup is never blocked.
- A single **Admin** login remains local (Fortify) as break-glass, independent
  of Authelia uptime.

## Considered and rejected: Wizarr as the invite/onboarding layer

[Wizarr](https://github.com/wizarrrr/wizarr) was evaluated. It is media-server-first
(invite + onboard users to Plex/Jellyfin/Emby/ABS/Romm/Komga/Kavita) and,
confirmed current as of 2026-06, it **does not** provision an external LDAP/LLDAP
directory and is not an OIDC/identity provider — its "SSO support" only makes it
a *client* of an external IdP. It therefore cannot be the portal's identity
layer, and adopting it for the Plex-invite slice alone would create two parallel
invite/onboarding front doors for ~12 members. The portal keeps a single front
door: LLDAP provisioning + a one-call Plex invite + a Services-hub/KB onboarding.
Wizarr is retained only as a UX reference for the onboarding wizard.
