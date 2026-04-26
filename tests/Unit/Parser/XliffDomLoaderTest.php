<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Parser;

use Diapason\Exception\XliffLoadException;
use Diapason\Parser\XliffDomLoader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class XliffDomLoaderTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../../Fixtures';

    #[Test]
    public function loadsValidXmlFile(): void
    {
        $doc = new XliffDomLoader()->load(self::FIXTURES . '/valid-2locales/messages.en.xlf');

        self::assertNotNull($doc->documentElement);
        self::assertSame('xliff', $doc->documentElement->localName);
        self::assertSame('urn:oasis:names:tc:xliff:document:2.0', $doc->documentElement->namespaceURI);
    }

    #[Test]
    public function throwsSyntaxErrorOnMalformedXml(): void
    {
        $loader = new XliffDomLoader();
        $path = self::FIXTURES . '/malformed-xml/messages.fr.xlf';

        try {
            $loader->load($path);
            self::fail('Expected XliffLoadException');
        } catch (XliffLoadException $e) {
            self::assertSame('xml.well-formed', $e->kind);
            self::assertSame($path, $e->path);
            self::assertStringContainsString('XML syntax error', $e->getMessage());
        }
    }

    #[Test]
    public function throwsMissingRootElementOnEmptyDocument(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'diapason-empty-');
        self::assertNotFalse($tmp);
        file_put_contents($tmp, '');

        try {
            new XliffDomLoader()->load($tmp);
            self::fail('Expected XliffLoadException');
        } catch (XliffLoadException $e) {
            self::assertSame('xml.well-formed', $e->kind);
            self::assertSame($tmp, $e->path);
        } finally {
            @unlink($tmp);
        }
    }
}
