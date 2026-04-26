<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Check;

use Diapason\Check\CheckSample;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CheckSampleTest extends TestCase
{
    #[Test]
    public function exposesTitleAndXmlPayloads(): void
    {
        $sample = new CheckSample(
            title: 'Cross-locale group order',
            xmlBefore: '<xliff><file/></xliff>',
            xmlAfter: '<xliff><file id="ok"/></xliff>',
        );

        self::assertSame('Cross-locale group order', $sample->title);
        self::assertSame('<xliff><file/></xliff>', $sample->xmlBefore);
        self::assertSame('<xliff><file id="ok"/></xliff>', $sample->xmlAfter);
    }

    #[Test]
    public function xmlAfterDefaultsToNullForValidationOnlyChecks(): void
    {
        $sample = new CheckSample(title: 'Bare invalid input', xmlBefore: '<not-xliff/>');

        self::assertNull($sample->xmlAfter);
    }
}
