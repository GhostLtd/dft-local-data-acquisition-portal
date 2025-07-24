<?php

namespace App\Messenger\DataExport;

use App\Messenger\AlphagovNotify\AbstractMessage;
use Symfony\Component\Uid\Ulid;

class DataExportJob extends AbstractMessage
{
    protected string $id;
    protected ?int $year;
    protected ?int $quarter;

    public function __construct(
        ?int $year = null,
        ?int $quarter = null
    )
    {
        $this->id = strval(Ulid::generate());
        $this->year = $year;
        $this->quarter = $quarter;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function getQuarter(): ?int
    {
        return $this->quarter;
    }
}