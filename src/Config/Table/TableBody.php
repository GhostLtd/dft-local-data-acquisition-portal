<?php

namespace App\Config\Table;

class TableBody extends AbstractElementWithClasses
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
        return 'table-body';
    }

    public function getRows(): array
    {
        return $this->rows;
    }
}
