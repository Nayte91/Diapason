<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Formatter;

use Diapason\Formatter\IndentStyle;
use Diapason\Formatter\XliffFormatter;
use DOMDocument;
use DOMElement;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SectionalFixtureFormatterTest extends TestCase
{
    private const string FIXTURES_DIR = __DIR__ . '/../../Fixtures/Format';

    private const array REQUIRED_SECTIONS = ['TEST', 'INPUT', 'EXPECT'];

    private const array KNOWN_SECTIONS = ['TEST', 'CONFIG', 'INPUT', 'EXPECT'];

    private const array ALLOWED_CONFIG_KEYS = ['indent', 'newlineAfterTag', 'blankLineBetween'];

    /**
     * @param array<string, mixed> $config
     */
    #[Test]
    #[DataProvider('provideFixtures')]
    public function it_formats_per_fixture(string $description, array $config, string $input, string $expected): void
    {
        self::assertNotSame('', $description, 'Fixture description must not be empty.');

        $formatter = $this->buildFormatter($config);

        $doc = new DOMDocument();
        $loaded = $doc->loadXML($input, LIBXML_NOBLANKS);
        self::assertTrue($loaded, 'Fixture INPUT must be valid XML.');

        $formatter->format($doc);

        self::assertSame($expected, $this->serialize($doc));
    }

    /**
     * @return Generator<string, array{string, array<string, mixed>, string, string}>
     */
    public static function provideFixtures(): Generator
    {
        $paths = glob(self::FIXTURES_DIR . '/*.test');
        if ($paths === false || $paths === []) {
            throw new RuntimeException(sprintf('No fixtures discovered under "%s".', self::FIXTURES_DIR));
        }

        sort($paths);

        foreach ($paths as $path) {
            $name = basename($path, '.test');
            $parsed = self::parseFixture($path);

            yield $name => [$parsed['description'], $parsed['config'], $parsed['input'], $parsed['expected']];
        }
    }

    /**
     * @return array{description: string, config: array<string, mixed>, input: string, expected: string}
     */
    private static function parseFixture(string $path): array
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read fixture "%s".', $path));
        }

        $sections = self::splitSections($contents, $path);

        foreach (self::REQUIRED_SECTIONS as $required) {
            if (!array_key_exists($required, $sections)) {
                throw new RuntimeException(sprintf('Fixture "%s" is missing required section --%s--.', $path, $required));
            }
        }

        $config = [];
        if (array_key_exists('CONFIG', $sections)) {
            $config = self::decodeConfig($sections['CONFIG'], $path);
        }

        return [
            'description' => trim($sections['TEST']),
            'config' => $config,
            'input' => $sections['INPUT'],
            'expected' => $sections['EXPECT'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function splitSections(string $contents, string $path): array
    {
        if (!str_starts_with($contents, '--')) {
            throw new RuntimeException(sprintf('Fixture "%s" must start with a section marker.', $path));
        }

        $pattern = '/^--([A-Z]+)--\R/m';
        $matches = [];
        if (preg_match_all($pattern, $contents, $matches, PREG_OFFSET_CAPTURE) === false) {
            throw new RuntimeException(sprintf('Failed to scan sections in fixture "%s".', $path));
        }

        if ($matches[0] === []) {
            throw new RuntimeException(sprintf('Fixture "%s" contains no section markers.', $path));
        }

        $sections = [];
        $totalLength = strlen($contents);
        $headers = $matches[0];
        $names = $matches[1];
        $count = count($headers);

        for ($i = 0; $i < $count; $i++) {
            $header = $headers[$i];
            $name = $names[$i][0];
            if (!in_array($name, self::KNOWN_SECTIONS, true)) {
                throw new RuntimeException(sprintf('Fixture "%s" contains unknown section --%s--.', $path, $name));
            }

            $contentStart = $header[1] + strlen($header[0]);
            $contentEnd = $i + 1 < $count ? $headers[$i + 1][1] : $totalLength;
            $sections[$name] = substr($contents, $contentStart, $contentEnd - $contentStart);
        }

        return $sections;
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeConfig(string $raw, string $path): array
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return [];
        }

        try {
            $decoded = json_decode($trimmed, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException(sprintf('Fixture "%s" has invalid JSON in --CONFIG--: %s', $path, $e->getMessage()), $e->getCode(), previous: $e);
        }

        if (!is_array($decoded)) {
            throw new RuntimeException(sprintf('Fixture "%s" --CONFIG-- must decode to a JSON object.', $path));
        }

        foreach (array_keys($decoded) as $key) {
            if (!in_array($key, self::ALLOWED_CONFIG_KEYS, true)) {
                throw new RuntimeException(sprintf('Fixture "%s" --CONFIG-- contains unknown key "%s".', $path, $key));
            }
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function buildFormatter(array $config): XliffFormatter
    {
        $indent = IndentStyle::Tab;
        if (array_key_exists('indent', $config)) {
            $value = $config['indent'];
            if (!is_string($value)) {
                throw new RuntimeException('Fixture --CONFIG-- "indent" must be a string.');
            }
            $resolved = IndentStyle::tryFrom($value);
            if ($resolved === null) {
                throw new RuntimeException(sprintf('Fixture --CONFIG-- "indent" has unknown value "%s".', $value));
            }
            $indent = $resolved;
        }

        $newlineAfterTag = true;
        if (array_key_exists('newlineAfterTag', $config)) {
            $value = $config['newlineAfterTag'];
            if (!is_bool($value)) {
                throw new RuntimeException('Fixture --CONFIG-- "newlineAfterTag" must be a boolean.');
            }
            $newlineAfterTag = $value;
        }

        $blankLineBetween = ['group', 'unit'];
        if (array_key_exists('blankLineBetween', $config)) {
            $value = $config['blankLineBetween'];
            if (!is_array($value)) {
                throw new RuntimeException('Fixture --CONFIG-- "blankLineBetween" must be an array of strings.');
            }
            $normalised = [];
            foreach ($value as $entry) {
                if (!is_string($entry)) {
                    throw new RuntimeException('Fixture --CONFIG-- "blankLineBetween" must contain only strings.');
                }
                $normalised[] = $entry;
            }
            $blankLineBetween = $normalised;
        }

        return new XliffFormatter(
            indent: $indent,
            newlineAfterTag: $newlineAfterTag,
            blankLineBetween: $blankLineBetween,
        );
    }

    private function serialize(DOMDocument $doc): string
    {
        $root = $doc->documentElement;
        if (!$root instanceof DOMElement) {
            return '';
        }

        $body = $doc->saveXML($root);
        if ($body === false) {
            return '';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $body . "\n";
    }
}
