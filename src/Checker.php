<?php

declare(strict_types=1);

namespace Diapason;

use Diapason\Check\CrossLocaleCheckInterface;
use Diapason\Check\FinalStateCheck;
use Diapason\Check\GroupsConsistencyCheck;
use Diapason\Check\SourcesConsistencyCheck;
use Diapason\Check\UnitsConsistencyCheck;
use Diapason\Config\DiapasonConfig;
use Diapason\Config\FormatMode;
use Diapason\Formatter\FormatterInterface;
use Diapason\Model\DomainBundle;
use Diapason\Model\DomainVerdict;
use Diapason\Model\FormatPreview;
use Diapason\Model\Issue;
use Diapason\Model\IssueList;
use Diapason\Model\Project;
use Diapason\Model\ProjectReport;
use Diapason\Parser\DomainResolver;
use Diapason\Parser\XliffParser;
use DOMDocument;

final readonly class Checker
{
    /**
     * Maps an Issue::checkId emitted by a CrossLocaleCheck to the DomainVerdict
     * mutator that must be applied. Per-file Issues (xml.*, xliff.*) are not
     * listed: they never affect the cross-locale verdict.
     *
     * @var array<string, string>
     */
    private const array FLAG_MUTATORS = [
        GroupsConsistencyCheck::CHECK_ID_MISSING => 'withGroupsInconsistent',
        GroupsConsistencyCheck::CHECK_ID_EXTRA => 'withGroupsInconsistent',
        GroupsConsistencyCheck::CHECK_ID_ORDER => 'withGroupOrderInconsistent',
        UnitsConsistencyCheck::CHECK_ID_MISSING => 'withUnitsInconsistent',
        UnitsConsistencyCheck::CHECK_ID_EXTRA => 'withUnitsInconsistent',
        UnitsConsistencyCheck::CHECK_ID_ORDER => 'withUnitOrderInconsistent',
        SourcesConsistencyCheck::CHECK_ID_MISMATCH => 'withSourcesInconsistent',
        FinalStateCheck::CHECK_ID_NON_FINAL => 'withFinalNotReached',
    ];

    public function __construct(
        private XliffParser $parser,
        private DomainResolver $domainResolver,
    ) {}

    public function run(DiapasonConfig $config, ?string $domainPrefix, string $cwd): ProjectReport
    {
        $paths = $domainPrefix !== null
            ? $this->domainResolver->resolveDomainPrefix($domainPrefix, $cwd)
            : $this->domainResolver->resolveGlobs($config->getPaths(), $cwd);

        $previews = $this->processFormat($config, $paths);

        $project = new Project();
        foreach ($paths as $path) {
            $project->addFile($this->parser->parseFile($path));
        }

        /** @var array<string, IssueList> $issuesByDomain */
        $issuesByDomain = [];
        /** @var array<string, DomainVerdict> $verdictByDomain */
        $verdictByDomain = [];

        foreach ($project->bundles() as $bundle) {
            [$verdict, $issues] = $this->runCrossLocaleChecks($bundle, $config);

            $verdictByDomain[$bundle->domain] = $verdict;
            $issuesByDomain[$bundle->domain] = $issues;
        }

        return new ProjectReport($project, $verdictByDomain, $issuesByDomain, $previews);
    }

    /**
     * @return array{0: DomainVerdict, 1: IssueList}
     */
    private function runCrossLocaleChecks(DomainBundle $bundle, DiapasonConfig $config): array
    {
        $verdict = new DomainVerdict($bundle->domain);
        $issues = new IssueList();

        foreach ($config->getChecks() as $check) {
            if (!$check instanceof CrossLocaleCheckInterface) {
                continue;
            }

            $produced = iterator_to_array($check->check($bundle, $verdict), false);
            $issues->addAll($produced);
            $verdict = $this->projectVerdict($verdict, $produced);
        }

        return [$verdict, $issues];
    }

    /** @param list<Issue> $issues */
    private function projectVerdict(DomainVerdict $verdict, array $issues): DomainVerdict
    {
        foreach ($issues as $issue) {
            $mutator = self::FLAG_MUTATORS[$issue->checkId] ?? null;
            if ($mutator === null) {
                continue;
            }

            $verdict = $verdict->{$mutator}();
        }

        return $verdict;
    }

    /**
     * @param  list<string> $paths
     * @return array<string, FormatPreview>
     */
    private function processFormat(DiapasonConfig $config, array $paths): array
    {
        $mode = $config->getFormatMode();
        if ($mode === FormatMode::Disabled) {
            return [];
        }

        $formatter = $config->getFormatter();
        if (!$formatter instanceof FormatterInterface) {
            return [];
        }

        $previews = [];
        foreach ($paths as $path) {
            $serialized = $this->formatToString($formatter, $path);
            if ($serialized === null) {
                continue;
            }

            if ($mode === FormatMode::Apply) {
                @file_put_contents($path, $serialized);
                continue;
            }

            $original = @file_get_contents($path);
            if ($original === false || $original === $serialized) {
                continue;
            }

            $previews[$path] = FormatPreview::fromBeforeAfter($path, $original, $serialized);
        }

        return $previews;
    }

    private function formatToString(FormatterInterface $formatter, string $path): ?string
    {
        $doc = new DOMDocument();
        $previousUseInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $loaded = $doc->load($path, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseInternalErrors);
        }

        if ($loaded === false) {
            return null;
        }

        $formatter->format($doc);

        $root = $doc->documentElement;
        if (!$root instanceof \DOMElement) {
            return null;
        }

        $body = $doc->saveXML($root);
        if ($body === false) {
            return null;
        }

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $body . "\n";
    }
}
