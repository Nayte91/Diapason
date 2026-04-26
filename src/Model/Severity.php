<?php

declare(strict_types=1);

namespace Diapason\Model;

enum Severity: string
{
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';
}
