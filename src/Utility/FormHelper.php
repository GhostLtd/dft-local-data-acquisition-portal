<?php

namespace App\Utility;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;

class FormHelper
{
    public static function wasClicked(FormInterface $form, string $buttonName): bool
    {
        $button = $form->get('buttons')->get($buttonName);
        return $button instanceof SubmitButton && $button->isClicked();
    }
}
