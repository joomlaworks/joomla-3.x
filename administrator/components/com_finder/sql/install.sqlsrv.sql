-- com_finder: its tables, created only where they don't exist yet (a restore may find them kept)

BEGIN TRY
CREATE TABLE "#__finder_filters" (
  "filter_id" bigint IDENTITY(1,1) NOT NULL,
  "title" nvarchar(255) NOT NULL,
  "alias" nvarchar(255) NOT NULL,
  "state" smallint NOT NULL DEFAULT 1,
  "created" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "created_by" bigint NOT NULL,
  "created_by_alias" nvarchar(255) NOT NULL,
  "modified" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "modified_by" bigint NOT NULL DEFAULT 0,
  "checked_out" bigint NOT NULL DEFAULT 0,
  "checked_out_time" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "map_count" bigint NOT NULL DEFAULT 0,
  "data" nvarchar(max) NOT NULL,
  "params" nvarchar(max) NULL,
 CONSTRAINT "PK_#__finder_filters_filter_id" PRIMARY KEY CLUSTERED
(
  "filter_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_links" (
  "link_id" bigint IDENTITY(1,1) NOT NULL,
  "url" nvarchar(255) NOT NULL,
  "route" nvarchar(255) NOT NULL,
  "title" nvarchar(255) NULL,
  "description" nvarchar(max) NULL,
  "indexdate" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "md5sum" nvarchar(32) NULL,
  "published" smallint NOT NULL DEFAULT 1,
  "state" int NULL DEFAULT 1,
  "access" int NULL DEFAULT 0,
  "language" nvarchar(8) NOT NULL,
  "publish_start_date" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "publish_end_date" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "start_date" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "end_date" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "list_price" float NOT NULL DEFAULT 0,
  "sale_price" float NOT NULL DEFAULT 0,
  "type_id" int NOT NULL,
  "object" nvarchar(max) NOT NULL,
 CONSTRAINT "PK_#__finder_links_link_id" PRIMARY KEY CLUSTERED
(
  "link_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_title" ON "#__finder_links"
(
  "title" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_type" ON "#__finder_links"
(
  "type_id" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_url" ON "#__finder_links"
(
  "url" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_links_terms0" (
  "link_id" bigint NOT NULL,
  "term_id" bigint NOT NULL,
  "weight" real NOT NULL,
 CONSTRAINT "PK_#__finder_links_terms0_link_id" PRIMARY KEY CLUSTERED
(
  "link_id" ASC,
  "term_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_link_term_weight" ON "#__finder_links_terms0"
(
  "link_id" ASC,
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_term_weight" ON "#__finder_links_terms0"
(
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_links_terms1" (
  "link_id" bigint NOT NULL,
  "term_id" bigint NOT NULL,
  "weight" real NOT NULL,
 CONSTRAINT "PK_#__finder_links_terms1_link_id" PRIMARY KEY CLUSTERED
(
  "link_id" ASC,
  "term_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_link_term_weight" ON "#__finder_links_terms1"
(
  "link_id" ASC,
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_term_weight" ON "#__finder_links_terms1"
(
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_links_terms2" (
  "link_id" bigint NOT NULL,
  "term_id" bigint NOT NULL,
  "weight" real NOT NULL,
 CONSTRAINT "PK_#__finder_links_terms2_link_id" PRIMARY KEY CLUSTERED
(
  "link_id" ASC,
  "term_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_link_term_weight" ON "#__finder_links_terms2"
(
  "link_id" ASC,
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_term_weight" ON "#__finder_links_terms2"
(
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_links_terms3" (
  "link_id" bigint NOT NULL,
  "term_id" bigint NOT NULL,
  "weight" real NOT NULL,
 CONSTRAINT "PK_#__finder_links_terms3_link_id" PRIMARY KEY CLUSTERED
(
  "link_id" ASC,
  "term_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_link_term_weight" ON "#__finder_links_terms3"
(
  "link_id" ASC,
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_term_weight" ON "#__finder_links_terms3"
(
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_links_terms4" (
  "link_id" bigint NOT NULL,
  "term_id" bigint NOT NULL,
  "weight" real NOT NULL,
 CONSTRAINT "PK_#__finder_links_terms4_link_id" PRIMARY KEY CLUSTERED
(
  "link_id" ASC,
  "term_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_link_term_weight" ON "#__finder_links_terms4"
(
  "link_id" ASC,
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_term_weight" ON "#__finder_links_terms4"
(
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_links_terms5" (
  "link_id" bigint NOT NULL,
  "term_id" bigint NOT NULL,
  "weight" real NOT NULL,
 CONSTRAINT "PK_#__finder_links_terms5_link_id" PRIMARY KEY CLUSTERED
(
  "link_id" ASC,
  "term_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_link_term_weight" ON "#__finder_links_terms5"
(
  "link_id" ASC,
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_term_weight" ON "#__finder_links_terms5"
(
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_links_terms6" (
  "link_id" bigint NOT NULL,
  "term_id" bigint NOT NULL,
  "weight" real NOT NULL,
 CONSTRAINT "PK_#__finder_links_terms6_link_id" PRIMARY KEY CLUSTERED
(
  "link_id" ASC,
  "term_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_link_term_weight" ON "#__finder_links_terms6"
(
  "link_id" ASC,
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_term_weight" ON "#__finder_links_terms6"
(
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_links_terms7" (
  "link_id" bigint NOT NULL,
  "term_id" bigint NOT NULL,
  "weight" real NOT NULL,
 CONSTRAINT "PK_#__finder_links_terms7_link_id" PRIMARY KEY CLUSTERED
(
  "link_id" ASC,
  "term_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_link_term_weight" ON "#__finder_links_terms7"
(
  "link_id" ASC,
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_term_weight" ON "#__finder_links_terms7"
(
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_links_terms8" (
  "link_id" bigint NOT NULL,
  "term_id" bigint NOT NULL,
  "weight" real NOT NULL,
 CONSTRAINT "PK_#__finder_links_terms8_link_id" PRIMARY KEY CLUSTERED
(
  "link_id" ASC,
  "term_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_link_term_weight" ON "#__finder_links_terms8"
(
  "link_id" ASC,
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_term_weight" ON "#__finder_links_terms8"
(
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_links_terms9" (
  "link_id" bigint NOT NULL,
  "term_id" bigint NOT NULL,
  "weight" real NOT NULL,
 CONSTRAINT "PK_#__finder_links_terms9_link_id" PRIMARY KEY CLUSTERED
(
  "link_id" ASC,
  "term_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_link_term_weight" ON "#__finder_links_terms9"
(
  "link_id" ASC,
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_term_weight" ON "#__finder_links_terms9"
(
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_links_termsa" (
  "link_id" bigint NOT NULL,
  "term_id" bigint NOT NULL,
  "weight" real NOT NULL,
 CONSTRAINT "PK_#__finder_links_termsa_link_id" PRIMARY KEY CLUSTERED
(
  "link_id" ASC,
  "term_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_link_term_weight" ON "#__finder_links_termsa"
(
  "link_id" ASC,
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_term_weight" ON "#__finder_links_termsa"
(
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_links_termsb" (
  "link_id" bigint NOT NULL,
  "term_id" bigint NOT NULL,
  "weight" real NOT NULL,
 CONSTRAINT "PK_#__finder_links_termsb_link_id" PRIMARY KEY CLUSTERED
(
  "link_id" ASC,
  "term_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_link_term_weight" ON "#__finder_links_termsb"
(
  "link_id" ASC,
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_term_weight" ON "#__finder_links_termsb"
(
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_links_termsc" (
  "link_id" bigint NOT NULL,
  "term_id" bigint NOT NULL,
  "weight" real NOT NULL,
 CONSTRAINT "PK_#__finder_links_termsc_link_id" PRIMARY KEY CLUSTERED
(
  "link_id" ASC,
  "term_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_link_term_weight" ON "#__finder_links_termsc"
(
  "link_id" ASC,
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_term_weight" ON "#__finder_links_termsc"
(
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_links_termsd" (
  "link_id" bigint NOT NULL,
  "term_id" bigint NOT NULL,
  "weight" real NOT NULL,
 CONSTRAINT "PK_#__finder_links_termsd_link_id" PRIMARY KEY CLUSTERED
(
  "link_id" ASC,
  "term_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_link_term_weight" ON "#__finder_links_termsd"
(
  "link_id" ASC,
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_term_weight" ON "#__finder_links_termsd"
(
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_links_termse" (
  "link_id" bigint NOT NULL,
  "term_id" bigint NOT NULL,
  "weight" real NOT NULL,
 CONSTRAINT "PK_#__finder_links_termse_link_id" PRIMARY KEY CLUSTERED
(
  "link_id" ASC,
  "term_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_link_term_weight" ON "#__finder_links_termse"
(
  "link_id" ASC,
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_term_weight" ON "#__finder_links_termse"
(
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_links_termsf" (
  "link_id" bigint NOT NULL,
  "term_id" bigint NOT NULL,
  "weight" real NOT NULL,
 CONSTRAINT "PK_#__finder_links_termsf_link_id" PRIMARY KEY CLUSTERED
(
  "link_id" ASC,
  "term_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_link_term_weight" ON "#__finder_links_termsf"
(
  "link_id" ASC,
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_term_weight" ON "#__finder_links_termsf"
(
  "term_id" ASC,
  "weight" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_taxonomy" (
  "id" bigint IDENTITY(2,1) NOT NULL,
  "parent_id" bigint NOT NULL DEFAULT 0,
  "title" nvarchar(255) NOT NULL,
  "state" tinyint NOT NULL DEFAULT 1,
  "access" tinyint NOT NULL DEFAULT 0,
  "ordering" tinyint NOT NULL DEFAULT 0,
 CONSTRAINT "PK_#__finder_taxonomy_id" PRIMARY KEY CLUSTERED
(
  "id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "access" ON "#__finder_taxonomy"
(
  "access" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_parent_published" ON "#__finder_taxonomy"
(
  "parent_id" ASC,
  "state" ASC,
  "access" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "ordering" ON "#__finder_taxonomy"
(
  "ordering" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "parent_id" ON "#__finder_taxonomy"
(
  "parent_id" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "state" ON "#__finder_taxonomy"
(
  "state" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

SET IDENTITY_INSERT "#__finder_taxonomy" ON;

IF NOT EXISTS (SELECT 1 FROM "#__finder_taxonomy")
BEGIN
INSERT INTO "#__finder_taxonomy" ("id", "parent_id", "title", "state", "access", "ordering") VALUES
(1, 0, 'ROOT', 0, 0, 0)
END;

SET IDENTITY_INSERT "#__finder_taxonomy" OFF;

BEGIN TRY
CREATE TABLE "#__finder_taxonomy_map" (
  "link_id" bigint NOT NULL,
  "node_id" bigint NOT NULL,
 CONSTRAINT "PK_#__finder_taxonomy_map_link_id" PRIMARY KEY CLUSTERED
(
  "link_id" ASC,
  "node_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "link_id" ON "#__finder_taxonomy_map"
(
  "link_id" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "node_id" ON "#__finder_taxonomy_map"
(
  "node_id" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_terms" (
  "term_id" bigint IDENTITY(1,1) NOT NULL,
  "term" nvarchar(75) NOT NULL,
  "stem" nvarchar(75) NOT NULL,
  "common" tinyint NOT NULL DEFAULT 0,
  "phrase" tinyint NOT NULL DEFAULT 0,
  "weight" real NOT NULL DEFAULT 0,
  "soundex" nvarchar(75) NOT NULL,
  "links" int NOT NULL DEFAULT 0,
  "language" nvarchar(3) NOT NULL DEFAULT ''
 CONSTRAINT "PK_#__finder_terms_term_id" PRIMARY KEY CLUSTERED
(
  "term_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
 CONSTRAINT "#__finder_terms$idx_term" UNIQUE NONCLUSTERED
(
  "term" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_soundex_phrase" ON "#__finder_terms"
(
  "soundex" ASC,
  "phrase" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_stem_phrase" ON "#__finder_terms"
(
  "stem" ASC,
  "phrase" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_term_phrase" ON "#__finder_terms"
(
  "term" ASC,
  "phrase" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_terms_common" (
  "term" nvarchar(75) NOT NULL,
  "language" nvarchar(3) NOT NULL,
 CONSTRAINT "PK_#__finder_terms_common" PRIMARY KEY CLUSTERED
(
  "term" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_lang" ON "#__finder_terms_common"
(
  "language" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_word_lang" ON "#__finder_terms_common"
(
  "term" ASC,
  "language" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

IF NOT EXISTS (SELECT 1 FROM "#__finder_terms_common")
BEGIN
INSERT INTO "#__finder_terms_common" ("term", "language") VALUES
('a', 'en'),
('about', 'en'),
('after', 'en'),
('ago', 'en'),
('all', 'en'),
('am', 'en'),
('an', 'en'),
('and', 'en'),
('any', 'en'),
('are', 'en'),
('aren''t', 'en'),
('as', 'en'),
('at', 'en'),
('be', 'en'),
('but', 'en'),
('by', 'en'),
('for', 'en'),
('from', 'en'),
('get', 'en'),
('go', 'en'),
('how', 'en'),
('if', 'en'),
('in', 'en'),
('into', 'en'),
('is', 'en'),
('isn''t', 'en'),
('it', 'en'),
('its', 'en'),
('me', 'en'),
('more', 'en'),
('most', 'en'),
('must', 'en'),
('my', 'en'),
('new', 'en'),
('no', 'en'),
('none', 'en'),
('not', 'en'),
('nothing', 'en'),
('of', 'en'),
('off', 'en'),
('often', 'en'),
('old', 'en'),
('on', 'en'),
('onc', 'en'),
('once', 'en'),
('only', 'en'),
('or', 'en'),
('other', 'en'),
('our', 'en'),
('ours', 'en'),
('out', 'en'),
('over', 'en'),
('page', 'en'),
('she', 'en'),
('should', 'en'),
('small', 'en'),
('so', 'en'),
('some', 'en'),
('than', 'en'),
('thank', 'en'),
('that', 'en'),
('the', 'en'),
('their', 'en'),
('theirs', 'en'),
('them', 'en'),
('then', 'en'),
('there', 'en'),
('these', 'en'),
('they', 'en'),
('this', 'en'),
('those', 'en'),
('thus', 'en'),
('time', 'en'),
('times', 'en'),
('to', 'en'),
('too', 'en'),
('true', 'en'),
('under', 'en'),
('until', 'en'),
('up', 'en'),
('upon', 'en'),
('use', 'en'),
('user', 'en'),
('users', 'en'),
('version', 'en'),
('very', 'en'),
('via', 'en'),
('want', 'en'),
('was', 'en'),
('way', 'en'),
('were', 'en'),
('what', 'en'),
('when', 'en'),
('where', 'en'),
('which', 'en'),
('who', 'en'),
('whom', 'en'),
('whose', 'en'),
('why', 'en'),
('wide', 'en'),
('will', 'en'),
('with', 'en'),
('within', 'en'),
('without', 'en'),
('would', 'en'),
('yes', 'en'),
('yet', 'en'),
('you', 'en'),
('your', 'en'),
('yours', 'en')
END;

BEGIN TRY
CREATE TABLE "#__finder_tokens" (
  "term" nvarchar(75) NOT NULL,
  "stem" nvarchar(75) NOT NULL,
  "common" tinyint NOT NULL DEFAULT 0,
  "phrase" tinyint NOT NULL DEFAULT 0,
  "weight" real NOT NULL DEFAULT 0,
  "context" tinyint NOT NULL DEFAULT 2,
  "language" nvarchar(3) NOT NULL DEFAULT ''
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_context" ON "#__finder_tokens"
(
  "context" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_word" ON "#__finder_tokens"
(
  "term" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_tokens_aggregate" (
  "term_id" bigint NOT NULL,
  "map_suffix" nchar(1) NULL,
  "term" nvarchar(75) NOT NULL,
  "stem" nvarchar(75) NOT NULL,
  "common" tinyint NOT NULL DEFAULT 0,
  "phrase" tinyint NOT NULL DEFAULT 0,
  "term_weight" real NOT NULL,
  "context" tinyint NOT NULL DEFAULT 2,
  "context_weight" real NOT NULL,
  "total_weight" real NOT NULL,
  "language" nvarchar(3) NOT NULL DEFAULT ''
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "keyword_id" ON "#__finder_tokens_aggregate"
(
  "term_id" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "token" ON "#__finder_tokens_aggregate"
(
  "term" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__finder_types" (
  "id" bigint IDENTITY(1,1) NOT NULL,
  "title" nvarchar(100) NOT NULL,
  "mime" nvarchar(100) NOT NULL,
 CONSTRAINT "PK_#__finder_types_id" PRIMARY KEY CLUSTERED
(
  "id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
 CONSTRAINT "#__finder_types$title" UNIQUE NONCLUSTERED
(
  "title" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;
