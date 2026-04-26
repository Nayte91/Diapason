<?php

declare(strict_types=1);

namespace Diapason\Config;

enum FormatMode: string
{
    case Disabled = 'disabled';
    case Apply = 'apply';
    case DryRun = 'dry-run';
}
