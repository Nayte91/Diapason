<?php

declare(strict_types=1);

namespace Diapason\Check;

use Diapason\Model\DomainBundle;
use Diapason\Model\DomainVerdict;
use Diapason\Model\Issue;
use Diapason\Model\Severity;
use Diapason\Model\XliffFile;

final class GroupsConsistencyCheck implements CrossLocaleCheckInterface
{
    public const string CHECK_ID_MISSING = 'groups.missing';
    public const string CHECK_ID_EXTRA = 'groups.extra';
    public const string CHECK_ID_ORDER = 'groups.order';

    public function getDefinition(): CheckDefinition
    {
        return new CheckDefinition(
            id: 'groups.consistency',
            summary: 'Cross-locale groups must be identical and in the same order.',
            category: CheckCategory::CrossLocale,
            defaultSeverity: Severity::Error,
            description: 'Compares the set of group IDs across all locales of a domain. Detects missing groups, extra groups, and order divergences relative to the reference locale.',
        );
    }

    public function check(DomainBundle $bundle, DomainVerdict $verdict): iterable
    {
        if ($bundle->files === []) {
            return;
        }

        $reference = $bundle->pickReference();
        $refIds = $this->groupIds($reference);
        $refSet = array_fill_keys($refIds, true);
        ksort($refSet);

        foreach ($bundle->files as $file) {
            if ($file === $reference) {
                continue;
            }

            $fIds = $this->groupIds($file);
            $fSet = array_fill_keys($fIds, true);
            ksort($fSet);

            if ($refSet !== $fSet) {
                $missing = array_keys(array_diff_key($refSet, $fSet));
                $extra = array_keys(array_diff_key($fSet, $refSet));
                sort($missing);
                sort($extra);

                if ($missing !== []) {
                    yield new Issue(
                        severity: Severity::Error,
                        checkId: self::CHECK_ID_MISSING,
                        file: $file->filename,
                        message: sprintf(
                            '%s: missing groups: %s',
                            $file->filename,
                            implode(', ', $missing),
                        ),
                    );
                }

                if ($extra !== []) {
                    yield new Issue(
                        severity: Severity::Error,
                        checkId: self::CHECK_ID_EXTRA,
                        file: $file->filename,
                        message: sprintf(
                            '%s: extra groups: %s',
                            $file->filename,
                            implode(', ', $extra),
                        ),
                    );
                }

                continue;
            }

            if ($fIds === $refIds) {
                continue;
            }

            $count = min(count($refIds), count($fIds));
            for ($i = 0; $i < $count; $i++) {
                if ($refIds[$i] === $fIds[$i]) {
                    continue;
                }

                yield new Issue(
                    severity: Severity::Error,
                    checkId: self::CHECK_ID_ORDER,
                    file: $file->filename,
                    message: sprintf(
                        "%s: group order differs at position %d (expected '%s', got '%s')",
                        $file->filename,
                        $i,
                        $refIds[$i],
                        $fIds[$i],
                    ),
                    groupId: $fIds[$i],
                );
                break;
            }
        }
    }

    /** @return list<string> */
    private function groupIds(XliffFile $file): array
    {
        $ids = [];
        foreach ($file->groups as $group) {
            $ids[] = $group->id;
        }

        return $ids;
    }
}
