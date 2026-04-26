<?php

declare(strict_types=1);

namespace Diapason\Model;

final class Project
{
    /** @var array<string, array<string, XliffFile>> */
    private array $catalog = [];

    private readonly IssueList $globalIssues;

    public function __construct()
    {
        $this->globalIssues = new IssueList();
    }

    public function addFile(XliffFile $file): void
    {
        $this->catalog[$file->domain][$file->locale] = $file;
    }

    public function addIssue(Issue $issue): void
    {
        $this->globalIssues->add($issue);
    }

    public function getGlobalIssues(): IssueList
    {
        return $this->globalIssues;
    }

    /** @return iterable<DomainBundle> */
    public function bundles(): iterable
    {
        foreach ($this->catalog as $domain => $localeMap) {
            $sorted = $localeMap;
            ksort($sorted);

            yield new DomainBundle($domain, array_values($sorted));
        }
    }

    /** @return array<string, array<string, XliffFile>> */
    public function getCatalog(): array
    {
        return $this->catalog;
    }
}
