<?php

namespace App\Utility;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Psr7\StreamWrapper;
use GuzzleHttp\Psr7\Utils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Uid\Ulid;
use ZipStream\ZipStream;

class CsvExporter
{
    protected array $ulidColumns;

    public function __construct(
        protected Connection $connection,
    ) {
        $ulidColumns = [
            'authority_id',
            'fund_return_id',
            'scheme_id',
            'scheme_return_id',
        ];
        $this->ulidColumns = array_combine($ulidColumns, $ulidColumns);
    }

    public function exportZip(?int $returnYear=null, ?int $returnQuarter=null): StreamedResponse
    {
        $filenamePrefix = 'crsts';

        if ($returnYear !== null && $returnQuarter !== null) {
            $nextYear = substr(strval($returnYear + 1), - 2);
            $filenamePrefix .= "_{$returnYear}_{$nextYear}_Q{$returnQuarter}";
        }

        return new StreamedResponse(function() use ($filenamePrefix, $returnYear, $returnQuarter) {

            $views = [
                'export_fund_return_data' => "export_fund_return_data.csv",
                'export_fund_return_expense_data' => "export_fund_return_expense_data.csv",
                'export_scheme_return_data' => "export_scheme_return_data.csv",
                'export_scheme_return_expense_data' => "export_scheme_return_expense_data.csv",
            ];

            $filenameSuffix = (new \DateTime())->format('Ymd_Hisv');
            $zip = new ZipStream(
                sendHttpHeaders: true,
                outputName: "{$filenamePrefix}_{$filenameSuffix}.zip",
                flushOutput: true
            );

            foreach($views as $view => $filename) {
                $generator = $this->viewToCsvLines($view, $returnYear, $returnQuarter);
                $stream = StreamWrapper::getResource(Utils::streamFor($generator));
                $zip->addFileFromStream($filename, $stream);
            }

            $zip->finish();
        });
    }

    /**
     * @return \Generator<string>
     */
    protected function viewToCsvLines(string $view, ?int $returnYear, ?int $returnQuarter): \Generator
    {
        $qb = $this->connection->createQueryBuilder()
            ->from($view, 'v')
            ->select('v.*');

        if ($returnYear !== null && $returnQuarter !== null ) {
            $qb->andWhere('v.return_year = :returnYear')
                ->andWhere('v.return_quarter = :returnQuarter')
                ->setParameter('returnYear', $returnYear)
                ->setParameter('returnQuarter', $returnQuarter);
        }

        $result = $qb->executeQuery();

        $headerWritten = false;

        foreach($result->iterateAssociative() as $row) {
            if (!$headerWritten) {
                yield $this->csvLine(array_keys($row));
                $headerWritten = true;
            }

            foreach($row as $idx => $value) {
                if (isset($this->ulidColumns[$idx])) {
                    $row[$idx] = Ulid::fromBinary($value)->toRfc4122();
                }
            }

            yield $this->csvLine($row);
        }
    }

    protected function csvLine(array $fields): string
    {
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, $fields, escape: '');
        rewind($fh);
        $line = stream_get_contents($fh);
        fclose($fh);
        return $line;
    }
}
