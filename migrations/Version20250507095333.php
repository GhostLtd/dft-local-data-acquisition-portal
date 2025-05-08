<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250507095333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add export views';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<EOQ
CREATE VIEW export_fund_return_data AS

SELECT
    a.id AS authority_id,
    fr.id AS fund_return_id,
    a.name AS authority_name,
    fr.year as return_year,
    fr.quarter as return_quarter,
    --
    fr.signoff_name,
    fr.signoff_email,
    fr.signoff_date,
    cfr.local_contribution,
    cfr.overall_confidence,
    cfr.progress_summary,
    cfr.resource_funding,
    cfr.comments,
    cfr.delivery_confidence,
    cfr.expense_division_comments -- JSON
FROM crsts_fund_return cfr
JOIN fund_return fr ON cfr.id = fr.id
JOIN fund_award fa ON fr.fund_award_id = fa.id
JOIN authority a ON fa.authority_id = a.id
WHERE fr.signoff_date IS NOT NULL
EOQ);

        $this->addSql(<<<EOQ
CREATE VIEW export_fund_return_expense_data AS
SELECT
    a.id AS authority_id,
    fr.id AS fund_return_id,
    a.name AS authority_name,
    fr.year as return_year,
    fr.quarter as return_quarter,
    --
    ee.type,
    ee.col,
    ee.division,
    ee.value
FROM crsts_fund_return cfr
JOIN fund_return fr ON cfr.id = fr.id
JOIN fund_award fa ON fr.fund_award_id = fa.id
JOIN authority a ON fa.authority_id = a.id
JOIN crsts_fund_return_expense_entry e ON cfr.id = e.crsts_fund_return_id
JOIN expense_entry ee ON e.expense_entry_id = ee.id
WHERE fr.signoff_date IS NOT NULL
EOQ);

        $this->addSql(<<<EOQ
CREATE VIEW export_scheme_return_data AS

SELECT
    a.id AS authority_id,
    fr.id AS fund_return_id,
    a.name AS authority_name,
    fr.year as return_year,
    fr.quarter as return_quarter,
    --
    s.id as scheme_id,
    CONCAT(REGEXP_REPLACE(a.name, '[^A-Z]', '', 1, 0, 'c'), '-', s.scheme_identifier) AS scheme_identifier,
    s.name as scheme_name,
    s.crsts_data_retained AS is_retained,
    csr.development_only AS is_development_only,
    s.description AS scheme_description,
    s.active_travel_element,
    s.transport_mode,
    s.crsts_data_funded_mostly_as AS funded_mostly_as,
    s.crsts_data_previously_tcf AS was_previously_tcf,
    sr.type,
    sr.risks,
    csr.benefit_cost_ratio_type,
    csr.benefit_cost_ratio_value,
    csr.business_case,
    csr.expected_business_case_approval,
    csr.on_track_rating,
    csr.progress_update,
    csr.agreed_funding,
    csr.total_cost,
    csr.expense_division_comments, -- JSON
    (
        SELECT JSON_OBJECTAGG(m.type, m.date)
        FROM crsts_scheme_return_milestone csm
        JOIN milestone m ON csm.milestone_id = m.id
        WHERE csm.crsts_scheme_return_id = csr.id
        AND m.date IS NOT NULL
    ) AS milestones -- JSON
FROM crsts_fund_return cfr
JOIN fund_return fr ON cfr.id = fr.id
JOIN fund_award fa ON fr.fund_award_id = fa.id
JOIN authority a ON fa.authority_id = a.id
JOIN scheme_return sr ON fr.id = sr.fund_return_id
JOIN scheme s ON sr.scheme_id = s.id
JOIN crsts_scheme_return csr ON sr.id = csr.id
WHERE fr.signoff_date IS NOT NULL
EOQ);

        $this->addSql(<<<EOQ
CREATE VIEW export_scheme_return_expense_data AS

SELECT
    a.id AS authority_id,
    fr.id AS fund_return_id,
    a.name AS authority_name,
    fr.year as return_year,
    fr.quarter as return_quarter,
    --
    s.id as scheme_id,
    CONCAT(REGEXP_REPLACE(a.name, '[^A-Z]', '', 1, 0, 'c'), '-', s.scheme_identifier) AS scheme_identifier,
    s.name AS scheme_name,
    ee.type,
    ee.col,
    ee.division,
    ee.value
FROM crsts_fund_return cfr
JOIN fund_return fr ON cfr.id = fr.id
JOIN fund_award fa ON fr.fund_award_id = fa.id
JOIN authority a ON fa.authority_id = a.id
JOIN scheme_return sr ON fr.id = sr.fund_return_id
JOIN crsts_scheme_return csr ON sr.id = csr.id
JOIN scheme s ON sr.scheme_id = s.id
JOIN crsts_scheme_return_expense_entry see ON csr.id = see.crsts_scheme_return_id
JOIN expense_entry ee ON see.expense_entry_id = ee.id
WHERE fr.signoff_date IS NOT NULL
EOQ);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP VIEW export_fund_return_data');
        $this->addSql('DROP VIEW export_fund_return_expense_data');
        $this->addSql('DROP VIEW export_scheme_return_data');
        $this->addSql('DROP VIEW export_scheme_return_expense_data');
    }
}
