<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250331105045 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'convert table collations';
    }

    public function up(Schema $schema): void
    {
        $tables = $this->connection->executeQuery('show full tables where Table_type = "BASE TABLE"')->fetchAllNumeric();
        foreach ($tables as $table) {
            $this->convertTable($table[0]);
        }
    }

    public function down(Schema $schema): void
    {
        $tables = $this->connection->executeQuery('show full tables where Table_type = "BASE TABLE"')->fetchAllNumeric();
        foreach ($tables as $table) {
            $this->convertTable($table[0], 'utf8mb3', 'utf8mb3_general_ci');
        }
    }

    protected function convertTable(string $tableName, string $characterSet = 'utf8mb4', string $collation = 'utf8mb4_unicode_ci'): void
    {
        $this->addSql("alter table $tableName convert to character set $characterSet collate $collation;");
    }
}
