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
A Member's identity lives in LLDAP; login federates through Authelia. The grant
is expressed mechanically as presence in the LLDAP **`members`** group — mere
existence in the directory is not membership, since LLDAP is shared across the
homelab and also holds service accounts (ADR-0004). The portal's local `users`
row is a **shadow** of that identity, bound by `oidc_sub`, not a second roster.
_Avoid_: user, friend, account. (The `users` table is infrastructure, not a
domain concept — a Member is never called a user in prose.)

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
An Admin-issued authorization for one person — bound to their email address —
to become a Member. It expires (about two weeks), is revocable while pending,
and single-use means **one successful redemption**: a failed provisioning
attempt leaves it live. Accepting it provisions the Member into LLDAP (the
invitee chooses their own permanent username and display name), auto-enrolls
them as a Subscriber, and requests their media-server access (Plex today —
provider-agnostic, like Membership itself). The portal never handles the new
Member's password; the initial password is set through Authelia's reset flow
(ADR-0006).
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

## Deploying the Campaign send sweep

Campaign sends run through `campaigns:send-due`, driven by Laravel's scheduler
(see ADR-0003). Two host-level prerequisites — **without the crontab line,
nothing sends**:

1. Add the single crontab line that runs the scheduler every minute:

    ```cron
    * * * * * cd /path/to/portal && php artisan schedule:run >> /dev/null 2>&1
    ```

2. Set `PUSHOVER_TOKEN` and `PUSHOVER_USER` (see `.env.example`) so the
   success/failure notification fires — the only signal that a send happened or
   that the cron has stopped.

## Deploying SSO login

SSO federates to infrastructure the portal does not own, so four steps are done
**by hand** on the homelab before the code can work (see ADR-0004):

1. **Create the LLDAP `members` group** and add every Member to it. Do not add
   `admin` (directory superuser) or `authelia` (bind service account) — neither
   is a person, and neither was invited.

    **Each existing Member's LLDAP email must match their portal row's email
    before their first SSO login.** The email match is the one-shot bootstrap
    that binds a directory identity to an existing row; if the addresses
    differ, JIT provisioning quietly creates a fresh non-admin row instead —
    learned the hard way when the Admin's mismatched email produced a
    duplicate, non-admin account. (Deliberately fail-safe: mismatches
    downgrade, never escalate.)

2. **Register the portal as an OIDC client** in Authelia's `configuration.yml`,
   under `identity_providers.oidc.clients`:

    ```yaml
    - client_id: "solamnia-portal"
      client_name: "Solamnia Portal"
      client_secret: "<hash from: authelia crypto hash generate pbkdf2>"
      public: false
      authorization_policy: "one_factor"
      redirect_uris:
          - "https://solamnia.tv/auth/callback"
      scopes: ["openid", "profile", "email", "groups"]
      token_endpoint_auth_method: "client_secret_post"
    ```

    `client_secret_post` is **required, not a preference**: Socialite sends
    the client credentials in the token-request POST body, and Authelia
    enforces the declared method strictly — registering `client_secret_basic`
    (as the other homelab clients use) makes every token exchange fail with
    a 401 and every login a 500.

    The **`groups` scope is required** — without it the `members` gate cannot be
    evaluated and every login fails closed. `redirect_uris` is a security
    allowlist: register `solamnia.tv` only, and keep the plaintext secret in the
    portal's `.env`, never in the repo.

3. **Switch Authelia's notifier from `filesystem` to SMTP.** _(Done — the
   live config sends through Resend.)_ Left as `filesystem`, password-reset
   mail is written to `/config/notification.txt` instead of being sent — so a
   Member who cannot log in has no way to recover, and the Invite flow's
   set-password handoff (ADR-0006) never arrives.

4. **Restart Authelia** so steps 2–3 take effect. This drops the sessions of
   every federated service (Immich, Mealie, Audiobookshelf, Calibre-Web) —
   pick the moment.

On the portal side, set `AUTHELIA_BASE_URL` / `AUTHELIA_CLIENT_ID` /
`AUTHELIA_CLIENT_SECRET` / `AUTHELIA_REDIRECT_URI` in the stack's `.env`, then
`docker compose pull && docker compose up -d` — the `pull` is load-bearing:
`up -d` alone recreates containers but never re-fetches a same-tag image, so
the portal keeps running the previous release.

## AI Campaign authoring (MCP)

Campaign authoring is exposed to AI agents as a **local stdio MCP server**
(ADR-0005): draft-only tools for create/update/delete/read, image ingest, and
test-send. No tool schedules or sends — that stays human, in the panel.

- **Locally**, the checked-in `.mcp.json` starts it for Claude Code
  automatically (`php artisan mcp:start campaigns`).
- **In production** there is no HTTP endpoint; Claude Code connects through the
  operator's SSH access, which is the authentication boundary:

    ```json
    {
        "command": "ssh",
        "args": [
            "solamnia",
            "docker",
            "compose",
            "-f",
            "/opt/stacks/solamnia-portal/compose.yaml",
            "exec",
            "-T",
            "app",
            "php",
            "artisan",
            "mcp:start",
            "campaigns"
        ]
    }
    ```

`ingest_image` writes to the `public` disk under `campaigns/` (the named
storage volume in production), so ingested images survive container restarts
exactly like panel uploads.

The break-glass Admin login (local password, Fortify, with 2FA and passkeys)
is deliberately independent of all of the above: it lives at `/backup/login`
and must keep working when Authelia does not. `/login` is SSO-only, and the
Filament panel has no login page of its own — an SSO session reaches it,
subject to `is_admin`.
