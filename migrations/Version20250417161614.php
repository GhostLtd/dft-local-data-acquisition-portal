<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250417161614 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'change expense fields to 2 decimal places';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE crsts_scheme_return CHANGE total_cost total_cost NUMERIC(14, 2) DEFAULT NULL, CHANGE agreed_funding agreed_funding NUMERIC(14, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE expense_entry CHANGE value value NUMERIC(14, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE crsts_scheme_return CHANGE total_cost total_cost NUMERIC(12, 0) DEFAULT NULL, CHANGE agreed_funding agreed_funding NUMERIC(12, 0) DEFAULT NULL');
        $this->addSql('ALTER TABLE expense_entry CHANGE value value NUMERIC(12, 0) DEFAULT NULL');
    }
}
