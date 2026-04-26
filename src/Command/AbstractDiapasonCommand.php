<?php

declare(strict_types=1);

namespace Diapason\Command;

use Diapason\Checker;
use Diapason\Config\ConfigLoader;
use Diapason\Config\DiapasonConfig;
use Diapason\Exception\ConfigException;
use Diapason\Exception\InputException;
use Diapason\Model\ProjectReport;
use Diapason\Parser\DomainResolver;
use Diapason\Parser\XliffDomLoader;
use Diapason\Parser\XliffParser;
use Diapason\Reporter\JsonReporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractDiapasonCommand extends Command
{
    protected const int EXIT_INPUT_ERROR = 2;

    protected function configure(): void
    {
        $this
            ->addArgument(
                'domain',
                InputArgument::OPTIONAL,
                "Domain prefix (e.g. 'translations/messages+intl-icu'). Without this, all configured paths are scanned.",
            )
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to a Diapason config file.')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format: table (default) or json.', 'table');
    }

    protected function loadConfig(InputInterface $input, string $cwd, OutputInterface $output): ?DiapasonConfig
    {
        try {
            $config = new ConfigLoader()->load($this->stringOption($input, 'config'), $cwd);
        } catch (ConfigException $e) {
            $output->writeln(sprintf('<error>Config error: %s</error>', $e->getMessage()));

            return null;
        }

        $format = $this->stringOption($input, 'format') ?? 'table';
        if ($format === 'json') {
            $config = $config->withReporter(new JsonReporter());
        }

        return $config;
    }

    protected function runChecker(DiapasonConfig $config, ?string $domain, string $cwd, OutputInterface $output): ?ProjectReport
    {
        $domainResolver = new DomainResolver();
        $checker = new Checker(new XliffParser($domainResolver, new XliffDomLoader()), $domainResolver);

        try {
            return $checker->run($config, $domain, $cwd);
        } catch (InputException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return null;
        }
    }

    protected function resolveCwd(): string
    {
        $cwd = getcwd();

        return $cwd === false ? '.' : $cwd;
    }

    protected function stringOption(InputInterface $input, string $name): ?string
    {
        $value = $input->getOption($name);

        return is_string($value) ? $value : null;
    }

    protected function stringArgument(InputInterface $input, string $name): ?string
    {
        $value = $input->getArgument($name);

        return is_string($value) ? $value : null;
    }
}
