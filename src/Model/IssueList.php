<?php

declare(strict_types=1);

namespace Diapason\Model;

/** @implements \IteratorAggregate<int, Issue> */
final class IssueList implements \IteratorAggregate, \Countable
{
    /** @var list<Issue> */
    private array $issues = [];

    /** @param iterable<Issue> $issues */
    public function __construct(iterable $issues = [])
    {
        $this->addAll($issues);
    }

    public function add(Issue $issue): void
    {
        $this->issues[] = $issue;
    }

    /** @param iterable<Issue> $issues */
    public function addAll(iterable $issues): void
    {
        foreach ($issues as $issue) {
            $this->issues[] = $issue;
        }
    }

    public function isEmpty(): bool
    {
        return $this->issues === [];
    }

    /** @return list<Issue> */
    public function all(): array
    {
        return $this->issues;
    }

    public function forFile(string $path): self
    {
        return new self(array_filter($this->issues, static fn(Issue $issue): bool => $issue->file === $path));
    }

    public function forSeverity(Severity $severity): self
    {
        return new self(array_filter($this->issues, static fn(Issue $issue): bool => $issue->severity === $severity));
    }

    public function forCheckId(string $checkId): self
    {
        return new self(array_filter($this->issues, static fn(Issue $issue): bool => $issue->checkId === $checkId));
    }

    /** @return \Generator<int, Issue> */
    public function getIterator(): \Generator
    {
        yield from $this->issues;
    }

    public function count(): int
    {
        return count($this->issues);
    }
}
