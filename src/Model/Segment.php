<?php

declare(strict_types=1);

namespace Diapason\Model;

final readonly class Segment
{
    public function __construct(
        public ?string $state,
        public string $source,
        public ?string $target,
    ) {}
}
