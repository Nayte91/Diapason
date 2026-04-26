<?php

declare(strict_types=1);

namespace Diapason\Tests\Integration;

use Diapason\Application;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class FormatDryRunTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures';

    private CommandTester $tester;
    private string $tempDir;

    protected function setUp(): void
    {
        $application = new Application();
        $application->setAutoExit(false);
        $this->tester = new CommandTester($application->find('format'));

        $this->tempDir = sys_get_temp_dir() . '/diapason-test-' . uniqid('', true);
        mkdir($this->tempDir, 0o755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    #[Test]
    public function dryRunOnAlreadyFormattedFileReportsNoChanges(): void
    {
        $this->tester->execute([
            'domain' => self::FIXTURES . '/valid-2locales/messages',
            '--dry-run' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
        self::assertStringContainsString('No formatting changes would be applied.', $this->tester->getDisplay());
    }

    #[Test]
    public function dryRunOnUnformattedFileReportsDiffWithoutMutatingSource(): void
    {
        $sourceFixture = self::FIXTURES . '/unformatted/messages.before.xlf';
        $targetFixture = self::FIXTURES . '/unformatted/messages.after.xlf';
        $sourceCopy = $this->tempDir . '/messages.en.xlf';
        $targetCopy = $this->tempDir . '/messages.fr.xlf';
        copy($sourceFixture, $sourceCopy);
        copy($targetFixture, $targetCopy);
        $sourceMd5Before = md5_file($sourceCopy);
        $targetMd5Before = md5_file($targetCopy);

        $this->tester->execute([
            'domain' => $this->tempDir . '/messages',
            '--dry-run' => true,
        ]);

        $display = $this->tester->getDisplay();
        self::assertStringContainsString('--- before', $display);
        self::assertStringContainsString('+++ after', $display);
        self::assertStringContainsString('Summary:', $display);
        self::assertSame($sourceMd5Before, md5_file($sourceCopy));
        self::assertSame($targetMd5Before, md5_file($targetCopy));
    }

    #[Test]
    public function dryRunWithJsonFormatExposesPreviewsKey(): void
    {
        $sourceFixture = self::FIXTURES . '/unformatted/messages.before.xlf';
        $targetFixture = self::FIXTURES . '/unformatted/messages.after.xlf';
        copy($sourceFixture, $this->tempDir . '/messages.en.xlf');
        copy($targetFixture, $this->tempDir . '/messages.fr.xlf');

        $this->tester->execute([
            'domain' => $this->tempDir . '/messages',
            '--format' => 'json',
            '--dry-run' => true,
        ]);

        $decoded = json_decode($this->tester->getDisplay(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        self::assertArrayHasKey('previews', $decoded);
        self::assertIsArray($decoded['previews']);
        self::assertNotEmpty($decoded['previews']);
        $firstPreview = (array) reset($decoded['previews']);
        self::assertArrayHasKey('diff', $firstPreview);
        self::assertArrayHasKey('linesAdded', $firstPreview);
        self::assertArrayHasKey('linesRemoved', $firstPreview);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $entries = scandir($path);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $path . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($full)) {
                $this->removeDirectory($full);
                continue;
            }
            unlink($full);
        }

        rmdir($path);
    }
}
