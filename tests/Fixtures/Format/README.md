# Sectional Formatter Fixtures

This directory contains `.test` fixtures consumed by `tests/Unit/Formatter/SectionalFixtureFormatterTest.php`.
Each fixture is a single, self-contained behavioural case for `XliffFormatter`. The format is inspired by
PHP-CS-Fixer integration tests: one case per file, diff-friendly, **no PHP to write**.

## Format

A fixture is composed of named sections. Each section is introduced by a marker line `--NAME--` at the
beginning of a line. The content of a section spans from the byte right after the marker line's newline
until the byte right before the next marker line (or end-of-file).

### Sections

| Section      | Required | Content                                                                                  |
|--------------|----------|------------------------------------------------------------------------------------------|
| `--TEST--`   | yes      | Plain-text human description of the case.                                                |
| `--CONFIG--` | no       | JSON object mapping formatter options. Omit to use defaults.                             |
| `--INPUT--`  | yes      | Raw XML fed to the formatter via `DOMDocument::loadXML()`.                               |
| `--EXPECT--` | yes      | Byte-exact expected output after formatting and serialisation.                           |

### Configuration keys

The JSON object in `--CONFIG--` maps directly to `XliffFormatter` constructor parameters:

| Key                | Type           | Default              | Notes                                                             |
|--------------------|----------------|----------------------|-------------------------------------------------------------------|
| `indent`           | string         | `"tab"`              | One of: `"tab"`, `"2-spaces"`, `"4-spaces"`, `"none"`.            |
| `newlineAfterTag`  | bool           | `true`               | When `false`, output is collapsed to a single line.               |
| `blankLineBetween` | array<string>  | `["group", "unit"]`  | Local element names between which a blank line is inserted.      |

### Example

```
--TEST--
Two-space indentation with default newline-after-tag rules.
--CONFIG--
{"indent": "2-spaces"}
--INPUT--
<?xml version="1.0" encoding="UTF-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0" srcLang="en"><file id="m"><unit id="u"><source>Hi</source></unit></file></xliff>
--EXPECT--
<?xml version="1.0" encoding="UTF-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0" srcLang="en">
  <file id="m">
    <unit id="u">
      <source>Hi</source>
    </unit>
  </file>
</xliff>
```

## Adding a fixture

1. Create a new file `tests/Fixtures/Format/<your-case>.test`.
2. Fill in the four sections. Indentation and trailing newlines are preserved byte-for-byte in
   `--INPUT--` and `--EXPECT--`, so type them as you want them compared.
3. Run the test suite — the runner discovers fixtures automatically. No PHP to write.

```sh
docker run --rm -v "$PWD":/app -w /app php:8.4-cli vendor/bin/phpunit tests/Unit/Formatter/SectionalFixtureFormatterTest.php
```

A malformed fixture (missing required section, invalid JSON in `--CONFIG--`, unknown `indent` value)
fails the test loudly with an explicit message; never silently.
