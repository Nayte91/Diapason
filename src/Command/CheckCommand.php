<?php

declare(strict_types=1);

namespace Diapason\Command;

use Diapason\Config\FormatMode;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'check', description: 'Run all checks without writing any files.')]
final class CheckCommand extends AbstractDiapasonCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cwd = $this->resolveCwd();
        $config = $this->loadConfig($input, $cwd, $output);
        if (!$config instanceof \Diapason\Config\DiapasonConfig) {
            return self::EXIT_INPUT_ERROR;
        }

        $config = $config->withFormatMode(FormatMode::Disabled);

        $report = $this->runChecker($config, $this->stringArgument($input, 'domain'), $cwd, $output);
        if (!$report instanceof \Diapason\Model\ProjectReport) {
            return self::EXIT_INPUT_ERROR;
        }

        $config->getReporter()->report($report, $output);

        return $report->isOk() ? Command::SUCCESS : Command::FAILURE;
    }
}
