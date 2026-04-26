<?php

declare(strict_types=1);

namespace Diapason\Model;

final readonly class XliffFile
{
    /**
     * @param list<Group>          $groups
     * @param array<string, Unit>  $units
     */
    public function __construct(
        public string $path,
        public string $filename,
        public string $domain,
        public string $locale,
        public string $srcLang,
        public bool $isSource,
        public array $groups,
        public array $units,
        public IssueList $issues,
        public bool $valid,
    ) {}
}
