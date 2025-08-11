<?php

namespace App\Command\Fix;

use App\Repository\PropertyChangeLogRepository;
use App\Repository\SchemeRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Ulid;

#[AsCommand(
    name: 'ldap:fix:20250811-fix-scheme-import',
    description: 'Scheme import resulted in some non-contiguous schemes where schemes had been renamed. Merge them.',
)]
class LdapFix20250811FixSchemeImportCommand extends Command
{
    public function __construct(
        protected EntityManagerInterface      $entityManager,
        protected PropertyChangeLogRepository $propertyChangeLogRepository,
        protected SchemeRepository            $schemeRepository, private readonly Connection $connection
    )
    {
        parent::__construct();
    }

    protected function getSortedSchemes(array $renameIds): array
    {
        $paramNames = array_map(fn(string $x) => "id{$x}", range(1, count($renameIds)));
        $paramsSQL = implode(', ', array_map(fn(string $p) => ":{$p}", $paramNames));

        $stmt = $this->entityManager->getConnection()->executeQuery(
            $this->getSqlForRenamesQuery($paramsSQL),
            array_combine($paramNames, array_map(fn(string $x) => Ulid::fromRfc4122($x), $renameIds)),
            array_combine($paramNames, array_fill(0, count($paramNames), UlidType::NAME)),
        );

        return $stmt->fetchAllAssociative();
    }

    protected function configure(): void
    {
    }

    /*
     * These are schemes which accidentally got split due to a problem with the import process.
     *
     * Scheme A: 23Q4 24Q1 ---- ---- ---- ----
     * Scheme B: ---- ---- 24Q2 24Q3 24Q4 25Q1
     *
     * General summary of fix process:
     *
     * a) We'll keep scheme A
     * b) We'll update all references to scheme B to scheme A
     * c) We'll remove scheme B
     * d) We'll update property change log inserts that were for scheme B to sensible dates (based upon initial year/quarter)
     * e) We'll update fields in scheme A using the latest entry from the property change log
     */
    protected function getSchemeRenamesThatShouldBeMerged(): array {
        return [
            // Greater Manchester Combined Authority
            [
                '01965cd1-022b-d341-f724-67c9e1de2efb',
                '01965cd2-55d3-f63c-9534-0f80cb69dc6a',
            ],
            [
                '01965cd1-022b-d341-f724-67c9e1de2efc',
                '01965cd2-55d4-b298-ee6a-472fff329d1e',
            ],
            [
                '01965cd1-0242-df59-b2ce-59ae14134b6c',
                '01965cd4-3e41-4ae2-9556-86310bc369ee',
            ],
            [
                '01965cd2-55e6-0077-0326-35e8e008ff20',
                '01965cd1-023c-9b3b-2df0-c60f17a770e3',
            ],
            [
                '01965cd1-0241-be01-4dc3-6d0f53b25cdc',
                '01965cd4-3e40-3d01-7bcf-b53c07b18a53',
            ],
            [
                '01965cd8-f17e-1584-d201-0ff73d93b038',
                '01965cd1-025e-37ce-d2e1-f041f3e564b3',
            ],
            [
                '01965cd8-f174-7fa2-0c17-959288f530e9',
                '01965cd2-5600-081d-ed4f-2ade5d6d1160',
                '01965cd1-0254-49be-a181-d2eca174b0dc',
            ],

            // South Yorkshire Mayoral Combined Authority
            [
                '01965cd8-f1b6-7026-2064-d7b5a6fe161b',
                '01965cd1-0268-bc2e-d4b8-7c94b7490494',
            ],
            [
                '01965cd4-3e9a-f2e0-0e87-19dd501d0e6f',
                '01965cd2-564b-0e1f-0e42-91fd4fefb5a6',
                '01965cd1-026b-4d5a-e80f-fd5e81f23ee4',
            ],
            [
                '01965cd4-3e9c-0a4c-30a4-b5e677a549d1',
                '01965cd8-f1be-a9e2-8849-438540d499df',
            ],
            [
                '01965cd4-3e9d-8901-6315-936893cf62d2',
                '01965cd1-026d-6023-d68b-8e4a69e7aef6',
            ],
            [
                '01965cd4-3e9e-5d09-a182-9f44c7fce520',
                '01965cd1-026e-ec58-a8ae-8ee29b912435',
            ],
            [
                '01965cd1-0270-bf7e-9080-38af317cad8f',
                '01965cd8-f1c1-bf23-d945-2ad482e62e82',
            ],
            [
                '01965cd2-5650-33c7-7d6d-06ae597b1618',
                '01965cd1-0271-325e-3d00-7dabfc9fd03a',
            ],
            [
                '01965cd4-3ea7-2576-56a6-bcaa11e89250',
                '01965cd8-f1c7-6e68-304b-6eafca58137b',
                '01965cd2-5656-471e-fa31-b3aa7acc3db4',
                '01965cd1-0276-6239-93f9-877b90abfad3',
            ],
            [
                '01965cd4-3eab-c8b1-a485-7f05f4521353',
                '01965cd2-565b-3fa8-a8e5-83aa08d7e67c',
                '01965cd1-027a-cba8-791b-229eb8da7f67',
            ],
            [
                '01965cd1-027b-4a0d-de29-e6c04ac12599',
                '01965cd2-565c-65cf-b116-1ad983c5390c',
            ],
            [
                '01965cd1-027e-3a2b-0312-e5da019cbd7c',
                '01965cd2-565e-6722-a62c-a64b287cf2c9',
            ],
            [
                '01965cd4-3eb5-f62b-4e5e-2aa0ea59019a',
                '01965cd1-0284-6fa0-1ef1-3612c9fd8ae9',
            ],
            [
                '01965cd1-0289-af30-5d25-4c95884cd24e',
                '01965cd4-3ebb-fac0-ade5-82eaa1b21f6c',
            ],

            // West of England Combined Authority
            [
                '01965cd4-3f1f-9fc4-c888-ffc915efa97a',
                '01965cd1-0310-f4c7-da1d-b61539e6e8bf',
            ],
            [
                '01965cd4-3f17-53ee-a258-4e0e32b68f34',
                '01965cd1-0312-d75a-2395-a6ce5e935d09',
            ],
            [
                '01965cd4-3f18-2352-74dc-73d5c4d8bb0b',
                '01965cd1-0313-a450-deff-f400bf5ec929',
            ],
            [
                '01965cd4-3f19-9cb5-7f89-3283b76e7cfc',
                '01965cd1-0314-de3d-cdd0-cde970594e8a',
            ],
            [
                '01965cd4-3f19-9cb5-7f89-3283b76e7cfb',
                '01965cd1-0313-a450-deff-f400bf5ec92a',
            ],
            [
                '01965cd1-02ff-cac8-ea5c-456756169721',
                '01965cd8-f224-29fa-7b4f-a11c556e3fdc',
            ],
            [
                '01965cd4-3f99-3a3e-1904-f213003ff866',
                '01965cd1-0375-4204-7174-9db4c866cae6',
            ],
            [
                '01965cd1-02f5-5500-aa29-fce6961721b8',
                '01965cd8-f21a-2ffd-afa3-4686133304b3',
            ],
            [
                '01965cd1-02f3-0434-53eb-fe118bbfedf5',
                '01965cd8-f218-dec8-f844-634bf6593747',
            ],
            [
                '01965cd2-56bd-2bb8-84bd-7cf120450bd4',
                '01965cd8-f21f-cff6-cc83-028f5a23f39c',
            ],
            [
                '01965cd2-56be-43fa-c661-cec5deffbfe4',
                '01965cd8-f21f-cff6-cc83-028f5a23f39d',
            ],
            [
                '01965cd8-f21d-3f47-dd8a-52e94e17c46e',
                '01965cd1-02f9-1efe-fdce-3a5cdfc64ef7',
            ],
        ];
    }

