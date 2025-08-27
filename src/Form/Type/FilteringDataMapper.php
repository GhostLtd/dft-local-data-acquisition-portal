<?php

namespace App\Form\Type;

use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;
use Symfony\Component\Form\FormInterface;

class FilteringDataMapper extends DataMapper
{
    protected function filterForms(\Traversable $forms, $exclude=[]): \Generator
    {
        /** @var FormInterface $form */
        foreach($forms as $form) {
            $name = $form->getName();

            if (in_array($name, $exclude)) {
                continue;
            }

            yield $form;
        }
    }
}
