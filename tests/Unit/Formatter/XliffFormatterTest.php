<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Formatter;

use Diapason\Formatter\IndentStyle;
use Diapason\Formatter\XliffFormatter;
use DOMDocument;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class XliffFormatterTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../../Fixtures/unformatted';

    #[Test]
    public function formatsArbitraryIndentationToCanonicalForm(): void
    {
        $doc = new DOMDocument();
        $doc->load(self::FIXTURES . '/messages.before.xlf', LIBXML_NOBLANKS);
        new XliffFormatter()->format($doc);

        $serialized = $this->serialize($doc);
        $expected = (string) file_get_contents(self::FIXTURES . '/messages.after.xlf');

        self::assertSame($expected, $serialized);
    }

    #[Test]
    public function formattingIsIdempotent(): void
    {
        $doc = new DOMDocument();
        $doc->load(self::FIXTURES . '/messages.after.xlf', LIBXML_NOBLANKS);
        new XliffFormatter()->format($doc);

        $serialized = $this->serialize($doc);
        $expected = (string) file_get_contents(self::FIXTURES . '/messages.after.xlf');

        self::assertSame($expected, $serialized);
    }

    #[Test]
    public function leavesWithoutDomElementChildIsHandledGracefully(): void
    {
        $doc = new DOMDocument();

        new XliffFormatter()->format($doc);

        self::assertNull($doc->documentElement);
    }

    #[Test]
    public function formatsWithFourSpacesIndent(): void
    {
        $serialized = $this->formatBefore(
            XliffFormatter::configure()->withIndent(IndentStyle::FourSpaces),
        );

        self::assertStringContainsString("\n    <file", $serialized);
        self::assertStringContainsString("\n        <group", $serialized);
        self::assertStringContainsString("\n            <unit", $serialized);
        self::assertStringNotContainsString("\t", $serialized);
    }

    #[Test]
    public function formatsWithTwoSpacesIndent(): void
    {
        $serialized = $this->formatBefore(
            XliffFormatter::configure()->withIndent(IndentStyle::TwoSpaces),
        );

        self::assertStringContainsString("\n  <file", $serialized);
        self::assertStringContainsString("\n    <group", $serialized);
        self::assertStringContainsString("\n      <unit", $serialized);
        self::assertStringNotContainsString("\t", $serialized);
    }

    #[Test]
    public function formatsWithNoIndentKeepsNewlines(): void
    {
        $serialized = $this->formatBefore(
            XliffFormatter::configure()->withIndent(IndentStyle::None),
        );

        self::assertStringContainsString("\n<file", $serialized);
        self::assertStringContainsString("\n<group", $serialized);
        self::assertStringContainsString("\n<unit", $serialized);
        self::assertStringNotContainsString("\t", $serialized);
        self::assertStringNotContainsString("  <", $serialized);
    }

    #[Test]
    public function formatsCompactWhenNewlineAfterTagDisabled(): void
    {
        $serialized = $this->formatBefore(
            XliffFormatter::configure()->withNewlineAfterTag(false),
        );

        self::assertStringNotContainsString("\n\t", $serialized);
        self::assertStringNotContainsString("\n  ", $serialized);
        self::assertStringContainsString('<unit', $serialized);
        self::assertStringContainsString('</xliff>', $serialized);
    }

    #[Test]
    public function formatsBlankLineBetweenCustomElements(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0" srcLang="en">
                <file id="a"><unit id="x"><segment><source>X</source></segment></unit></file>
                <file id="b"><unit id="y"><segment><source>Y</source></segment></unit></file>
            </xliff>
            XML, LIBXML_NOBLANKS);

        XliffFormatter::configure()
            ->withBlankLineBetween(['file', 'group', 'unit'])
            ->format($doc);

        $serialized = $this->serialize($doc);

        self::assertStringContainsString("</file>\n\n\t<file", $serialized);
    }

    #[Test]
    public function formatsWithoutBlankLinesWhenListEmpty(): void
    {
        $serialized = $this->formatBefore(
            XliffFormatter::configure()->withBlankLineBetween([]),
        );

        self::assertStringNotContainsString("\n\n", $serialized);
    }

    private function formatBefore(XliffFormatter $formatter): string
    {
        $doc = new DOMDocument();
        $doc->load(self::FIXTURES . '/messages.before.xlf', LIBXML_NOBLANKS);
        $formatter->format($doc);

        return $this->serialize($doc);
    }

    private function serialize(DOMDocument $doc): string
    {
        $root = $doc->documentElement;
        if (!$root instanceof \DOMElement) {
            return '';
        }

        $body = $doc->saveXML($root);
        if ($body === false) {
            return '';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $body . "\n";
    }
}
