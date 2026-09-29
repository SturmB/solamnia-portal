---
paths:
  - '**/*.php'
---

# PHP

## Rector enforces these, so let it fix them instead of hand-replicating
`composer rector:check` runs in `composer test`, the pre-push hook, and the `linter` workflow. Run `composer rector` then `composer lint` (Rector first; repeat until `rector:check` is clean, since one pass can expose work for the next). Rector owns: return types on closures and arrow functions (`function (): void`, `fn (): array`), single-return closures becoming arrow functions, no inline FQCNs (names are imported, unused imports removed), no redundant `new` parentheses (`new Foo($x)->bar()`), and `to_route()` over `redirect()->route()`. Deliberately NOT enforced (skipped in `rector.php`, do not apply by hand either): `declare(strict_types=1)`, renaming `$e` in catch blocks, `resolve()` over `app()`, `dispatch(new Job)` over `Job::dispatch()`, the `Date` facade over `Carbon`, `instanceof` in place of null checks, and `#[Override]` on properties.
