<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Reporter;

use Diapason\Model\DomainVerdict;
use Diapason\Model\Issue;
use Diapason\Model\IssueList;
use Diapason\Model\Project;
use Diapason\Model\ProjectReport;
use Diapason\Model\Severity;
use Diapason\Model\XliffFile;
use Diapason\Reporter\TableReporter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

final class TableReporterTest extends TestCase
{
    #[Test]
    public function rendersBoxDrawingTableWithOkFooterWhenReportIsHappy(): void
    {
        $project = new Project();
        $project->addFile(new XliffFile(
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
        ));
        $report = new ProjectReport(
            project: $project,
            verdictByDomain: ['messages' => new DomainVerdict('messages')],
            issuesByDomain: ['messages' => new IssueList()],
        );
        $output = new BufferedOutput();

        new TableReporter()->report($report, $output);

        $rendered = $output->fetch();
        self::assertStringContainsString('╔', $rendered);
        self::assertStringContainsString('║', $rendered);
        self::assertStringContainsString('messages.en.xlf', $rendered);
        self::assertStringContainsString('Consistency: messages', $rendered);
        self::assertStringContainsString('✅ Everything is fine!', $rendered);
    }

    #[Test]
    public function rendersFailFooterWhenReportHasIssues(): void
    {
        $project = new Project();
        $project->addFile(new XliffFile(
            path: '/tmp/messages.fr.xlf',
            filename: 'messages.fr.xlf',
            domain: 'messages',
            locale: 'fr',
            srcLang: 'en',
            isSource: false,
            groups: [],
            units: [],
            issues: new IssueList([new Issue(
                severity: Severity::Error,
                checkId: 'xml.well-formed',
                file: '/tmp/messages.fr.xlf',
                message: 'broken',
            )]),
            valid: false,
        ));
        $report = new ProjectReport(
            project: $project,
            verdictByDomain: ['messages' => new DomainVerdict('messages')],
            issuesByDomain: ['messages' => new IssueList()],
        );
        $output = new BufferedOutput();

        new TableReporter()->report($report, $output);

        $rendered = $output->fetch();
        self::assertStringContainsString('❌ 1 issue found', $rendered);
        self::assertStringContainsString('messages.fr.xlf: broken', $rendered);
    }

    #[Test]
    public function rendersDomainCrossLocaleIssuesUnderDomainHeading(): void
    {
        $project = new Project();
        $project->addFile(new XliffFile(
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
        ));
        $project->addFile(new XliffFile(
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
        ));
        $verdict = new DomainVerdict('messages')->withGroupsInconsistent();

        $report = new ProjectReport(
            project: $project,
            verdictByDomain: ['messages' => $verdict],
            issuesByDomain: ['messages' => new IssueList([new Issue(
                severity: Severity::Error,
                checkId: 'groups.missing',
                file: 'messages.fr.xlf',
                message: 'missing groups: foo',
            )])],
        );
        $output = new BufferedOutput();

        new TableReporter()->report($report, $output);

        $rendered = $output->fetch();
        self::assertStringContainsString("Domain 'messages':", $rendered);
        self::assertStringContainsString('missing groups: foo', $rendered);
    }
}
