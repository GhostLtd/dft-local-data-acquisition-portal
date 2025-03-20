<?php

namespace App\Utility\Breadcrumb;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractLinksBuilder
{
    protected array $links = [];

    public function __construct(
        protected RouterInterface     $router,
        protected TranslatorInterface $translator,
    )
    {}

    protected function addLink(
        string                            $set,
        string                            $key,
        string                            $routeName,
        array                             $routeParameters = [],
        ?string                           $translationKey = null,
        array                             $translationParameters = [],
        null|string|TranslatableInterface $text = null,
        ?string                           $hash = null,
    ): void
    {
        if ($text === null && $translationKey === null) {
            throw new \RuntimeException('BreadcrumbBuilder->addItem() - either text or translationKey must be set');
        }

        if ($text instanceof TranslatableInterface) {
            $text = $text->trans($this->translator);
        }

        $this->links[$set][$key] = [
            'text' => $text ?? $this->translator->trans($translationKey, $translationParameters),
            'href' => $this->router->generate($routeName, $routeParameters) . ($hash ? "#$hash" : ""),
        ];
    }

    public function getTitleFor(string $set, string $key): string
    {
        return $this->links[$set][$key]['text'];
    }

    public function getLinks(string $set): array
    {
        return array_values($this->getLinksWithOriginalKeys($set));
    }

    public function getLinksWithOriginalKeys(string $set): array
    {
        return $this->links[$set] ?? [];
    }
}
