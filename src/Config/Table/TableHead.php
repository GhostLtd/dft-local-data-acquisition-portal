<?php

namespace App\Config\Table;

class TableHead extends AbstractElementWithClasses
{
    public function __construct(
        protected array $rows,
        array           $options = [],
        protected array $attributes = [],
    ) {
        parent::__construct($options, $attributes);
    }

    public function getType(): string
    {
        return 'table-head';
    }

    public function getRows(): array
    {
        return $this->rows;
    }
}
