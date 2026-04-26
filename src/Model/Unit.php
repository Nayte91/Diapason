<?php

declare(strict_types=1);

namespace Diapason\Model;

final readonly class Unit
{
    /** @param list<Segment> $segments */
    public function __construct(
        public string $id,
        public string $source,
        public string $groupId,
        public array $segments,
    ) {}
}
