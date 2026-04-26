<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Check;

use Diapason\Check\SourcesConsistencyCheck;
use Diapason\Model\DomainBundle;
use Diapason\Model\DomainVerdict;
use Diapason\Model\IssueList;
use Diapason\Model\Unit;
use Diapason\Model\XliffFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SourcesConsistencyCheckTest extends TestCase
{
    #[Test]
    public function exposesStableIdAndSummary(): void
    {
        $check = new SourcesConsistencyCheck();
        $definition = $check->getDefinition();

        self::assertSame('sources.consistency', $definition->id);
        self::assertNotSame('', $definition->summary);
    }

    #[Test]
    public function happyPathYieldsNothingAndKeepsVerdictOk(): void
    {
        $units = [
            'account.login' => new Unit(id: 'account.login', source: 'Login', groupId: 'account', segments: []),
        ];
        $reference = new XliffFile(
            path: '/tmp/messages.en.xlf',
            filename: 'messages.en.xlf',
            domain: 'messages',
            locale: 'en',
            srcLang: 'en',
            isSource: true,
            groups: [],
            units: $units,
            issues: new IssueList(),
            valid: true,
        );
        $french = new XliffFile(
            path: '/tmp/messages.fr.xlf',
            filename: 'messages.fr.xlf',
            domain: 'messages',
            locale: 'fr',
            srcLang: 'en',
            isSource: false,
            groups: [],
            units: $units,
            issues: new IssueList(),
            valid: true,
        );
        $bundle = new DomainBundle(domain: 'messages', files: [$reference, $french]);
        $verdict = new DomainVerdict('messages');

        $issues = iterator_to_array(new SourcesConsistencyCheck()->check($bundle, $verdict), false);

        self::assertSame([], $issues);
    }

    #[Test]
    public function detectsSourceMismatchPerUnit(): void
    {
        $reference = new XliffFile(
            path: '/tmp/messages.en.xlf',
            filename: 'messages.en.xlf',
            domain: 'messages',
            locale: 'en',
            srcLang: 'en',
            isSource: true,
            groups: [],
            units: [
                'account.login' => new Unit(id: 'account.login', source: 'Login', groupId: 'account', segments: []),
            ],
            issues: new IssueList(),
            valid: true,
        );
        $french = new XliffFile(
            path: '/tmp/messages.fr.xlf',
            filename: 'messages.fr.xlf',
            domain: 'messages',
            locale: 'fr',
            srcLang: 'en',
            isSource: false,
            groups: [],
            units: [
                'account.login' => new Unit(id: 'account.login', source: 'Sign in', groupId: 'account', segments: []),
            ],
            issues: new IssueList(),
            valid: true,
        );
        $bundle = new DomainBundle(domain: 'messages', files: [$reference, $french]);
        $verdict = new DomainVerdict('messages');

        $issues = iterator_to_array(new SourcesConsistencyCheck()->check($bundle, $verdict), false);

        self::assertCount(1, $issues);
        self::assertSame('sources.mismatch', $issues[0]->checkId);
        self::assertSame('account.login', $issues[0]->unitId);
        self::assertStringContainsString("ref='Login'", $issues[0]->message);
        self::assertStringContainsString("got='Sign in'", $issues[0]->message);
    }

    #[Test]
    public function ignoresUnitsAbsentFromOtherFile(): void
    {
        $reference = new XliffFile(
            path: '/tmp/messages.en.xlf',
            filename: 'messages.en.xlf',
            domain: 'messages',
            locale: 'en',
            srcLang: 'en',
            isSource: true,
            groups: [],
            units: [
                'a' => new Unit(id: 'a', source: 'A', groupId: 'g', segments: []),
                'b' => new Unit(id: 'b', source: 'B', groupId: 'g', segments: []),
            ],
            issues: new IssueList(),
            valid: true,
        );
        $french = new XliffFile(
            path: '/tmp/messages.fr.xlf',
            filename: 'messages.fr.xlf',
            domain: 'messages',
            locale: 'fr',
            srcLang: 'en',
            isSource: false,
            groups: [],
            units: [
                'a' => new Unit(id: 'a', source: 'A', groupId: 'g', segments: []),
            ],
            issues: new IssueList(),
            valid: true,
        );
        $bundle = new DomainBundle(domain: 'messages', files: [$reference, $french]);
        $verdict = new DomainVerdict('messages');

        $issues = iterator_to_array(new SourcesConsistencyCheck()->check($bundle, $verdict), false);

        self::assertSame([], $issues);
    }

    #[Test]
    public function emptyBundleYieldsNothing(): void
    {
        $bundle = new DomainBundle(domain: 'messages', files: []);
        $verdict = new DomainVerdict('messages');

        $issues = iterator_to_array(new SourcesConsistencyCheck()->check($bundle, $verdict), false);

        self::assertSame([], $issues);
    }
}
