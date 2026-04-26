<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Check;

use Diapason\Check\GroupsConsistencyCheck;
use Diapason\Model\DomainBundle;
use Diapason\Model\DomainVerdict;
use Diapason\Model\Group;
use Diapason\Model\Issue;
use Diapason\Model\IssueList;
use Diapason\Model\XliffFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GroupsConsistencyCheckTest extends TestCase
{
    #[Test]
    public function exposesStableIdAndSummary(): void
    {
        $check = new GroupsConsistencyCheck();
        $definition = $check->getDefinition();

        self::assertSame('groups.consistency', $definition->id);
        self::assertNotSame('', $definition->summary);
    }

    #[Test]
    public function happyPathYieldsNothing(): void
    {
        $reference = new XliffFile(
            path: '/tmp/messages.en.xlf',
            filename: 'messages.en.xlf',
            domain: 'messages',
            locale: 'en',
            srcLang: 'en',
            isSource: true,
            groups: [new Group(id: 'account', unitIds: []), new Group(id: 'navigation', unitIds: [])],
            units: [],
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
            groups: [new Group(id: 'account', unitIds: []), new Group(id: 'navigation', unitIds: [])],
            units: [],
            issues: new IssueList(),
            valid: true,
        );
        $bundle = new DomainBundle(domain: 'messages', files: [$reference, $french]);
        $verdict = new DomainVerdict('messages');

        $issues = iterator_to_array(new GroupsConsistencyCheck()->check($bundle, $verdict), false);

        self::assertSame([], $issues);
    }

    #[Test]
    public function detectsMissingAndExtraGroups(): void
    {
        $reference = new XliffFile(
            path: '/tmp/messages.en.xlf',
            filename: 'messages.en.xlf',
            domain: 'messages',
            locale: 'en',
            srcLang: 'en',
            isSource: true,
            groups: [new Group(id: 'account', unitIds: []), new Group(id: 'navigation', unitIds: [])],
            units: [],
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
            groups: [new Group(id: 'navigation', unitIds: []), new Group(id: 'extra', unitIds: [])],
            units: [],
            issues: new IssueList(),
            valid: true,
        );
        $bundle = new DomainBundle(domain: 'messages', files: [$reference, $french]);
        $verdict = new DomainVerdict('messages');

        $issues = iterator_to_array(new GroupsConsistencyCheck()->check($bundle, $verdict), false);

        $checkIds = array_map(static fn(Issue $issue): string => $issue->checkId, $issues);
        self::assertSame(['groups.missing', 'groups.extra'], $checkIds);
        self::assertStringContainsString('missing groups: account', $issues[0]->message);
        self::assertStringContainsString('extra groups: extra', $issues[1]->message);
    }

    #[Test]
    public function detectsGroupOrderDifference(): void
    {
        $reference = new XliffFile(
            path: '/tmp/messages.en.xlf',
            filename: 'messages.en.xlf',
            domain: 'messages',
            locale: 'en',
            srcLang: 'en',
            isSource: true,
            groups: [new Group(id: 'account', unitIds: []), new Group(id: 'navigation', unitIds: [])],
            units: [],
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
            groups: [new Group(id: 'navigation', unitIds: []), new Group(id: 'account', unitIds: [])],
            units: [],
            issues: new IssueList(),
            valid: true,
        );
        $bundle = new DomainBundle(domain: 'messages', files: [$reference, $french]);
        $verdict = new DomainVerdict('messages');

        $issues = iterator_to_array(new GroupsConsistencyCheck()->check($bundle, $verdict), false);

        self::assertCount(1, $issues);
        self::assertSame('groups.order', $issues[0]->checkId);
        self::assertStringContainsString('group order differs at position 0', $issues[0]->message);
        self::assertSame('navigation', $issues[0]->groupId);
    }

    #[Test]
    public function emptyBundleYieldsNothing(): void
    {
        $bundle = new DomainBundle(domain: 'messages', files: []);
        $verdict = new DomainVerdict('messages');

        $issues = iterator_to_array(new GroupsConsistencyCheck()->check($bundle, $verdict), false);

        self::assertSame([], $issues);
    }
}
