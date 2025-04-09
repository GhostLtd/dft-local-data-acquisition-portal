<?php

namespace App\Command\Import;

use App\Entity\Authority;
use App\Entity\Enum\Fund;
use App\Entity\FundAward;
use App\Entity\User;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;

class UserSheetImporter extends AbstractSheetImporter
{
    protected const array COLUMNS = [
        'name' => 'ucla',
        'admin.name' => 'name',
        'admin.position' => 'position',
        'admin.phone' => 'phone',
        'admin.email' => 'email',
    ];

    protected function processRow(Row $row): void
    {
        $values = $this->getCellValues($row);
        if ($this->findAuthorityByName($values['name'])) {
            return;
        }
        $authority = (new Authority())
            ->setAdmin(new User())
            ->addFundAward($fundAward = (new FundAward())->setType(Fund::CRSTS1));

        $this->setColumnValues($authority, $values);

        $this->persist($authority, $authority->getAdmin(), $fundAward);
    }
}