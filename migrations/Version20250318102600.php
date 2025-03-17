<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250318102600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Scheme: Add funds column, move risks to scheme_return, and allow crstsData.retained to be null (e.g. when crstsData not filled)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE scheme CHANGE crsts_data_retained crsts_data_retained TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE scheme ADD funds JSON NOT NULL');
        $this->addSql('ALTER TABLE scheme DROP risks, DROP includes_clean_air_elements, DROP includes_charging_points');
        $this->addSql('ALTER TABLE scheme_return ADD risks LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE scheme CHANGE crsts_data_retained crsts_data_retained TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE scheme DROP funds');
        $this->addSql('ALTER TABLE scheme_return DROP risks');
        $this->addSql('ALTER TABLE scheme ADD includes_clean_air_elements TINYINT(1) DEFAULT NULL, ADD includes_charging_points TINYINT(1) DEFAULT NULL');
    }
}
