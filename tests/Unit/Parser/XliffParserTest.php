<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Parser;

use Diapason\Model\Issue;
use Diapason\Parser\DomainResolver;
use Diapason\Parser\XliffDomLoader;
use Diapason\Parser\XliffParser;
use DOMDocument;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class XliffParserTest extends TestCase
{
    private function parser(): XliffParser
    {
        return new XliffParser(new DomainResolver(), new XliffDomLoader());
    }

    private function loadXml(string $xml): DOMDocument
    {
        $doc = new DOMDocument();
        $doc->loadXML($xml);

        return $doc;
    }

    #[Test]
    public function parsesValidSourceFile(): void
    {
        $doc = $this->loadXml(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0" srcLang="en" trgLang="en">
                <file id="messages">
                    <group id="account">
                        <unit id="account.login">
                            <segment><source>Login</source></segment>
                        </unit>
                        <unit id="account.logout">
                            <segment><source>Logout</source></segment>
                        </unit>
                    </group>
                    <group id="navigation">
                        <unit id="nav.home">
                            <segment><source>Home</source></segment>
                        </unit>
                    </group>
                </file>
            </xliff>
            XML);

        $file = $this->parser()->parse($doc, '/virtual/messages.en.xlf');

        self::assertTrue($file->valid);
        self::assertSame('messages', $file->domain);
        self::assertSame('en', $file->locale);
        self::assertSame('en', $file->srcLang);
        self::assertTrue($file->isSource);
        self::assertCount(2, $file->groups);
        self::assertSame('account', $file->groups[0]->id);
        self::assertSame(['account.login', 'account.logout'], $file->groups[0]->unitIds);
        self::assertSame('navigation', $file->groups[1]->id);
        self::assertArrayHasKey('account.login', $file->units);
        self::assertSame('Login', $file->units['account.login']->source);
        self::assertTrue($file->issues->isEmpty());
    }

    #[Test]
    public function parsesValidTranslatedFile(): void
    {
        $doc = $this->loadXml(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0" srcLang="en" trgLang="fr">
                <file id="messages">
                    <group id="account">
                        <unit id="account.login">
                            <segment state="final">
                                <source>Login</source>
                                <target>Connexion</target>
                            </segment>
                        </unit>
                    </group>
                </file>
            </xliff>
            XML);

        $file = $this->parser()->parse($doc, '/virtual/messages.fr.xlf');

        self::assertTrue($file->valid);
        self::assertSame('fr', $file->locale);
        self::assertSame('en', $file->srcLang);
        self::assertFalse($file->isSource);
        self::assertSame('Connexion', $file->units['account.login']->segments[0]->target);
        self::assertSame('final', $file->units['account.login']->segments[0]->state);
    }

    #[Test]
    public function reportsWrongRootNamespaceAsXliffNamespaceIssue(): void
    {
        $doc = $this->loadXml(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2" srcLang="en">
                <file original="x" datatype="plaintext" source-language="en">
                    <body><trans-unit id="x"><source>X</source></trans-unit></body>
                </file>
            </xliff>
            XML);

        $file = $this->parser()->parse($doc, '/virtual/messages.en.xlf');

        self::assertFalse($file->valid);
        self::assertCount(1, $file->issues);
        self::assertSame('xliff.namespace', $file->issues->all()[0]->checkId);
    }

    #[Test]
    public function reportsMissingSrcLangAttribute(): void
    {
        $doc = $this->loadXml(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0">
                <file id="messages">
                    <group id="account">
                        <unit id="account.login">
                            <segment><source>Login</source></segment>
                        </unit>
                    </group>
                </file>
            </xliff>
            XML);

        $file = $this->parser()->parse($doc, '/virtual/messages.en.xlf');

        self::assertFalse($file->valid);
        $checkIds = array_map(static fn(Issue $issue): string => $issue->checkId, $file->issues->all());
        self::assertContains('xliff.srcLang', $checkIds);
    }

    #[Test]
    public function reportsInvalidXliffVersion(): void
    {
        $doc = $this->loadXml(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="3.0" srcLang="en">
                <file id="messages"/>
            </xliff>
            XML);

        $file = $this->parser()->parse($doc, '/virtual/messages.en.xlf');

        self::assertFalse($file->valid);
        $checkIds = array_map(static fn(Issue $issue): string => $issue->checkId, $file->issues->all());
        self::assertContains('xliff.namespace', $checkIds);
    }

    #[Test]
    public function reportsDuplicateUnitIdsAcrossGroups(): void
    {
        $doc = $this->loadXml(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0" srcLang="en" trgLang="en">
                <file id="messages">
                    <group id="account">
                        <unit id="dup"><segment><source>First</source></segment></unit>
                    </group>
                    <group id="navigation">
                        <unit id="dup"><segment><source>Second</source></segment></unit>
                    </group>
                </file>
            </xliff>
            XML);

        $file = $this->parser()->parse($doc, '/virtual/messages.en.xlf');

        self::assertFalse($file->valid);
        $duplicateIssues = $file->issues->forCheckId('xliff.duplicateUnitId')->all();
        self::assertNotEmpty($duplicateIssues);
        self::assertSame('dup', $duplicateIssues[0]->unitId);
    }

    #[Test]
    public function reportsDuplicateGroupIds(): void
    {
        $doc = $this->loadXml(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0" srcLang="en" trgLang="en">
                <file id="messages">
                    <group id="dupgroup">
                        <unit id="a"><segment><source>A</source></segment></unit>
                    </group>
                    <group id="dupgroup">
                        <unit id="b"><segment><source>B</source></segment></unit>
                    </group>
                </file>
            </xliff>
            XML);

        $file = $this->parser()->parse($doc, '/virtual/messages.en.xlf');

        self::assertFalse($file->valid);
        $duplicateIssues = $file->issues->forCheckId('xliff.duplicateGroupId')->all();
        self::assertNotEmpty($duplicateIssues);
        self::assertSame('dupgroup', $duplicateIssues[0]->groupId);
    }

    #[Test]
    public function parseFileLoadsFromDiskAndDelegates(): void
    {
        $file = $this->parser()->parseFile(__DIR__ . '/../../Fixtures/valid-2locales/messages.en.xlf');

        self::assertTrue($file->valid);
        self::assertSame('messages', $file->domain);
        self::assertSame('en', $file->locale);
    }

    #[Test]
    public function parseFileReportsXmlSyntaxErrorAsXmlWellFormedIssue(): void
    {
        $file = $this->parser()->parseFile(__DIR__ . '/../../Fixtures/malformed-xml/messages.fr.xlf');

        self::assertFalse($file->valid);
        self::assertCount(1, $file->issues);
        self::assertSame('xml.well-formed', $file->issues->all()[0]->checkId);
    }
}
