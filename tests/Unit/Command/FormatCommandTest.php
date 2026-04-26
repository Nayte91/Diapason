<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Command;

use Diapason\Application;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class FormatCommandTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../../Fixtures';

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
    public function applyModeRewritesUnformattedFileToCanonicalForm(): void
    {
        $sourceFixture = self::FIXTURES . '/unformatted/messages.before.xlf';
        $targetFixture = self::FIXTURES . '/unformatted/messages.after.xlf';
        $sourceCopy = $this->tempDir . '/messages.en.xlf';
        $targetCopy = $this->tempDir . '/messages.fr.xlf';
        copy($sourceFixture, $sourceCopy);
        copy($targetFixture, $targetCopy);
        $sourceMd5Before = md5_file($sourceCopy);

        $this->tester->execute([
            'domain' => $this->tempDir . '/messages',
        ]);

        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
        self::assertNotSame($sourceMd5Before, md5_file($sourceCopy));
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
