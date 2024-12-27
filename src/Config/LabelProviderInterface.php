<?php

namespace App\Config;

use Symfony\Contracts\Translation\TranslatableInterface;

interface LabelProviderInterface
{
    /**
     * TranslatableInterface is a bit deficient in that it doesn't allow the injection of extra parameters at
     * runtime (e.g. {fundName}), so the ability is provided in getLabel() instead
     */
    public function getLabel(array $extraParameters=[]): string|TranslatableInterface;
}
