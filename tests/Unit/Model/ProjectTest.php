<?php

declare(strict_types=1);

namespace Diapason\Tests\Unit\Model;

use Diapason\Model\Issue;
use Diapason\Model\IssueList;
use Diapason\Model\Project;
use Diapason\Model\Severity;
use Diapason\Model\XliffFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProjectTest extends TestCase
{
    #[Test]
    public function addFileIndexesByDomainAndLocale(): void
    {
        $project = new Project();
        $messagesFr = new XliffFile(
            path: '/tmp/messages.fr.xlf',
            filename: 'messages.fr.xlf',
            domain: 'messages',
            locale: 'fr',
            srcLang: 'en',
            isSource: false,
            groups: [],
            units: [],
            issues: new IssueList(),
            valid: true,
        );
        $project->addFile($messagesFr);

        $catalog = $project->getCatalog();

        self::assertArrayHasKey('messages', $catalog);
        self::assertArrayHasKey('fr', $catalog['messages']);
        self::assertSame($messagesFr, $catalog['messages']['fr']);
    }

    #[Test]
    public function bundlesYieldsFilesAlphabeticallySortedByLocale(): void
    {
        $project = new Project();
        $project->addFile(new XliffFile(
            path: '/tmp/messages.fr.xlf',
            filename: 'messages.fr.xlf',
            domain: 'messages',
            locale: 'fr',
            srcLang: 'en',
            isSource: false,
            groups: [],
            units: [],
            issues: new IssueList(),
            valid: true,
        ));
        $project->addFile(new XliffFile(
            path: '/tmp/messages.en.xlf',
            filename: 'messages.en.xlf',
            domain: 'messages',
            locale: 'en',
            srcLang: 'en',
            isSource: true,
            groups: [],
            units: [],
            issues: new IssueList(),
            valid: true,
        ));
        $project->addFile(new XliffFile(
            path: '/tmp/messages.de.xlf',
            filename: 'messages.de.xlf',
            domain: 'messages',
            locale: 'de',
            srcLang: 'en',
            isSource: false,
            groups: [],
            units: [],
            issues: new IssueList(),
            valid: true,
        ));

        $bundles = iterator_to_array($project->bundles(), false);

        self::assertCount(1, $bundles);
        $files = $bundles[0]->files;
        self::assertSame('de', $files[0]->locale);
        self::assertSame('en', $files[1]->locale);
        self::assertSame('fr', $files[2]->locale);
    }

    #[Test]
    public function bundlesYieldsOneBundlePerDomain(): void
    {
        $project = new Project();
        $project->addFile(new XliffFile(
            path: '/tmp/messages.en.xlf',
            filename: 'messages.en.xlf',
            domain: 'messages',
            locale: 'en',
            srcLang: 'en',
            isSource: true,
            groups: [],
            units: [],
            issues: new IssueList(),
            valid: true,
        ));
        $project->addFile(new XliffFile(
            path: '/tmp/security.en.xlf',
            filename: 'security.en.xlf',
            domain: 'security',
            locale: 'en',
            srcLang: 'en',
            isSource: true,
            groups: [],
            units: [],
            issues: new IssueList(),
            valid: true,
        ));

        $bundles = iterator_to_array($project->bundles(), false);

        self::assertCount(2, $bundles);
        $domains = array_map(static fn(\Diapason\Model\DomainBundle $bundle): string => $bundle->domain, $bundles);
        self::assertContains('messages', $domains);
        self::assertContains('security', $domains);
    }

    #[Test]
    public function addIssueAccumulatesGlobalIssues(): void
    {
        $project = new Project();
        $issue = new Issue(severity: Severity::Error, checkId: 'global', file: '', message: 'oops');

        $project->addIssue($issue);

        self::assertInstanceOf(IssueList::class, $project->getGlobalIssues());
        self::assertSame([$issue], $project->getGlobalIssues()->all());
    }

    #[Test]
    public function getGlobalIssuesIsEmptyByDefault(): void
    {
        $project = new Project();

        self::assertTrue($project->getGlobalIssues()->isEmpty());
    }
}
