<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250422140804 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add "development_only" flag for CRSTS';
    }

    public function isTransactional(): bool
    {
        // See: https://www.doctrine-project.org/projects/doctrine-migrations/en/stable/explanation/implicit-commits.html
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE crsts_scheme_return ADD development_only TINYINT(1) DEFAULT NULL');
        $this->addSql(<<<EOQ
WITH schemes_with_non_dev AS (
    SELECT DISTINCT s.id
    FROM crsts_scheme_return s
    JOIN crsts_scheme_return_milestone sm ON s.id = sm.crsts_scheme_return_id
    JOIN milestone m ON sm.milestone_id = m.id
    WHERE m.type IN ('start_construction', 'end_construction', 'final_delivery')
)
UPDATE crsts_scheme_return
SET development_only = CASE
    WHEN id IN (SELECT * FROM schemes_with_non_dev) THEN 0
    ELSE 1
END;
EOQ);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE crsts_scheme_return DROP development_only');
    }
}
