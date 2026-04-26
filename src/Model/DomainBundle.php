<?php

declare(strict_types=1);

namespace Diapason\Model;

final readonly class DomainBundle
{
    /** @param list<XliffFile> $files */
    public function __construct(
        public string $domain,
        public array $files,
    ) {}

    public function pickReference(): XliffFile
    {
        foreach ($this->files as $file) {
            if ($file->isSource) {
                return $file;
            }
        }

        return $this->files[0];
    }
}
