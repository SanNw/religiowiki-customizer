<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Services;

use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * Configuração de performance (Fase 7) — mesma tabela, chave 'performance'.
 *
 * Decisão de arquitetura 1 da Fase 7: "lazy loading"/"preload" aqui
 * significam usar recursos nativos (atributo `loading=lazy` do navegador,
 * `<link rel=preload>`) por cima do que o ResourceLoader já faz sozinho
 * (minificação e cache) — nada de sistema de build próprio.
 */
class PerformanceSettingsStore {

	private const SETTINGS_KEY = 'performance';

	public const DEFAULTS = [
		'lazyLoadImages' => false,
		// uma URL de fonte por linha, pra <link rel=preload as=font>
		'preloadFontsText' => '',
	];

	private ILoadBalancer $loadBalancer;

	public function __construct( ILoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	public static function newFromGlobalState(): self {
		return new self( MediaWikiServices::getInstance()->getDBLoadBalancer() );
	}

	/**
	 * @return array{lazyLoadImages:bool,preloadFontsText:string}
	 */
	public function getSettings(): array {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$row = $dbr->selectRow(
			'religiowiki_customizer_settings',
			[ 'rwcs_value' ],
			[ 'rwcs_key' => self::SETTINGS_KEY ],
			__METHOD__
		);
		if ( !$row ) {
			return self::DEFAULTS;
		}
		$saved = json_decode( (string)$row->rwcs_value, true );
		if ( !is_array( $saved ) ) {
			return self::DEFAULTS;
		}
		$merged = array_merge( self::DEFAULTS, array_intersect_key( $saved, self::DEFAULTS ) );
		$merged['lazyLoadImages'] = (bool)$merged['lazyLoadImages'];
		return $merged;
	}

	/**
	 * @param array $values
	 */
	public function saveSettings( array $values, ?int $actorId ): void {
		$normalized = [
			'lazyLoadImages' => !empty( $values['lazyLoadImages'] ),
			'preloadFontsText' => (string)( $values['preloadFontsText'] ?? '' ),
		];
		$dbw = $this->loadBalancer->getConnection( DB_PRIMARY );
		$dbw->upsert(
			'religiowiki_customizer_settings',
			[
				'rwcs_key' => self::SETTINGS_KEY,
				'rwcs_value' => json_encode( $normalized ),
				'rwcs_updated' => $dbw->timestamp(),
				'rwcs_updated_by_actor' => $actorId,
			],
			[ 'rwcs_key' ],
			[
				'rwcs_value' => json_encode( $normalized ),
				'rwcs_updated' => $dbw->timestamp(),
				'rwcs_updated_by_actor' => $actorId,
			],
			__METHOD__
		);
	}
}
