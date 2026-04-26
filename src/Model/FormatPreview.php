<?php

declare(strict_types=1);

namespace Diapason\Model;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

final readonly class FormatPreview
{
    public function __construct(
        public string $path,
        public string $diff,
        public int $linesAdded,
        public int $linesRemoved,
    ) {}

    public static function fromBeforeAfter(string $path, string $before, string $after): self
    {
        $builder = new UnifiedDiffOutputBuilder("--- before\n+++ after\n", true);
        $differ = new Differ($builder);
        $diff = $differ->diff($before, $after);

        $added = 0;
        $removed = 0;
        foreach (explode("\n", $diff) as $line) {
            if (str_starts_with($line, '+++') || str_starts_with($line, '---')) {
                continue;
            }
            if (str_starts_with($line, '+')) {
                $added++;
            } elseif (str_starts_with($line, '-')) {
                $removed++;
            }
        }

        return new self(
            path: $path,
            diff: $diff,
            linesAdded: $added,
            linesRemoved: $removed,
        );
    }
}
