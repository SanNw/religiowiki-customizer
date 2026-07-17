<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Services;

use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * Lê e grava o CSS/JS personalizado (Fase 2) — mesma tabela e mesmo padrão
 * de storage da Fase 1 (ThemeSettingsStore), só com chaves diferentes
 * ('custom_css' / 'custom_js'), confirmando a decisão de arquitetura 1 da
 * Fase 2: reaproveitar o storage já existente sem migração de schema.
 */
class CustomCodeStore {

	private const CSS_KEY = 'custom_css';
	private const JS_KEY = 'custom_js';

	private ILoadBalancer $loadBalancer;

	public function __construct( ILoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	public static function newFromGlobalState(): self {
		return new self( MediaWikiServices::getInstance()->getDBLoadBalancer() );
	}

	public function getCustomCss(): string {
		return $this->getRaw( self::CSS_KEY );
	}

	public function getCustomJs(): string {
		return $this->getRaw( self::JS_KEY );
	}

	/**
	 * @param string $css Sanitização mínima (ver ThemeCssGenerator::sanitizeCssValue
	 *   por analogia) — só o suficiente pra não quebrar layout de páginas
	 *   futuras se o valor vazar pra algum outro contexto; não tenta validar
	 *   CSS de verdade.
	 */
	public function saveCustomCss( string $css, ?int $actorId ): void {
		$css = str_replace( '</style', '<\\/style', $css );
		$this->saveRaw( self::CSS_KEY, $css, $actorId );
	}

	/**
	 * @param string $js JS bruto, SEM sanitização de conteúdo — a mitigação
	 *   real é controle de acesso (Special:ReligiowikiCustomizer exige
	 *   editinterface), não filtragem de código. Risco equivalente ao já
	 *   existente MediaWiki:Common.js hoje; só centralizado aqui.
	 */
	public function saveCustomJs( string $js, ?int $actorId ): void {
		$this->saveRaw( self::JS_KEY, $js, $actorId );
	}

	private function getRaw( string $key ): string {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$row = $dbr->selectRow(
			'religiowiki_customizer_settings',
			[ 'rwcs_value' ],
			[ 'rwcs_key' => $key ],
			__METHOD__
		);
		if ( !$row ) {
			return '';
		}
		$decoded = json_decode( (string)$row->rwcs_value );
		return is_string( $decoded ) ? $decoded : '';
	}

	private function saveRaw( string $key, string $value, ?int $actorId ): void {
		$dbw = $this->loadBalancer->getConnection( DB_PRIMARY );
		$dbw->upsert(
			'religiowiki_customizer_settings',
			[
				'rwcs_key' => $key,
				'rwcs_value' => json_encode( $value ),
				'rwcs_updated' => $dbw->timestamp(),
				'rwcs_updated_by_actor' => $actorId,
			],
			[ 'rwcs_key' ],
			[
				'rwcs_value' => json_encode( $value ),
				'rwcs_updated' => $dbw->timestamp(),
				'rwcs_updated_by_actor' => $actorId,
			],
			__METHOD__
		);
	}
}
