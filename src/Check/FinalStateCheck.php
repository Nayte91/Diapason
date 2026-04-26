<?php

declare(strict_types=1);

namespace Diapason\Check;

use Diapason\Model\DomainBundle;
use Diapason\Model\DomainVerdict;
use Diapason\Model\Issue;
use Diapason\Model\Severity;
use Diapason\Model\Unit;

final class FinalStateCheck implements CrossLocaleCheckInterface
{
    public const string CHECK_ID_NON_FINAL = 'state.nonFinal';

    public function getDefinition(): CheckDefinition
    {
        return new CheckDefinition(
            id: 'state.final',
            summary: 'Non-source files must have all segments in state="final".',
            category: CheckCategory::State,
            defaultSeverity: Severity::Error,
            description: 'Iterates non-source XLIFF files and reports any unit containing at least one segment whose state is not "final" (including null state).',
        );
    }

    public function check(DomainBundle $bundle, DomainVerdict $verdict): iterable
    {
        foreach ($bundle->files as $file) {
            if ($file->isSource) {
                continue;
            }

            $nonFinalCount = 0;
            foreach ($file->units as $unit) {
                if ($this->hasNonFinalSegment($unit)) {
                    $nonFinalCount++;
                }
            }

            if ($nonFinalCount === 0) {
                continue;
            }

            yield new Issue(
                severity: Severity::Error,
                checkId: self::CHECK_ID_NON_FINAL,
                file: $file->filename,
                message: sprintf(
                    '%s: %d units with non-final segments',
                    $file->filename,
                    $nonFinalCount,
                ),
            );
        }
    }

    private function hasNonFinalSegment(Unit $unit): bool
    {
        return array_any($unit->segments, fn($segment): bool => $segment->state !== 'final');
    }
}
