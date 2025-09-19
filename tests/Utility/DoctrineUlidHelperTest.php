<?php

namespace App\Tests\Utility;

use App\Utility\DoctrineUlidHelper;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

class DoctrineUlidHelperTest extends TestCase
{
    private DoctrineUlidHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new DoctrineUlidHelper();
    }

    /**
     * @dataProvider ulidParametersProvider
     */
    public function testGetSqlForWhereInAndInjectParams(string $prefix, array $ulids, string $expectedSql, array $expectedParams): void
    {
        /** @var QueryBuilder&MockObject $qb */
        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->exactly(count($ulids)))
            ->method('setParameter')
            ->willReturnCallback(function($key, $value, $type) use (&$expectedParams, $qb) {
                $this->assertArrayHasKey($key, $expectedParams);
                $this->assertEquals($expectedParams[$key], $value);
                $this->assertEquals(UlidType::NAME, $type);
                return $qb;
            });

        $result = $this->helper->getSqlForWhereInAndInjectParams($qb, $prefix, $ulids);

        $this->assertEquals($expectedSql, $result);
    }

    public function ulidParametersProvider(): array
    {
        $ulid1 = new Ulid();
        $ulid2 = new Ulid();
        $ulid3 = new Ulid();

        return [
            'single ULID with standard prefix' => [
                'prefix' => 'test',
                'ulids' => [$ulid1],
                'expectedSql' => ':test_value_0',
                'expectedParams' => ['test_value_0' => $ulid1],
            ],
            'multiple ULIDs with standard prefix' => [
                'prefix' => 'test',
                'ulids' => [$ulid1, $ulid2, $ulid3],
                'expectedSql' => ':test_value_0,:test_value_1,:test_value_2',
                'expectedParams' => [
                    'test_value_0' => $ulid1,
                    'test_value_1' => $ulid2,
                    'test_value_2' => $ulid3,
                ],
            ],
            'single ULID with custom prefix' => [
                'prefix' => 'my_custom_prefix',
                'ulids' => [$ulid1],
                'expectedSql' => ':my_custom_prefix_value_0',
                'expectedParams' => ['my_custom_prefix_value_0' => $ulid1],
            ],
        ];
    }

    public function testGetSqlForWhereInThrowsExceptionForEmptyArray(): void
    {
        /** @var QueryBuilder&MockObject $qb */
        $qb = $this->createMock(QueryBuilder::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This method should not be passed an empty array');

        $this->helper->getSqlForWhereInAndInjectParams($qb, 'test', []);
    }
}
