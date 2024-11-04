<?php

namespace App\DataFixtures\Definition;

class ContactDefinition
{
    public function __construct(
        protected string $name,
        protected string $position,
        protected string $phone,
        protected string $email,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
