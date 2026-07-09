# Campaign sends run via a cron sweep, with no persistent workers

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
