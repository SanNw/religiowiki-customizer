<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Services;

use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * Lê e grava o grupo de configuração "theme" (cores, tipografia, largura
 * máxima) em religiowiki_customizer_settings.
 *
 * DEFAULTS espelha os tokens hoje hardcoded em
 * religio-wiki/mediawiki-config/common.css (--rw-bg #FBF3E1, --rw-text
 * #241C15, --rw-link #92400E etc.) — uma instalação nova, sem nenhuma
 * configuração salva ainda, renderiza idêntica ao site atual.
 */
class ThemeSettingsStore {

	private const SETTINGS_KEY = 'theme';

	/** @var array<string,string> */
	public const DEFAULTS = [
		'primary' => '#92400E',
		'secondary' => '#DC2626',
		'background' => '#FBF3E1',
		'surface' => '#FFFDF7',
		'border' => '#E2D5B8',
		'text' => '#241C15',
		'textMuted' => '#5C5142',
		'fontFamily' => "'Noto Sans', 'Noto Sans Arabic', 'Noto Sans Hebrew', "
			. "'Noto Sans Greek', 'Noto Sans Devanagari', -apple-system, 'Segoe UI', sans-serif",
		'fontSizeBase' => '16px',
		'maxWidth' => '1200px',
	];

	private ILoadBalancer $loadBalancer;

	public function __construct( ILoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	public static function newFromGlobalState(): self {
		return new self( MediaWikiServices::getInstance()->getDBLoadBalancer() );
	}

	/**
	 * @return array<string,string> Valores salvos, com DEFAULTS preenchendo
	 *   qualquer chave ausente (config nunca salva, ou salva antes de um
	 *   campo novo existir).
	 */
	public function getTheme(): array {
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

		// Só chaves conhecidas sobrevivem — nunca deixa chave arbitrária
		// chegar ao gerador de CSS.
		$saved = array_intersect_key( $saved, self::DEFAULTS );
		return array_merge( self::DEFAULTS, $saved );
	}

	/**
	 * @param array<string,string> $values Só chaves presentes em DEFAULTS
	 *   são de fato gravadas; o resto é descartado silenciosamente.
	 * @param int|null $actorId actor_id de quem está salvando (auditoria).
	 */
	public function saveTheme( array $values, ?int $actorId ): void {
		$values = array_intersect_key( $values, self::DEFAULTS ) + self::DEFAULTS;

		$dbw = $this->loadBalancer->getConnectionRef( DB_PRIMARY );
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

	/**
	 * Remove a configuração salva — o wiki volta a usar DEFAULTS.
	 */
	public function resetTheme(): void {
		$dbw = $this->loadBalancer->getConnectionRef( DB_PRIMARY );
		$dbw->delete(
			'religiowiki_customizer_settings',
			[ 'rwcs_key' => self::SETTINGS_KEY ],
			__METHOD__
		);
	}
}
