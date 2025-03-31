<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250331106846 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE crsts_fund_return CHANGE progress_summary progress_summary TEXT DEFAULT NULL, CHANGE delivery_confidence delivery_confidence TEXT DEFAULT NULL, CHANGE local_contribution local_contribution TEXT DEFAULT NULL, CHANGE resource_funding resource_funding TEXT DEFAULT NULL, CHANGE comments comments TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE crsts_scheme_return CHANGE progress_update progress_update TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE scheme CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE scheme_return CHANGE risks risks TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE crsts_scheme_return CHANGE progress_update progress_update LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE scheme_return CHANGE risks risks LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE crsts_fund_return CHANGE progress_summary progress_summary LONGTEXT DEFAULT NULL, CHANGE delivery_confidence delivery_confidence LONGTEXT DEFAULT NULL, CHANGE local_contribution local_contribution LONGTEXT DEFAULT NULL, CHANGE resource_funding resource_funding LONGTEXT DEFAULT NULL, CHANGE comments comments LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE scheme CHANGE description description LONGTEXT DEFAULT NULL');
    }
}
