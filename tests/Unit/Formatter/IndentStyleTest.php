<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Formatter;

use Diapason\Formatter\IndentStyle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IndentStyleTest extends TestCase
{
    #[Test]
    public function exposesFourCases(): void
    {
        self::assertCount(4, IndentStyle::cases());
    }

    /** @return iterable<string, array{IndentStyle, string, string}> */
    public static function cases(): iterable
    {
        yield 'tab' => [IndentStyle::Tab, 'tab', "\t"];
        yield 'two spaces' => [IndentStyle::TwoSpaces, '2-spaces', '  '];
        yield 'four spaces' => [IndentStyle::FourSpaces, '4-spaces', '    '];
        yield 'none' => [IndentStyle::None, 'none', ''];
    }

    #[Test]
    #[DataProvider('cases')]
    public function exposesStringValueAndIndentUnit(IndentStyle $style, string $value, string $unit): void
    {
        self::assertSame($value, $style->value);
        self::assertSame($unit, $style->unit());
    }
}
