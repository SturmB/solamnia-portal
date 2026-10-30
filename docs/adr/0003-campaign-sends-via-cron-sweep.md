---
status: accepted
---

# Campaign sends run inline via a per-minute scheduler sweep

## Amendment (2026-10-30)

The **trigger** and the **"no persistent processes"** stance below no longer
match production. The rest of the decision stands.

- **What changed.** No host crontab is used. The canonical Compose stack runs a
  `scheduler` service (the app image with its entrypoint swapped to
  `php artisan schedule:work`) that fires `campaigns:send-due` every minute. A
  separate `worker` service runs `php artisan queue:work`, but only for the
  media-server share job (dispatched after an Invite is accepted, or by the
  Admin's retry action).
- **Why.** The portal is deployed as a Docker (Dockge) stack, so a host crontab
  would have to `docker exec` into it, coupling the host to the stack's
  internals and hiding a moving part outside `compose.yaml`. Sibling services
  keep the stack self-contained, and they cost almost nothing: measured on
  2026-10-30, `scheduler` used ~6 MiB and `worker` ~49 MiB, both at ~0% CPU
  when idle.
- **Unchanged.** Campaigns are still sent **inline** by `campaigns:send-due`,
  never through the queue. "Send now" is still scheduling for now (one code
  path), there is still no per-recipient retry, and a Pushover still fires on
  every send. "Add a queue worker then" below still holds for Campaign sends:
  the existing `worker` doesn't change it.

The original title was "Campaign sends run via a cron sweep, with no persistent
workers". Read "cron" below as "the `scheduler` service".

## Original decision

All Campaign sends — immediate and scheduled — go through a single per-minute,
cron-driven sweep: a host crontab line runs `php artisan schedule:run`, which
runs a `campaigns:send-due` command that sends any due Campaign **inline**.
"Send now" simply sets `scheduled_at = now`, so immediate and scheduled sends
share one code path.

This deliberately uses **no queue worker and no `schedule:work` daemon** — the
portal adds **zero always-on background processes** to the homelab (one crontab
line, ~100 ms/minute). Chosen because the homelab already runs ~37 containers
and the newsletter is quarterly and tiny (~13 recipients); a queue worker plus
immediate send would be more standing infrastructure for no benefit at this
scale.

## Consequences

- "Send now" fires within ≤60 s rather than instantly — irrelevant for a
  quarterly email.
- No per-recipient send **retry**. If volume ever warrants it, add a queue
  worker then — do not pre-build it.
- A Pushover fires on every send (success and failure) so a stopped cron is
  immediately noticeable.
- There is no distinct "Send now" affordance: the panel's schedule action
  defaults its send time to now, so "send now" is just scheduling for now.
  Immediate and scheduled sends stay on one path rather than forking the UI.
