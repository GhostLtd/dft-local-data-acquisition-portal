<?php

namespace App\Utility;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;

class FormHelper
{
    public static function whichButtonClicked(FormInterface $form, string|array $buttonNames): ?string
    {
        $buttons = $form->get('buttons');

        if (is_string($buttonNames)) {
            $buttonNames = [$buttonNames];
        }

        foreach($buttonNames as $buttonName) {
            if (!$buttons->has($buttonName)) {
                continue;
            }

            $button = $buttons->get($buttonName);
            if ($button instanceof SubmitButton && $button->isClicked()) {
                return $buttonName;
            }
        }

        return null;
    }
}
