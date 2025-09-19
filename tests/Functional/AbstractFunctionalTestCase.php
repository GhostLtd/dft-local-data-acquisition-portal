<?php

namespace App\Tests\Functional;

use App\Messenger\AlphagovNotify\LoginEmail;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;

abstract class AbstractFunctionalTestCase extends PantherTestCase
{
    protected Client $client;
    protected EntityManagerInterface $entityManager;
    protected ReferenceRepository $fixtureReferenceRepository;

    public function initialiseClientAndLoadFixtures(array $fixtures = [], array $pantherOptions = [], array $kernelOptions = []): void
    {
        $pantherOptions['hostname'] ??= 'ldap-frontend.localhost';

        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->fixtureReferenceRepository = $databaseTool->loadFixtures($fixtures)->getReferenceRepository();

//        if (static::$webServerManager?->isStarted()) {
//            $reflClass = new \ReflectionClass(static::$webServerManager);
//            $hostname = $reflClass->getProperty('hostname')->getValue(static::$webServerManager);
//
//            if ($hostname !== ($pantherOptions['hostname'] ?? null)) {
//                // The webserver's already running but not with the hostname that we want, so let's stop it
//                // so that it then gets spun up again with the correct hostname...
//                static::stopWebServer();
//
//                // A possibly-better alternative would be to manage the webservers ourselves and
//                // then instead use the external_base_uri option to dictate which server to use
//            }
//        }

        $managerOptions = [];

        $this->client = static::createPantherClient($pantherOptions, $kernelOptions, $managerOptions);
    }

    protected function clickLinkContaining(string $textContains, int $index = 0, string $xpathBase = '//'): void
    {
        // Doing things this way seems to mean that we don't need to use client->waitFor()
        // (compared with client->findElement->click)
        $textContains = str_replace("'", "\'", $textContains);
        $xpath = $xpathBase.'a[contains(., "' . $textContains . '")]';

        $links = $this->client->getCrawler()->filterXPath($xpath)->links();
        $link = $links[$index] ?? null;

        if ($link === null) {
            $this->fail("No such link found (contains: \"$textContains\", index: $index)");
        }

        $this->client->click($link);
    }

    protected function fetchMessage(): ?LoginEmail
    {
        try {
            $messages = $this->entityManager
                ->getConnection()
                ->executeQuery('SELECT * FROM messenger_messages')
                ->fetchAllAssociative();
        } catch (Exception) {
            return null;
        }

        $count = count($messages);

        if ($count === 0) {
            return null;
        } else if ($count > 1) {
            $this->fail('Multiple messages found in message queue');
        } else {
            $serializer = new PhpSerializer();
            $envelope = $serializer->decode($messages[0]);
            $message = $envelope->getMessage();

            $this->assertInstanceOf(LoginEmail::class, $message);
            return $message;
        }
    }

    protected function emptyMessageQueue(): void
    {
        $this->entityManager
            ->getConnection()
            ->executeQuery('DELETE FROM messenger_messages');
    }
}
