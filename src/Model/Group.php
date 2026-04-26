<?php

declare(strict_types=1);

namespace Diapason\Model;

final readonly class Group
{
    /** @param list<string> $unitIds */
    public function __construct(
        public string $id,
        public array $unitIds,
    ) {}
}
