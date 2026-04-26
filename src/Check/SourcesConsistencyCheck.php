<?php

declare(strict_types=1);

namespace Diapason\Check;

use Diapason\Model\DomainBundle;
use Diapason\Model\DomainVerdict;
use Diapason\Model\Issue;
use Diapason\Model\Severity;

final class SourcesConsistencyCheck implements CrossLocaleCheckInterface
{
    public const string CHECK_ID_MISMATCH = 'sources.mismatch';

    public function getDefinition(): CheckDefinition
    {
        return new CheckDefinition(
            id: 'sources.consistency',
            summary: 'Each unit must declare the same <source> across locales.',
            category: CheckCategory::CrossLocale,
            defaultSeverity: Severity::Error,
            description: 'Compares each unit\'s <source> element against the reference locale. Mismatches indicate translators edited the source text instead of the target.',
        );
    }

    public function check(DomainBundle $bundle, DomainVerdict $verdict): iterable
    {
        if ($bundle->files === []) {
            return;
        }

        $reference = $bundle->pickReference();

        foreach ($bundle->files as $file) {
            if ($file === $reference) {
                continue;
            }

            foreach ($reference->units as $uid => $refUnit) {
                $fUnit = $file->units[$uid] ?? null;
                if ($fUnit === null) {
                    continue;
                }
                if ($fUnit->source === $refUnit->source) {
                    continue;
                }

                yield new Issue(
                    severity: Severity::Error,
                    checkId: self::CHECK_ID_MISMATCH,
                    file: $file->filename,
                    message: sprintf(
                        "%s: unit '%s' source mismatch (ref='%s', got='%s')",
                        $file->filename,
                        $uid,
                        $refUnit->source,
                        $fUnit->source,
                    ),
                    unitId: $uid,
                );
            }
        }
    }
}
