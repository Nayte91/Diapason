<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Config;

use Diapason\Config\ConfigLoader;
use Diapason\Config\DiapasonConfig;
use Diapason\Config\FormatMode;
use Diapason\Exception\ConfigException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigLoaderTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../../Fixtures';

    #[Test]
    public function loadsDefaultsWhenNoConfigFileExistsInCwd(): void
    {
        $loader = new ConfigLoader();

        $config = $loader->load(null, sys_get_temp_dir());

        self::assertEquals(DiapasonConfig::defaults(), $config);
    }

    #[Test]
    public function loadsDiapasonPhpFromCwdWhenAvailable(): void
    {
        $loader = new ConfigLoader();

        $config = $loader->load(null, self::FIXTURES . '/config');

        self::assertSame(['custom/*.xlf'], $config->getPaths());
        self::assertCount(2, $config->getChecks());
        self::assertSame(FormatMode::Disabled, $config->getFormatMode());
    }

    #[Test]
    public function fallsBackToDiapasonDistPhpWhenOnlyDistExists(): void
    {
        $loader = new ConfigLoader();

        $config = $loader->load(null, self::FIXTURES . '/config-only-dist');

        self::assertSame(['dist/*.xlf'], $config->getPaths());
    }

    #[Test]
    public function loadsDotfileVariantWhenAvailable(): void
    {
        $loader = new ConfigLoader();

        $config = $loader->load(null, self::FIXTURES . '/config-dotfile');

        self::assertSame(['dotfile/*.xlf'], $config->getPaths());
    }

    #[Test]
    public function fallsBackToDotfileDistWhenOnlyDotfileDistExists(): void
    {
        $loader = new ConfigLoader();

        $config = $loader->load(null, self::FIXTURES . '/config-dotfile-only-dist');

        self::assertSame(['dotfile-dist/*.xlf'], $config->getPaths());
    }

    #[Test]
    public function loadsExplicitPathWhenProvided(): void
    {
        $loader = new ConfigLoader();

        $config = $loader->load(self::FIXTURES . '/config/diapason.php', sys_get_temp_dir());

        self::assertSame(['custom/*.xlf'], $config->getPaths());
    }

    #[Test]
    public function throwsConfigExceptionWhenExplicitPathDoesNotExist(): void
    {
        $loader = new ConfigLoader();

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Configuration file not found:');

        $loader->load('/nonexistent/diapason.php', sys_get_temp_dir());
    }

    #[Test]
    public function throwsConfigExceptionWhenFileReturnsNonConfigInstance(): void
    {
        $loader = new ConfigLoader();

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('must return a Diapason\\Config\\DiapasonConfig instance');

        $loader->load(self::FIXTURES . '/config/diapason-bad.php', sys_get_temp_dir());
    }
}
