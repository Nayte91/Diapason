# Configuration

Diapason is configured via a `diapason.php` file at the root of your project. The file returns a `DiapasonConfig` instance built with a fluent immutable API.

```php
<?php
declare(strict_types=1);

use Diapason\Config\DiapasonConfig;

return DiapasonConfig::configure()
    ->withPaths('translations/*.xlf');
```

If no config file exists, Diapason falls back to `DiapasonConfig::defaults()` — sane defaults for a standard Symfony project.

## Table of contents

1. [Discovery & file location](#1-discovery--file-location)
2. [The `DiapasonConfig` builder](#2-the-diapasonconfig-builder)
3. [Formatter configuration](#3-formatter-configuration)
4. [Checks](#4-checks)
5. [Output formats](#5-output-formats)

---

## 1. Discovery & file location

When invoked without `--config`, Diapason looks for a config file in the current working directory in the following order. The first match wins.

| Order | File name | Purpose |
|---|---|---|
| 1 | `diapason.php` | Local override (gitignored by convention) |
| 2 | `diapason.dist.php` | Shared, committed config |
| 3 | `.diapason.php` | Unix dot-file local override |
| 4 | `.diapason.dist.php` | Unix dot-file shared config |

If none of those exist, `DiapasonConfig::defaults()` is used.

You can always pass an explicit path:

```bash
vendor/bin/diapason --config=tools/diapason.php
```

The explicit path bypasses the auto-discovery list entirely. If the file does not exist, Diapason exits with code `2` and a `Config error: …` message.

The file **must** `return` a `DiapasonConfig` instance. Returning anything else raises a `ConfigException` and exits with code `2`.

---

## 2. The `DiapasonConfig` builder

`DiapasonConfig` is an immutable fluent builder. Every `with*()` call returns a new instance — your config file builds the chain and returns the result.

| Method | Purpose | Default |
|---|---|---|
| `DiapasonConfig::configure()` | Empty starting point | — |
| `DiapasonConfig::defaults()` | Pre-loaded with all checks, the `XliffFormatter`, and `TableReporter` | — |
| `withPaths(string ...$globs)` | File globs to scan when no domain prefix is given | `['translations/*.xlf']` |
| `withChecks(CheckInterface ...$checks)` | Active checks for this run | All 8 v1 checks |
| `withFormatter(?FormatterInterface $formatter)` | Formatter instance, or `null` to disable | `new XliffFormatter()` |
| `withFormatMode(FormatMode $mode)` | `Apply` (write) / `DryRun` (diff) / `Disabled` | `Apply` (overridden by CLI verb) |
| `withReporter(ReporterInterface $reporter)` | How results are rendered | `new TableReporter()` |

### Example: scan two folders, custom checks subset

```php
use Diapason\Config\DiapasonConfig;
use Diapason\Check\{GroupsConsistencyCheck, UnitsConsistencyCheck, SourcesConsistencyCheck};

return DiapasonConfig::configure()
    ->withPaths('translations/*.xlf', 'translations/admin/*.xlf')
    ->withChecks(
        new GroupsConsistencyCheck(),
        new UnitsConsistencyCheck(),
        new SourcesConsistencyCheck(),
    );
```

> Note: `withFormatMode()` is normally driven by the CLI verb (`format` → `Apply`, `format --dry-run` → `DryRun`, `check` → `Disabled`). You rarely need to set it manually in `diapason.php`.

---

## 3. Formatter configuration

The formatter rewrites your XLIFF files to a canonical layout when running `diapason format`. Three axes are independently configurable.

```php
use Diapason\Config\DiapasonConfig;
use Diapason\Formatter\{IndentStyle, XliffFormatter};

return DiapasonConfig::configure()
    ->withFormatter(
        XliffFormatter::configure()
            ->withIndent(IndentStyle::Tab)
            ->withNewlineAfterTag(true)
            ->withBlankLineBetween(['group', 'unit'])
    );
```

### `withIndent(IndentStyle $style)`

Controls the indentation unit applied per nesting level.

| Case | Effect |
|---|---|
| `IndentStyle::Tab` (default) | One tab per nesting level |
| `IndentStyle::TwoSpaces` | Two spaces per level |
| `IndentStyle::FourSpaces` | Four spaces per level |
| `IndentStyle::None` | No indent — every line starts at column 0 |

`IndentStyle::None` keeps line breaks between tags but eliminates the per-depth indentation. Combined with `withNewlineAfterTag(false)`, it yields a single-line compact file.

### `withNewlineAfterTag(bool $enabled)`

When `true` (default), every closing tag is followed by a newline + indent before the next sibling.

When `false`, adjacent tags can stay on the same line (`</foo><bar>`). Useful for compact output. Implicitly disables blank lines between siblings (the formatter has no text-node tail to mutate).

### `withBlankLineBetween(array $localNames)`

List of element local names that should have a blank line inserted between consecutive same-name siblings. Default `['group', 'unit']` — the canonical Symfony XLIFF style.

```php
// Add blank lines between <file> siblings too
->withBlankLineBetween(['file', 'group', 'unit'])

// No blank lines anywhere
->withBlankLineBetween([])
```

### Disabling the formatter entirely

```php
->withFormatter(null)
```

`diapason format` then becomes equivalent to `diapason check`: parse + checks, no writes.

---

## 4. Checks

Checks live under the `Diapason\Check\` namespace and implement either `PerFileCheckInterface` or `CrossLocaleCheckInterface`.

### Per-file checks

These run on each `XliffFile` independently. The XLIFF parser populates structural issues into `$file->issues` at parse time; the generic `IssueIdFilterCheck` filters and surfaces them under stable `checkId`s.

| Configured `id()` | What it surfaces | Filter |
|---|---|---|
| `xml.well-formed` | XML parses without syntax errors | exact `xml.well-formed` |
| `xliff.namespace` | Root is `{urn:oasis:names:tc:xliff:document:2.0}xliff` with `version` 2.0 or 2.1 | exact `xliff.namespace` |
| `xliff.srcLang` | Root carries a non-empty `srcLang` attribute | exact `xliff.srcLang` |
| `xliff.duplicateId` | No two `<group>` or `<unit>` share the same `id` | prefix `xliff.duplicate` |

### Cross-locale checks

These run per **domain bundle** (= one domain across all its locales) and mutate a shared `DomainVerdict`.

| Class | `id()` | What it verifies |
|---|---|---|
| `GroupsConsistencyCheck` | `groups.consistency` | Same set of groups in same order across locales |
| `UnitsConsistencyCheck` | `units.consistency` | Same units in same order within each group |
| `SourcesConsistencyCheck` | `sources.consistency` | The `<source>` text is identical across locales for every unit ID |
| `FinalStateCheck` | `state.final` | Every non-source locale has `state="final"` on all its segments |

### Adding or removing checks

`withChecks()` replaces the active check list entirely:

```php
use Diapason\Check\{
    IssueIdFilterCheck,
    GroupsConsistencyCheck, UnitsConsistencyCheck,
};

return DiapasonConfig::configure()
    ->withChecks(
        new IssueIdFilterCheck('xml.well-formed', 'XLIFF file must be well-formed XML.', 'xml.well-formed'),
        new IssueIdFilterCheck('xliff.srcLang', 'XLIFF root element must declare srcLang attribute.', 'xliff.srcLang'),
        new GroupsConsistencyCheck(),
        new UnitsConsistencyCheck(),
        // FinalStateCheck and SourcesConsistencyCheck deliberately omitted
    );
```

To add your own check, implement `PerFileCheckInterface` or `CrossLocaleCheckInterface` and register it via `withChecks()` alongside the built-ins.

---

## 5. Output formats

Two reporters ship with v1.

### `TableReporter` (default)

Renders a Unicode boxed table to stdout, one row per file plus one verdict row per domain. Glyphs `✓` / `✗` / `-` mark each check column. Failures are followed by a bullet list of details.

Selected via:

```bash
vendor/bin/diapason --format=table   # default, can be omitted
```

### `JsonReporter`

Emits a structured JSON document on stdout. Useful for CI pipelines and editor integrations.

```bash
vendor/bin/diapason --format=json
```

Schema (v1):

```json
{
  "ok": false,
  "domains": {
    "messages": {
      "ok": false,
      "files": [
        { "filename": "messages.en.xlf", "locale": "en", "valid": true,
          "groupsCount": 2, "unitsCount": 3, "isSource": true, "finalOk": null }
      ],
      "verdict": {
        "groupsMatch": true, "groupOrder": true,
        "unitsMatch": true, "unitOrder": true,
        "sourcesMatch": true, "finalOk": false
      },
      "issues": [
        { "severity": "error", "checkId": "state.final", "file": "messages.fr.xlf",
          "message": "1 units with non-final segments", "unitId": null,
          "groupId": null, "line": null }
      ]
    }
  },
  "previews": {
    "/abs/path/translations/messages.en.xlf": {
      "diff": "--- before\n+++ after\n@@ -1,3 +1,3 @@\n…",
      "linesAdded": 4,
      "linesRemoved": 2
    }
  }
}
```

`previews` is non-empty only after `format --dry-run` (it surfaces what each file *would* be rewritten to). For all other invocations it is `{}`.

### Custom reporters

Implement `Diapason\Reporter\ReporterInterface` and register via `withReporter()`. The interface is a single method:

```php
public function report(ProjectReport $report, OutputInterface $output): void;
```
