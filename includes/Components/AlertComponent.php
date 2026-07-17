<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Components;

use Html;
use Parser;
use PPFrame;

/**
 * `<rwalert tipo="info|aviso|erro|sucesso">conteúdo em '''wikitext'''</rwalert>`
 *
 * Conteúdo passa por recursiveTagParse (wikitext completo, incluindo
 * links/templates) — diferente de um parâmetro de atributo simples, é
 * corpo de tag, então isso é o comportamento esperado/documentado pra tags
 * de conteúdo do MediaWiki (igual <ref>, <gallery> etc.).
 */
class AlertComponent {

	private const VALID_TYPES = [ 'info', 'aviso', 'erro', 'sucesso' ];
	private const ICONS = [
		'info' => 'ℹ️',
		'aviso' => '⚠️',
		'erro' => '⛔',
		'sucesso' => '✅',
	];

	public static function render( ?string $input, array $args, Parser $parser, PPFrame $frame ): string {
		$type = strtolower( trim( $args['tipo'] ?? $args['type'] ?? 'info' ) );
		if ( !in_array( $type, self::VALID_TYPES, true ) ) {
			$type = 'info';
		}

		$content = $input === null ? '' : $parser->recursiveTagParse( $input, $frame );
		if ( trim( $content ) === '' ) {
			return '';
		}

		return Html::rawElement( 'div', [
			'class' => "rwc-alert rwc-alert-{$type}",
		], Html::element( 'span', [ 'class' => 'rwc-alert-icon' ], self::ICONS[ $type ] )
			. Html::rawElement( 'div', [ 'class' => 'rwc-alert-body' ], $content ) );
	}
}
