<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Widgets;

use Html;
use MediaWiki\Extension\ReligiowikiCustomizer\Components\SegmentSplitter;
use Parser;
use PPFrame;

/**
 * ```
 * <rwtimeline>
 * 313
 * Edito de Milão — cristianismo passa a ser tolerado no Império Romano.
 * ----
 * 380
 * Edito de Tessalônica — cristianismo se torna religião oficial do Império.
 * </rwtimeline>
 * ```
 * Mesma convenção `----` de `<rwtabs>`/`<rwgrid>` (SegmentSplitter,
 * Fase 4) — primeira linha do segmento é a data, o resto é a descrição em
 * wikitext. Sem nenhum `----`, vira um evento só.
 */
class TimelineWidget {

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

		$html = '<ol class="rww-timeline">';
		foreach ( $segments as $segment ) {
			[ $date, $rest ] = SegmentSplitter::splitLabel( $segment );
			$html .= '<li class="rww-timeline-item">'
				. Html::element( 'span', [ 'class' => 'rww-timeline-date' ], $date )
				. Html::rawElement( 'div', [ 'class' => 'rww-timeline-desc' ],
					$parser->recursiveTagParse( $rest, $frame ) )
				. '</li>';
		}
		$html .= '</ol>';

		return $html;
	}
}
