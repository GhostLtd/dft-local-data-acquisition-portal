<?php

namespace App\Tests\DataFixtures;

use App\Entity\Authority;
use App\Entity\Enum\Fund;
use App\Entity\Enum\OnTrackRating;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SchemeReturnPointWhereNotEditable extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $allReturns = [
            1 => [
                [2023, 1, OnTrackRating::AMBER],
                [2023, 2, OnTrackRating::SCHEME_COMPLETED],
                [2023, 3, OnTrackRating::SCHEME_MERGED],
                [2023, 4, OnTrackRating::SCHEME_SPLIT],
                [2024, 1, null],
                [2024, 2, OnTrackRating::SCHEME_COMPLETED],
                [2024, 3, OnTrackRating::SCHEME_MERGED], // final closed again
            ],
            2 => [
                [2023, 1, OnTrackRating::RED],
                [2023, 2, null],
                [2023, 3, OnTrackRating::SCHEME_COMPLETED],
                [2023, 4, OnTrackRating::GREEN], // reopened
                [2024, 1, OnTrackRating::SCHEME_COMPLETED],
            ],
        ];

        $user = (new User())
            ->setName('Test admin')
            ->setEmail('one@example.com');

        $authority = (new Authority())
            ->setName('Test authority')
            ->setAdmin($user);

        $fundAward = (new FundAward())
            ->setType(Fund::CRSTS1)
            ->setAuthority($authority);

        $manager->persist($authority);
        $manager->persist($fundAward);
        $manager->persist($user);

        foreach ($allReturns as $schemeId => $returns) {
            $scheme = (new Scheme())
                ->setName("Test scheme {$schemeId}")
                ->setAuthority($authority);

            $manager->persist($scheme);

            foreach ($returns as [$year, $quarter, $status]) {
                $fundReturn = (new CrstsFundReturn())
                    ->setYear($year)
                    ->setQuarter($quarter)
                    ->setFundAward($fundAward);

                $manager->persist($fundReturn);

                $schemeReturn = (new CrstsSchemeReturn())
                    ->setFundReturn($fundReturn)
                    ->setOnTrackRating($status)
                    ->setScheme($scheme);

                $manager->persist($schemeReturn);

                $ref = sprintf('sr_%d_%d_%d', $schemeId, $year, $quarter);
                $this->addReference($ref, $schemeReturn);
            }
        }

        $manager->flush();
    }
}
