<?php

declare(strict_types=1);

namespace Diapason\Model;

final readonly class Issue
{
    public function __construct(
        public Severity $severity,
        public string $checkId,
        public string $file,
        public string $message,
        public ?int $line = null,
        public ?string $unitId = null,
        public ?string $groupId = null,
    ) {}
}
