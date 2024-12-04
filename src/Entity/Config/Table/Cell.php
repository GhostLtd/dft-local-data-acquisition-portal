<?php

namespace App\Entity\Config\Table;

class Cell extends AbstractCell
{
    protected function configureOptionsResolver(): void
    {
        parent::configureOptionsResolver();
        $this->resolver
            ->setDefaults([
                'disabled' => null,
                'attributes' => [],
            ])
            ->setRequired(['key'])
            ->setAllowedTypes('attributes', ['string[]'])
            ->setAllowedTypes('disabled', ['bool', 'null'])
            ->setAllowedTypes('key', ['string']);
    }

    public function getType(): string
    {
        return 'cell';
    }
}
