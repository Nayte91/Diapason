<?php

declare(strict_types=1);

namespace Diapason\Check;

use Diapason\Model\Issue;
use Diapason\Model\XliffFile;

interface PerFileCheckInterface extends CheckInterface
{
    /** @return iterable<Issue> */
    public function check(XliffFile $file): iterable;
}
