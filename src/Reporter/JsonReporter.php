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

final class JsonReporter implements ReporterInterface
{
    public function report(ProjectReport $report, OutputInterface $output): void
    {
        $domains = [];
        foreach ($report->project->bundles() as $bundle) {
            $verdict = $report->verdictByDomain[$bundle->domain] ?? new DomainVerdict($bundle->domain);
            $issues = $report->issuesByDomain[$bundle->domain] ?? new IssueList();
            $domains[$bundle->domain] = $this->buildDomainPayload($bundle, $verdict, $issues);
        }

        $previews = [];
        foreach ($report->previewsByPath as $path => $preview) {
            $previews[$path] = [
                'diff' => $preview->diff,
                'linesAdded' => $preview->linesAdded,
                'linesRemoved' => $preview->linesRemoved,
            ];
        }

        $payload = [
            'ok' => $report->isOk(),
            'domains' => $domains,
            'previews' => $previews,
        ];

        $output->writeln((string) json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }

    /** @return array<string, mixed> */
    private function buildDomainPayload(DomainBundle $bundle, DomainVerdict $verdict, IssueList $issues): array
    {
        $files = [];
        $allValid = true;
        foreach ($bundle->files as $file) {
            if (!$file->valid) {
                $allValid = false;
            }
            $files[] = $this->buildFilePayload($file);
        }

        return [
            'ok' => $allValid && $verdict->isOk(),
            'files' => $files,
            'verdict' => [
                'groupsMatch' => $verdict->groupsMatch,
                'groupOrder' => $verdict->groupOrder,
                'unitsMatch' => $verdict->unitsMatch,
                'unitOrder' => $verdict->unitOrder,
                'sourcesMatch' => $verdict->sourcesMatch,
                'finalOk' => $verdict->finalOk,
            ],
            'issues' => array_map($this->buildIssuePayload(...), $this->collectIssues($bundle, $issues)->all()),
        ];
    }

    /** @return array<string, mixed> */
    private function buildFilePayload(XliffFile $file): array
    {
        return [
            'filename' => $file->filename,
            'locale' => $file->locale,
            'valid' => $file->valid,
            'groupsCount' => count($file->groups),
            'unitsCount' => count($file->units),
            'isSource' => $file->isSource,
            'finalOk' => $file->isSource ? null : !$this->hasNonFinalUnits($file),
        ];
    }

    /** @return array<string, mixed> */
    private function buildIssuePayload(Issue $issue): array
    {
        return [
            'severity' => $issue->severity->value,
            'checkId' => $issue->checkId,
            'file' => $issue->file,
            'message' => $issue->message,
            'unitId' => $issue->unitId,
            'groupId' => $issue->groupId,
            'line' => $issue->line,
        ];
    }

    private function collectIssues(DomainBundle $bundle, IssueList $crossLocaleIssues): IssueList
    {
        $all = new IssueList();
        foreach ($bundle->files as $file) {
            $all->addAll($file->issues);
        }
        $all->addAll($crossLocaleIssues);

        return $all;
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
}
