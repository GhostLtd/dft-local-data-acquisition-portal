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
        $authorityName = $this->extractValueFromArray($values, 'authorityName');
        if (!($scheme = $this->findSchemeByName($values['name'], $authorityName))) {
            $scheme = (new Scheme())
                ->setAuthority($this->findAuthorityByName($authorityName));
            $this->persist($scheme);
        }

        $values['transportMode'] = $this->attemptToFormatAsEnum(TransportMode::class, $values['transportMode']);
        $values['crstsData.retained'] = ($values['crstsData.retained'] === 'Retained');
        $values['crstsData.previouslyTcf'] = ($values['crstsData.previouslyTcf'] === 'Y');

        $this->setColumnValues($scheme, $values);
    }
}