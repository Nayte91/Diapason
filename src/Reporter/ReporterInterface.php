<?php

declare(strict_types=1);

namespace Diapason\Reporter;

use Diapason\Model\ProjectReport;
use Symfony\Component\Console\Output\OutputInterface;

interface ReporterInterface
{
    public function report(ProjectReport $report, OutputInterface $output): void;
}
