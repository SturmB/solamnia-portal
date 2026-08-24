# AI authoring is draft-only, over a local stdio MCP server

Campaign authoring is exposed to AI agents (Claude Code) as an MCP server
(`laravel/mcp`), registered as a **local stdio server** started with
`php artisan mcp:start campaigns`. The tools cover create, update, delete,
read, image ingest, and test-send — and **no tool reads or writes
`scheduled_at`**. Scheduling *is* sending (ADR-0003: "send now" = schedule for
now), and the panel deliberately gates it behind a human confirmation showing
the live recipient count. The AI composes; only a human pulls the trigger.

Transport is **stdio only — no HTTP endpoint, no OAuth, no tunnel route**. In
production, Claude Code reaches the server over the operator's existing SSH
access (`docker compose exec app php artisan mcp:start campaigns`); SSH is the
authentication boundary. This adds zero exposed surface and zero standing
infrastructure, consistent with ADR-0003's no-new-daemons stance.

All tools go through the app — Eloquent, the shared validation rules
(`Campaign::rules()`, also consumed by the panel form), the `public` storage
disk, and the existing `CampaignMail` MJML pipeline — never around it.

## Consequences

- An AI-authored Campaign is indistinguishable from a GUI-authored one; the
  panel remains the single place Campaigns are scheduled or sent.
- Sent Campaigns stay immutable from every surface: the MCP update and delete
  tools refuse them, mirroring `CampaignPolicy` in the panel.
- The MCP server ships in the production image, so `laravel/mcp` is a direct
  `require` dependency (it must survive `--no-dev`).
- If a "schedule via agent" need ever materializes, it must be a deliberate
  new decision superseding this one — not a new tool slipped into this server.
