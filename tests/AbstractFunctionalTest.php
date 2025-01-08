<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractFunctionalTest extends WebTestCase
{

    /**
     * This method helps to ensure that PHPStorm understands the type of variables fetched from the container
     *
     * @template T
     * @param string $id
     * @param class-string<T> $expectedClass
     * @return T
     */
    public function getFromContainer(string $id, string $expectedClass)
    {
        $object = $this->getContainer()->get($id);

        if (!$object instanceof $expectedClass) {
            $actualClass = get_class($object);
            $this->fail("Expected container to return instance of $expectedClass for $id, but it instead returned a $actualClass");
        }

        return $object;
    }
}
