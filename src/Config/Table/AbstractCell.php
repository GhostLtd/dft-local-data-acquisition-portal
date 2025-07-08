<?php

namespace App\Config\Table;

use Symfony\Component\Translation\TranslatableMessage;

abstract class AbstractCell extends AbstractElementWithClasses
{
    protected function configureOptionsResolver(): void
    {
        parent::configureOptionsResolver();
        $this->resolver
            ->setDefaults([
                'colspan' => null,
                'rowspan' => null,
                'text' => '',
                'html' => null,
            ])
            ->setAllowedTypes('colspan', ['int', 'null'])
            ->setAllowedTypes('rowspan', ['int', 'null'])
            ->setAllowedTypes('text', ['string', TranslatableMessage::class])
            ->setAllowedTypes('html', ['string', 'null']);
    }
}
