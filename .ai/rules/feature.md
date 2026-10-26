---
paths:
  - 'tests/Feature/**'
---

# Feature

## Http::fake stubs match in registration order, so never start with a bare Http::fake()
A bare `Http::fake()` registers a catch-all that answers every URL with an empty 200, and later `Http::fake([...])` stubs are never consulted. In `beforeEach`, fake only the URLs every test shares (e.g. Pushover) and add `Http::preventStrayRequests()` so an unfaked call throws instead of silently returning nothing. Also: `Str` inside a Pest dataset array runs before the app boots, so import `Illuminate\Support\Str` explicitly.

## Squish whitespace before asserting Blade copy
The pre-commit hook runs Prettier (@shufo/prettier-plugin-blade, 120 columns), which re-wraps long sentences in Blade templates at commit time. An exact-text assertion (`assertSee`, `assertSeeInHtml`) on a phrase from a template can pass locally and then fail on the committed file once a line break lands inside the phrase. When asserting copy written in a Blade template, match against `Str::squish($html)`. Copy that comes from PHP data (e.g. a notification's `data['message']`) is not re-wrapped and is safe to match directly.
