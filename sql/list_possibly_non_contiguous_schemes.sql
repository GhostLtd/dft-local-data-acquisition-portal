-- List all schemes that don't have an entry in each of our returns (so far)
SET @expected_full_set_of_returns = '23Q4, 24Q1, 24Q2, 24Q3, 24Q4, 25Q1';
WITH schemes_by_quarters AS (
    SELECT
        s.id AS scheme_id,
        a.name AS authority,
        COALESCE(IF(e.scheme_identifier IS NULL, NULL, SUBSTRING_INDEX(e.scheme_identifier, '-', -1)), s.scheme_identifier) AS scheme_identifier,
        GROUP_CONCAT(
                CONCAT(SUBSTR(fr.year, 3), 'Q', fr.quarter)
                ORDER BY fr.year, fr.quarter
                SEPARATOR ', '
        ) AS quarters
    FROM scheme s
         JOIN scheme_return sr ON sr.scheme_id = s.id
         JOIN fund_return fr ON fr.id = sr.fund_return_id
         JOIN authority a ON s.authority_id = a.id
         LEFT JOIN export_scheme_return_data e ON e.scheme_id = s.id AND e.return_year = fr.year AND e.return_quarter = fr.quarter
    GROUP BY scheme_id, a.name, scheme_identifier
),
schemes_by_quarter_with_names AS (
    SELECT sbq.*,
        s.name AS scheme
    FROM schemes_by_quarters AS sbq
    JOIN scheme s ON s.id = sbq.scheme_id
),
non_contiguous_by_quarters AS (
    SELECT *
    FROM schemes_by_quarter_with_names
    WHERE quarters != @expected_full_set_of_returns
),
pre_rename_parts AS (
    SELECT '1-pre' AS part,
           authority,
           scheme_id,
           scheme,
           scheme_identifier,
           quarters
    FROM non_contiguous_by_quarters
    WHERE quarters LIKE '23Q4%'
),
post_rename_parts AS (
    SELECT '3-post' AS part,
           authority,
           scheme_id,
           scheme,
           scheme_identifier,
           quarters
    FROM non_contiguous_by_quarters
    WHERE quarters LIKE '%25Q1'
),
mid_rename_parts AS (
    SELECT '2-mid' AS part,
           authority,
           scheme_id,
           scheme,
           scheme_identifier,
           quarters
    FROM non_contiguous_by_quarters
    WHERE quarters NOT LIKE '%25Q1'
      AND quarters NOT LIKE '23Q4%'
)
SELECT * FROM pre_rename_parts
UNION ALL
SELECT * FROM mid_rename_parts
UNION ALL
SELECT * FROM post_rename_parts
ORDER BY authority, scheme, part;