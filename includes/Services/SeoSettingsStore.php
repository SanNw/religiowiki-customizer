<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Services;

use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * Configuração global de SEO (Fase 6) — mesma tabela, chave 'seo'.
 */
class SeoSettingsStore {

	private const SETTINGS_KEY = 'seo';

	public const DEFAULTS = [
		'siteNameOverride' => '',
		'defaultDescription' => 'A Religio Wiki é uma enciclopédia colaborativa sobre as grandes religiões do mundo.',
		'defaultOgImage' => '',
		'twitterHandle' => '',
	];

	private ILoadBalancer $loadBalancer;

	public function __construct( ILoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	public static function newFromGlobalState(): self {
		return new self( MediaWikiServices::getInstance()->getDBLoadBalancer() );
	}

	/**
	 * @return array<string,string>
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
		return array_merge( self::DEFAULTS, array_intersect_key( $saved, self::DEFAULTS ) );
	}

	/**
	 * @param array<string,string> $values
	 */
	public function saveSettings( array $values, ?int $actorId ): void {
		$values = array_intersect_key( $values, self::DEFAULTS ) + self::DEFAULTS;
		$dbw = $this->loadBalancer->getConnection( DB_PRIMARY );
		$dbw->upsert(
			'religiowiki_customizer_settings',
			[
				'rwcs_key' => self::SETTINGS_KEY,
				'rwcs_value' => json_encode( $values ),
				'rwcs_updated' => $dbw->timestamp(),
				'rwcs_updated_by_actor' => $actorId,
			],
			[ 'rwcs_key' ],
			[
				'rwcs_value' => json_encode( $values ),
				'rwcs_updated' => $dbw->timestamp(),
				'rwcs_updated_by_actor' => $actorId,
			],
			__METHOD__
		);
	}
}
