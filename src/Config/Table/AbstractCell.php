<?php

namespace App\Config\Table;

use App\Config\Table\AbstractElement;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

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
            ->setAllowedTypes('text', ['string', TranslatableMessage::class]);
    }
}
