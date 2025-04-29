<?php

namespace App\Command;

use App\Entity\Scheme;
use App\EventSubscriber\PropertyChangeLogEventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'ldap:fix:20250429-fix-scheme-identifiers',
    description: 'Fix any scheme identifiers that were edited prior to editing being disallowed',
)]
class LdapFix20250429FixSchemeIdentifiersCommand extends Command
{
    public function __construct(
        protected EntityManagerInterface           $entityManager,
        protected PropertyChangeLogEventSubscriber $changeLogEventSubscriber,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $schemes = $this->entityManager->getRepository(Scheme::class)->findAll();

        foreach($schemes as $scheme) {
            $identifier = $scheme->getSchemeIdentifier(true);

            if (preg_match('/^\d{5}$/', $identifier)) {
                continue;
            }

            if (!preg_match('/^SYMCA-(\d{5})$/', $identifier, $matches)) {
                $io->error("Found non-compliant identifier '{$identifier}' for scheme {$scheme->getId()}, but it doesn't match the known pattern");
                continue;
            }

            $newIdentifier = $matches[1];
            $io->success("Replaced non-compliant identifier '{$identifier}' with '{$newIdentifier}' for scheme {$scheme->getId()}");
            $scheme->setSchemeIdentifier($newIdentifier);
        }

        $this->changeLogEventSubscriber->setDefaultSource('fix:2025-04-29:fix-scheme-identifiers');
        $this->entityManager->flush();
        $this->changeLogEventSubscriber->setDefaultSource(null);

        return Command::SUCCESS;
    }
}
