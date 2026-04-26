<?php

declare(strict_types=1);

namespace Diapason\Check;

use Diapason\Model\Issue;
use Diapason\Model\Severity;
use Diapason\Model\XliffFile;

final readonly class IssueIdFilterCheck implements PerFileCheckInterface
{
    private CheckDefinition $definition;

    /**
     * @param string|list<string> $checkIdOrPrefixes
     *   Either an exact check id, or a list of id prefixes to match (e.g. ['xliff.duplicate']).
     */
    public function __construct(
        string $id,
        string $summary,
        private string|array $checkIdOrPrefixes,
        CheckCategory $category = CheckCategory::Structural,
        Severity $defaultSeverity = Severity::Error,
    ) {
        $this->definition = new CheckDefinition(
            id: $id,
            summary: $summary,
            category: $category,
            defaultSeverity: $defaultSeverity,
        );
    }

    public function getDefinition(): CheckDefinition
    {
        return $this->definition;
    }

    /** @return iterable<Issue> */
    public function check(XliffFile $file): iterable
    {
        foreach ($file->issues as $issue) {
            if ($this->matches($issue->checkId)) {
                yield $issue;
            }
        }
    }

    private function matches(string $checkId): bool
    {
        if (is_string($this->checkIdOrPrefixes)) {
            return $checkId === $this->checkIdOrPrefixes;
        }
        return array_any($this->checkIdOrPrefixes, fn($prefix): bool => str_starts_with($checkId, (string) $prefix));
    }
}
