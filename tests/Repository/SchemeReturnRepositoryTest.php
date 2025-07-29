<?php

namespace App\Tests\Repository;

use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Repository\SchemeReturn\CrstsSchemeReturnRepository;
use App\Tests\DataFixtures\SchemeReturnPointWhereNotEditable;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SchemeReturnRepositoryTest extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;
    protected AbstractDatabaseTool $databaseTool;
    protected ReferenceRepository $referenceRepository;
    protected CrstsSchemeReturnRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->databaseTool = static::getContainer()
            ->get(DatabaseToolCollection::class)
            ->get();

        $fixtures = $this->databaseTool
            ->loadFixtures([SchemeReturnPointWhereNotEditable::class]);

        $this->referenceRepository = $fixtures->getReferenceRepository();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->repository = $this->entityManager->getRepository(CrstsSchemeReturn::class);
    }

    /**
     * @dataProvider schemeReturnScenarios
     */
    public function testFindPointWhereReturnBecomesNotEditable(string $targetRef, ?string $expectedClosedRef): void
    {
        /** @var CrstsSchemeReturn $target */
        $target = $this->referenceRepository->getReference($targetRef, CrstsSchemeReturn::class);
        $expected = $expectedClosedRef ? $this->referenceRepository->getReference($expectedClosedRef, CrstsSchemeReturn::class) : null;

//        $x = $this->entityManager->getConnection()
//            ->executeQuery(<<<SQL
//SELECT fr.year, fr.quarter, csr.on_track_rating
//FROM crsts_scheme_return csr
//JOIN scheme_return sr ON csr.id = sr.id
//JOIN fund_return fr ON sr.fund_return_id = fr.id
//WHERE sr.scheme_id = :schemeId
//SQL, [
//    'schemeId' => $target->getScheme()->getId()
//], [
//    'schemeId' => UlidType::NAME
//])
//            ->fetchAllAssociative();
//        dump($x);

        $result = $this->repository->findPointWhereReturnBecameNonEditable($target);

        if ($expected === null) {
            $this->assertNull($result, "Expected null (editable), got a closed return instead.");
        } else {
            $this->assertInstanceOf(CrstsSchemeReturn::class, $result);
            $this->assertSame($expected->getId(), $result->getId(), "Returned closed return does not match expected.");
        }
    }

    public static function schemeReturnScenarios(): array
    {
        return [
            // Scheme 1 (primary)
            '1a: editable - no previous return' => ['sr_1_2023_1', null],
            '1b: editable - no previous closed return' => ['sr_1_2023_2', null],
            '1c: not editable - previous return closed' => ['sr_1_2023_3', 'sr_1_2023_2'],
            '1d: not editable - return -2 closed' => ['sr_1_2023_4', 'sr_1_2023_2'],
            '1e: not editable - return -3 closed' => ['sr_1_2023_4', 'sr_1_2023_2'],
            '1f: not editable - return -4 closed ' => ['sr_1_2024_1', 'sr_1_2024_2'],
            '1g: editable - previous return open' => ['sr_1_2024_2', null],
            '1h: not editable - previous return closed' => ['sr_1_2024_3', 'sr_1_2024_2'],

            // Scheme 2 (secondary scheme)
            '2a: editable - no previous return' => ['sr_2_2023_1', null],
            '2b: editable - no previous closed return' => ['sr_2_2023_2', null],
            '2c: editable - still no previous closed return' => ['sr_2_2023_3', null],
            '2d: not editable - previous return closed' => ['sr_2_2023_4', 'sr_2_2023_3'],
            '2e: editable - previous return closed' => ['sr_2_2024_1', null],
        ];
    }
}
