<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250909121743 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update export_scheme_return_data to fix some type inconsistencies caused by merging the JSON property_change_log values in';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<EOQ
CREATE OR REPLACE VIEW export_scheme_return_data AS

WITH raw_data AS (
    SELECT
        a.id                           AS authority_id
        , fr.id                        AS fund_return_id
        , a.name                       AS authority_name
        , fr.year                      AS return_year
        , fr.quarter                   AS return_quarter
        --
        , fr.signoff_date
        , sr.id                        AS scheme_return_id
        --
        , s.id                         AS scheme_id
        , csr.development_only         AS is_development_only
        , sr.type
        , sr.risks
        , csr.benefit_cost_ratio_type
        , csr.benefit_cost_ratio_value
        , csr.business_case
        , csr.expected_business_case_approval
        , csr.on_track_rating
        , csr.progress_update
        , csr.agreed_funding
        , csr.total_cost
        , csr.expense_division_comments -- JSON
        , (
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
),

data_and_scheme_changes AS (
    SELECT
        r.*
        , json_data.scheme_changes
        , CASE WHEN earliest_return.id = scheme_return_id THEN 1 ELSE 0 END AS is_new_scheme
    FROM raw_data r
    LEFT JOIN LATERAL (
        SELECT
            JSON_OBJECTAGG(property_name, property_value) as scheme_changes
            FROM (
                SELECT
                    ROW_NUMBER() OVER (PARTITION BY property_name ORDER BY timestamp DESC) AS row_num
                    , property_name
                    , property_value
                FROM property_change_log
                WHERE entity_id = r.scheme_id
                AND timestamp < r.signoff_date
            ) AS ranked
            WHERE row_num = 1
    ) AS json_data ON TRUE
    LEFT JOIN LATERAL (
        SELECT
            scheme_return.id
            , ROW_NUMBER() over (PARTITION BY scheme_return.scheme_id ORDER BY fund_return.year, fund_return.quarter) AS rn
        FROM scheme_return
        JOIN fund_return ON scheme_return.fund_return_id = fund_return.id
        WHERE scheme_return.scheme_id = r.scheme_id
    ) AS earliest_return ON earliest_return.rn = 1
)

SELECT
    authority_id
    , fund_return_id
    , return_year
    , return_quarter
    --
    , scheme_id
    , is_new_scheme
    , scheme_return_id
    , scheme_changes->>'$.schemeIdentifier' AS scheme_identifier
    , scheme_changes->>'$.name' AS scheme_name
    , (scheme_changes->>'$."crstsData.retained"' = 'true') AS is_retained
    , is_development_only
    , scheme_changes->>'$.description' AS scheme_description
    , CASE
        WHEN scheme_changes->>'$.activeTravelElement' = 'null' THEN NULL
        ELSE scheme_changes->>'$.activeTravelElement'
      END AS active_travel_element
    , CASE
        WHEN scheme_changes->>'$.transportMode' = 'null' THEN NULL
        ELSE scheme_changes->>'$.transportMode'
      END AS transport_mode
    , scheme_changes->>'$."crstsData.fundedMostlyAs"' AS funded_mostly_as
    , (scheme_changes->>'$."crstsData.previouslyTcf"' = 'true') AS was_previously_tcf
    , type
    , risks
    , benefit_cost_ratio_type
    , benefit_cost_ratio_value
    , business_case
    , expected_business_case_approval
    , on_track_rating
    , progress_update
    , agreed_funding
    , total_cost
    , expense_division_comments -- JSON
    , milestones -- JSON
FROM data_and_scheme_changes
ORDER BY scheme_identifier, return_year, return_quarter;
EOQ);
    }

    public function down(Schema $schema): void
    {
    }
}
