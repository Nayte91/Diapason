<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Check;

use Diapason\Check\CheckCategory;
use Diapason\Check\CheckDefinition;
use Diapason\Check\CheckSample;
use Diapason\Model\Severity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CheckDefinitionTest extends TestCase
{
    #[Test]
    public function exposesAllConstructorArgumentsAsReadonlyProperties(): void
    {
        $sample = new CheckSample(title: 'Missing srcLang', xmlBefore: '<xliff/>');
        $definition = new CheckDefinition(
            id: 'xliff.srcLang',
            summary: 'XLIFF root must declare srcLang.',
            category: CheckCategory::Structural,
            defaultSeverity: Severity::Error,
            description: 'The XLIFF root element MUST declare a srcLang attribute.',
            samples: [$sample],
        );

        self::assertSame('xliff.srcLang', $definition->id);
        self::assertSame('XLIFF root must declare srcLang.', $definition->summary);
        self::assertSame(CheckCategory::Structural, $definition->category);
        self::assertSame(Severity::Error, $definition->defaultSeverity);
        self::assertSame('The XLIFF root element MUST declare a srcLang attribute.', $definition->description);
        self::assertSame([$sample], $definition->samples);
    }

    #[Test]
    public function descriptionAndSamplesDefaultToNullAndEmptyList(): void
    {
        $definition = new CheckDefinition(
            id: 'xml.well-formed',
            summary: 'XLIFF file must be well-formed XML.',
            category: CheckCategory::Structural,
            defaultSeverity: Severity::Error,
        );

        self::assertNull($definition->description);
        self::assertSame([], $definition->samples);
    }
}
