<?php

namespace App\Utility\Breadcrumb;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractBreadcrumbBuilder
{
    protected array $items = [];

    public function __construct(
        protected RouterInterface     $router,
        protected TranslatorInterface $translator,
    )
    {}

    protected function addItem(
        string                            $key,
        string                            $routeName,
        array                             $routeParameters = [],
        ?string                           $translationKey = null,
        array                             $translationParameters = [],
        null|string|TranslatableInterface $text = null,
    ): void
    {
        if ($text === null && $translationKey === null) {
            throw new \RuntimeException('BreadcrumbBuilder->addItem() - either text or translationKey must be set');
        }

        if ($text instanceof TranslatableInterface) {
            $text = $text->trans($this->translator);
        }

        $this->items[$key] = [
            'text' => $text ?? $this->translator->trans($translationKey, $translationParameters),
            'href' => $this->router->generate($routeName, $routeParameters),
        ];
    }

    public function getTitleFor(string $key): string
    {
        return $this->items[$key]['text'];
    }

    public function getBreadcrumbs(): array
    {
        return array_values($this->items);
    }
}