    /*
     * Some renames were also done with the erroneous idea that when splitting a scheme, the original could be
     * renamed to continue as one of the split branches.
     *
     * This is not the case - the original scheme should continue - albeit flagged as split - and the new successors
     * should be added as new schemes.
     *
     * These IDs are for property_change_log entries where such renames were performed.
     */
    protected function getProblematicRenamesThatNeedToBeUndone(): array
    {
        return [
            '01987566-1b7b-c726-7ae2-9bdcc364cf87',
            '0198755c-c503-9fa0-d639-85df3fb0b4fb',
            '01987558-7c63-c549-ebd9-fec5ebed6fd5',
            '01986644-cec2-bb18-f3d4-6d3ed8294752',
            '0198663d-932c-1d7f-9da1-b2666461f3e4',
        ];
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);


        $this->connection->beginTransaction();

        foreach($this->getProblematicRenamesThatNeedToBeUndone() as $propertyChangeLogId) {
            $this->connection->executeStatement('DELETE FROM property_change_log WHERE id = UUID_TO_BIN(:id)', [
                'id' => $propertyChangeLogId,
            ]);
        }

        // A manually collated list of schemes that should be merged - see commentary on the method
        $renames = $this->getSchemeRenamesThatShouldBeMerged();
        foreach($renames as $renameIds) {

            // Get the schemes sorted in date order
            $data = $this->getSortedSchemes($renameIds);

            if (count($data) < count($renameIds) || count($data) < 2) {
                $io->error("Error: Only got ".count($data)." rows for renameIDs -> ".implode(', ', $renameIds));
                return Command::FAILURE;
            }

            // We'll merge schemes 2, 3, ...n (superfluous schemes) into scheme 1 (main scheme)
            $toSchemeId = $data[0]['scheme_id'];

            $paramValues = [];
            $schemeDateMap = [];

            // Generate SQL fragments to help with operations on the superfluous schemes
            foreach($data as $idx => $row) {
                if ($idx === 0) {
                    continue;
                }

                $schemeId = $row['scheme_id'];
                $paramValues["id{$idx}"] = $schemeId;

                $firstYearQuarter = explode(',', $row['quarters'])[0] ?? null;

                if (!preg_match('/^(\d{2})Q(\d)$/', $firstYearQuarter, $matches)) {
                    $io->error("Error: Couldn't parse first year quarter '{$firstYearQuarter}' for scheme {$schemeId}");
                    return Command::FAILURE;
                }

                $firstYear = intval($matches[1]);
                $firstQuarter = intval($matches[2]);

                // Calculate the month based upon FY - i.e. Q1 being April and Q4 being January
                if ($firstQuarter === 4) {
                    $firstYear += 1;
                    $firstQuarter = 0;
                }

                $firstMonth = $firstQuarter * 3 + 1;
                $schemeDateMap[$schemeId] = new \DateTime("20{$firstYear}-{$firstMonth}-01");
            }

            $paramsSQL = implode(', ', array_map(fn(string $p) => ":{$p}", array_keys($paramValues)));

            // Remove any schemeIdentifier log entries for the superfluous scheme fragments
            $this->connection->executeStatement(
                "DELETE FROM property_change_log WHERE entity_id IN ({$paramsSQL}) AND property_name = 'schemeIdentifier'",
                $paramValues,
            );

            // Remove any property change log entries in the superfluous scheme fragments purporting to change values to NULL (invalid)
            $this->connection->executeStatement(
                "DELETE FROM property_change_log WHERE entity_id IN ({$paramsSQL}) AND property_value IS NULL AND (property_name = 'activeTravelElement' OR property_name = 'transportMode')",
                $paramValues,
            );

            // Updates the dates on the property change log entries for the superfluous scheme fragments
            // (they'll be pointing at the initial import date)
            foreach($schemeDateMap as $schemeId => $date) {
                $this->connection->executeStatement(
                    "UPDATE property_change_log SET timestamp = :date WHERE entity_id = :schemeId AND action = 'insert'",
                    ['date' => $date, 'schemeId' => $schemeId],
                    ['date' => 'datetime', 'schemeId' => UlidType::NAME],
                );
            }

            // Point the property change log entries for the superfluous scheme fragments at the main scheme
            $this->connection->executeStatement(
                "UPDATE property_change_log SET entity_id = :toSchemeId, action = 'update' WHERE entity_id IN ({$paramsSQL})",
                [...$paramValues, 'toSchemeId' => $toSchemeId],
            );

            // De-duplicate the newly merged scheme log (i.e. where the main and superfluous schemes had the same values)
            $this->connection->executeStatement($this->getSqlForPropertyChangeLogDuplicatesDeletion(), [
                'entityId' => $toSchemeId,
            ]);

            // Find entries where the last entry in the property change log is different from the value in the scheme
            $mismatches = $this->connection->executeQuery($this->getSqlForPropertyChangeLogMismatch(), [
                'entityId' => $toSchemeId,
            ])->fetchAllAssociative();

            if (!empty($mismatches)) {
                $mismatchParams = [];
                $mismatchValues = [];

                foreach($mismatches as $idx => $mismatch) {
                    // Camel case to snake case
                    $propertyName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $mismatch['property_name']));
                    $latestValue = $mismatch['latest_value'];

                    $idx = "mismatch{$idx}";
                    $mismatchParams[$propertyName] = $idx;
                    $mismatchValues[$idx] = $latestValue;
                }

