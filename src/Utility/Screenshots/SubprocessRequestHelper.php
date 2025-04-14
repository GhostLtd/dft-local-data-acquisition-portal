<?php

namespace App\Utility\Screenshots;

use App\Entity\Authority;
use App\Entity\Enum\ActiveTravelElement;
use App\Entity\Enum\Fund;
use App\Entity\Enum\TransportMode;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeData\CrstsData;
use App\Entity\User;
use App\Messenger\AlphagovNotify\LoginEmail;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Process\InputStream;

class SubprocessRequestHelper
{
    protected const string AUTHORITY_NAME = 'Ghostlands regional authority';
    protected const string SCREENSHOTS_USER = 'screenshots@example.com';

    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {}

    public function process(string $buffer, InputStream $inputStream): void
    {
        $lines = explode("\n", $buffer);
        foreach($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, 'SCREENSHOTS:')) {
                $command = substr($line, strlen('SCREENSHOTS:'));
                $this->processCommand($command, $inputStream);
            } else {
                echo "- " . $line . "\n";
            }
        }
    }

    public function processCommand(string $command, InputStream $inputStream): void
    {
        if (preg_match("/^screenshotsSetup\('(?P<username>[^']*)'\)$/", $command, $matches)) {
            $this->screenshotsSetup($matches, $inputStream);
        } else if (preg_match("/^retrieveEmailLink\('(?P<username>[^']*)'\)$/", $command, $matches)) {
            $this->retrieveEmailLink($matches, $inputStream);
        } else {
            dump("Unrecognised command: $command");
            $inputStream->write('FAIL: Unrecognised command');
        }
    }

    /**
     * @return \Generator<array, LoginEmail>
     */
    protected function messagesFor(string $email): \Generator
    {
        try {
            $messages = $this->entityManager
                ->getConnection()
                ->executeQuery('SELECT * FROM messenger_messages')
                ->fetchAllAssociative();
        } catch (Exception) {
            return null;
        }

        if (empty($messages)) {
            return null;
        }

        foreach($messages as $message) {
            $serializer = new PhpSerializer();
            $envelope = $serializer->decode($messages[0]);
            $decoded = $envelope->getMessage();

            if ($decoded instanceof LoginEmail || $decoded->getRecipient() === $email) {
                yield [$message, $decoded];
            }
        }
    }

    protected function retrieveEmailLink(array $matches, InputStream $inputStream): void
    {
        $email = $matches[1];

        /** @var array<LoginEmail> $decodedMessages */
        $decodedMessages = [];
        foreach($this->messagesFor($email) as [$message, $decoded]) {
            $decodedMessages[] = $decoded;
        }

        $count = count($decodedMessages);
        if ($count === 0) {
            $inputStream->write("FAIL: No messages found\n");
            return;
        } else if ($count > 1) {
            $inputStream->write("FAIL: Multiple messages found\n");
            return;
        }

        $personalisation = $decodedMessages[0]->getPersonalisation();
        $loginLink = $personalisation['login_link'] ?? null;

        if (!$loginLink) {
            $inputStream->write("FAIL: No login link found\n");
            return;
        }

        $inputStream->write("OK: {$loginLink}\n");
    }

    protected function screenshotsSetup(array $matches, InputStream $inputStream): void
    {
        $email = $matches[1];

        foreach($this->messagesFor($email) as [$message, $decoded]) {
            $this->entityManager->getConnection()
                ->executeQuery('DELETE FROM messenger_messages WHERE id=:id', ['id' => $message['id']]);
        }

        $existingAuthority = $this->entityManager
            ->getRepository(Authority::class)
            ->findOneBy(['name' => self::AUTHORITY_NAME]);

        if ($existingAuthority) {
            $this->entityManager->remove($existingAuthority);
            $this->entityManager->remove($existingAuthority->getAdmin());

            foreach($existingAuthority->getFundAwards() as $fundAward) {
                $this->entityManager->remove($fundAward);

                foreach($fundAward->getReturns() as $fundReturn) {
                    $this->entityManager->remove($fundReturn);

                    foreach($fundReturn->getSchemeReturns() as $schemeReturn) {
                        $this->entityManager->remove($schemeReturn);
                    }
                }
            }

            foreach($existingAuthority->getSchemes() as $scheme) {
                $this->entityManager->remove($scheme);
            }

            $this->entityManager->flush();
        }

        $admin = (new User())
            ->setName('Mark')
            ->setEmail(self::SCREENSHOTS_USER)
            ->setPhone('1122345')
            ->setPosition('Screenshots generator');

        $this->entityManager->persist($admin);

        $authority = (new Authority())
            ->setName(self::AUTHORITY_NAME)
            ->setAdmin($admin);

        $this->entityManager->persist($authority);

        $fundAward = (new FundAward())
            ->setType(Fund::CRSTS1);

        $authority->addFundAward($fundAward);
        $this->entityManager->persist($fundAward);

        $return = (new CrstsFundReturn())
            ->setYear(2025)
            ->setQuarter(1);

        $fundAward->addReturn($return);
        $this->entityManager->persist($return);

        $crstsData = (new CrstsData())
            ->setRetained(true)
            ->setPreviouslyTcf(false);

        $scheme = (new Scheme())
            ->addFund(Fund::CRSTS1)
            ->setName('Casper railway station improvements')
            ->setSchemeIdentifier('GRA00001')
            ->setTransportMode(TransportMode::RAIL_INTERCHANGE_OR_NETWORK_UPGRADE)
            ->setActiveTravelElement(ActiveTravelElement::PROVISION_OF_SECURE_CYCLE_PARKING)
            ->setDescription('Various improvements')
            ->setCrstsData($crstsData);

        $authority->addScheme($scheme);
        $this->entityManager->persist($scheme);

        $this->entityManager->flush();

        $inputStream->write("OK: Created\n");
    }
}
