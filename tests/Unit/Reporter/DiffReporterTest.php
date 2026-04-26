<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Reporter;

use Diapason\Model\DomainVerdict;
use Diapason\Model\FormatPreview;
use Diapason\Model\Project;
use Diapason\Model\ProjectReport;
use Diapason\Reporter\DiffReporter;
use Diapason\Reporter\ReporterInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class DiffReporterTest extends TestCase
{
    #[Test]
    public function announcesNoChangesAndDelegatesToInnerWhenPreviewsAreEmpty(): void
    {
        $report = new ProjectReport(
            project: new Project(),
            verdictByDomain: [],
            issuesByDomain: [],
            previewsByPath: [],
        );
        $inner = new RecordingReporter('inner-payload');
        $output = new BufferedOutput();

        new DiffReporter($inner)->report($report, $output);

        $rendered = $output->fetch();
        self::assertStringContainsString('No formatting changes would be applied.', $rendered);
        self::assertStringContainsString('inner-payload', $rendered);
        self::assertSame(1, $inner->callCount);
    }

    #[Test]
    public function rendersPathsDiffsAndSummariesForEachPreviewBeforeDelegatingToInner(): void
    {
        $previews = [
            '/tmp/foo.en.xlf' => FormatPreview::fromBeforeAfter(
                '/tmp/foo.en.xlf',
                "old-foo-line\n",
                "new-foo-line\n",
            ),
            '/tmp/bar.en.xlf' => FormatPreview::fromBeforeAfter(
                '/tmp/bar.en.xlf',
                "old-bar-line\n",
                "new-bar-line\n",
            ),
        ];
        $report = new ProjectReport(
            project: new Project(),
            verdictByDomain: [],
            issuesByDomain: [],
            previewsByPath: $previews,
        );
        $inner = new RecordingReporter('inner-payload');
        $output = new BufferedOutput();

        new DiffReporter($inner)->report($report, $output);

        $rendered = $output->fetch();
        self::assertStringContainsString('/tmp/foo.en.xlf', $rendered);
        self::assertStringContainsString('/tmp/bar.en.xlf', $rendered);
        self::assertStringContainsString('Summary:', $rendered);
        self::assertStringContainsString('+1', $rendered);
        self::assertStringContainsString('-1', $rendered);
        self::assertStringContainsString('inner-payload', $rendered);
        self::assertStringNotContainsString('No formatting changes would be applied.', $rendered);
    }
}

final class RecordingReporter implements ReporterInterface
{
    public int $callCount = 0;

    public function __construct(private readonly string $marker) {}

    public function report(ProjectReport $report, OutputInterface $output): void
    {
        $this->callCount++;
        $output->writeln($this->marker);
    }
}
