# Diapason

[![Packagist](https://img.shields.io/packagist/v/nayte91/diapason.svg)](https://packagist.org/packages/nayte91/diapason)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%3E%3D8.4-777bb4.svg)](composer.json)
[![CI](https://github.com/Nayte91/Diapason/actions/workflows/ci.yml/badge.svg)](https://github.com/Nayte91/Diapason/actions/workflows/ci.yml)

> Validate and format XLIFF 2.0/2.1 translation files in Symfony projects, keeping every locale **au diapason** with the others.

Diapason is a single-binary CLI that checks the structural consistency of your XLIFF translation files across locales — same groups, same units in same order, same `<source>` per unit ID, `state="final"` everywhere it matters — and reformats every file to a canonical, diff-friendly layout.

## Why Diapason?

Symfony's built-in `lint:xliff` validates each file against the schema, but says nothing about cross-locale coherence: nothing stops `messages.fr.xlf` from drifting out of sync with `messages.en.xlf` (missing keys, reordered groups, mismatched `<source>` text). Diapason fills that gap, and along the way enforces a single canonical formatting so review diffs stay readable.

## Scope & compatibility

Diapason is **deliberately narrow** — it does one thing well rather than trying to be a universal i18n toolbox.

| Aspect | What's supported |
|---|---|
| **Format** | XLIFF **2.0** and **2.1** only. XLIFF 1.2 is rejected with a `xliff.namespace` error. |
| **File extension** | `.xlf` only (the Symfony convention). `.xliff` files are not picked up. |
| **Filename convention** | Symfony's `<domain>.<locale>.xlf` pattern, where `<domain>` is everything up to the last dot (so `messages+intl-icu.fr.xlf` correctly resolves to domain `messages+intl-icu`, locale `fr`). |
| **Project layout** | Designed for Symfony's standard `translations/` directory but works on any layout — point it at any glob via `withPaths()`. |
| **Other formats** | YAML, PO, JSON, CSV translation files are out of scope and won't be touched. |

If your project uses XLIFF 1.2, YAML, or anything else, Diapason is not for you (yet). For YAML coherence checks across locales, see [`tgalopin/symfony-translations-checker`](https://github.com/tgalopin/symfony-translations-checker).

## Installation

```bash
composer require --dev nayte91/diapason
```

## Quickstart (zero config)

Diapason exposes two verbs:

| Verb | Mutates files? | Use case |
|---|---|---|
| `format` | Yes (default) | Apply canonical formatting and run checks. |
| `check` | No | Run checks only — never writes anything (CI-friendly read-only mode). |

Calling `diapason` without a verb is an alias for `diapason format`.

```bash
# Reformat + check (the common case)
vendor/bin/diapason

# Equivalent, explicit
vendor/bin/diapason format

# Read-only: run checks without touching any file
vendor/bin/diapason check

# Preview what `format` would change as a unified diff
vendor/bin/diapason format --dry-run
```

Without a config file, Diapason scans `translations/*.xlf` in the current working directory and runs every check.

To target a single Symfony translation domain, pass its prefix (the path up to and including the domain name, **without** the locale and `.xlf` extension):

```bash
vendor/bin/diapason translations/messages+intl-icu
vendor/bin/diapason check translations/messages+intl-icu
```

The first globs `translations/messages+intl-icu.*.xlf`, formats and checks every matched locale. The second runs the same cross-locale checks read-only.

## Configuration

Drop a `diapason.php` at the root of your project to override any default:

```php
<?php
use Diapason\Config\DiapasonConfig;

return DiapasonConfig::configure()
    ->withPaths('translations/*.xlf', 'translations/admin/*.xlf');
```

Diapason auto-discovers `diapason.php`, `diapason.dist.php`, `.diapason.php`, or `.diapason.dist.php` in the current directory (in that order). Pass `--config=path/to/file.php` to override.

📖 See [CONFIG.md](CONFIG.md) for the full reference: builder methods, formatter axes (indent style, blank lines, newline behavior), check classes, output reporters.

## CLI options

Both `format` and `check` accept the same shared options:

| Flag | Description |
|---|---|
| `--config`, `-c` | Path to a Diapason config file. |
| `--format`, `-f` | Output format: `table` (default) or `json`. |

Additional options on `format` only:

| Flag | Description |
|---|---|
| `--dry-run` | Don't write any files. Outputs a unified diff per file (`--- before / +++ after`) and a `+N / -N lines` summary. With `--format=json`, the diffs appear under the top-level `previews` key instead. |

Exit codes:

| Code | Meaning |
|---|---|
| `0` | All checks passed. |
| `1` | One or more checks reported violations. |
| `2` | Input or config error (missing files, invalid config). |

## Checks shipped in v1

Eight checks ship out of the box: 4 per-file (XML well-formedness, XLIFF namespace, `srcLang`, duplicate IDs) and 4 cross-locale (group consistency, unit consistency, source consistency, `state="final"`). See [CONFIG.md § Checks](CONFIG.md#4-checks) for the full list with class names and `checkId`s.

## Roadmap (V2 candidates)

These are intentionally **out of scope for v1** but the architecture is built to accept them:

- **Symfony locales coverage**: cross-check the number of locale files per domain against `framework.enabled_locales`.
- **ICU MessageFormat placeholder consistency**: same placeholders / plural categories across locales.
- **ID / key coherence**: enforce naming conventions on unit IDs (e.g. dot-segmented prefixes matching the group ID).
- **Empty translation detection**: flag `<target>` elements that are empty or equal to the source text.
- **XLIFF 1.2 support**: parse and check the older namespace alongside 2.x.

## Contributing

```bash
composer install
composer ci   # phpstan + cs-fixer + phpunit
```

To add a new check, implement either `Diapason\Check\PerFileCheckInterface` or `Diapason\Check\CrossLocaleCheckInterface` and register it via `DiapasonConfig::withChecks()`.

## Credits

Architectural inspiration from [`tgalopin/symfony-translations-checker`](https://github.com/tgalopin/symfony-translations-checker) — same shape (parser/check seams, project DTO, single CLI command), different scope (XLIFF rather than YAML, with formatting and cross-locale structural checks).

## License

[MIT](LICENSE).
