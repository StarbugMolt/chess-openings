-- REVIEW PROPOSAL ONLY: do not run against the shared development/production DB.
-- Existing imported/curriculum tables, completion keys/FKs and cascade stay intact.
ALTER TABLE user_line_completion
    ADD COLUMN assessment_json VARCHAR(4096) CHARACTER SET ascii COLLATE ascii_bin NULL,
    ADD CONSTRAINT ck_completion_assessment CHECK (
        assessment_json IS NULL OR (
            JSON_VALID(assessment_json)
            AND JSON_TYPE(assessment_json) = 'OBJECT'
            AND JSON_CONTAINS_PATH(assessment_json, 'all', '$.version', '$.usedHint', '$.usedRetry', '$.usedUndo', '$.decisions') = 1
            AND JSON_TYPE(JSON_EXTRACT(assessment_json, '$.version')) = 'INTEGER'
            AND JSON_EXTRACT(assessment_json, '$.version') = 1
            AND JSON_TYPE(JSON_EXTRACT(assessment_json, '$.usedHint')) = 'BOOLEAN'
            AND JSON_TYPE(JSON_EXTRACT(assessment_json, '$.usedRetry')) = 'BOOLEAN'
            AND JSON_TYPE(JSON_EXTRACT(assessment_json, '$.usedUndo')) = 'BOOLEAN'
            AND JSON_TYPE(JSON_EXTRACT(assessment_json, '$.decisions')) = 'ARRAY'
            AND JSON_LENGTH(JSON_EXTRACT(assessment_json, '$.decisions')) BETWEEN 1 AND 32
        )
    );
