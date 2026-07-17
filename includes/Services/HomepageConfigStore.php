<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Services;

use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * Lê e grava a configuração da Homepage Builder (Fase 3) — mesma tabela,
 * chave 'homepage' (decisão de arquitetura 1 e 2 da Fase 3: reaproveita o
 * storage já existente; formato é um array/JSON com um item por bloco,
 * cada um com 'type', 'enabled', 'order' e os campos específicos do tipo).
 */
class HomepageConfigStore {

	private const SETTINGS_KEY = 'homepage';

	public const BLOCK_TYPES = [
		'hero', 'cards', 'featured', 'categories', 'search',
		// Fase 4 — dependiam da biblioteca de componentes (Card/Grid).
		'noticias', 'livros', 'estatisticas',
	];

	/**
	 * @var array<string,array<string,mixed>> Um bloco por tipo, na ordem
	 *   padrão, todos habilitados com conteúdo de exemplo mínimo.
	 */
	public const DEFAULTS = [
		'hero' => [
			'type' => 'hero',
			'enabled' => true,
			'order' => 1,
			'title' => 'Boas-vindas à Religio Wiki',
			'subtitle' => 'a enciclopédia autêntica sobre as religiões',
			'backgroundImage' => '',
			'ctaText' => '',
			'ctaLink' => '',
		],
		'cards' => [
			'type' => 'cards',
			'enabled' => false,
			'order' => 2,
			'itemsJson' => '[]',
		],
		'featured' => [
			'type' => 'featured',
			'enabled' => true,
			'order' => 3,
			'mode' => 'manual',
			'pagesJson' => '["Cristianismo"]',
		],
		'categories' => [
			'type' => 'categories',
			'enabled' => true,
			'order' => 4,
			'categoriesJson' => '["I. Xamanismos Hiperbóreos","II. Mitologias Arianas","III. Monoteísmos Semíticos"]',
		],
		'search' => [
			'type' => 'search',
			'enabled' => false,
			'order' => 5,
		],
		'noticias' => [
			'type' => 'noticias',
			'enabled' => false,
			'order' => 6,
			// cada item: {"title":"...","text":"...","link":"...","date":"..."}
			'itemsJson' => '[]',
		],
		'livros' => [
			'type' => 'livros',
			'enabled' => false,
			'order' => 7,
			// cada item: {"title":"...","author":"...","link":"...","icon":"📖"}
			'itemsJson' => '[]',
		],
		'estatisticas' => [
			'type' => 'estatisticas',
			'enabled' => false,
			'order' => 8,
			// cada item: {"label":"...","value":"..."}
			'itemsJson' => '[]',
		],
	];

	private ILoadBalancer $loadBalancer;

	public function __construct( ILoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	public static function newFromGlobalState(): self {
		return new self( MediaWikiServices::getInstance()->getDBLoadBalancer() );
	}

	/**
	 * @return array<string,array<string,mixed>> Indexado por tipo de bloco.
	 */
	public function getConfig(): array {
		$dbr = $this->loadBalancer->getConnectionRef( DB_REPLICA );
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

		$result = self::DEFAULTS;
		foreach ( self::BLOCK_TYPES as $type ) {
			if ( isset( $saved[ $type ] ) && is_array( $saved[ $type ] ) ) {
				$result[ $type ] = array_merge( $result[ $type ], array_intersect_key(
					$saved[ $type ],
					$result[ $type ]
				) );
			}
		}
		return $result;
	}

	/**
	 * @param array<string,array<string,mixed>> $config Indexado por tipo de bloco.
	 * @param int|null $actorId
	 */
	public function saveConfig( array $config, ?int $actorId ): void {
		$sanitized = self::DEFAULTS;
		foreach ( self::BLOCK_TYPES as $type ) {
			if ( isset( $config[ $type ] ) && is_array( $config[ $type ] ) ) {
				$sanitized[ $type ] = array_merge( $sanitized[ $type ], array_intersect_key(
					$config[ $type ],
					$sanitized[ $type ]
				) );
			}
		}

		$dbw = $this->loadBalancer->getConnectionRef( DB_PRIMARY );
		$dbw->upsert(
			'religiowiki_customizer_settings',
			[
				'rwcs_key' => self::SETTINGS_KEY,
				'rwcs_value' => json_encode( $sanitized ),
				'rwcs_updated' => $dbw->timestamp(),
				'rwcs_updated_by_actor' => $actorId,
			],
			[ 'rwcs_key' ],
			[
				'rwcs_value' => json_encode( $sanitized ),
				'rwcs_updated' => $dbw->timestamp(),
				'rwcs_updated_by_actor' => $actorId,
			],
			__METHOD__
		);
	}

	/**
	 * @return array<int,array<string,mixed>> Só os blocos habilitados, em
	 *   ordem de exibição — o que HomepageRenderer consome.
	 */
	public function getEnabledBlocksInOrder(): array {
		$config = $this->getConfig();
		$enabled = array_filter( $config, static function ( array $block ): bool {
			return !empty( $block['enabled'] );
		} );
		usort( $enabled, static function ( array $a, array $b ): int {
			return ( $a['order'] ?? 0 ) <=> ( $b['order'] ?? 0 );
		} );
		return array_values( $enabled );
	}
}
