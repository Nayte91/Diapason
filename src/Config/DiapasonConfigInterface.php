<?php

declare(strict_types=1);

namespace Diapason\Config;

use Diapason\Check\CheckInterface;
use Diapason\Formatter\FormatterInterface;
use Diapason\Reporter\ReporterInterface;

interface DiapasonConfigInterface
{
    public function withPaths(string ...$globs): self;

    public function withChecks(CheckInterface ...$checks): self;

    public function withFormatter(?FormatterInterface $formatter): self;

    public function withFormatMode(FormatMode $mode): self;

    public function withReporter(ReporterInterface $reporter): self;
}
