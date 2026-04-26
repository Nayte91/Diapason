<?php

declare(strict_types=1);

namespace Diapason\Check;

use Diapason\Model\DomainBundle;
use Diapason\Model\DomainVerdict;
use Diapason\Model\Group;
use Diapason\Model\Issue;
use Diapason\Model\Severity;
use Diapason\Model\XliffFile;

final class UnitsConsistencyCheck implements CrossLocaleCheckInterface
{
    public const string CHECK_ID_MISSING = 'units.missing';
    public const string CHECK_ID_EXTRA = 'units.extra';
    public const string CHECK_ID_ORDER = 'units.order';

    public function getDefinition(): CheckDefinition
    {
        return new CheckDefinition(
            id: 'units.consistency',
            summary: 'Cross-locale units must be identical and in the same order within each group.',
            category: CheckCategory::CrossLocale,
            defaultSeverity: Severity::Error,
            description: 'For each group shared across locales, compares unit IDs and their order against the reference locale. Falls back to overall missing/extra reporting when groups already diverged.',
        );
    }

    public function check(DomainBundle $bundle, DomainVerdict $verdict): iterable
    {
        if ($bundle->files === []) {
            return;
        }

        $reference = $bundle->pickReference();

        if (!$verdict->groupsMatch || !$verdict->groupOrder) {
            yield from $this->checkWhenGroupsInconsistent($bundle, $reference);

            return;
        }

        yield from $this->checkWhenGroupsConsistent($bundle, $reference);
    }

    /** @return iterable<Issue> */
    private function checkWhenGroupsInconsistent(DomainBundle $bundle, XliffFile $reference): iterable
    {
        /** @var array<string, true> $refSet */
        $refSet = array_fill_keys(array_keys($reference->units), true);

        foreach ($bundle->files as $file) {
            if ($file === $reference) {
                continue;
            }

            /** @var array<string, true> $fSet */
            $fSet = array_fill_keys(array_keys($file->units), true);
            $missing = array_diff_key($refSet, $fSet);
            $extra = array_diff_key($fSet, $refSet);

            if ($missing === [] && $extra === []) {
                continue;
            }

            if ($missing !== []) {
                yield new Issue(
                    severity: Severity::Error,
                    checkId: self::CHECK_ID_MISSING,
                    file: $file->filename,
                    message: sprintf(
                        '%s: %d missing units overall',
                        $file->filename,
                        count($missing),
                    ),
                );
            }

            if ($extra !== []) {
                yield new Issue(
                    severity: Severity::Error,
                    checkId: self::CHECK_ID_EXTRA,
                    file: $file->filename,
                    message: sprintf(
                        '%s: %d extra units overall',
                        $file->filename,
                        count($extra),
                    ),
                );
            }
        }
    }

    /** @return iterable<Issue> */
    private function checkWhenGroupsConsistent(DomainBundle $bundle, XliffFile $reference): iterable
    {
        $refGroups = $this->indexGroups($reference);

        foreach ($bundle->files as $file) {
            if ($file === $reference) {
                continue;
            }

            $fGroups = $this->indexGroups($file);

            foreach ($refGroups as $gid => $refGroup) {
                if (!isset($fGroups[$gid])) {
                    continue;
                }

                $candidate = $fGroups[$gid];

                $membershipIssues = iterator_to_array(
                    $this->compareUnitMembership($refGroup, $candidate, $file->filename),
                    false,
                );

                if ($membershipIssues !== []) {
                    yield from $membershipIssues;

                    continue;
                }

                yield from $this->compareUnitOrder($refGroup, $candidate, $file->filename);
            }
        }
    }

    /** @return iterable<Issue> */
    private function compareUnitMembership(Group $reference, Group $candidate, string $candidateFilename): iterable
    {
        /** @var array<string, true> $refSet */
        $refSet = array_fill_keys($reference->unitIds, true);
        /** @var array<string, true> $candidateSet */
        $candidateSet = array_fill_keys($candidate->unitIds, true);
        ksort($refSet);
        ksort($candidateSet);

        if ($refSet === $candidateSet) {
            return;
        }

        $missing = array_keys(array_diff_key($refSet, $candidateSet));
        $extra = array_keys(array_diff_key($candidateSet, $refSet));
        sort($missing);
        sort($extra);

        if ($missing !== []) {
            yield new Issue(
                severity: Severity::Error,
                checkId: self::CHECK_ID_MISSING,
                file: $candidateFilename,
                message: sprintf(
                    "%s group '%s': missing units: %s",
                    $candidateFilename,
                    $reference->id,
                    implode(', ', $missing),
                ),
                groupId: $reference->id,
            );
        }

        if ($extra !== []) {
            yield new Issue(
                severity: Severity::Error,
                checkId: self::CHECK_ID_EXTRA,
                file: $candidateFilename,
                message: sprintf(
                    "%s group '%s': extra units: %s",
                    $candidateFilename,
                    $reference->id,
                    implode(', ', $extra),
                ),
                groupId: $reference->id,
            );
        }
    }

    /** @return iterable<Issue> */
    private function compareUnitOrder(Group $reference, Group $candidate, string $candidateFilename): iterable
    {
        $refUnitIds = $reference->unitIds;
        $candidateUnitIds = $candidate->unitIds;

        if ($candidateUnitIds === $refUnitIds) {
            return;
        }

        $count = min(count($refUnitIds), count($candidateUnitIds));
        for ($i = 0; $i < $count; $i++) {
            if ($refUnitIds[$i] === $candidateUnitIds[$i]) {
                continue;
            }

            yield new Issue(
                severity: Severity::Error,
                checkId: self::CHECK_ID_ORDER,
                file: $candidateFilename,
                message: sprintf(
                    "%s group '%s': unit order differs at position %d (expected '%s', got '%s')",
                    $candidateFilename,
                    $reference->id,
                    $i,
                    $refUnitIds[$i],
                    $candidateUnitIds[$i],
                ),
                unitId: $candidateUnitIds[$i],
                groupId: $reference->id,
            );
            break;
        }
    }

    /** @return array<string, Group> */
    private function indexGroups(XliffFile $file): array
    {
        $indexed = [];
        foreach ($file->groups as $group) {
            $indexed[$group->id] = $group;
        }

        return $indexed;
    }
}
