<?php

namespace App\Command\Import;

use App\Entity\Authority;
use App\Entity\Enum\Fund;
use App\Entity\Enum\Rating;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

abstract class AbstractSheetImporter
{
    protected const array COLUMNS = [];
    protected SymfonyStyle $io;
    protected int $quarter;
    protected int $year;
    protected Worksheet $sheet;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected PropertyAccessorInterface $propertyAccessor,
    ) {}

    public function import(SymfonyStyle $io, Worksheet $sheet, int $year, int $quarter): void
    {
        $this->io = $io;
        $this->sheet = $sheet;
        $this->year = $year;
        $this->quarter = $quarter;
        $this->io->section("Running: " . (new \ReflectionClass($this))->getShortName());
        $this->verifyExpectedHeaderRow($sheet, static::COLUMNS);

        $this->io->progressStart($sheet->getHighestDataRow() - 1);
        foreach ($sheet->getRowIterator(2) as $row) {
            $this->processRow($row);
            $this->io->progressAdvance();
        }
        $this->entityManager->flush();
        $this->io->progressFinish();
    }

    abstract protected function processRow(Row $row): void;

    protected function verifyExpectedHeaderRow(Worksheet $sheet, array $expectedHeaders): void
    {
        $row = $sheet->getRowIterator(1)->current();
        $cellValues = $this->getCellValues($row);
        if (!empty(array_diff($cellValues, $expectedHeaders))) {
            throw new \RuntimeException('headers not as expected: ' . implode(', ', $cellValues));
        }
    }

    protected function persist(object $obj, ...$objects): void
    {
        $this->entityManager->persist($obj);
        array_walk($objects, fn (object $object) => $this->entityManager->persist($object));
    }

    protected function getCellValues(Row $row): array
    {

        return array_values(array_map(fn(Cell $c) => $this->getCellValue($c), iterator_to_array($row->getCellIterator())));
    }

    protected function extractValueFromArray(array &$values, int $index): array
    {
        $value = $values[$index];
        $newValues =
            array_slice($values, 0, $index, true)
            + array_slice($values, $index + 1, null, true)
            ;
        return [$value, $newValues];
    }

    protected function getCellValue(Cell $cell): mixed
    {
        $value = trim($cell->getValue());
        if ($value === '') {
            return null;
        }
        if ($date = $this->attemptToFormatAsDate($value)) {
            return $date;
        }
        if ($rating = $this->attemptToFormatAsRating($value)) {
            return $rating;
        }
        return $value;
    }

    protected function attemptToFormatAsDate(string $value): ?\DateTimeInterface
    {
        return match(true) {
            1 === preg_match('/^4\d{4}$/', $value) => Date::excelToDateTimeObject($value),
            1 === preg_match('/^(?<day>\d{2})\.(?<month>\d{2})\.(?<year>\d{4})$/', $value, $matches) => new \DateTime($matches['year'] . '-' . $matches['month'] . '-' . $matches['day']),
            default => null
        };
    }

    protected function attemptToFormatAsRating(string $value): ?Rating
    {
        return Rating::tryFrom(strtolower(str_replace(['/'], ['_'], $value)));
    }

    protected function setColumnValues(object $obj, array $values): void
    {
        foreach ($values as $k=>$v) {
            try {
                $this->propertyAccessor->setValue($obj, array_keys(static::COLUMNS)[$k], $v);
            } catch (\Throwable $th) {
                $this->io->writeln("unable to process '$v': {$th->getMessage()}");
            }
        }
    }

    protected function findAuthorityByName(string $name): ?Authority
    {
        return $this->entityManager->getRepository(Authority::class)->findOneBy(['name' => $name]);
    }

    protected function findCrstsFundAwardByAuthorityName(string $authorityName): ?FundAward
    {
        $authority = $this->findAuthorityByName($authorityName);
        return $authority?->getFundAwards()->filter(fn(FundAward $fa) => $fa->getType() === Fund::CRSTS1)->first();
    }

    protected function findCrstsFundReturnByAuthorityName(string $authorityName): ?CrstsFundReturn
    {
        $authority = $this->findAuthorityByName($authorityName);
        if (!$authority) {
            throw new \RuntimeException('authority not found: ' . $authorityName);
        }
        return $this->entityManager->getRepository(FundReturn::class)->findOneBy([
            'fundAward' => $authority->getFundAwards()->filter(fn(FundAward $fa) => $fa->getType() === Fund::CRSTS1)->first(),
            'year' => $this->year,
            'quarter' => $this->quarter
        ]);
    }

    protected function findSchemeByName(string $schemeName, string $authorityName): ?Scheme
    {
        $authority = $this->findAuthorityByName($authorityName);
        if (!$authority) {
            throw new \RuntimeException('authority not found: ' . $authorityName);
        }
        return $this->entityManager->getRepository(Scheme::class)->findOneBy([
            'name' => $schemeName,
            'authority' => $authority,
        ]);
    }
}