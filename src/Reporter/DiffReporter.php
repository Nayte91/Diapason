<?php

declare(strict_types=1);

namespace Diapason\Reporter;

use Diapason\Model\ProjectReport;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class DiffReporter implements ReporterInterface
{
    public function __construct(
        private ReporterInterface $inner,
    ) {}

    public function report(ProjectReport $report, OutputInterface $output): void
    {
        if ($report->previewsByPath === []) {
            $output->writeln('<info>No formatting changes would be applied.</info>');
            $output->writeln('');
        } else {
            foreach ($report->previewsByPath as $path => $preview) {
                $output->writeln(sprintf('<info>%s</info>', $path));
                $output->writeln($this->colorize($preview->diff));
                $output->writeln(sprintf(
                    'Summary: <fg=green>+%d</> / <fg=red>-%d</> lines',
                    $preview->linesAdded,
                    $preview->linesRemoved,
                ));
                $output->writeln('');
            }
        }

        $this->inner->report($report, $output);
    }

    private function colorize(string $diff): string
    {
        $lines = explode("\n", $diff);
        $colored = [];
        foreach ($lines as $line) {
            if (str_starts_with($line, '+++') || str_starts_with($line, '---')) {
                $colored[] = sprintf('<fg=cyan>%s</>', $this->escape($line));
                continue;
            }
            if (str_starts_with($line, '@@')) {
                $colored[] = sprintf('<fg=magenta>%s</>', $this->escape($line));
                continue;
            }
            if (str_starts_with($line, '+')) {
                $colored[] = sprintf('<fg=green>%s</>', $this->escape($line));
                continue;
            }
            if (str_starts_with($line, '-')) {
                $colored[] = sprintf('<fg=red>%s</>', $this->escape($line));
                continue;
            }
            $colored[] = $this->escape($line);
        }

        return implode("\n", $colored);
    }

    private function escape(string $text): string
    {
        return str_replace(['<', '>'], ['\\<', '\\>'], $text);
    }
}
