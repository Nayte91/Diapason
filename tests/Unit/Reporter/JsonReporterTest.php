<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Reporter;

use Diapason\Model\DomainVerdict;
use Diapason\Model\Issue;
use Diapason\Model\IssueList;
use Diapason\Model\Project;
use Diapason\Model\ProjectReport;
use Diapason\Model\Segment;
use Diapason\Model\Severity;
use Diapason\Model\Unit;
use Diapason\Model\XliffFile;
use Diapason\Reporter\JsonReporter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

final class JsonReporterTest extends TestCase
{
    #[Test]
    public function emitsOkTrueWhenReportIsHappy(): void
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

        new JsonReporter()->report($report, $output);

        $decoded = $this->decodePayload($output->fetch());

        self::assertTrue($decoded['ok']);
        self::assertArrayHasKey('messages', $decoded['domains']);
        $messages = $this->asArray($decoded['domains']['messages']);
        self::assertTrue($messages['ok']);
    }

    #[Test]
    public function emitsOkFalseWhenAnyVerdictFails(): void
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
            issues: new IssueList(),
            valid: true,
        ));
        $verdict = new DomainVerdict('messages')->withSourcesInconsistent();
        $report = new ProjectReport(
            project: $project,
            verdictByDomain: ['messages' => $verdict],
            issuesByDomain: ['messages' => new IssueList([new Issue(
                severity: Severity::Error,
                checkId: 'sources.consistency',
                file: 'messages.fr.xlf',
                message: 'mismatch',
            )])],
        );
        $output = new BufferedOutput();

        new JsonReporter()->report($report, $output);

        $decoded = $this->decodePayload($output->fetch());

        self::assertFalse($decoded['ok']);
        $messages = $this->asArray($decoded['domains']['messages']);
        $verdictOut = $this->asArray($messages['verdict']);
        self::assertFalse($verdictOut['sourcesMatch']);
        $issues = $this->asArray($messages['issues']);
        self::assertCount(1, $issues);
        $first = $this->asArray($issues[0]);
        self::assertSame('sources.consistency', $first['checkId']);
    }

    #[Test]
    public function exposesFinalOkAsNullForSourceFilesAndBooleanForTargets(): void
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
            units: [
                'a' => new Unit(
                    id: 'a',
                    source: 'A',
                    groupId: 'g',
                    segments: [new Segment(state: 'final', source: 'A', target: 'a-fr')],
                ),
            ],
            issues: new IssueList(),
            valid: true,
        ));
        $report = new ProjectReport(
            project: $project,
            verdictByDomain: ['messages' => new DomainVerdict('messages')],
            issuesByDomain: ['messages' => new IssueList()],
        );
        $output = new BufferedOutput();

        new JsonReporter()->report($report, $output);

        $decoded = $this->decodePayload($output->fetch());

        $messages = $this->asArray($decoded['domains']['messages']);
        $files = $this->asArray($messages['files']);
        $sourceFile = $this->asArray($files[0]);
        $targetFile = $this->asArray($files[1]);
        self::assertNull($sourceFile['finalOk']);
        self::assertTrue($targetFile['finalOk']);
    }

    /** @return array{ok: bool, domains: array<string, mixed>} */
    private function decodePayload(string $json): array
    {
        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        self::assertArrayHasKey('ok', $decoded);
        self::assertArrayHasKey('domains', $decoded);
        self::assertIsBool($decoded['ok']);
        self::assertIsArray($decoded['domains']);

        /** @var array{ok: bool, domains: array<string, mixed>} $decoded */
        return $decoded;
    }

    /** @return array<int|string, mixed> */
    private function asArray(mixed $value): array
    {
        self::assertIsArray($value);

        /** @var array<int|string, mixed> $value */
        return $value;
    }
}
