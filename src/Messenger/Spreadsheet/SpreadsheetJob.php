<?php

namespace App\Messenger\Spreadsheet;

use App\Entity\FundReturn\FundReturn;
use App\Messenger\AlphagovNotify\AbstractMessage;
use App\Messenger\JobInterface;
use Symfony\Component\Uid\Ulid;

class SpreadsheetJob extends AbstractMessage implements JobInterface
{
    protected string $id;
    protected string $fundReturnId;

    public function __construct(
        FundReturn $fundReturn,
    )
    {
        $this->id = Ulid::generate();
        $this->fundReturnId = strval($fundReturn->getId());
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFundReturnId(): string
    {
        return $this->fundReturnId;
    }
}
