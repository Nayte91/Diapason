<?php

declare(strict_types=1);

namespace Diapason;

use Diapason\Command\CheckCommand;
use Diapason\Command\FormatCommand;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;

final class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('Diapason', '0.1.0');
        $this->addCommand(new FormatCommand());
        $this->addCommand(new CheckCommand());
        $this->setDefaultCommand('format');
    }

    #[\Override]
    protected function getCommandName(InputInterface $input): string
    {
        $first = $input->getFirstArgument();

        if ($first === null) {
            return 'format';
        }

        if ($this->has($first)) {
            return $first;
        }

        if ($input instanceof ArgvInput) {
            $input->bind($this->find('format')->getDefinition());
        }

        return 'format';
    }
}
