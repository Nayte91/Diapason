<?php

declare(strict_types=1);

namespace Diapason\Tests\Integration;

use Diapason\Checker;
use Diapason\Config\DiapasonConfig;
use Diapason\Config\FormatMode;
use Diapason\Parser\DomainResolver;
use Diapason\Parser\XliffDomLoader;
use Diapason\Parser\XliffParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CheckerTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures';

    private Checker $checker;
    private DiapasonConfig $config;

    protected function setUp(): void
    {
        $resolver = new DomainResolver();
        $this->checker = new Checker(new XliffParser($resolver, new XliffDomLoader()), $resolver);
        $this->config = DiapasonConfig::defaults()->withFormatMode(FormatMode::Disabled);
    }

    #[Test]
    public function valid2LocalesPassesAllChecks(): void
    {
        $report = $this->checker->run($this->config, self::FIXTURES . '/valid-2locales/messages', '/');

        self::assertTrue($report->isOk());
        self::assertSame(0, $report->countIssues());
    }

    #[Test]
    public function missingGroupFailsGroupsConsistency(): void
    {
        $report = $this->checker->run($this->config, self::FIXTURES . '/missing-group/messages', '/');

        self::assertFalse($report->isOk());
        $verdict = $report->verdictByDomain['messages'];
        self::assertFalse($verdict->groupsMatch);
    }

    #[Test]
    public function missingUnitInGroupFailsUnitsConsistency(): void
    {
        $report = $this->checker->run($this->config, self::FIXTURES . '/missing-unit-in-group/messages', '/');

        self::assertFalse($report->isOk());
        $verdict = $report->verdictByDomain['messages'];
        self::assertTrue($verdict->groupsMatch);
        self::assertFalse($verdict->unitsMatch);
    }

    #[Test]
    public function wrongGroupOrderFailsGroupOrderFlag(): void
    {
        $report = $this->checker->run($this->config, self::FIXTURES . '/wrong-group-order/messages', '/');

        self::assertFalse($report->isOk());
        $verdict = $report->verdictByDomain['messages'];
        self::assertTrue($verdict->groupsMatch);
        self::assertFalse($verdict->groupOrder);
    }

    #[Test]
    public function wrongUnitOrderFailsUnitOrderFlag(): void
    {
        $report = $this->checker->run($this->config, self::FIXTURES . '/wrong-unit-order/messages', '/');

        self::assertFalse($report->isOk());
        $verdict = $report->verdictByDomain['messages'];
        self::assertTrue($verdict->unitsMatch);
        self::assertFalse($verdict->unitOrder);
    }

    #[Test]
    public function sourceMismatchFailsSourcesMatchFlag(): void
    {
        $report = $this->checker->run($this->config, self::FIXTURES . '/source-mismatch/messages', '/');

        self::assertFalse($report->isOk());
        $verdict = $report->verdictByDomain['messages'];
        self::assertFalse($verdict->sourcesMatch);
    }

    #[Test]
    public function nonFinalStateFailsFinalOkFlag(): void
    {
        $report = $this->checker->run($this->config, self::FIXTURES . '/non-final-state/messages', '/');

        self::assertFalse($report->isOk());
        $verdict = $report->verdictByDomain['messages'];
        self::assertFalse($verdict->finalOk);
    }

    #[Test]
    public function duplicateUnitIdMakesFileInvalid(): void
    {
        $report = $this->checker->run($this->config, self::FIXTURES . '/duplicate-unit-id/messages', '/');

        self::assertFalse($report->isOk());
        $catalog = $report->project->getCatalog();
        self::assertFalse($catalog['messages']['en']->valid);
    }
}
