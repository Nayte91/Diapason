<?php

declare(strict_types=1);

namespace Diapason\Tests\Integration;

use Diapason\Formatter\XliffFormatter;
use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FormatterIdempotenceTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures/valid-2locales';

    #[Test]
    #[DataProvider('provideCanonicalFixtureFiles')]
    public function reformattingCanonicalFixtureProducesIdenticalBytes(string $relativePath): void
    {
        $path = self::FIXTURES . '/' . $relativePath;
        $original = (string) file_get_contents($path);

        $doc = new DOMDocument();
        $doc->load($path, LIBXML_NOBLANKS);
        new XliffFormatter()->format($doc);

        $body = $doc->saveXML($doc->documentElement);
        $reformatted = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $body . "\n";

        self::assertSame($original, $reformatted);
    }

    /** @return iterable<string, array{string}> */
    public static function provideCanonicalFixtureFiles(): iterable
    {
        yield 'source english' => ['messages.en.xlf'];
        yield 'target french'  => ['messages.fr.xlf'];
    }
}
