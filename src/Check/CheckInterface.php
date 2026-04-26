<?php

declare(strict_types=1);

namespace Diapason\Check;

interface CheckInterface
{
    public function getDefinition(): CheckDefinition;
}
