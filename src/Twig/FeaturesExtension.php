<?php

namespace App\Twig;

use App\Features;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FeaturesExtension extends AbstractExtension
{
    public function __construct(protected readonly RequestStack $requestStack)
    {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_feature_enabled', $this->isFeatureEnabled(...)),
        ];
    }

    /**
     * @throws SyntaxError
     */
    public function isFeatureEnabled(string $str): bool {
        try {
            return Features::isEnabled($str);
        } catch(Exception) {
            throw new SyntaxError("Unknown feature '$str'");
        }
    }
}
