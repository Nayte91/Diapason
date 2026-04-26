<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Model;

use Diapason\Model\Issue;
use Diapason\Model\IssueList;
use Diapason\Model\Severity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IssueListTest extends TestCase
{
    #[Test]
    public function constructsEmptyByDefault(): void
    {
        $list = new IssueList();

        self::assertTrue($list->isEmpty());
        self::assertSame(0, $list->count());
        self::assertSame([], $list->all());
    }

    #[Test]
    public function constructsFromIterableSeed(): void
    {
        $first = $this->makeIssue('a');
        $second = $this->makeIssue('b');

        $list = new IssueList([$first, $second]);

        self::assertSame(2, $list->count());
        self::assertSame([$first, $second], $list->all());
    }

    #[Test]
    public function addAppendsSingleIssue(): void
    {
        $list = new IssueList();
        $issue = $this->makeIssue('a');

        $list->add($issue);

        self::assertSame(1, $list->count());
        self::assertSame([$issue], $list->all());
    }

    #[Test]
    public function addAllAcceptsArrayIterableAndGenerator(): void
    {
        $first = $this->makeIssue('a');
        $second = $this->makeIssue('b');
        $third = $this->makeIssue('c');

        $list = new IssueList();
        $list->addAll([$first]);
        $list->addAll(new \ArrayIterator([$second]));
        $list->addAll((static function () use ($third) {
            yield $third;
        })());

        self::assertSame([$first, $second, $third], $list->all());
    }

    #[Test]
    public function isEmptyReflectsContent(): void
    {
        $list = new IssueList();
        self::assertTrue($list->isEmpty());

        $list->add($this->makeIssue('a'));
        self::assertFalse($list->isEmpty());
    }

    #[Test]
    public function countMatchesNumberOfAddedIssues(): void
    {
        $list = new IssueList();
        $list->add($this->makeIssue('a'));
        $list->add($this->makeIssue('b'));
        $list->add($this->makeIssue('c'));

        self::assertSame(3, $list->count());
        self::assertCount(3, $list);
    }

    #[Test]
    public function forFileReturnsNewListFilteredByFilePath(): void
    {
        $a = $this->makeIssue('a', file: '/tmp/messages.fr.xlf');
        $b = $this->makeIssue('b', file: '/tmp/messages.de.xlf');
        $c = $this->makeIssue('c', file: '/tmp/messages.fr.xlf');
        $list = new IssueList([$a, $b, $c]);

        $filtered = $list->forFile('/tmp/messages.fr.xlf');

        self::assertNotSame($list, $filtered);
        self::assertSame([$a, $c], $filtered->all());
        self::assertSame(3, $list->count());
    }

    #[Test]
    public function forSeverityReturnsNewListFilteredBySeverity(): void
    {
        $error = $this->makeIssue('a', severity: Severity::Error);
        $warning = $this->makeIssue('b', severity: Severity::Warning);
        $info = $this->makeIssue('c', severity: Severity::Info);
        $list = new IssueList([$error, $warning, $info]);

        $errors = $list->forSeverity(Severity::Error);

        self::assertSame([$error], $errors->all());
    }

    #[Test]
    public function forCheckIdReturnsNewListFilteredByCheckId(): void
    {
        $a = $this->makeIssue('a', checkId: 'xml.well-formed');
        $b = $this->makeIssue('b', checkId: 'xliff.namespace');
        $c = $this->makeIssue('c', checkId: 'xml.well-formed');
        $list = new IssueList([$a, $b, $c]);

        $filtered = $list->forCheckId('xml.well-formed');

        self::assertSame([$a, $c], $filtered->all());
    }

    #[Test]
    public function isIterableInForeach(): void
    {
        $a = $this->makeIssue('a');
        $b = $this->makeIssue('b');
        $list = new IssueList([$a, $b]);

        $collected = [];
        foreach ($list as $issue) {
            $collected[] = $issue;
        }

        self::assertSame([$a, $b], $collected);
    }

    #[Test]
    public function iteratorToArrayProducesListOfIssues(): void
    {
        $a = $this->makeIssue('a');
        $b = $this->makeIssue('b');
        $list = new IssueList([$a, $b]);

        $iterated = iterator_to_array($list, false);

        self::assertSame([$a, $b], $iterated);
    }

    #[Test]
    public function nativeCountFunctionWorksThanksToCountable(): void
    {
        $list = new IssueList([$this->makeIssue('a'), $this->makeIssue('b')]);

        self::assertSame(2, count($list));
    }

    #[Test]
    public function filtersAreChainable(): void
    {
        $a = $this->makeIssue('a', severity: Severity::Error, file: '/tmp/x.xlf');
        $b = $this->makeIssue('b', severity: Severity::Warning, file: '/tmp/x.xlf');
        $c = $this->makeIssue('c', severity: Severity::Error, file: '/tmp/y.xlf');
        $list = new IssueList([$a, $b, $c]);

        $filtered = $list->forFile('/tmp/x.xlf')->forSeverity(Severity::Error);

        self::assertSame([$a], $filtered->all());
    }

    private function makeIssue(
        string $message,
        Severity $severity = Severity::Error,
        string $checkId = 'xml.well-formed',
        string $file = '/tmp/messages.fr.xlf',
    ): Issue {
        return new Issue(
            severity: $severity,
            checkId: $checkId,
            file: $file,
            message: $message,
        );
    }
}
