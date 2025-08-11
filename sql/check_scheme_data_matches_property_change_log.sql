-- Find scheme column values that don't match their latest entry in the logs...
WITH change_log_entries AS (
    SELECT
        entity_id,
        property_name,
        JSON_UNQUOTE(property_value) AS property_value,
        timestamp,
        ROW_NUMBER() over (PARTITION BY entity_id, property_name ORDER BY timestamp DESC) AS row_num
    FROM property_change_log
),
adjusted_change_log_entries AS (
    SELECT
        entity_id,
        property_name,
        -- schemeIdentifier is stored as "AUTH-1234" in the logs, but only "1234" in the field on the entity
        CASE
            WHEN property_name = 'schemeIdentifier'
                THEN SUBSTRING_INDEX(property_value, '-', -1)  -- gets "01023"
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
SELECT * FROM scheme_values_that_do_not_match_the_logs
