---
paths:
  - 'app/Models/*.php'
---

# Models

## Call #[Scope] scopes via static::query() from inside the model
Attribute scopes are declared `protected function pending(Builder $query): void`. From outside the class `Invite::pending()` works only because the protected method is invisible and the call falls through to `__callStatic`. Inside the class, `self::pending()` hits the real method with no arguments and fails ("too few arguments"). Use `static::query()->pending()` inside model methods (see `Invite::accept()`).
