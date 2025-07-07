<?php

namespace App\Command\Fix;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractSheetBasedCommand extends Command
{
    public function convertAuthorityName(?string $authName): string
    {
        return str_replace([
            'MCA', 'CA', 'The West', 'North East Joint Transport Committee / Transport North East',
        ], [
            'Mayoral Combined Authority', 'Combined Authority', 'West', 'North East Combined Authority',
        ], $authName);
    }

    /** @return array<int, array<int, string>> */
    protected function getSourcesByYear(SymfonyStyle $io, string $importPath): array
    {

        $sourcesByYear = [];

        foreach(new \DirectoryIterator($importPath) as $fileInfo) {
            if (
                $fileInfo->isDot()
                || $fileInfo->isDir()
                || $fileInfo->getExtension() !== 'xlsx'
            ) {
                continue;
            }

            if (!preg_match('/(?P<year>\d\d)(?P<nextYear>\d\d)Q(?P<quarter>\d)/i', $fileInfo->getFilename(), $matches)) {
                $io->warning('Skipping non-import file: ' . $fileInfo->getFilename());
                continue;
            }

            ['year' => $year, 'nextYear' => $nextYear, 'quarter' => $quarter] = $matches;

            $year = 2000 + intval($year);
            $nextYear = 2000 + intval($nextYear);
            $quarter = intval($quarter);

            if ($year + 1 !== $nextYear) {
                $io->error('Invalid import file: ' . $fileInfo->getFilename());
                continue;
            }

            $sourcesByYear[$year] ??= [];
            $sourcesByYear[$year][$quarter] = $fileInfo->getRealpath();
        }

        ksort($sourcesByYear);
        foreach($sourcesByYear as $year => $item) {
            ksort($sourcesByYear[$year]);
        }

        return $sourcesByYear;
    }
}