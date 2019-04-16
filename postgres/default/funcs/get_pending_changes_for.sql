SET search_path TO public, thirdparty;

-- Almost a view... returns the changes that can be materialized by
-- time in_ts, either for all jobs (if in_job_id is null) or just for
-- the given job_id.
DROP FUNCTION IF EXISTS get_pending_changes_for(TIMESTAMP WITH TIME ZONE, INT);
CREATE FUNCTION get_pending_changes_for(
  in_ts TIMESTAMP WITH TIME ZONE,
  in_job_id INT
)
RETURNS TABLE (
  job_id INT,
  check_frequency_id INT,
  xfrm_id INT,
  pending_change_ids JSON,
  provided_ts TIMESTAMP WITH TIME ZONE,
  old_doc_id INT,
  old_doc_contents BYTEA,
  old_doc_contents_hash BYTEA,
  new_doc_id INT,
  new_doc_contents BYTEA,
  new_doc_contents_hash BYTEA,
  agg_delta JSON,
  alert_attrs JSON
)
AS $$
  WITH
    agg_pending_changes AS (
      SELECT
        url.spider_job_id AS job_id,
        url.check_frequency_id,
        url.xfrm_id,
        json_uniq(json_agg(pending_change_id)) AS pending_change_ids,
        min(old_doc_id) AS old_doc_id,
        max(new_doc_id) AS new_doc_id,
        json_array_collapse(json_agg(delta::JSON)) AS agg_delta,
        json_uniq(json_agg(unnested_url_id)) AS all_url_ids,
        max(CASE substr(url.url, 1, 9)
          WHEN 'external:' THEN c.creation_ts
          ELSE NULL
        END) AS provided_ts
      FROM pending_change c
      INNER JOIN unnest(c.url_ids) AS unnested_url_id
        ON True
      INNER JOIN url
        ON unnested_url_id = url.url_id
      INNER JOIN xfrm
        ON xfrm.xfrm_id = url.xfrm_id
      WHERE
        not_before_ts <= in_ts
        AND url.url_active
        AND (url.spider_job_id = in_job_id OR in_job_id IS NULL)
        -- We're only interested in URLs that actually have at least one
        -- active alert.
        AND EXISTS
        (
          SELECT a.alert_id
          FROM alert_x_url_group aug
          INNER JOIN alert a
            ON aug.alert_id = a.alert_id
          WHERE aug.url_group_id = url.url_group_id
            AND a.alert_active
        )
      GROUP BY
        url.spider_job_id, url.check_frequency_id, url.xfrm_id,
        -- We must not aggregate changes for external URLs or RSS
        -- feeds coming in as multipart documents, but import each
        -- change individually for those. Therefore, we add this nifty
        -- group by clause here.
        CASE (substr(url, 1, 9) = 'external:'
              OR xfrm.xfrm_commands LIKE '%rss2markdown-split%')
          WHEN True THEN pending_change_id
          ELSE NULL
        END
    )
  SELECT
    pc.job_id,
    pc.check_frequency_id,
    pc.xfrm_id,
    pc.pending_change_ids,
    pc.provided_ts,
    pc.old_doc_id,
    old_doc.contents,
    old_doc.contents_hash,
    pc.new_doc_id,
    new_doc.contents,
    new_doc.contents_hash,
    agg_delta,
    (
      -- Lookup all the affected alerts for this change by url.
      SELECT json_object_agg(job_alert.alert_id, ai.per_alert_info)
      FROM json_array_elements(all_url_ids) AS unnested_url_id
      INNER JOIN spider_job_alert_type_cycle AS job_alert
        ON
          job_alert.xfrm_id = pc.xfrm_id
          AND job_alert.url_id = unnested_url_id::TEXT::INT
      INNER JOIN (
          -- This nifty piece of SQL assembles a json object somewhat
          -- like {"threshold": x, "keywords": [...]} per alert_id.
          SELECT a1.alert_id, (SELECT row_to_json(t) AS per_alert_info
            FROM (
              SELECT
                a2.alert_threshold AS threshold,
                json_uniq(json_agg(kw.alert_keyword)) AS keyword_lists
              FROM alert a2
              LEFT JOIN alert_keyword kw
                ON kw.alert_id = a2.alert_id
              WHERE a1.alert_id = a2.alert_id
                AND kw.alert_keyword_active
              GROUP BY a2.alert_id
              LIMIT 1
            ) t)
          FROM alert a1
        ) ai
        ON job_alert.alert_id = ai.alert_id
    ) AS alert_attrs
  FROM agg_pending_changes pc
  LEFT JOIN spider_document new_doc
    ON pc.new_doc_id = new_doc.spider_document_id
  LEFT JOIN spider_document old_doc
    ON pc.old_doc_id = old_doc.spider_document_id;
$$ LANGUAGE sql;

-- FIXME: shouldn't belong to the app user
ALTER FUNCTION public.get_pending_changes_for(in_ts timestamp with time zone, in_job_id integer)
  OWNER TO project_mon;

