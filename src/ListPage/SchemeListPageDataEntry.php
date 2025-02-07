<?php

namespace App\ListPage;

use App\Entity\SchemeFund\CrstsSchemeFund;
use App\Entity\SchemeReturn\CrstsSchemeReturn;

class SchemeListPageDataEntry
{
    public function __construct(
        protected CrstsSchemeFund $schemeFund,
        protected ?CrstsSchemeReturn $schemeReturn,
    ) {}

    public function getSchemeFund(): CrstsSchemeFund
    {
        return $this->schemeFund;
    }

    public function getSchemeReturn(): ?CrstsSchemeReturn
    {
        return $this->schemeReturn;
    }
}