                $mismatchSQL = join(', ', array_map(
                    fn(string $name, string $placeholder) => "{$name} = :{$placeholder}",
                    array_keys($mismatchParams),
                    $mismatchParams
                ));

                // Update mismatches so the scheme is in sync with the property change log
                $this->connection->executeStatement("UPDATE scheme SET {$mismatchSQL} WHERE id = :id", [
                    ...$mismatchValues,
                    'id' => $toSchemeId,
                ]);
            }

            // Update scheme returns to point at the main scheme
            $this->connection->executeStatement(
                "UPDATE scheme_return SET scheme_id = :toSchemeId WHERE scheme_id IN ({$paramsSQL})",
                [...$paramValues, 'toSchemeId' => $toSchemeId],
            );

            // Delete the superfluous schemes
            $this->connection->executeStatement(
                "DELETE FROM scheme WHERE id IN ({$paramsSQL})",
                $paramValues,
            );
        }

        $this->connection->commit();
        return Command::SUCCESS;
    }

    public function getSqlForRenamesQuery(string $paramsSQL): string
    {
        return <<<SQL
WITH schemes_by_quarters AS (
    SELECT
        s.id AS scheme_id,
        COALESCE(e.scheme_name, s.name) AS scheme,
        GROUP_CONCAT(
            CONCAT(SUBSTR(fr.year, 3), 'Q', fr.quarter)
            ORDER BY fr.year, fr.quarter
            SEPARATOR ', '
        ) AS quarters
    FROM scheme s
         JOIN scheme_return sr ON sr.scheme_id = s.id
         JOIN fund_return fr ON fr.id = sr.fund_return_id
         LEFT JOIN export_scheme_return_data e ON e.scheme_id = s.id AND e.return_year = fr.year AND e.return_quarter = fr.quarter
    GROUP BY scheme_id, scheme
)
SELECT *
FROM schemes_by_quarters
WHERE scheme_id IN ({$paramsSQL})
ORDER BY quarters
SQL;
    }

    protected function getSqlForPropertyChangeLogDuplicatesDeletion(): string
    {
        return <<<SQL
WITH changes AS (
    SELECT
        id,
        entity_id,
        CASE
            WHEN LAG(property_value) over (PARTITION BY entity_id, property_name ORDER BY timestamp) = property_value THEN true
            ELSE false
        END AS should_delete
    FROM property_change_log
)
DELETE FROM property_change_log
WHERE id IN (SELECT id FROM changes WHERE entity_id = :entityId AND should_delete = true)
SQL;
    }

    protected function getSqlForPropertyChangeLogMismatch(): string
    {
        return <<<SQL
WITH change_log_entries AS (
    SELECT
        entity_id,
        property_name,
        JSON_UNQUOTE(property_value) AS property_value,
        timestamp,
        ROW_NUMBER() over (PARTITION BY entity_id, property_name ORDER BY timestamp DESC) AS row_num
    FROM property_change_log
    WHERE entity_id = :entityId
),
adjusted_change_log_entries AS (
    SELECT
        entity_id,
        property_name,
        -- schemeIdentifier is stored as "AUTH-1234" in the logs, but only "1234" in the field on the entity
        CASE
            WHEN property_name = 'schemeIdentifier'THEN SUBSTRING_INDEX(property_value, '-', -1)  -- gets "01023"
            ELSE property_value
        END AS property_value,
        `timestamp`,
        row_num
    FROM change_log_entries
),
latest_change_log_entries AS (
    -- Get the latest entry for each scheme, property name pair
    SELECT entity_id, property_name, property_value, timestamp
    FROM adjusted_change_log_entries
    WHERE row_num = 1
),
schemes_pivoted AS (
    -- Pivot the scheme data into a table with one row per property, with the scheme ID as the primary key
    SELECT
        s.id,
        jt.property_name,
        jt.property_value
    FROM scheme AS s,
         JSON_TABLE(
                 JSON_ARRAY(
                         JSON_OBJECT('k','name','v', s.name),
                         JSON_OBJECT('k','description','v', s.description),
                         JSON_OBJECT('k','transportMode','v', s.transport_mode),
                         JSON_OBJECT('k','activeTravelElement','v', s.active_travel_element),
                         JSON_OBJECT('k','schemeIdentifier','v', s.scheme_identifier),
                         -- These two fields are INT (1/0), but logged as JSON (true/false)
                         JSON_OBJECT('k','crstsData.retained','v', CAST(s.crsts_data_retained = 1 AS JSON)),
                         JSON_OBJECT('k','crstsData.previouslyTcf','v', CAST(s.crsts_data_previously_tcf = 1 AS JSON)),
                         JSON_OBJECT('k','crstsData.fundedMostlyAs','v', s.crsts_data_funded_mostly_as)
                 ),
                 '$[*]' COLUMNS (
                     ord            FOR ORDINALITY,
                     property_name  VARCHAR(32) PATH '$.k',
                     property_value TEXT        PATH '$.v'
                 )
         ) AS jt
    WHERE s.id = :entityId
    ORDER BY s.id, jt.ord
),

scheme_values_that_do_not_match_the_logs AS (
    -- Select scheme (column, value) pairs that don't match the latest entry in the property_change_log table
    SELECT p.id, p.property_name, p.property_value, l.property_value AS latest_value
    FROM schemes_pivoted p
    LEFT JOIN latest_change_log_entries l ON p.id = l.entity_id AND p.property_name = l.property_name
    WHERE p.property_value IS NOT NULL
    AND (
        l.entity_id IS NULL
        OR NOT (p.property_value <=> l.property_value)
    )
)
SELECT id, property_name, latest_value 
FROM scheme_values_that_do_not_match_the_logs
SQL;
    }
}
