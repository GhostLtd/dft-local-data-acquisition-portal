<?php

namespace App\Utility;

use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

class DoctrineUlidHelper
{
    /**
     * We have all of this messing around, because we need to specify our parameterType, but
     * ArrayParameterType only supports a few primitives, and "ulid" isn't one of them. As such
     * we need to do the expansion ourselves and specify each of the individual parameters in
     * the "IN (:params)" [i.e. IN (:param1, :param2, ...)]
     *
     * @param array<int, Ulid> $ulids
     */
    public function getSqlForWhereInAndInjectParams(QueryBuilder $qb, string $paramPrefix, array $ulids): string
    {
        if (empty($ulids)) {
            throw new \RuntimeException('This method should not be passed an empty array');
        }

        $paramKeys = [];
        foreach($ulids as $x => $ulid) {
            $paramKey = "{$paramPrefix}_value_{$x}";
            $paramKeys[] = ":{$paramKey}";
            $qb->setParameter($paramKey, $ulid, UlidType::NAME);
        }

        return join(',', $paramKeys);
    }
}
