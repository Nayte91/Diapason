<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Check;

use Diapason\Check\IssueIdFilterCheck;
use Diapason\Model\Issue;
use Diapason\Model\IssueList;
use Diapason\Model\Severity;
use Diapason\Model\XliffFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IssueIdFilterCheckTest extends TestCase
{
    #[Test]
    public function exposesIdAndSummaryFromConstructor(): void
    {
        $check = new IssueIdFilterCheck('xml.well-formed', 'XML must be well-formed', 'xml.well-formed');
        $definition = $check->getDefinition();

        self::assertSame('xml.well-formed', $definition->id);
        self::assertSame('XML must be well-formed', $definition->summary);
    }

    #[Test]
    public function yieldsOnlyIssuesMatchingExactCheckId(): void
    {
        $matching = new Issue(
            severity: Severity::Error,
            checkId: 'xml.well-formed',
            file: '/tmp/messages.fr.xlf',
            message: 'syntax error',
        );
        $other = new Issue(
            severity: Severity::Error,
            checkId: 'xliff.namespace',
            file: '/tmp/messages.fr.xlf',
            message: 'wrong ns',
        );
        $file = $this->makeFile([$matching, $other]);

        $check = new IssueIdFilterCheck('xml.well-formed', 'desc', 'xml.well-formed');
        $issues = iterator_to_array($check->check($file), false);

        self::assertSame([$matching], $issues);
    }

    #[Test]
    public function yieldsNothingWhenNoIssueMatches(): void
    {
        $file = $this->makeFile([]);

        $check = new IssueIdFilterCheck('xml.well-formed', 'desc', 'xml.well-formed');
        $issues = iterator_to_array($check->check($file), false);

        self::assertSame([], $issues);
    }

    #[Test]
    public function yieldsIssuesMatchingAnyConfiguredPrefix(): void
    {
        $duplicateGroup = new Issue(
            severity: Severity::Error,
            checkId: 'xliff.duplicateGroupId',
            file: '/tmp/messages.en.xlf',
            message: 'dup group',
        );
        $duplicateUnit = new Issue(
            severity: Severity::Error,
            checkId: 'xliff.duplicateUnitId',
            file: '/tmp/messages.en.xlf',
            message: 'dup unit',
        );
        $unrelated = new Issue(
            severity: Severity::Error,
            checkId: 'xliff.namespace',
            file: '/tmp/messages.en.xlf',
            message: 'wrong ns',
        );
        $file = $this->makeFile([$duplicateGroup, $duplicateUnit, $unrelated]);

        $check = new IssueIdFilterCheck(
            'xliff.duplicateId',
            'Group and unit IDs must be unique within an XLIFF file.',
            ['xliff.duplicate'],
        );
        $issues = iterator_to_array($check->check($file), false);

        self::assertSame([$duplicateGroup, $duplicateUnit], $issues);
    }

    #[Test]
    public function exactStringDoesNotMatchByPrefix(): void
    {
        $almost = new Issue(
            severity: Severity::Error,
            checkId: 'xml.well-formed.extra',
            file: '/tmp/messages.fr.xlf',
            message: 'should not match',
        );
        $file = $this->makeFile([$almost]);

        $check = new IssueIdFilterCheck('xml.well-formed', 'desc', 'xml.well-formed');
        $issues = iterator_to_array($check->check($file), false);

        self::assertSame([], $issues);
    }

    /** @param list<Issue> $issues */
    private function makeFile(array $issues): XliffFile
    {
        return new XliffFile(
            path: '/tmp/messages.fr.xlf',
            filename: 'messages.fr.xlf',
            domain: 'messages',
            locale: 'fr',
            srcLang: 'en',
            isSource: false,
            groups: [],
            units: [],
            issues: new IssueList($issues),
            valid: $issues === [],
        );
    }
}
