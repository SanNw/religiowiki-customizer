-- This file is intended to mirror what `maintenance/generateSchemaSql.php`
-- would produce from ../tables.json (kept in sync by hand — a live MediaWiki
-- install to run that script wasn't available while authoring this file).
-- Do not diverge from tables.json without updating both.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes

CREATE TABLE /*_*/religiowiki_customizer_settings (
  rwcs_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
  rwcs_key VARBINARY(64) NOT NULL,
  rwcs_value MEDIUMBLOB DEFAULT NULL,
  rwcs_updated BINARY(14) NOT NULL,
  rwcs_updated_by_actor BIGINT UNSIGNED DEFAULT NULL,
  PRIMARY KEY(rwcs_id)
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/rwcs_key ON /*_*/religiowiki_customizer_settings (rwcs_key);


CREATE TABLE /*_*/rwc_page_views (
  rwcpv_page INT UNSIGNED NOT NULL,
  rwcpv_day INT UNSIGNED NOT NULL,
  rwcpv_views INT UNSIGNED DEFAULT 0 NOT NULL,
  PRIMARY KEY(rwcpv_page, rwcpv_day)
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/rwcpv_day ON /*_*/rwc_page_views (rwcpv_day);
