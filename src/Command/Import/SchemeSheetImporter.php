<?php

namespace App\Command\Import;

use App\Entity\Enum\TransportMode;
use App\Entity\Scheme;
use BackedEnum;
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
        'schemeType' => 'scheme_type',
    ];

    protected function processRow(Row $row): void
    {
        $values = $this->getCellValues($row);
        $authorityName = $this->extractValueFromArray($values, 'authorityName');
        unset($values['schemeType']);
        $authority = $this->findAuthorityByName($authorityName);
        if (!($scheme = $this->findSchemeByName($values['name'], $authorityName))) {
            $authority->addScheme(
                $scheme = (new Scheme())
                    ->addFund($authority->getFundAwards()->first()->getType())
            );
            $this->persist($scheme);
        }

        $values['transportMode'] = $this->attemptToFormatAsTransportMode(TransportMode::class, $values['transportMode']);
        $values['crstsData.retained'] = ($values['crstsData.retained'] === 'Retained');
        $values['crstsData.previouslyTcf'] = ($values['crstsData.previouslyTcf'] === 'Y');

        $this->setColumnValues($scheme, $values);
    }

    protected function attemptToFormatAsTransportMode(string $enumClass, ?string $value): ?TransportMode
    {
        if ($value === null) {
            return null;
        }
        $map = [
            'multi-modal' => TransportMode::MM_OTHER,
            'bus' => TransportMode::BUS_OTHER,
            'other' => TransportMode::OTHER_OTHER,
            'rail' => TransportMode::RAIL_OTHER,
            'tram/metro/light rail' => TransportMode::TRAM_OTHER,
            'active travel' => TransportMode::AT_OTHER,
            'highways maintenance' => TransportMode::ROAD_HIGHWAYS_MAINTENANCE,
            'other maintenance' => TransportMode::OTHER_OTHER,
            'multi-modal (inc. at or bus)' => TransportMode::MM_OTHER,

        ];

        $mode = $map[strtolower($value)] ?? null;
        if ($mode === null) {
            $this->logger->warning('Unhandled transport mode', [$value]);
        }

        return $mode;
    }
}
