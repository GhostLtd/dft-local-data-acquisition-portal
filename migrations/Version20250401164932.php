<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250401164932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create property change log table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE property_change_log (entity_id BINARY(16) NOT NULL, action VARCHAR(6) NOT NULL, entity_class VARCHAR(255) NOT NULL, property_name VARCHAR(255) DEFAULT NULL, property_value JSON DEFAULT NULL, user_email VARCHAR(255) DEFAULT NULL, firewall_name VARCHAR(10) DEFAULT NULL, timestamp DATETIME NOT NULL, id BINARY(16) NOT NULL, INDEX property_change_idx (entity_id, entity_class), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE property_change_log');
    }
}
