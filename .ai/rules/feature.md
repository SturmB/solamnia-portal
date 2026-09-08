---
paths:
  - 'tests/Feature/**'
---

# Feature

## Http::fake stubs match in registration order, so never start with a bare Http::fake()
A bare `Http::fake()` registers a catch-all that answers every URL with an empty 200, and later `Http::fake([...])` stubs are never consulted. In `beforeEach`, fake only the URLs every test shares (e.g. Pushover) and add `Http::preventStrayRequests()` so an unfaked call throws instead of silently returning nothing. Also: `Str` inside a Pest dataset array runs before the app boots, so import `Illuminate\Support\Str` explicitly.
