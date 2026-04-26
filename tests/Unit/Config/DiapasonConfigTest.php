<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Config;

use Diapason\Check\IssueIdFilterCheck;
use Diapason\Config\DiapasonConfig;
use Diapason\Config\FormatMode;
use Diapason\Formatter\XliffFormatter;
use Diapason\Reporter\JsonReporter;
use Diapason\Reporter\TableReporter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DiapasonConfigTest extends TestCase
{
    #[Test]
    public function configureBuildsBlankConfigWithApplyModeAndTableReporter(): void
    {
        $config = DiapasonConfig::configure();

        self::assertSame([], $config->getPaths());
        self::assertSame([], $config->getChecks());
        self::assertNull($config->getFormatter());
        self::assertSame(FormatMode::Apply, $config->getFormatMode());
        self::assertInstanceOf(TableReporter::class, $config->getReporter());
    }

    #[Test]
    public function defaultsExposesEightChecksAndXliffFormatterAndApplyModeAndTableReporter(): void
    {
        $config = DiapasonConfig::defaults();

        self::assertSame(['translations/*.xlf'], $config->getPaths());
        self::assertCount(8, $config->getChecks());
        self::assertInstanceOf(XliffFormatter::class, $config->getFormatter());
        self::assertSame(FormatMode::Apply, $config->getFormatMode());
        self::assertInstanceOf(TableReporter::class, $config->getReporter());
    }

    #[Test]
    public function builderClonesAndPropagatesPaths(): void
    {
        $original = DiapasonConfig::configure();

        $modified = $original->withPaths('foo/*.xlf', 'bar/*.xlf');

        self::assertNotSame($original, $modified);
        self::assertSame([], $original->getPaths());
        self::assertSame(['foo/*.xlf', 'bar/*.xlf'], $modified->getPaths());
    }

    #[Test]
    public function builderClonesAndPropagatesChecks(): void
    {
        $check = new IssueIdFilterCheck('xml.well-formed', 'desc', 'xml.well-formed');
        $original = DiapasonConfig::configure();

        $modified = $original->withChecks($check);

        self::assertSame([], $original->getChecks());
        self::assertSame([$check], $modified->getChecks());
    }

    #[Test]
    public function builderClonesAndPropagatesFormatter(): void
    {
        $formatter = new XliffFormatter();
        $original = DiapasonConfig::configure();

        $modified = $original->withFormatter($formatter);

        self::assertNull($original->getFormatter());
        self::assertSame($formatter, $modified->getFormatter());
    }

    #[Test]
    public function builderClonesAndPropagatesReporter(): void
    {
        $jsonReporter = new JsonReporter();
        $original = DiapasonConfig::configure();

        $modified = $original->withReporter($jsonReporter);

        self::assertInstanceOf(TableReporter::class, $original->getReporter());
        self::assertSame($jsonReporter, $modified->getReporter());
    }

    #[Test]
    #[DataProvider('provideFormatModes')]
    public function withFormatModeClonesAndPropagates(FormatMode $mode): void
    {
        $original = DiapasonConfig::configure();

        $modified = $original->withFormatMode($mode);

        self::assertNotSame($original, $modified);
        self::assertSame(FormatMode::Apply, $original->getFormatMode());
        self::assertSame($mode, $modified->getFormatMode());
    }

    /** @return iterable<string, array{FormatMode}> */
    public static function provideFormatModes(): iterable
    {
        yield 'disabled' => [FormatMode::Disabled];
        yield 'apply'    => [FormatMode::Apply];
        yield 'dry-run'  => [FormatMode::DryRun];
    }
}
