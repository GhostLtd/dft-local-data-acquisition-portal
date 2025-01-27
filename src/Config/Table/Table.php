<?php

namespace App\Config\Table;

class Table extends AbstractElementWithClasses
{
    public function __construct(
        protected array $headAndBodies,
        array           $options = [],
        protected array $attributes = [],
    ) {
        parent::__construct($options, $attributes);
    }

    public function getType(): string
    {
        return 'table';
    }

    public function getRows(): array
    {
        $rows = [];

        foreach($this->headAndBodies as $headOrBody) {
            foreach($headOrBody->getRows() as $row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public function getHeadAndBodies(): array
    {
        return $this->headAndBodies;
    }
}
