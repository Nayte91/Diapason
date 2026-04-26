<?php

declare(strict_types=1);

namespace Diapason\Command;

use Diapason\Config\FormatMode;
use Diapason\Reporter\DiffReporter;
use Diapason\Reporter\JsonReporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'format', description: 'Format XLIFF files in place and run all checks.')]
final class FormatCommand extends AbstractDiapasonCommand
{
    #[\Override]
    protected function configure(): void
    {
        parent::configure();
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            "Show what would change without writing any files (unified diff per file).",
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cwd = $this->resolveCwd();
        $config = $this->loadConfig($input, $cwd, $output);
        if (!$config instanceof \Diapason\Config\DiapasonConfig) {
            return self::EXIT_INPUT_ERROR;
        }

        $dryRun = (bool) $input->getOption('dry-run');
        $config = $config->withFormatMode($dryRun ? FormatMode::DryRun : FormatMode::Apply);

        if ($dryRun && !$config->getReporter() instanceof JsonReporter) {
            $config = $config->withReporter(new DiffReporter($config->getReporter()));
        }

        $report = $this->runChecker($config, $this->stringArgument($input, 'domain'), $cwd, $output);
        if (!$report instanceof \Diapason\Model\ProjectReport) {
            return self::EXIT_INPUT_ERROR;
        }

        $config->getReporter()->report($report, $output);

        return $report->isOk() ? Command::SUCCESS : Command::FAILURE;
    }
}
