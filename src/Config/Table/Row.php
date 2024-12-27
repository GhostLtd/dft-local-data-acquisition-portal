<?php

namespace App\Config\Table;

use App\Config\Table\AbstractElement;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Row extends AbstractElement
{
    public function __construct(
        protected array $cells,
        array           $options = [],
        protected array $attributes = [],
    ) {
        parent::__construct($options, $attributes);
    }

    protected function configureOptionsResolver(): void
    {
        $this->resolver = (new OptionsResolver())
            ->setDefaults([
                'classes' => null,
            ])
            ->setAllowedTypes('classes', ['string', 'null']);
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
