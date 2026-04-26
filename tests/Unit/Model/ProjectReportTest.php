<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Model;

use Diapason\Model\DomainVerdict;
use Diapason\Model\Issue;
use Diapason\Model\IssueList;
use Diapason\Model\Project;
use Diapason\Model\ProjectReport;
use Diapason\Model\Severity;
use Diapason\Model\XliffFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProjectReportTest extends TestCase
{
    #[Test]
    public function isOkWhenAllFilesValidAndAllVerdictsOkAndNoErrorIssues(): void
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

        self::assertTrue($report->isOk());
    }

    #[Test]
    public function isNotOkWhenAnyFileIsInvalid(): void
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
            valid: false,
        ));

        $report = new ProjectReport(
            project: $project,
            verdictByDomain: ['messages' => new DomainVerdict('messages')],
            issuesByDomain: ['messages' => new IssueList()],
        );

        self::assertFalse($report->isOk());
    }

    #[Test]
    public function isNotOkWhenAnyVerdictFails(): void
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

        $verdict = new DomainVerdict('messages')->withGroupsInconsistent();

        $report = new ProjectReport(
            project: $project,
            verdictByDomain: ['messages' => $verdict],
            issuesByDomain: ['messages' => new IssueList()],
        );

        self::assertFalse($report->isOk());
    }

    #[Test]
    public function isNotOkWhenGlobalIssueHasErrorSeverity(): void
    {
        $project = new Project();
        $project->addIssue(new Issue(
            severity: Severity::Error,
            checkId: 'global',
            file: '',
            message: 'fail',
        ));

        $report = new ProjectReport(project: $project, verdictByDomain: [], issuesByDomain: []);

        self::assertFalse($report->isOk());
    }

    #[Test]
    public function ignoresGlobalIssuesBelowErrorSeverity(): void
    {
        $project = new Project();
        $project->addIssue(new Issue(
            severity: Severity::Warning,
            checkId: 'global',
            file: '',
            message: 'careful',
        ));

        $report = new ProjectReport(project: $project, verdictByDomain: [], issuesByDomain: []);

        self::assertTrue($report->isOk());
    }

    #[Test]
    public function isNotOkWhenDomainIssueHasErrorSeverity(): void
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
            issuesByDomain: ['messages' => new IssueList([new Issue(
                severity: Severity::Error,
                checkId: 'sources.consistency',
                file: 'messages.fr.xlf',
                message: 'mismatch',
            )])],
        );

        self::assertFalse($report->isOk());
    }

    #[Test]
    public function countIssuesSumsFileIssuesAndDomainIssues(): void
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
            issues: new IssueList([
                new Issue(severity: Severity::Error, checkId: 'xml.well-formed', file: 'm', message: 'a'),
                new Issue(severity: Severity::Error, checkId: 'xml.well-formed', file: 'm', message: 'b'),
            ]),
            valid: false,
        ));

        $report = new ProjectReport(
            project: $project,
            verdictByDomain: ['messages' => new DomainVerdict('messages')],
            issuesByDomain: ['messages' => new IssueList([
                new Issue(severity: Severity::Error, checkId: 'sources.consistency', file: 'f', message: 'c'),
                new Issue(severity: Severity::Error, checkId: 'sources.consistency', file: 'f', message: 'd'),
                new Issue(severity: Severity::Error, checkId: 'sources.consistency', file: 'f', message: 'e'),
            ])],
        );

        self::assertSame(5, $report->countIssues());
    }
}
