<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Command;

use Diapason\Application;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CheckCommandTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../../Fixtures';

    private CommandTester $tester;
    private string $tempDir;

    protected function setUp(): void
    {
        $application = new Application();
        $application->setAutoExit(false);
        $this->tester = new CommandTester($application->find('check'));

        $this->tempDir = sys_get_temp_dir() . '/diapason-test-' . uniqid('', true);
        mkdir($this->tempDir, 0o755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    #[Test]
    public function checkDoesNotMutateFilesEvenWhenFormatterIsActive(): void
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
        ]);

        self::assertSame($sourceMd5Before, md5_file($sourceCopy));
        self::assertSame($targetMd5Before, md5_file($targetCopy));
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
