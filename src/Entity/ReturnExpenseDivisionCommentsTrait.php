<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait ReturnExpenseDivisionCommentsTrait
{
    /**
     * @var array<string>
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $expenseDivisionComments = [];

    public function setExpenseDivisionComment(string $divKey, ?string $comment): static
    {
        if (empty($comment)) {
            unset($this->expenseDivisionComments[$divKey]);
        } else {
            $this->expenseDivisionComments[$divKey] = $comment;
        }
        return $this;
    }

    public function getExpenseDivisionComment(string $divKey): ?string
    {
        return $this->expenseDivisionComments[$divKey] ?? null;
    }

}