-- Patch de adição da tabela rwc_page_views (Fase 9) para instalações que já
-- têm a religiowiki_customizer_settings criada.
--
-- Por que um arquivo SÓ com esta tabela (e não reaproveitar tables-generated.sql):
-- addExtensionTable('rwc_page_views', FILE) roda FILE inteiro quando a tabela
-- não existe. Se apontasse para tables-generated.sql (que tem as DUAS tabelas),
-- numa instalação onde a religiowiki_customizer_settings JÁ existe o patch
-- tentaria recriá-la e abortaria com "Error 1050: Table already exists". Este
-- arquivo cria apenas a rwc_page_views, então funciona tanto em instalação
-- nova quanto existente.

CREATE TABLE /*_*/rwc_page_views (
  rwcpv_page INT UNSIGNED NOT NULL,
  rwcpv_day INT UNSIGNED NOT NULL,
  rwcpv_views INT UNSIGNED DEFAULT 0 NOT NULL,
  PRIMARY KEY(rwcpv_page, rwcpv_day)
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/rwcpv_day ON /*_*/rwc_page_views (rwcpv_day);
