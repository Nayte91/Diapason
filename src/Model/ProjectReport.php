<?php

declare(strict_types=1);

namespace Diapason\Model;

final readonly class ProjectReport
{
    /**
     * @param array<string, DomainVerdict>     $verdictByDomain
     * @param array<string, IssueList>         $issuesByDomain
     * @param array<string, FormatPreview>     $previewsByPath
     */
    public function __construct(
        public Project $project,
        public array $verdictByDomain,
        public array $issuesByDomain,
        public array $previewsByPath = [],
    ) {}

    public function isOk(): bool
    {
        foreach ($this->project->getCatalog() as $localeMap) {
            foreach ($localeMap as $file) {
                if (!$file->valid) {
                    return false;
                }
            }
        }

        foreach ($this->verdictByDomain as $verdict) {
            if (!$verdict->isOk()) {
                return false;
            }
        }

        if (!$this->project->getGlobalIssues()->forSeverity(Severity::Error)->isEmpty()) {
            return false;
        }
        return array_all($this->issuesByDomain, fn($issues) => $issues->forSeverity(Severity::Error)->isEmpty());
    }

    public function countIssues(): int
    {
        $count = 0;

        foreach ($this->project->getCatalog() as $localeMap) {
            foreach ($localeMap as $file) {
                $count += $file->issues->count();
            }
        }

        foreach ($this->issuesByDomain as $issues) {
            $count += $issues->count();
        }

        return $count;
    }
}
