<?php

declare(strict_types=1);

namespace Diapason\Tests\Integration;

use Diapason\Application;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CheckCommandTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures';

    private CommandTester $tester;

    protected function setUp(): void
    {
        $application = new Application();
        $application->setAutoExit(false);
        $this->tester = new CommandTester($application->find('check'));
    }

    #[Test]
    public function happyPathReturnsSuccessExitCode(): void
    {
        $this->tester->execute([
            'domain' => self::FIXTURES . '/valid-2locales/messages',
        ]);

        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
        self::assertStringContainsString('✅ Everything is fine!', $this->tester->getDisplay());
    }

    #[Test]
    public function missingGroupReturnsFailureExitCode(): void
    {
        $this->tester->execute([
            'domain' => self::FIXTURES . '/missing-group/messages',
        ]);

        self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
        self::assertStringContainsString('❌', $this->tester->getDisplay());
    }

    #[Test]
    public function unknownDomainReturnsInputErrorExitCode(): void
    {
        $this->tester->execute([
            'domain' => self::FIXTURES . '/does-not-exist/missing',
        ]);

        self::assertSame(2, $this->tester->getStatusCode());
        self::assertStringContainsString('No files matching', $this->tester->getDisplay());
    }

    #[Test]
    public function badConfigReturnsInputErrorExitCode(): void
    {
        $this->tester->execute([
            'domain' => self::FIXTURES . '/valid-2locales/messages',
            '--config' => self::FIXTURES . '/config/diapason-bad.php',
        ]);

        self::assertSame(2, $this->tester->getStatusCode());
        self::assertStringContainsString('Config error', $this->tester->getDisplay());
    }

    #[Test]
    public function jsonFormatProducesParseableOutput(): void
    {
        $this->tester->execute([
            'domain' => self::FIXTURES . '/source-mismatch/messages',
            '--format' => 'json',
        ]);

        self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
        $decoded = json_decode($this->tester->getDisplay(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        self::assertFalse($decoded['ok']);
        self::assertIsArray($decoded['domains']);
        self::assertArrayHasKey('messages', $decoded['domains']);
    }
}
