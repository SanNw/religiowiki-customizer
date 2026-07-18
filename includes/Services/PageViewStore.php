<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Services;

use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * Contagem diária de visualizações por página (Fase 9 — dashboard
 * Special:Artigos). Uma linha por (página, dia), com o dia guardado como
 * inteiro AAAAMMDD em UTC (gmdate) para não depender do fuso do servidor.
 *
 * A gravação (recordView) é chamada dentro de um DeferredUpdate pelo
 * HookHandler, então nunca bloqueia a renderização da página. As consultas
 * são só de leitura, usadas pela página especial (admin).
 */
class PageViewStore {

	private const TABLE = 'rwc_page_views';

	private ILoadBalancer $loadBalancer;

	public function __construct( ILoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	public static function newFromGlobalState(): self {
		return new self( MediaWikiServices::getInstance()->getDBLoadBalancer() );
	}

	/** Dia atual (UTC) como inteiro AAAAMMDD. */
	private static function today(): int {
		return (int)gmdate( 'Ymd' );
	}

	/**
	 * Incrementa em 1 a contagem de hoje para a página. Idempotente por
	 * requisição no sentido de que é seguro chamar concorrentemente: faz um
	 * UPDATE incremental e, se a linha (página, dia) ainda não existir, um
	 * INSERT IGNORE; numa corrida em que outra requisição criou a linha entre
	 * os dois passos, o INSERT é ignorado e refazemos o UPDATE.
	 *
	 * @param int $pageId page_id (> 0) da página visualizada.
	 */
	public function recordView( int $pageId ): void {
		if ( $pageId <= 0 ) {
			return;
		}
		$day = self::today();
		$dbw = $this->loadBalancer->getConnection( DB_PRIMARY );

		$dbw->update(
			self::TABLE,
			[ 'rwcpv_views = rwcpv_views + 1' ],
			[ 'rwcpv_page' => $pageId, 'rwcpv_day' => $day ],
			__METHOD__
		);
		if ( $dbw->affectedRows() > 0 ) {
			return;
		}

		$dbw->insert(
			self::TABLE,
			[ 'rwcpv_page' => $pageId, 'rwcpv_day' => $day, 'rwcpv_views' => 1 ],
			__METHOD__,
			[ 'IGNORE' ]
		);
		if ( $dbw->affectedRows() === 0 ) {
			// Corrida: a linha passou a existir entre o UPDATE e o INSERT.
			$dbw->update(
				self::TABLE,
				[ 'rwcpv_views = rwcpv_views + 1' ],
				[ 'rwcpv_page' => $pageId, 'rwcpv_day' => $day ],
				__METHOD__
			);
		}
	}

	/**
	 * Série diária de uma página, em ordem cronológica.
	 *
	 * @param int $pageId
	 * @return array<int,int> Mapa AAAAMMDD => visualizações.
	 */
	public function getDailyViews( int $pageId ): array {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$res = $dbr->select(
			self::TABLE,
			[ 'rwcpv_day', 'rwcpv_views' ],
			[ 'rwcpv_page' => $pageId ],
			__METHOD__,
			[ 'ORDER BY' => 'rwcpv_day ASC' ]
		);
		$out = [];
		foreach ( $res as $row ) {
			$out[ (int)$row->rwcpv_day ] = (int)$row->rwcpv_views;
		}
		return $out;
	}

	/** Total acumulado de visualizações de uma página. */
	public function getTotalViews( int $pageId ): int {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$val = $dbr->selectField(
			self::TABLE,
			'SUM(rwcpv_views)',
			[ 'rwcpv_page' => $pageId ],
			__METHOD__
		);
		return (int)$val;
	}

	/**
	 * Total acumulado por página, para várias páginas de uma vez (usado na
	 * listagem para não fazer N consultas).
	 *
	 * @param int[] $pageIds
	 * @return array<int,int> Mapa page_id => total (páginas sem visitas
	 *   simplesmente não aparecem no mapa).
	 */
	public function getTotalViewsForPages( array $pageIds ): array {
		$pageIds = array_values( array_filter( array_map( 'intval', $pageIds ) ) );
		if ( $pageIds === [] ) {
			return [];
		}
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$res = $dbr->select(
			self::TABLE,
			[ 'rwcpv_page', 'total' => 'SUM(rwcpv_views)' ],
			[ 'rwcpv_page' => $pageIds ],
			__METHOD__,
			[ 'GROUP BY' => 'rwcpv_page' ]
		);
		$out = [];
		foreach ( $res as $row ) {
			$out[ (int)$row->rwcpv_page ] = (int)$row->total;
		}
		return $out;
	}

	/**
	 * Dias com mais visualizações de uma página.
	 *
	 * @param int $pageId
	 * @param int $limit
	 * @return array<int,array{day:int,views:int}> Ordenado por visualizações
	 *   (desc).
	 */
	public function getTopDays( int $pageId, int $limit = 10 ): array {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$res = $dbr->select(
			self::TABLE,
			[ 'rwcpv_day', 'rwcpv_views' ],
			[ 'rwcpv_page' => $pageId ],
			__METHOD__,
			[ 'ORDER BY' => 'rwcpv_views DESC', 'LIMIT' => max( 1, $limit ) ]
		);
		$out = [];
		foreach ( $res as $row ) {
			$out[] = [ 'day' => (int)$row->rwcpv_day, 'views' => (int)$row->rwcpv_views ];
		}
		return $out;
	}

	/**
	 * Total de visitas do site inteiro por dia, nos últimos N dias.
	 *
	 * @param int $days Janela em dias (inclui hoje).
	 * @return array<int,int> Mapa AAAAMMDD => total, em ordem cronológica.
	 */
	public function getSiteWideByDay( int $days = 30 ): array {
		$days = max( 1, $days );
		$minDay = (int)gmdate( 'Ymd', time() - ( $days - 1 ) * 86400 );
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$res = $dbr->select(
			self::TABLE,
			[ 'rwcpv_day', 'total' => 'SUM(rwcpv_views)' ],
			[ 'rwcpv_day >= ' . $minDay ],
			__METHOD__,
			[ 'GROUP BY' => 'rwcpv_day', 'ORDER BY' => 'rwcpv_day ASC' ]
		);
		$out = [];
		foreach ( $res as $row ) {
			$out[ (int)$row->rwcpv_day ] = (int)$row->total;
		}
		return $out;
	}
}
