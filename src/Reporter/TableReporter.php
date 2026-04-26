<?php

declare(strict_types=1);

namespace Diapason\Reporter;

use Diapason\Model\DomainBundle;
use Diapason\Model\DomainVerdict;
use Diapason\Model\Issue;
use Diapason\Model\IssueList;
use Diapason\Model\ProjectReport;
use Diapason\Model\XliffFile;
use Symfony\Component\Console\Output\OutputInterface;

final class TableReporter implements ReporterInterface
{
    private const string SYM_OK = '✓';
    private const string SYM_FAIL = '✗';
    private const string SYM_NA = '-';

    /** @var list<string> */
    private const array COLUMNS = ['File', 'Valid', 'Groups', 'G. Order', 'Units', 'U. Order', 'Sources', 'Final'];

    public function report(ProjectReport $report, OutputInterface $output): void
    {
        /** @var list<DomainBundle> $bundles */
        $bundles = [];
        foreach ($report->project->bundles() as $bundle) {
            $bundles[] = $bundle;
        }

        if ($bundles !== []) {
            $this->renderTable($bundles, $report, $output);
        }

        if ($report->isOk()) {
            $output->writeln('');
            $output->writeln('✅ Everything is fine!');

            return;
        }

        $count = $report->countIssues();
        $output->writeln('');
        $output->writeln(sprintf('❌ %d issue%s found', $count, $count === 1 ? '' : 's'));
        $output->writeln('');

        foreach ($bundles as $bundle) {
            $this->renderBundleDetails($bundle, $report, $output);
        }
    }

    /** @param list<DomainBundle> $bundles */
    private function renderTable(array $bundles, ProjectReport $report, OutputInterface $output): void
    {
        /** @var list<list<string>> $rows */
        $rows = [self::COLUMNS];
        /** @var list<list<string>> $bodyRows */
        $bodyRows = [];

        foreach ($bundles as $bundle) {
            $verdict = $report->verdictByDomain[$bundle->domain] ?? new DomainVerdict($bundle->domain);

            foreach ($bundle->files as $file) {
                $row = $this->fileRow($file);
                $bodyRows[] = $row;
                $rows[] = $row;
            }

            $verdictRow = $this->verdictRow($bundle, $verdict);
            $bodyRows[] = $verdictRow;
            $rows[] = $verdictRow;
        }

        $widths = $this->computeWidths($rows);

        $output->writeln($this->separator('╔', '╦', '╗', '═', $widths));
        $output->writeln($this->dataRow(self::COLUMNS, $widths));
        $output->writeln($this->separator('╠', '╬', '╣', '═', $widths));

        $bodyCount = count($bodyRows);
        for ($i = 0; $i < $bodyCount; $i++) {
            $output->writeln($this->dataRow($bodyRows[$i], $widths));
            if ($i === $bodyCount - 1) {
                continue;
            }
            $needsSeparator = $this->isVerdictRow($bodyRows[$i]) || $this->isVerdictRow($bodyRows[$i + 1]);
            if (!$needsSeparator) {
                continue;
            }
            $output->writeln($this->separator('╠', '╬', '╣', '═', $widths));
        }

        $output->writeln($this->separator('╚', '╩', '╝', '═', $widths));
    }

    /** @param list<string> $row */
    private function isVerdictRow(array $row): bool
    {
        return str_starts_with($row[0] ?? '', 'Consistency: ');
    }

    /**
     * @param list<list<string>> $rows
     * @return list<int>
     */
    private function computeWidths(array $rows): array
    {
        $widths = [];
        $columnCount = count(self::COLUMNS);
        for ($i = 0; $i < $columnCount; $i++) {
            $max = 0;
            foreach ($rows as $row) {
                $length = mb_strlen($row[$i] ?? '');
                if ($length > $max) {
                    $max = $length;
                }
            }
            $widths[] = $max + 2;
        }

        return $widths;
    }

    /** @return list<string> */
    private function fileRow(XliffFile $file): array
    {
        $finalCell = self::SYM_NA;
        if (!$file->isSource) {
            $finalCell = $this->hasNonFinalUnits($file) ? self::SYM_FAIL : self::SYM_OK;
        }

        return [
            $file->filename,
            $file->valid ? self::SYM_OK : self::SYM_FAIL,
            (string) count($file->groups),
            '',
            (string) count($file->units),
            '',
            '',
            $finalCell,
        ];
    }

    /** @return list<string> */
    private function verdictRow(DomainBundle $bundle, DomainVerdict $verdict): array
    {
        $allValid = array_all($bundle->files, fn($file) => $file->valid);
        return [
            sprintf('Consistency: %s', $bundle->domain),
            $allValid ? self::SYM_OK : self::SYM_FAIL,
            $verdict->groupsMatch ? self::SYM_OK : self::SYM_FAIL,
            $verdict->groupOrder ? self::SYM_OK : self::SYM_FAIL,
            $verdict->unitsMatch ? self::SYM_OK : self::SYM_FAIL,
            $verdict->unitOrder ? self::SYM_OK : self::SYM_FAIL,
            $verdict->sourcesMatch ? self::SYM_OK : self::SYM_FAIL,
            $verdict->finalOk ? self::SYM_OK : self::SYM_FAIL,
        ];
    }

    private function hasNonFinalUnits(XliffFile $file): bool
    {
        foreach ($file->units as $unit) {
            foreach ($unit->segments as $segment) {
                if ($segment->state !== 'final') {
                    return true;
                }
            }
        }

        return false;
    }

    /** @param list<int> $widths */
    private function separator(string $left, string $mid, string $right, string $fill, array $widths): string
    {
        $parts = [];
        foreach ($widths as $width) {
            $parts[] = str_repeat($fill, $width);
        }

        return $left . implode($mid, $parts) . $right;
    }

    /**
     * @param list<string> $cells
     * @param list<int>    $widths
     */
    private function dataRow(array $cells, array $widths): string
    {
        $padded = [];
        foreach ($cells as $i => $cell) {
            $padded[] = mb_str_pad($cell, $widths[$i], ' ', STR_PAD_BOTH);
        }

        return '║' . implode('║', $padded) . '║';
    }

    private function renderBundleDetails(DomainBundle $bundle, ProjectReport $report, OutputInterface $output): void
    {
        foreach ($bundle->files as $file) {
            foreach ($file->issues as $issue) {
                $output->writeln(sprintf('• %s: %s', $file->filename, $issue->message));
            }
        }

        $verdict = $report->verdictByDomain[$bundle->domain] ?? null;
        $issues = $report->issuesByDomain[$bundle->domain] ?? new IssueList();

        if ($verdict === null) {
            return;
        }
        if ($verdict->isOk()) {
            return;
        }

        $output->writeln(sprintf("• Domain '%s':", $bundle->domain));
        foreach ($issues as $issue) {
            if (!$this->isCrossLocaleIssue($issue)) {
                continue;
            }
            $output->writeln(sprintf('  - %s', $issue->message));
        }
    }

    private function isCrossLocaleIssue(Issue $issue): bool
    {
        return str_starts_with($issue->checkId, 'groups.')
            || str_starts_with($issue->checkId, 'units.')
            || str_starts_with($issue->checkId, 'sources.')
            || str_starts_with($issue->checkId, 'state.');
    }
}
