<?php

declare(strict_types=1);

namespace Diapason\Model;

final readonly class DomainVerdict
{
    public function __construct(
        public string $domain,
        public bool $groupsMatch = true,
        public bool $groupOrder = true,
        public bool $unitsMatch = true,
        public bool $unitOrder = true,
        public bool $sourcesMatch = true,
        public bool $finalOk = true,
    ) {}

    public function isOk(): bool
    {
        if (!$this->groupsMatch) {
            return false;
        }
        if (!$this->groupOrder) {
            return false;
        }
        if (!$this->unitsMatch) {
            return false;
        }
        if (!$this->unitOrder) {
            return false;
        }
        if (!$this->sourcesMatch) {
            return false;
        }

        return $this->finalOk;
    }

    public function withGroupsInconsistent(): self
    {
        return new self(
            domain: $this->domain,
            groupsMatch: false,
            groupOrder: false,
            unitsMatch: $this->unitsMatch,
            unitOrder: $this->unitOrder,
            sourcesMatch: $this->sourcesMatch,
            finalOk: $this->finalOk,
        );
    }

    public function withGroupOrderInconsistent(): self
    {
        return new self(
            domain: $this->domain,
            groupsMatch: $this->groupsMatch,
            groupOrder: false,
            unitsMatch: $this->unitsMatch,
            unitOrder: $this->unitOrder,
            sourcesMatch: $this->sourcesMatch,
            finalOk: $this->finalOk,
        );
    }

    public function withUnitsInconsistent(): self
    {
        return new self(
            domain: $this->domain,
            groupsMatch: $this->groupsMatch,
            groupOrder: $this->groupOrder,
            unitsMatch: false,
            unitOrder: $this->unitOrder,
            sourcesMatch: $this->sourcesMatch,
            finalOk: $this->finalOk,
        );
    }

    public function withUnitOrderInconsistent(): self
    {
        return new self(
            domain: $this->domain,
            groupsMatch: $this->groupsMatch,
            groupOrder: $this->groupOrder,
            unitsMatch: $this->unitsMatch,
            unitOrder: false,
            sourcesMatch: $this->sourcesMatch,
            finalOk: $this->finalOk,
        );
    }

    public function withSourcesInconsistent(): self
    {
        return new self(
            domain: $this->domain,
            groupsMatch: $this->groupsMatch,
            groupOrder: $this->groupOrder,
            unitsMatch: $this->unitsMatch,
            unitOrder: $this->unitOrder,
            sourcesMatch: false,
            finalOk: $this->finalOk,
        );
    }

    public function withFinalNotReached(): self
    {
        return new self(
            domain: $this->domain,
            groupsMatch: $this->groupsMatch,
            groupOrder: $this->groupOrder,
            unitsMatch: $this->unitsMatch,
            unitOrder: $this->unitOrder,
            sourcesMatch: $this->sourcesMatch,
            finalOk: false,
        );
    }
}
