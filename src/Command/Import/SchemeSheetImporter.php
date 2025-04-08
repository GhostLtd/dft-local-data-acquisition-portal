<?php

namespace App\Command\Import;

use App\Entity\Enum\TransportMode;
use App\Entity\Scheme;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;

class SchemeSheetImporter extends AbstractSheetImporter
{
    protected const array COLUMNS = [
        'name' => 'name',
        'authorityName' => 'location',
        'crstsData.retained' => 'crsts_data_is_retained',
        'description' => 'description',
        'crstsData.previouslyTcf' => 'crsts_data_is_previously_tcf',
        'transportMode' => 'transport_mode',
    ];

    protected function processRow(Row $row): void
    {
        $values = $this->getCellValues($row);
        [$authorityName, $values] = $this->extractValueFromArray($values, 1);
        if (!($scheme = $this->findSchemeByName($values[0], $authorityName))) {
            $scheme = (new Scheme())
                ->setAuthority($this->findAuthorityByName($authorityName));
            $this->persist($scheme);
        }
        $values[5] = $this->attemptToFormatAsEnum(TransportMode::class, $values[5]);
        $values[2] = $values[2] === 'Retained';
        $values[4] = $values[4] === 'Y';
        $this->setColumnValues($scheme, $values);
    }
}