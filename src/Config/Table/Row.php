<?php

namespace App\Config\Table;

class Row extends AbstractElementWithClasses
{
    public function __construct(
        protected array $cells,
        array           $options = [],
        protected array $attributes = [],
    ) {
        parent::__construct($options, $attributes);
    }

    public function getType(): string
    {
        return 'row';
    }

    public function getCells(): array
    {
        return $this->cells;
    }
}
