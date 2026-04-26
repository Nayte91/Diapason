<?php

declare(strict_types=1);

namespace Diapason\Check;

enum CheckCategory: string
{
    case Structural = 'structural';
    case PerFile = 'per-file';
    case CrossLocale = 'cross-locale';
    case State = 'state';
}
