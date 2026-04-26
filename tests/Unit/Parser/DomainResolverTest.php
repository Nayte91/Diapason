<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Parser;

use Diapason\Exception\InputException;
use Diapason\Parser\DomainResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DomainResolverTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../../Fixtures';

    /** @param array{0: string, 1: string}|null $expected */
    #[Test]
    #[DataProvider('provideSplitFilenameSamples')]
    public function splitFilenameParsesDomainAndLocale(string $filename, ?array $expected): void
    {
        $resolver = new DomainResolver();

        self::assertSame($expected, $resolver->splitFilename($filename));
    }

    /** @return iterable<string, array{string, array{0: string, 1: string}|null}> */
    public static function provideSplitFilenameSamples(): iterable
    {
        yield 'simple domain'              => ['messages.en.xlf', ['messages', 'en']];
        yield 'composite suffix'           => ['messages+intl-icu.fr.xlf', ['messages+intl-icu', 'fr']];
        yield 'no extension'               => ['noextension', null];
        yield 'no separator before locale' => ['noseparator.xlf', null];
        yield 'splits on rightmost dot'    => ['foo.bar.baz.xlf', ['foo.bar', 'baz']];
        yield 'wrong extension'            => ['messages.en.xml', null];
    }

    #[Test]
    public function resolveDomainPrefixReturnsAllMatchingFilesSorted(): void
    {
        $resolver = new DomainResolver();
        $prefix = self::FIXTURES . '/valid-2locales/messages';

        $paths = $resolver->resolveDomainPrefix($prefix, getcwd() ?: '.');

        self::assertCount(2, $paths);
        self::assertStringEndsWith('messages.en.xlf', $paths[0]);
        self::assertStringEndsWith('messages.fr.xlf', $paths[1]);
    }

    #[Test]
    public function resolveDomainPrefixThrowsWhenNoMatch(): void
    {
        $resolver = new DomainResolver();

        $this->expectException(InputException::class);
        $this->expectExceptionMessage("No files matching 'tests/Fixtures/does-not-exist/missing.*.xlf' found");

        $resolver->resolveDomainPrefix('tests/Fixtures/does-not-exist/missing', '/home/nayte/OpenSource/Diapason');
    }

    #[Test]
    public function resolveGlobsExpandsWildcardsAndDeduplicates(): void
    {
        $resolver = new DomainResolver();

        $paths = $resolver->resolveGlobs(
            [self::FIXTURES . '/valid-2locales/*.xlf', self::FIXTURES . '/valid-2locales/*.xlf'],
            getcwd() ?: '.',
        );

        self::assertCount(2, $paths);
        self::assertStringEndsWith('messages.en.xlf', $paths[0]);
        self::assertStringEndsWith('messages.fr.xlf', $paths[1]);
    }

    #[Test]
    public function resolveGlobsReturnsEmptyArrayWhenNoMatches(): void
    {
        $resolver = new DomainResolver();

        $paths = $resolver->resolveGlobs(['nonexistent/*.xlf'], '/tmp');

        self::assertSame([], $paths);
    }

    #[Test]
    public function resolveGlobsResultsAreSortedAlphabetically(): void
    {
        $resolver = new DomainResolver();

        $paths = $resolver->resolveGlobs([self::FIXTURES . '/valid-2locales/*.xlf'], getcwd() ?: '.');

        $sorted = $paths;
        sort($sorted);
        self::assertSame($sorted, $paths);
    }
}
