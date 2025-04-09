<?php

namespace App\Command\Import;

use App\Entity\Authority;
use App\Entity\Enum\Fund;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use BackedEnum;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Psr\Log\LoggerInterface;
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
        protected LoggerInterface $logger,
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
//        $values = array_values();
        return array_combine(
            array_keys(static::COLUMNS),
            array_map(fn(Cell $c) => $this->getCellValue($c), iterator_to_array($row->getCellIterator()))
        );
    }

    protected function extractValueFromArray(array &$values, mixed $key): mixed
    {
        $value = $values[$key];
        unset($values[$key]);
        return $value;
    }

    protected function getCellValue(Cell $cell): mixed
    {
        $value = trim($cell->getValue());
        if ($value === '') {
            return null;
        }
        return $value;
    }

    protected function attemptToFormatAsDate(?string $value): ?\DateTimeInterface
    {
        return match(true) {
            1 === preg_match('/^4|5\d{4}$/', $value) => Date::excelToDateTimeObject($value),
            1 === preg_match('/^(?<day>\d{2})\.(?<month>\d{2})\.(?<year>\d{4})$/', $value, $matches) => new \DateTime($matches['year'] . '-' . $matches['month'] . '-' . $matches['day']),
            default => $value ? ($this->logger->error("Invalid date format: '$value'") ?? null) : null
        };
    }

    protected function attemptToFormatAsFinancial(?string $value): ?string
    {
        $originalValue = $value;
        $value = $this->attemptToFormatAsDecimal($value);
        if ($value > 0) {
            if ($value < 1000) {
                $value *= 1000000;
                $this->logger->info("Financial multiplied by 1m: '$originalValue'");
            } elseif ($value > 10000000000) {
                $value /= 1000000;
                $this->logger->info("Financial divided by 1m: '$originalValue'");
            }
        }

        if ($originalValue && !is_numeric($value)) {
            $this->logger->error("Financial conversion failed: '$originalValue'");
        }

        return "" . intval($value);
    }

    protected function attemptToFormatAsDecimal(?string $value): ?float
    {
        return match(true) {
            1 === preg_match('/^£?(?<val>\d+(\.\d+)?)m?$/iu', $value, $matches)
                => floatval($matches['val']),
            1 === preg_match('/^[+\-]?(?=.)(?:0|[1-9]\d*)?(?:\.\d*)?(?:\d[eE][+\-]?\d+)?$/', $value)
                => intval($value),
            default => (null !== $value) ? ($this->logger->error("unable to transform decimal: '$value'") ?? null) : null
        };
    }

    protected function attemptToFormatAsEnum(string $enumClass, ?string $value): ?BackedEnum
    {
        $originalEnumClass = $enumClass; // needed to avoid syntax highlight in PHP storm
        if (null === $value || !$enumClass instanceof BackedEnum) {
            return null;
        }
        return $enumClass::tryFrom(strtolower(str_replace(['/'], ['_'], $value)))
            ?? ($this->logger->warning("Enum format failed: '$originalEnumClass'/'$value'") ?? null);
    }

    protected function setColumnValues(object $obj, array $values): void
    {
        foreach ($values as $k=>$v) {
            try {
                $this->propertyAccessor->setValue($obj, $k, $v);
            } catch (\Throwable $th) {
                $this->io->writeln("unable to process '$v': {$th->getMessage()}");
                $this->logger->error("Unable to set '$k' => '$v': {$th->getMessage()}");
            }
        }
    }

    protected function getSchemeAndAuthorityNames(?string $schemeIdentifier): array
    {
        return array_map(fn($v) => trim($v), explode('_', $schemeIdentifier));
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

    protected function findCrstsSchemeReturnByName(string $schemeName, string $authorityName): ?CrstsSchemeReturn
    {
        if ($this->isMissingZebraScheme("{$schemeName}_{$authorityName}")) {return null;}

        $fundReturn = $this->findCrstsFundReturnByAuthorityName($authorityName);
        if (!$fundReturn) {
            throw new \RuntimeException('crsts fund return not found: ' . $authorityName);
        }
        $scheme = $this->findSchemeByName($schemeName, $authorityName);
        if (!$scheme) {
            throw new \RuntimeException('scheme not found: ' . $schemeName);
        }
        return $this->entityManager->getRepository(CrstsSchemeReturn::class)->findOneBy([
            'scheme' => $scheme,
            'fundReturn' => $fundReturn,
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

    protected function isMissingZebraScheme(string $identifier): bool
    {
        return in_array($identifier, [
            'Electric Vehicles (EV) Buses_Greater Manchester CA',
            'CRSTS22/1 Zero Emission Buses - Phase 1 _South Yorkshire MCA',
            'CRSTS22/1 Zero Emission Buses - Phase 1_South Yorkshire MCA',
            'Overprogramming - Zero Emission Buses - additional scope opportunity_South Yorkshire MCA',
            '(51) Zero Emission Buses_The West Yorkshire Combined Authority',
        ]);
    }
}