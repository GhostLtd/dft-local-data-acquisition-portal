<?php

namespace App\Utility;

use Generator;

class FinancialQuarter
{

    public function __construct(public int $initialYear, public int $quarter) {}

    public function getStartDate(): \DateTime
    {
        $mod = ($this->quarter - 1) * 3;
        return (new \DateTime($this->initialYear . '-04-01'))->modify("+{$mod} months");
    }

    public function getAsArray(): array
    {
        return [$this->initialYear, $this->quarter];
    }

    public static function createFromDate(\DateTime $date): static
    {
        // we need to take the first of the given month, because if it's something like Dec-31, it becomes Oct-01
        // subtract 3 months, because FY starts in April
        $date = (new \DateTime($date->format('Y-m') . '-01'))->modify('-3 months');
        return new static($date->format('Y'), ceil($date->format('m') / 3));
    }

    public function getNextQuarter(): static
    {
        return match($this->quarter) {
            4 => new static($this->initialYear + 1, 1),
            default => new static($this->initialYear, $this->quarter + 1)
        };
    }

    public function __toString(): string
    {
        return $this->getStartDate()->format("Y-m-d");
    }

    /**
     * @return Generator<FinancialQuarter>
     */
    public static function getRange(FinancialQuarter $start, FinancialQuarter $end): Generator
    {
        if ($start > $end) {
            throw new \RuntimeException('start must be before end');
        }

        $i = clone $start;
        while ($i <= $end) {
            yield $i;
            $i = $i->getNextQuarter();
        }
    }
}