<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250312102417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adjust permissions_view to remove erroneous unique index';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_472E544681257D5D ON user_permission');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_472E544681257D5D ON user_permission (entity_id)');
    }
}
