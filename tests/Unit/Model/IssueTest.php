<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Model;

use Diapason\Model\Issue;
use Diapason\Model\Severity;
use Error;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IssueTest extends TestCase
{
    #[Test]
    public function exposesAllFieldsViaNamedConstructor(): void
    {
        $issue = new Issue(
            severity: Severity::Error,
            checkId: 'xliff.namespace',
            file: '/tmp/messages.fr.xlf',
            message: 'Invalid namespace',
            line: 12,
            unitId: 'account.login',
            groupId: 'account',
        );

        self::assertSame(Severity::Error, $issue->severity);
        self::assertSame('xliff.namespace', $issue->checkId);
        self::assertSame('/tmp/messages.fr.xlf', $issue->file);
        self::assertSame('Invalid namespace', $issue->message);
        self::assertSame(12, $issue->line);
        self::assertSame('account.login', $issue->unitId);
        self::assertSame('account', $issue->groupId);
    }

    #[Test]
    public function defaultsOptionalContextFieldsToNull(): void
    {
        $issue = new Issue(
            severity: Severity::Warning,
            checkId: 'sources.consistency',
            file: '/tmp/messages.fr.xlf',
            message: 'Mismatch',
        );

        self::assertNull($issue->line);
        self::assertNull($issue->unitId);
        self::assertNull($issue->groupId);
    }

    #[Test]
    public function readonlyForbidsMutation(): void
    {
        $issue = new Issue(
            severity: Severity::Error,
            checkId: 'x',
            file: 'f',
            message: 'm',
        );

        $this->expectException(Error::class);

        /** @phpstan-ignore-next-line */
        $issue->message = 'changed';
    }
}
