<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Check;

use Diapason\Check\FinalStateCheck;
use Diapason\Model\DomainBundle;
use Diapason\Model\DomainVerdict;
use Diapason\Model\IssueList;
use Diapason\Model\Segment;
use Diapason\Model\Unit;
use Diapason\Model\XliffFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FinalStateCheckTest extends TestCase
{
    #[Test]
    public function exposesStableIdAndSummary(): void
    {
        $check = new FinalStateCheck();
        $definition = $check->getDefinition();

        self::assertSame('state.final', $definition->id);
        self::assertNotSame('', $definition->summary);
    }

    #[Test]
    public function happyPathYieldsNothingAndKeepsFinalOkTrue(): void
    {
        $french = new XliffFile(
            path: '/tmp/messages.fr.xlf',
            filename: 'messages.fr.xlf',
            domain: 'messages',
            locale: 'fr',
            srcLang: 'en',
            isSource: false,
            groups: [],
            units: [
                'a' => new Unit(
                    id: 'a',
                    source: 'A',
                    groupId: 'g',
                    segments: [new Segment(state: 'final', source: 'A', target: 'a-fr')],
                ),
            ],
            issues: new IssueList(),
            valid: true,
        );
        $bundle = new DomainBundle(domain: 'messages', files: [$french]);
        $verdict = new DomainVerdict('messages');

        $issues = iterator_to_array(new FinalStateCheck()->check($bundle, $verdict), false);

        self::assertSame([], $issues);
    }

    #[Test]
    public function detectsNonFinalSegmentsInTargetFiles(): void
    {
        $french = new XliffFile(
            path: '/tmp/messages.fr.xlf',
            filename: 'messages.fr.xlf',
            domain: 'messages',
            locale: 'fr',
            srcLang: 'en',
            isSource: false,
            groups: [],
            units: [
                'a' => new Unit(
                    id: 'a',
                    source: 'A',
                    groupId: 'g',
                    segments: [new Segment(state: 'initial', source: 'A', target: null)],
                ),
            ],
            issues: new IssueList(),
            valid: true,
        );
        $bundle = new DomainBundle(domain: 'messages', files: [$french]);
        $verdict = new DomainVerdict('messages');

        $issues = iterator_to_array(new FinalStateCheck()->check($bundle, $verdict), false);

        self::assertCount(1, $issues);
        self::assertSame('state.nonFinal', $issues[0]->checkId);
        self::assertStringContainsString('1 units with non-final segments', $issues[0]->message);
    }

    #[Test]
    public function nullStateCountsAsNonFinal(): void
    {
        $french = new XliffFile(
            path: '/tmp/messages.fr.xlf',
            filename: 'messages.fr.xlf',
            domain: 'messages',
            locale: 'fr',
            srcLang: 'en',
            isSource: false,
            groups: [],
            units: [
                'a' => new Unit(
                    id: 'a',
                    source: 'A',
                    groupId: 'g',
                    segments: [new Segment(state: null, source: 'A', target: null)],
                ),
            ],
            issues: new IssueList(),
            valid: true,
        );
        $bundle = new DomainBundle(domain: 'messages', files: [$french]);
        $verdict = new DomainVerdict('messages');

        $issues = iterator_to_array(new FinalStateCheck()->check($bundle, $verdict), false);

        self::assertCount(1, $issues);
    }

    #[Test]
    public function ignoresSourceFiles(): void
    {
        $english = new XliffFile(
            path: '/tmp/messages.en.xlf',
            filename: 'messages.en.xlf',
            domain: 'messages',
            locale: 'en',
            srcLang: 'en',
            isSource: true,
            groups: [],
            units: [
                'a' => new Unit(
                    id: 'a',
                    source: 'A',
                    groupId: 'g',
                    segments: [new Segment(state: null, source: 'A', target: null)],
                ),
            ],
            issues: new IssueList(),
            valid: true,
        );
        $bundle = new DomainBundle(domain: 'messages', files: [$english]);
        $verdict = new DomainVerdict('messages');

        $issues = iterator_to_array(new FinalStateCheck()->check($bundle, $verdict), false);

        self::assertSame([], $issues);
    }
}
