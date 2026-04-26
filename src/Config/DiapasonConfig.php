<?php

declare(strict_types=1);

namespace Diapason\Config;

use Diapason\Check\CheckCategory;
use Diapason\Check\CheckInterface;
use Diapason\Check\FinalStateCheck;
use Diapason\Check\GroupsConsistencyCheck;
use Diapason\Check\IssueIdFilterCheck;
use Diapason\Check\SourcesConsistencyCheck;
use Diapason\Check\UnitsConsistencyCheck;
use Diapason\Formatter\FormatterInterface;
use Diapason\Formatter\XliffFormatter;
use Diapason\Reporter\ReporterInterface;
use Diapason\Reporter\TableReporter;

final class DiapasonConfig implements DiapasonConfigInterface
{
    /**
     * @param list<string>         $paths
     * @param list<CheckInterface> $checks
     */
    private function __construct(
        private array $paths,
        private array $checks,
        private ?FormatterInterface $formatter,
        private FormatMode $formatMode,
        private ReporterInterface $reporter,
    ) {}

    public static function configure(): self
    {
        return new self(
            paths: [],
            checks: [],
            formatter: null,
            formatMode: FormatMode::Apply,
            reporter: new TableReporter(),
        );
    }

    public static function defaults(): self
    {
        return new self(
            paths: ['translations/*.xlf'],
            checks: [
                new IssueIdFilterCheck(
                    'xml.well-formed',
                    'XLIFF file must be well-formed XML.',
                    'xml.well-formed',
                ),
                new IssueIdFilterCheck(
                    'xliff.namespace',
                    'Root element must be <xliff> in the XLIFF 2.0/2.1 namespace with a valid version.',
                    'xliff.namespace',
                ),
                new IssueIdFilterCheck(
                    'xliff.srcLang',
                    'XLIFF root element must declare srcLang attribute.',
                    'xliff.srcLang',
                ),
                new IssueIdFilterCheck(
                    'xliff.duplicateId',
                    'Group and unit IDs must be unique within an XLIFF file.',
                    ['xliff.duplicate'],
                    CheckCategory::PerFile,
                ),
                new GroupsConsistencyCheck(),
                new UnitsConsistencyCheck(),
                new SourcesConsistencyCheck(),
                new FinalStateCheck(),
            ],
            formatter: new XliffFormatter(),
            formatMode: FormatMode::Apply,
            reporter: new TableReporter(),
        );
    }

    public function withPaths(string ...$globs): self
    {
        $clone = clone $this;
        $clone->paths = array_values($globs);

        return $clone;
    }

    public function withChecks(CheckInterface ...$checks): self
    {
        $clone = clone $this;
        $clone->checks = array_values($checks);

        return $clone;
    }

    public function withFormatter(?FormatterInterface $formatter): self
    {
        $clone = clone $this;
        $clone->formatter = $formatter;

        return $clone;
    }

    public function withFormatMode(FormatMode $mode): self
    {
        $clone = clone $this;
        $clone->formatMode = $mode;

        return $clone;
    }

    public function withReporter(ReporterInterface $reporter): self
    {
        $clone = clone $this;
        $clone->reporter = $reporter;

        return $clone;
    }

    /** @return list<string> */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /** @return list<CheckInterface> */
    public function getChecks(): array
    {
        return $this->checks;
    }

    public function getFormatter(): ?FormatterInterface
    {
        return $this->formatter;
    }

    public function getFormatMode(): FormatMode
    {
        return $this->formatMode;
    }

    public function getReporter(): ReporterInterface
    {
        return $this->reporter;
    }
}
