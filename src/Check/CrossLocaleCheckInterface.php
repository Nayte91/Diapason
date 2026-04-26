<?php

declare(strict_types=1);

namespace Diapason\Check;

use Diapason\Model\DomainBundle;
use Diapason\Model\DomainVerdict;
use Diapason\Model\Issue;

interface CrossLocaleCheckInterface extends CheckInterface
{
    /**
     * Inspect the bundle and yield Issues describing inconsistencies.
     *
     * The provided $verdict reflects the state derived from previously executed
     * checks (the orchestrator projects the verdict from issues). It is supplied
     * so a check can adapt its strategy to upstream findings (e.g. degrade
     * gracefully when groups already diverged). Implementations MUST treat
     * $verdict as read-only — verdict mutation is the orchestrator's job.
     *
     * @return iterable<Issue>
     */
    public function check(DomainBundle $bundle, DomainVerdict $verdict): iterable;
}
