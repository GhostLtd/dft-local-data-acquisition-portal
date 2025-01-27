<?php

namespace App\Twig;

use App\Entity\FundReturn\FundReturn;
use App\Entity\SchemeReturn\SchemeReturn;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Routing to the various edit pages can be a little tricky, so it's abstracted out
 * here so that it doesn't end up being implemented in twig templates!
 */
class SectionRouterExtension extends AbstractExtension
{
    public function __construct(protected RouterInterface $router)
    {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('section_edit_path', $this->path(...)),
            new TwigFunction('section_edit_url', $this->url(...)),
        ];
    }

    public function path(FundReturn|SchemeReturn $return, string $section, bool $isExpense): string
    {
        return $this->generate($return, $section, $isExpense, UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    public function url(FundReturn|SchemeReturn $return, string $section, bool $isExpense): string
    {
        return $this->generate($return, $section, $isExpense, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function generate(FundReturn|SchemeReturn $return, string $section, bool $isExpense, string $referenceType): string
    {
        if ($return instanceof FundReturn) {
            $route = $isExpense ? 'app_fund_return_expense_edit' : 'app_fund_return_edit';
            $params = ['fundReturnId' => $return->getId()];
        } else {
            $route = $isExpense ? 'app_scheme_return_expense_edit' : 'app_scheme_return_edit';
            $params = [
                'fundReturnId' => $return->getFundReturn()->getId(),
                'schemeFundId' => $return->getSchemeFund()->getId(),
            ];
        }

        if ($isExpense) {
            $params['divisionKey'] = $section;
        } else {
            $params['section'] = $section;
        }

        return $this->router->generate($route, $params, $referenceType);
    }
}