<?php

namespace App\Config\Table;

abstract class AbstractElementWithClasses extends AbstractElement
{
    protected function configureOptionsResolver(): void
    {
        parent::configureOptionsResolver();
        $this->resolver
            ->setDefaults([
                'classes' => null,
            ])
            ->setAllowedTypes('classes', ['string', 'null']);
    }
}
