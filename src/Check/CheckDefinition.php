<?php

declare(strict_types=1);

namespace Diapason\Check;

use Diapason\Model\Severity;

final readonly class CheckDefinition
{
    /** @param list<CheckSample> $samples */
    public function __construct(
        public string $id,
        public string $summary,
        public CheckCategory $category,
        public Severity $defaultSeverity,
        public ?string $description = null,
        public array $samples = [],
    ) {}
}
