-- Joomla 3.x UTD 3.18.0 migration — October 31, 2026
-- Redirects: a hash of the normalised source URL for indexed lookups, and a table of their own for the errors the
-- plugin collects. The update script's "redirect-scheme" step fills the hashes and moves the data.

ALTER TABLE "#__redirect_links" ADD "old_url_hash" nchar(32) NOT NULL DEFAULT '';

CREATE NONCLUSTERED INDEX "idx_old_url_hash" ON "#__redirect_links"
(
  "old_url_hash" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF);

CREATE TABLE "#__redirect_errors" (
  "id" bigint IDENTITY(1,1) NOT NULL,
  "url" nvarchar(2048) NOT NULL,
  "url_hash" nchar(32) NOT NULL DEFAULT '',
  "code" smallint NOT NULL DEFAULT 404,
  "referer" nvarchar(2048) NOT NULL DEFAULT '',
  "hits" bigint NOT NULL DEFAULT 0,
  "created_date" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "last_hit" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
 CONSTRAINT "PK_#__redirect_errors_id" PRIMARY KEY CLUSTERED
(
  "id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
) ON [PRIMARY];

CREATE UNIQUE NONCLUSTERED INDEX "idx_url_hash" ON "#__redirect_errors"
(
  "url_hash" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF);

CREATE NONCLUSTERED INDEX "idx_code" ON "#__redirect_errors"
(
  "code" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF);

CREATE NONCLUSTERED INDEX "idx_last_hit" ON "#__redirect_errors"
(
  "last_hit" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF);
