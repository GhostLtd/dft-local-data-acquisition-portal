<?php

namespace App\ListPage;

use App\Entity\Scheme;
use App\Entity\SchemeReturn\CrstsSchemeReturn;

class SchemeListPageDataEntry
{
    public function __construct(
        protected Scheme             $scheme,
        protected ?CrstsSchemeReturn $schemeReturn,
    ) {}

    public function getScheme(): Scheme
    {
        return $this->scheme;
    }

    public function getSchemeReturn(): ?CrstsSchemeReturn
    {
        return $this->schemeReturn;
    }
}
