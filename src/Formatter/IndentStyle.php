<?php

declare(strict_types=1);

namespace Diapason\Formatter;

enum IndentStyle: string
{
    case Tab = 'tab';
    case TwoSpaces = '2-spaces';
    case FourSpaces = '4-spaces';
    case None = 'none';

    public function unit(): string
    {
        return match ($this) {
            self::Tab => "\t",
            self::TwoSpaces => '  ',
            self::FourSpaces => '    ',
            self::None => '',
        };
    }
}
