<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Model;

use Diapason\Model\DomainVerdict;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DomainVerdictTest extends TestCase
{
    #[Test]
    public function defaultsToAllFlagsTrueAndIsOk(): void
    {
        $verdict = new DomainVerdict('messages');

        self::assertTrue($verdict->groupsMatch);
        self::assertTrue($verdict->groupOrder);
        self::assertTrue($verdict->unitsMatch);
        self::assertTrue($verdict->unitOrder);
        self::assertTrue($verdict->sourcesMatch);
        self::assertTrue($verdict->finalOk);
        self::assertTrue($verdict->isOk());
    }

    #[Test]
    public function exposesDomainAsReadonlyProperty(): void
    {
        $verdict = new DomainVerdict('messages');

        self::assertSame('messages', $verdict->domain);
    }

    #[Test]
    #[DataProvider('provideMutators')]
    public function mutatorsReturnFreshInstanceWithExactlyTheTargetedFlagsFlipped(
        string $mutator,
        string $expectedFalseFlag,
        ?string $additionalFalseFlag,
    ): void {
        $initial = new DomainVerdict('messages');

        $mutated = $initial->{$mutator}();

        self::assertNotSame($initial, $mutated);
        self::assertSame('messages', $mutated->domain);
        self::assertFalse($mutated->{$expectedFalseFlag});
        if ($additionalFalseFlag !== null) {
            self::assertFalse($mutated->{$additionalFalseFlag});
        }
        self::assertTrue($initial->{$expectedFalseFlag}, 'Original verdict must remain unchanged');
        self::assertTrue($initial->isOk(), 'Original verdict must remain ok');
        self::assertFalse($mutated->isOk());
    }

    /** @return iterable<string, array{string, string, string|null}> */
    public static function provideMutators(): iterable
    {
        yield 'withGroupsInconsistent flips groupsMatch and groupOrder' => [
            'withGroupsInconsistent',
            'groupsMatch',
            'groupOrder',
        ];
        yield 'withGroupOrderInconsistent flips only groupOrder' => [
            'withGroupOrderInconsistent',
            'groupOrder',
            null,
        ];
        yield 'withUnitsInconsistent flips only unitsMatch' => [
            'withUnitsInconsistent',
            'unitsMatch',
            null,
        ];
        yield 'withUnitOrderInconsistent flips only unitOrder' => [
            'withUnitOrderInconsistent',
            'unitOrder',
            null,
        ];
        yield 'withSourcesInconsistent flips only sourcesMatch' => [
            'withSourcesInconsistent',
            'sourcesMatch',
            null,
        ];
        yield 'withFinalNotReached flips only finalOk' => [
            'withFinalNotReached',
            'finalOk',
            null,
        ];
    }

    #[Test]
    public function repeatedMutatorIsIdempotentInState(): void
    {
        $once = new DomainVerdict('messages')->withGroupsInconsistent();
        $twice = $once->withGroupsInconsistent();

        self::assertFalse($twice->groupsMatch);
        self::assertFalse($twice->groupOrder);
        self::assertTrue($twice->unitsMatch);
        self::assertTrue($twice->unitOrder);
        self::assertTrue($twice->sourcesMatch);
        self::assertTrue($twice->finalOk);
    }

    #[Test]
    public function chainedMutatorsAccumulateAllFailedFlags(): void
    {
        $verdict = new DomainVerdict('messages')
            ->withSourcesInconsistent()
            ->withFinalNotReached();

        self::assertTrue($verdict->groupsMatch);
        self::assertTrue($verdict->groupOrder);
        self::assertTrue($verdict->unitsMatch);
        self::assertTrue($verdict->unitOrder);
        self::assertFalse($verdict->sourcesMatch);
        self::assertFalse($verdict->finalOk);
        self::assertFalse($verdict->isOk());
    }
}
