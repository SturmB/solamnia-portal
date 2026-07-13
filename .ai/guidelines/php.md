## PHP language level

The app targets PHP 8.5 (see the foundation rules for the installed version).
Write modern PHP — prefer current-generation syntax over older idioms when it
genuinely improves clarity or safety, rather than defaulting to how it was
written five versions ago.

- **Use 8.5 features where they read better** — e.g. the pipe operator (`|>`)
  for transformation chains, `#[\NoDiscard]` on functions whose return must not
  be silently ignored, and `array_first()` / `array_last()`. Reach for them to
  clarify intent, not for novelty: boring beats clever when a new operator would
  cost a re-read at 3am.
- **Mind the floor.** Relying on an 8.5-only feature commits the app to that
  minimum. `composer.json`'s `require.php` and the CI test matrix must agree —
  if you adopt 8.5-only syntax, bump `require.php` to `^8.5` and drop older PHP
  legs from the test matrix **in the same change**, so the constraint and the
  versions actually tested never drift apart.
