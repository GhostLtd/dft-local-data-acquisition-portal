<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250116110650 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create permissions view';
    }

    public function up(Schema $schema): void
    {
        // When performing a UNION, any untyped NULL columns will have their column type inferred as being TEXT
        // This is a problem, because that's wrong and so the query will fail when trying to UNION in the later
        // queries' results. It's UUID on postgres for example, which will cause an error of "UNION types of TEXT
        // and UUID cannot be matched"

        // As such, we need to cast our NULL-columns as their correct column types (e.g. NULL::CHAR on sqlite)

        // Retrieve the column type that will be used for the ULID-holding columns
        // (see AbstractUidType::hasNativeGuidType for a thread into related code)
        $columnType = $this->platform->getGuidTypeDeclarationSQL([]);
        $bracketPos = strpos($columnType, '(');

        if ($bracketPos !== false) {
            $columnType = substr($columnType, 0, $bracketPos); // e.g. CHAR(16) -> CHAR
        }

        $null = "CAST(NULL AS $columnType)";

        $this->addSql(<<<EOQ
CREATE VIEW permissions_view AS

SELECT
    CAST(u.id as {$columnType}) AS id,
    u.user_id       AS user_id,
    u.entity_class  AS entity_class,
    authority.id    AS authority_id,
    {$null}         AS project_id,
    {$null}         AS project_return_id,
    {$null}         AS fund_return_id,
    u.fund_types    AS fund_types,
    u.section_types AS section_types
FROM user_permission u
    JOIN authority ON authority.id = u.entity_id
WHERE u.entity_class = 'App\Entity\Authority'

UNION

SELECT
    CAST(u.id as {$columnType}) AS id,
    u.user_id       AS user_id,
    u.entity_class  AS entity_class,
    authority.id    AS authority_id,
    {$null}         AS project_id,
    {$null}         AS project_return_id,
    fund_return.id  AS fund_return_id,
    u.fund_types    AS fund_types,
    u.section_types AS section_types
FROM user_permission u
    JOIN fund_return ON fund_return.id = u.entity_id
    JOIN fund_award ON fund_return.fund_award_id = fund_award.id
    JOIN authority ON fund_award.authority_id = authority.id
WHERE u.entity_class = 'App\Entity\FundReturn\FundReturn'

UNION

SELECT
    CAST(u.id as {$columnType}) AS id,
    u.user_id         AS user_id,
    u.entity_class  AS entity_class,
    authority.id      AS authority_id,
    project.id        AS project_id,
    project_return.id AS project_return_id,
    fund_return.id    AS fund_return_id,
    u.fund_types      AS fund_types,
    u.section_types   AS section_types
FROM user_permission u
    JOIN project_return ON project_return.id = u.entity_id
    JOIN fund_return ON project_return.fund_return_id = fund_return.id
    JOIN project_fund ON project_return.project_fund_id = project_fund.id
    JOIN project ON project_fund.project_id = project.id
    JOIN authority ON project.owner_id = authority.id
WHERE u.entity_class = 'App\Entity\ProjectReturn\ProjectReturn'

UNION

SELECT
    CAST(u.id as {$columnType}) AS id,
    u.user_id       AS user_id,
    u.entity_class  AS entity_class,
    authority.id    AS authority_id,
    project.id      AS project_id,
    {$null}         AS project_return_id,
    {$null}         AS fund_return_id,
    u.fund_types    AS fund_types,
    u.section_types AS section_types
FROM user_permission u
    JOIN project ON project.id = u.entity_id
    JOIN authority ON project.owner_id = authority.id
WHERE u.entity_class = 'App\Entity\Project'
EOQ);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP VIEW permissions_view');
    }
}
