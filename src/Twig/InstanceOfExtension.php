<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class InstanceOfExtension extends AbstractExtension
{
    public function getTests(): array
    {
        return [
            new TwigTest('instanceof', $this->instanceOf(...)),
        ];
    }

    public function instanceOf(mixed $object, string $class): bool
    {
        try {
            $reflClass = new \ReflectionClass($class);
        } catch (\ReflectionException) {
            return false;
        }
        return $reflClass->isInstance($object);
    }
}
