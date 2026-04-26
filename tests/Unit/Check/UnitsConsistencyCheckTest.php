<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Check;

use Diapason\Check\UnitsConsistencyCheck;
use Diapason\Model\DomainBundle;
use Diapason\Model\DomainVerdict;
use Diapason\Model\Group;
use Diapason\Model\IssueList;
use Diapason\Model\Unit;
use Diapason\Model\XliffFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UnitsConsistencyCheckTest extends TestCase
{
    #[Test]
    public function exposesStableIdAndSummary(): void
    {
        $check = new UnitsConsistencyCheck();
        $definition = $check->getDefinition();

        self::assertSame('units.consistency', $definition->id);
        self::assertNotSame('', $definition->summary);
    }

    #[Test]
    public function happyPathYieldsNothing(): void
    {
        $units = [
            'account.login'  => new Unit(id: 'account.login', source: 'Login', groupId: 'account', segments: []),
            'account.logout' => new Unit(id: 'account.logout', source: 'Logout', groupId: 'account', segments: []),
        ];
        $reference = new XliffFile(
            path: '/tmp/messages.en.xlf',
            filename: 'messages.en.xlf',
            domain: 'messages',
            locale: 'en',
            srcLang: 'en',
            isSource: true,
            groups: [new Group(id: 'account', unitIds: ['account.login', 'account.logout'])],
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
            groups: [new Group(id: 'account', unitIds: ['account.login', 'account.logout'])],
            units: $units,
            issues: new IssueList(),
            valid: true,
        );
        $bundle = new DomainBundle(domain: 'messages', files: [$reference, $french]);
        $verdict = new DomainVerdict('messages');

        $issues = iterator_to_array(new UnitsConsistencyCheck()->check($bundle, $verdict), false);

        self::assertSame([], $issues);
    }

    #[Test]
    public function detectsMissingUnitInGroup(): void
    {
        $reference = new XliffFile(
            path: '/tmp/messages.en.xlf',
            filename: 'messages.en.xlf',
            domain: 'messages',
            locale: 'en',
            srcLang: 'en',
            isSource: true,
            groups: [new Group(id: 'account', unitIds: ['account.login', 'account.logout'])],
            units: [
                'account.login'  => new Unit(id: 'account.login', source: 'Login', groupId: 'account', segments: []),
                'account.logout' => new Unit(id: 'account.logout', source: 'Logout', groupId: 'account', segments: []),
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
            groups: [new Group(id: 'account', unitIds: ['account.login'])],
            units: [
                'account.login' => new Unit(id: 'account.login', source: 'Login', groupId: 'account', segments: []),
            ],
            issues: new IssueList(),
            valid: true,
        );
        $bundle = new DomainBundle(domain: 'messages', files: [$reference, $french]);
        $verdict = new DomainVerdict('messages');

        $issues = iterator_to_array(new UnitsConsistencyCheck()->check($bundle, $verdict), false);

        self::assertNotEmpty($issues);
        self::assertSame('units.missing', $issues[0]->checkId);
        self::assertStringContainsString('missing units: account.logout', $issues[0]->message);
        self::assertSame('account', $issues[0]->groupId);
    }

    #[Test]
    public function detectsUnitOrderDifferenceWithinGroup(): void
    {
        $units = [
            'account.login'  => new Unit(id: 'account.login', source: 'Login', groupId: 'account', segments: []),
            'account.logout' => new Unit(id: 'account.logout', source: 'Logout', groupId: 'account', segments: []),
        ];
        $reference = new XliffFile(
            path: '/tmp/messages.en.xlf',
            filename: 'messages.en.xlf',
            domain: 'messages',
            locale: 'en',
            srcLang: 'en',
            isSource: true,
            groups: [new Group(id: 'account', unitIds: ['account.login', 'account.logout'])],
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
            groups: [new Group(id: 'account', unitIds: ['account.logout', 'account.login'])],
            units: $units,
            issues: new IssueList(),
            valid: true,
        );
        $bundle = new DomainBundle(domain: 'messages', files: [$reference, $french]);
        $verdict = new DomainVerdict('messages');

        $issues = iterator_to_array(new UnitsConsistencyCheck()->check($bundle, $verdict), false);

        self::assertCount(1, $issues);
        self::assertSame('units.order', $issues[0]->checkId);
        self::assertStringContainsString('unit order differs at position 0', $issues[0]->message);
        self::assertSame('account', $issues[0]->groupId);
        self::assertSame('account.logout', $issues[0]->unitId);
    }

    #[Test]
    public function degradedModeReportsOverallMissingAndExtraWhenGroupsAlreadyDiverged(): void
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
                'a' => new Unit(id: 'a', source: 'A', groupId: '', segments: []),
                'b' => new Unit(id: 'b', source: 'B', groupId: '', segments: []),
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
                'a' => new Unit(id: 'a', source: 'A', groupId: '', segments: []),
                'c' => new Unit(id: 'c', source: 'C', groupId: '', segments: []),
            ],
            issues: new IssueList(),
            valid: true,
        );
        $bundle = new DomainBundle(domain: 'messages', files: [$reference, $french]);
        $verdict = new DomainVerdict('messages')->withGroupsInconsistent();

        $issues = iterator_to_array(new UnitsConsistencyCheck()->check($bundle, $verdict), false);

        self::assertCount(2, $issues);
        self::assertSame('units.missing', $issues[0]->checkId);
        self::assertStringContainsString('1 missing units overall', $issues[0]->message);
        self::assertSame('units.extra', $issues[1]->checkId);
        self::assertStringContainsString('1 extra units overall', $issues[1]->message);
    }

    #[Test]
    public function emptyBundleYieldsNothing(): void
    {
        $bundle = new DomainBundle(domain: 'messages', files: []);
        $verdict = new DomainVerdict('messages');

        $issues = iterator_to_array(new UnitsConsistencyCheck()->check($bundle, $verdict), false);

        self::assertSame([], $issues);
    }
}
