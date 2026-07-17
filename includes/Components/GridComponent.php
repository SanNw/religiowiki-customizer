<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Components;

use Html;
use Parser;
use PPFrame;

/**
 * ```
 * <rwgrid colunas="3">
 * Conteúdo da coluna 1...
 * ----
 * Conteúdo da coluna 2...
 * ----
 * Conteúdo da coluna 3...
 * </rwgrid>
 * ```
 * Mesma convenção de separador `----` do `<rwtabs>` (ver SegmentSplitter),
 * só que sem rótulo — cada segmento vira uma coluna lado a lado. Sem
 * nenhum `----`, vira uma coluna só (fallback, não quebra a página).
 */
class GridComponent {

	private const MAX_COLUMNS = 6;

	public static function render( ?string $input, array $args, Parser $parser, PPFrame $frame ): string {
		if ( $input === null || trim( $input ) === '' ) {
			return '';
		}

		$segments = array_filter(
			SegmentSplitter::split( $input ),
			static function ( string $s ): bool {
				return trim( $s ) !== '';
			}
		);
		if ( $segments === [] ) {
			return '';
		}

		$requestedColumns = (int)( $args['colunas'] ?? $args['columns'] ?? count( $segments ) );
		$columns = max( 1, min( self::MAX_COLUMNS, $requestedColumns ) );

		$html = Html::rawElement( 'div', [
			'class' => 'rwc-grid',
			'style' => "--rwc-grid-columns: {$columns};",
		], implode( '', array_map( static function ( string $segment ) use ( $parser, $frame ): string {
			return Html::rawElement( 'div', [ 'class' => 'rwc-grid-col' ],
				$parser->recursiveTagParse( $segment, $frame ) );
		}, $segments ) ) );

		return $html;
	}
}
