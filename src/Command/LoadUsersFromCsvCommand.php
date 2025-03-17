<?php

namespace App\Command;

use App\Entity\Authority;
use App\Entity\Enum\Fund;
use App\Entity\Enum\Permission;
use App\Entity\User;
use App\Entity\UserPermission;
use App\Utility\SampleReturnGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-users-from-csv',
    description: 'Loads users from a CSV file',
)]
/**
 * A utility that can add authorities, users and user permissions based upon CSV data
 * generated from the following query:
 *
 * SELECT u.name, u.position, u.phone, u.email, p.permission, a.name AS is_admin_of, b.name AS permission_on
 * FROM user u
 * LEFT JOIN user_permission p ON u.id = p.user_id
 * LEFT JOIN authority a ON a.admin_id = u.id
 * LEFT JOIN authority b ON p.entity_id = b.id
 * ;
 *
 * (i.e. for testing purposes only)
 */
class LoadUsersFromCsvCommand extends Command
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected SampleReturnGenerator  $sampleReturnGenerator,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('filename', InputArgument::REQUIRED, 'Filename');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filename = $input->getArgument('filename');

        $handle = fopen($filename, 'r');

        if (!$handle) {
            $io->error("Cannot open file: {$filename}");
            return Command::FAILURE;
        }

        $authorityNames = [];
        $admins = [];
        $userPermissions = [];

        while(!feof($handle)) {
            [$name, $position, $phone, $email, $permission, $isAdminOf, $permissionFor] = fgetcsv($handle);

            if ($isAdminOf !== '') {
                $authorityNames[$isAdminOf] = $isAdminOf;
                $admins[] = [$name, $position, $phone, $email, $isAdminOf];
            }

            if ($permissionFor !== '') {
                $authorityNames[$permissionFor] = $permissionFor;
                $userPermissions[] = [$name, $position, $phone, $email, $permission, $permissionFor];
            }
        }

        fclose($handle);

        $authorityNames = array_values($authorityNames);
        $authoritiesByName = [];
        $authorityRepo = $this->entityManager->getRepository(Authority::class);

        foreach($authorityNames as $authorityName) {
            $existingAuthority = $authorityRepo->findOneBy(['name' => $authorityName]);

            if ($existingAuthority) {
                $authoritiesByName[$authorityName] = $existingAuthority;
            } else {
                $authoritiesByName[$authorityName] = (new Authority())->setName($authorityName);
                $this->entityManager->persist($authoritiesByName[$authorityName]);
            }
        }

        $userRepo = $this->entityManager->getRepository(User::class);

        foreach($admins as [$name, $position, $phone, $email, $isAdminOf]) {
            $existingUser = $userRepo->findOneBy(['email' => $email]);

            if ($existingUser) {
                continue;
            }

            $user = (new User())
                ->setName($name)
                ->setPosition($position)
                ->setPhone($phone)
                ->setEmail($email);

            $this->entityManager->persist($user);
            $authoritiesByName[$isAdminOf]->setAdmin($user);
        }

        foreach($userPermissions as [$name, $position, $phone, $email, $permission, $permissionFor]) {
            $existingUser = $userRepo->findOneBy(['email' => $email]);

            if ($existingUser) {
                continue;
            }

            $user = (new User())
                ->setName($name)
                ->setPosition($position)
                ->setPhone($phone)
                ->setEmail($email);

            $userPermission = (new UserPermission())
                ->setUser($user)
                ->setEntityClass(Authority::class)
                ->setEntityId($authoritiesByName[$permissionFor]->getId())
                ->setPermission(Permission::from($permission));

            $user->addPermission($userPermission);

            $this->entityManager->persist($user);
            $this->entityManager->persist($userPermission);
        }

        foreach($authoritiesByName as $authority) {
            $this->sampleReturnGenerator->createAssetsForNewAuthority($authority);
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
