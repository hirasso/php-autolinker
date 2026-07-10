# Plan: collapse the API to a single static method

## Goal

Replace the fluent API and the `autolink()` helper with one static entry point:

```php
/** @return ($source is string ? string : null) */
public static function link(
    string|HTMLDocument $source,
    bool $urls = true,
    bool $emails = true,
    bool $stripScheme = true,
    int $truncateText = 50,
    ?callable $postProcess = null,
): ?string
```

- `string` in → linked HTML string out.
- `HTMLDocument` in → mutated **by reference**, returns `null`. The caller keeps
  its own document handle; there is deliberately no other way to read the result
  back out of the document path.
- The conditional `@return` annotation gives a known-string caller a
  non-nullable `string` and a known-document caller `null`, so PHPStan never
  forces a spurious null check.

## Decisions (from the grilling session)

| Branch | Decision |
|---|---|
| Where options live | Named variadic args on `link()` (no wrapper object, no config reuse). |
| Return for document input | `null` (document mutated by reference). |
| Return typing | Native `?string` + conditional `@return` for PHPStan. |
| `autolink()` helper | **Removed.** |
| `Options` class | **Deleted.** Defaults live only in `link()`. |
| `Processor` shape | Takes the 5 values as promoted constructor props. |
| Release | Restart at `0.0.0`, changeset "Initial Release" (patch). Safe: `v0.1.1` was never pushed/published. |

## Changes

### `src/Autolinker.php`
- Remove the fluent methods (`urls/emails/stripScheme/truncateText/postProcess/process`),
  the `createFromString` / `createFromDocument` factories, `__toString`, and the
  `Stringable` interface.
- Keep the private full-document guard (`parseSource`); it runs on the string
  branch only, matching today's behaviour.
- Add `public static function link(...)`: build a `Processor` from the args
  (doing the `Closure::fromCallable` wrap for `postProcess`), run it, and return
  the linked string or `null`.
- No public constructor / instantiation path remains (pure static utility).

### `src/Processor.php`
- Constructor takes the 5 values as promoted, readonly props instead of `Options`.
- Replace every `$this->options->x` with `$this->x`.

### Deletions
- `src/Options.php`
- `src/helpers.php` (and its entry in composer `autoload.files`).

### `composer.json`
- Drop the `autoload.files` block that loaded `src/helpers.php`.

### Tests (`tests/AutolinkerTest.php`, `tests/Pest.php`)
- Rewrite the `render()` / `renderWith()` helpers on top of `Autolinker::link()`.
- Replace the four fluent-API tests with the equivalent named-arg calls.
- Add a test asserting the `HTMLDocument` input path mutates by reference and
  returns `null`.

### Docs
- Rewrite `README.md` to show only `Autolinker::link()`.
- Update `AGENTS.md` (mirrored to `CLAUDE.md`) wording: "fluent API" → static method.

### Versioning
- Set `package.json` version to `0.0.0`.
- Reset `CHANGELOG.md` to a clean header.
- Remove the superseded `rename-to-autolinker` changeset; add an "Initial
  Release" **patch** changeset.

## Verification
- `composer test`
- `composer analyse`
- `composer format`
