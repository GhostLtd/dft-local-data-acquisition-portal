<?php

namespace App\Tests\DataFixtures\Security\Voter;

use App\Entity\Enum\Fund;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Project;
use App\Entity\ProjectFund\CrstsProjectFund;
use App\Entity\ProjectFund\ProjectFund;
use App\Entity\ProjectReturn\CrstsProjectReturn;
use App\Entity\ProjectReturn\ProjectReturn;
use App\Entity\Authority;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PermissionDataFixture extends Fixture
{
    protected ObjectManager $manager;

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $admin1 = $this->createUser('Admin of Authority 1 + 2', 'admin1@example.com', 'admin:1');
        $admin2 = $this->createUser('Admin of Authority 3', 'admin2@example.com', 'admin:2');

        $authority1 = $this->createAuthority('Authority 1', 'authority:1', $admin1);
        $authority2 = $this->createAuthority('Authority 2', 'authority:2', $admin1);
        $authority3 = $this->createAuthority('Authority 3', 'authority:3', $admin2);

        $fundAward1 = $this->createFundAward(Fund::CRSTS1, 'authority:1/fund-award:1', $authority1);
        $fundAward2 = $this->createFundAward(Fund::CRSTS1, 'authority:2/fund-award:1', $authority2);
        $fundAward3 = $this->createFundAward(Fund::CRSTS1, 'authority:3/fund-award:1', $authority3);

        $return1 = $this->createFundReturn($fundAward1, 'authority:1/return:1');
        $return2 = $this->createFundReturn($fundAward1, 'authority:1/return:2');
        $return3 = $this->createFundReturn($fundAward2, 'authority:2/return:1');
        $return4 = $this->createFundReturn($fundAward3, 'authority:3/return:1');

        $user = $this->createUser('User', 'user@example.com', 'user');

        [$project1, $projectFund1] = $this->createProjectAndProjectFund('Authority 1, project 1', 'authority:1/project:1', Fund::CRSTS1, $authority1);
        [$project2, $projectFund2] = $this->createProjectAndProjectFund('Authority 1, project 2', 'authority:1/project:2', Fund::CRSTS1, $authority1);
        [$project3, $projectFund3] = $this->createProjectAndProjectFund('Authority 2, project 1', 'authority:2/project:1', Fund::CRSTS1, $authority2);
        [$project4, $projectFund4] = $this->createProjectAndProjectFund('Authority 3, project 1', 'authority:3/project:1', Fund::CRSTS1, $authority3);

        $projectReturn1 = $this->createProjectReturn('authority:1/return:1/project:1', $projectFund1, $return1);
        $projectReturn2 = $this->createProjectReturn('authority:1/return:2/project:1', $projectFund1, $return2);
        $projectReturn3 = $this->createProjectReturn('authority:1/return:1/project:2', $projectFund2, $return1);
        $projectReturn4 = $this->createProjectReturn('authority:2/return:1/project:1', $projectFund3, $return3);
        $projectReturn5 = $this->createProjectReturn('authority:3/return:1/project:1', $projectFund4, $return4);

        $manager->flush();
    }

    protected function createUser(string $name, string $email, string $referenceName): User
    {
        $user = (new User())
            ->setName($name)
            ->setEmail($email);

        return $this->persistAndAddReference($user, $referenceName);
    }

    protected function createFundReturn(FundAward $fundAward1, string $referenceName): FundReturn
    {
        $returnClass = match($fundAward1->getType()) {
            Fund::CRSTS1 => CrstsFundReturn::class,
            default => throw new \RuntimeException('Unsupported fund type'),
        };

        $return = (new $returnClass())
            ->setFundAward($fundAward1)
            ->setYear(2024)
            ->setQuarter(1);

        return $this->persistAndAddReference($return, $referenceName);
    }

    protected function createFundAward(Fund $type, string $referenceName, Authority $authority): FundAward
    {
        $award = (new FundAward())
            ->setType($type)
            ->setAuthority($authority);

        return $this->persistAndAddReference($award, $referenceName);
    }

    protected function createAuthority(string $name, string $referenceName, User $admin): Authority
    {
        $authority = (new Authority())
            ->setName($name)
            ->setAdmin($admin);

        return $this->persistAndAddReference($authority, $referenceName);
    }

    /**
     * @return array{0: Project, 1:ProjectFund}
     */
    protected function createProjectAndProjectFund(string $name, string $referenceName, Fund $type, Authority $authority): array
    {
        $project = (new Project())
            ->setOwner($authority)
            ->setName($name);

        if ($type === Fund::CRSTS1) {
            $projectFund = (new CrstsProjectFund())
                ->setProject($project)
                ->setRetained(true);
        } else {
            throw new \RuntimeException('Unsupported fund type');
        }

        return [
            $this->persistAndAddReference($project, $referenceName),
            $this->persistAndAddReference($projectFund, $referenceName.'/fund')
        ];
    }

    public function createProjectReturn(string $referenceName, ProjectFund $projectFund, FundReturn $fundReturn): ProjectReturn
    {
        if ($projectFund instanceof CrstsProjectFund) {
            $project = (new CrstsProjectReturn())
                ->setProjectFund($projectFund)
                ->setFundReturn($fundReturn);
        } else {
            throw new \RuntimeException('Unsupported project fund type');
        }

        return $this->persistAndAddReference($project, $referenceName);
    }

    /**
     * @template T
     * @param object<T> $object
     * @return T
     */
    protected function persistAndAddReference(object $object, string $referenceName): object
    {
        $this->manager->persist($object);
        $this->addReference($referenceName, $object);
        return $object;
    }
}
