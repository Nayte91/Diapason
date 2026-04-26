<?php

declare(strict_types=1);

namespace Diapason\Check;

final readonly class CheckSample
{
    public function __construct(
        public string $title,
        public string $xmlBefore,
        public ?string $xmlAfter = null,
    ) {}
}
