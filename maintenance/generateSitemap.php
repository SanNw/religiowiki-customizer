<?php

use MediaWiki\MediaWikiServices;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

/**
 * Gera sitemap.xml na raiz do MediaWiki (decisão de arquitetura 3 da
 * Fase 6: maintenance script agendável via cron, em vez de gerar sob
 * demanda a cada request — sitemap não precisa estar 100% em tempo real).
 *
 * Só inclui páginas do namespace principal (artigos), sem redirecionamentos.
 *
 * Uso: php maintenance/generateSitemap.php [--output /caminho/sitemap.xml]
 * Agendar via cron, ex.: 1x por dia.
 */
class GenerateSitemap extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Gera sitemap.xml com as páginas do namespace principal.' );
		$this->addOption( 'output', 'Caminho do arquivo de saída (padrão: raiz do MediaWiki)', false, true );
		$this->requireExtension( 'ReligiowikiCustomizer' );
	}

	public function execute() {
		global $IP;
		$dbr = $this->getServiceContainer()->getDBLoadBalancer()->getConnection( DB_REPLICA );

		$res = $dbr->select(
			'page',
			[ 'page_namespace', 'page_title', 'page_touched' ],
			[ 'page_namespace' => NS_MAIN, 'page_is_redirect' => 0 ],
			__METHOD__
		);

		$urls = [];
		foreach ( $res as $row ) {
			$title = Title::makeTitle( $row->page_namespace, $row->page_title );
			$lastmod = substr( $row->page_touched, 0, 8 );
			$lastmodFormatted = "{$lastmod[0]}{$lastmod[1]}{$lastmod[2]}{$lastmod[3]}-{$lastmod[4]}{$lastmod[5]}-{$lastmod[6]}{$lastmod[7]}";
			$urls[] = [
				'loc' => $title->getFullURL(),
				'lastmod' => $lastmodFormatted,
			];
		}

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		foreach ( $urls as $url ) {
			$xml .= "\t<url>\n";
			$xml .= "\t\t<loc>" . htmlspecialchars( $url['loc'], ENT_XML1 ) . "</loc>\n";
			$xml .= "\t\t<lastmod>" . $url['lastmod'] . "</lastmod>\n";
			$xml .= "\t</url>\n";
		}
		$xml .= '</urlset>' . "\n";

		$output = $this->getOption( 'output', "$IP/sitemap.xml" );
		file_put_contents( $output, $xml );

		$this->output( count( $urls ) . " URLs escritas em $output\n" );
	}
}

$maintClass = GenerateSitemap::class;
require_once RUN_MAINTENANCE_IF_MAIN;
