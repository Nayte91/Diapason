<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Model;

use Diapason\Model\Severity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SeverityTest extends TestCase
{
    #[Test]
    public function exposesThreeCasesWithStringValues(): void
    {
        self::assertSame('error', Severity::Error->value);
        self::assertSame('warning', Severity::Warning->value);
        self::assertSame('info', Severity::Info->value);
    }

    #[Test]
    public function castsBackFromStringValue(): void
    {
        self::assertSame(Severity::Error, Severity::from('error'));
        self::assertSame(Severity::Warning, Severity::from('warning'));
        self::assertSame(Severity::Info, Severity::from('info'));
    }
}
