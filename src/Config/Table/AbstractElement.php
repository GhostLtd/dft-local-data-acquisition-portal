<?php

namespace App\Config\Table;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractElement
{
    protected array $options;
    protected OptionsResolver $resolver;

    /**
     * Options are cell options, intended to be passed to a corresponding
     * twig cell macro (e.g. tableHeader or tableCell)
     *
     * Attributes is intended to be used for other data associated with
     * this cell
     */
    public function __construct(
        array           $options,
        protected array $attributes = []
    ) {
        $this->configureOptionsResolver();
        $this->options = $this->resolver->resolve($options);
    }

    protected function configureOptionsResolver(): void
    {
        $this->resolver = (new OptionsResolver());
    }

    public function getOptions(bool $filterNulls = true): array
    {
        return $filterNulls ?
            array_filter($this->options, fn(mixed $v) => $v !== null) :
            $this->options;
    }

    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    abstract public function getType(): string;
}
