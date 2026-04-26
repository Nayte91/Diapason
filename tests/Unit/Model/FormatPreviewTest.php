<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Model;

use Diapason\Model\FormatPreview;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FormatPreviewTest extends TestCase
{
    #[Test]
    public function fromBeforeAfterReportsZeroChangesWhenInputsMatch(): void
    {
        $content = "line one\nline two\nline three\n";

        $preview = FormatPreview::fromBeforeAfter('/tmp/messages.en.xlf', $content, $content);

        self::assertSame('/tmp/messages.en.xlf', $preview->path);
        self::assertSame(0, $preview->linesAdded);
        self::assertSame(0, $preview->linesRemoved);
    }

    #[Test]
    public function fromBeforeAfterCountsAddedAndRemovedLines(): void
    {
        $before = "alpha\nbravo\ncharlie\n";
        $after = "alpha\nbravo-edited\ncharlie\ndelta\n";

        $preview = FormatPreview::fromBeforeAfter('/tmp/messages.en.xlf', $before, $after);

        self::assertStringContainsString('--- before', $preview->diff);
        self::assertStringContainsString('+++ after', $preview->diff);
        self::assertSame(2, $preview->linesAdded);
        self::assertSame(1, $preview->linesRemoved);
    }

    #[Test]
    public function fromBeforeAfterReportsSingleLineSwap(): void
    {
        $before = "alpha\nbravo\ncharlie\n";
        $after = "alpha\nBRAVO\ncharlie\n";

        $preview = FormatPreview::fromBeforeAfter('/tmp/messages.en.xlf', $before, $after);

        self::assertSame(1, $preview->linesAdded);
        self::assertSame(1, $preview->linesRemoved);
    }
}
