<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Model;

use Diapason\Model\DomainBundle;
use Diapason\Model\IssueList;
use Diapason\Model\XliffFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DomainBundleTest extends TestCase
{
    #[Test]
    public function pickReferenceReturnsSourceFileWhenPresent(): void
    {
        $french = new XliffFile(
            path: '/tmp/messages.fr.xlf',
            filename: 'messages.fr.xlf',
            domain: 'messages',
            locale: 'fr',
            srcLang: 'en',
            isSource: false,
            groups: [],
            units: [],
            issues: new IssueList(),
            valid: true,
        );
        $english = new XliffFile(
            path: '/tmp/messages.en.xlf',
            filename: 'messages.en.xlf',
            domain: 'messages',
            locale: 'en',
            srcLang: 'en',
            isSource: true,
            groups: [],
            units: [],
            issues: new IssueList(),
            valid: true,
        );
        $bundle = new DomainBundle(domain: 'messages', files: [$french, $english]);

        self::assertSame($english, $bundle->pickReference());
    }

    #[Test]
    public function pickReferenceFallsBackToFirstFileWhenNoSource(): void
    {
        $french = new XliffFile(
            path: '/tmp/messages.fr.xlf',
            filename: 'messages.fr.xlf',
            domain: 'messages',
            locale: 'fr',
            srcLang: 'en',
            isSource: false,
            groups: [],
            units: [],
            issues: new IssueList(),
            valid: true,
        );
        $german = new XliffFile(
            path: '/tmp/messages.de.xlf',
            filename: 'messages.de.xlf',
            domain: 'messages',
            locale: 'de',
            srcLang: 'en',
            isSource: false,
            groups: [],
            units: [],
            issues: new IssueList(),
            valid: true,
        );
        $bundle = new DomainBundle(domain: 'messages', files: [$french, $german]);

        self::assertSame($french, $bundle->pickReference());
    }
}
