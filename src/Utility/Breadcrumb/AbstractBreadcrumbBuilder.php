<?php

namespace App\Utility\Breadcrumb;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractBreadcrumbBuilder
{
    protected array $items = [];

    public function __construct(
        protected RouterInterface     $router,
        protected TranslatorInterface $translator,
    )
    {
        $this->addInitialItems();
    }

    protected function addItem(
        string $key,
        string $translationKey,
        string $routeName,
        array  $routeParameters = [],
        array  $translationParameters = []
    ): void
    {
        $this->items[$key] = [
            'text' => $this->translator->trans($translationKey, $translationParameters),
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

    abstract protected function addInitialItems(): void;
}
