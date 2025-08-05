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
use Throwable;

abstract class AbstractSheetImporter
{
    protected const array COLUMNS = [];
    protected SymfonyStyle $io;
    protected int $quarter;
    protected int $year;
    protected Worksheet $sheet;

    protected const array AUTHORITY_NAME_MAP = [
        'Greater Manchester CA' => 'Greater Manchester Combined Authority',
        'South Yorkshire MCA' => 'South Yorkshire Mayoral Combined Authority',
        'North East Joint Transport Committee / Transport North East' => 'North East Combined Authority',
        'The West Yorkshire Combined Authority' => 'West Yorkshire Combined Authority',
    ];


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
        $cellValues = array_map(fn(Cell $c) => $c->getValue(), iterator_to_array($row->getCellIterator()));
        if (!empty(array_diff($cellValues, $expectedHeaders))) {
            dump(['values' => array_values($cellValues), 'expected' => array_values($expectedHeaders)]);
            throw new \RuntimeException('headers not as expected');
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
        try {
            return array_combine(
                $k = array_keys(static::COLUMNS),
                $v = array_map(fn(Cell $c) => $this->getCellValue($c), iterator_to_array($row->getCellIterator()))
            );
        } catch (\Throwable) {
            dump($k ?? null, $v ?? null); exit;
        }
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
        $ignoreValues = ['-', 'tbc', 'n/a', 'post fbc', 'development only', 'various', 'development progressing but dates tbc'];

        if (in_array(strtolower($value), $ignoreValues)) {
            $this->logger->info("Ignored date", [$value]);
            return null;
        }

        if ($value === null) {
            return null;
        }

        if (preg_match('/^[345]\d{4}$/', $value)) {
            return Date::excelToDateTimeObject($value);
        }

        if (preg_match('/^(?<day>[012]\d|3[01])\.(?<month>0\d|1[012])\.(?<year>20\d{2})$/', $value, $matches)) {
            return new \DateTime($matches['year'] . '-' . $matches['month'] . '-' . $matches['day']);
        }

        if (preg_match('/^(?<day>[012]\d|3[01])\/(?<month>0\d|1[012])\/(?<year>20\d{2})/', $value, $matches)) {
            $this->logger->info("partial date match", [$value]);
            return new \DateTime($matches['year'] . '-' . $matches['month'] . '-' . $matches['day']);
        }

        if (preg_match('/^(?<year>20\d{2})$/', $value, $matches)) {
            return new \DateTime($matches['year'] . '-01-01');
        }

        try{
            return new \DateTime($value);
        } catch(Throwable){
            $this->logger->error("Invalid date format", [$value]);
            return null;
        }
    }

    protected function attemptToFormatAsFinancial(?string $value, $autoMultiply = false, array $additionalLoggingContext = []): ?string
    {
        $originalValue = $value;
        $value = $this->attemptToFormatAsDecimal($value);
        if ($autoMultiply && $value !== 0) {
            if ($value < 1000 && $value > -10) {
                $value *= 1000000;
                $this->logger->debug("Financial multiplied by 1m", array_merge(['orig' => $originalValue, 'new' => $value], $additionalLoggingContext));
            } /* elseif ($value > 5000000000) {
                $value /= 1000000;
                $this->logger->warning("Financial divided by 1m", array_merge(['orig' => $originalValue, 'new' => $value], $additionalLoggingContext));
            } */
        }

        if ($originalValue && $value !== null && !is_numeric($value)) {
            $this->logger->error("Financial conversion failed", [$originalValue, $value]);
        }

        return "" . floatval($value);
    }

    protected function attemptToFormatAsDecimal(?string $value): ?float
    {
        $ignoreValues = ['tbc', 'n/a'];

        if ($value === null) {
            return null;
        }

        if (in_array(strtolower($value), $ignoreValues)) {
            $this->logger->info("ignore decimal value", [$value]);
            return null;
        }

        if (preg_match('/^£?(?<val>\d+(\.\d+)?)m?$/iu', $value, $matches)) {
            return floatval($matches['val']);
        }

        if (preg_match('/^[+\-]?(?=.)(?:0|[1-9]\d*)?(?:\.\d*)?(?:\d[eE][+\-]?\d+)?$/', $value)) {
            return floatval($value);
        }

        $this->logger->error("unable to transform decimal", [$value]);
        return null;
    }

    protected function attemptToFormatAsEnum(string $enumClass, ?string $value): ?BackedEnum
    {
        $originalEnumClass = $enumClass; // needed to avoid syntax highlight in PHP storm
        if (null === $value || !$enumClass instanceof BackedEnum) {
            return null;
        }
        return $enumClass::tryFrom(strtolower(str_replace(['/'], ['_'], $value)))
            ?? ($this->logger->warning("Enum format failed", [$originalEnumClass, $value]) ?? null);
    }

    protected function setColumnValues(object $obj, array $values): void
    {
        foreach ($values as $k=>$v) {
            try {
                $this->propertyAccessor->setValue($obj, $k, $v);
            } catch (\Throwable $th) {
                $this->io->writeln("unable to process '$v': {$th->getMessage()}");
                $this->logger->error("Unable to set value", [$k, $v, $th->getMessage()]);
            }
        }
    }

    protected function getSchemeAndAuthorityNames(?string $schemeIdentifier): array
    {
        [$schemeName, $authorityName] = array_map(fn($v) => trim($v), explode('_', $schemeIdentifier));
        $authorityName = static::AUTHORITY_NAME_MAP[$authorityName] ?? $authorityName;
        return [$schemeName, $authorityName];
    }

    protected function findAuthorityByName(string $name): ?Authority
    {
        $name = static::AUTHORITY_NAME_MAP[$name] ?? $name;
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
        return $this->entityManager->getRepository(CrstsFundReturn::class)->findOneBy([
            'fundAward' => $authority->getFundAwards()->filter(fn(FundAward $fa) => $fa->getType() === Fund::CRSTS1)->first(),
            'year' => $this->year,
            'quarter' => $this->quarter
        ]);
    }

    protected function findCrstsSchemeReturnByName(string $schemeName, string $authorityName): ?CrstsSchemeReturn
    {
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
}