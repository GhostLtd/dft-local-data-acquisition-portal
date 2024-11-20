<?php

namespace App\Utility;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;

class FormHelper
{
    // Returns the name of the button which was kept (optionally limiting the response to only one of $amongstTheseButtonNames)
    public static function whichButtonClicked(FormInterface $form, null|string|array $amongstTheseButtonNames=null): ?string
    {
        if (is_string($amongstTheseButtonNames)) {
            $amongstTheseButtonNames = [$amongstTheseButtonNames];
        }

        foreach($form->get('buttons') as $button) {
            $buttonName = $button->getName();
            $matchesLimitingCriteria = $amongstTheseButtonNames === null || in_array($buttonName, $amongstTheseButtonNames);

            if ($button instanceof SubmitButton && $button->isClicked() && $matchesLimitingCriteria) {
                return $buttonName;
            }
        }

        return null;
    }
}
