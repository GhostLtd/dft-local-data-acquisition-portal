<?php

namespace App\Entity\Config\Table;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractCell extends AbstractElement
{
    protected function configureOptionsResolver(): void
    {
        $this->resolver = (new OptionsResolver())
            ->setDefaults([
                'colspan' => null,
                'rowspan' => null,
                'text' => '',
            ])
            ->setAllowedTypes('colspan', ['int', 'null'])
            ->setAllowedTypes('rowspan', ['int', 'null'])
            ->setAllowedTypes('text', ['string']);
    }
}
