<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250429092057 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'PropertyChangeLog: Rename user_email to source';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property_change_log CHANGE user_email source VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property_change_log CHANGE source user_email VARCHAR(255) DEFAULT NULL');
    }
}
