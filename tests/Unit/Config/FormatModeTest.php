<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Config;

use Diapason\Config\FormatMode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FormatModeTest extends TestCase
{
    #[Test]
    public function exposesThreeCases(): void
    {
        self::assertCount(3, FormatMode::cases());
    }

    #[Test]
    #[DataProvider('provideExpectedValues')]
    public function backingValueMatchesContract(FormatMode $mode, string $expected): void
    {
        self::assertSame($expected, $mode->value);
    }

    /** @return iterable<string, array{FormatMode, string}> */
    public static function provideExpectedValues(): iterable
    {
        yield 'disabled' => [FormatMode::Disabled, 'disabled'];
        yield 'apply'    => [FormatMode::Apply, 'apply'];
        yield 'dry-run'  => [FormatMode::DryRun, 'dry-run'];
    }
}
