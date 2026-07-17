<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Components;

use Html;
use Parser;
use PPFrame;

/**
 * `<rwaccordion titulo="Pergunta">Resposta em wikitext</rwaccordion>`
 *
 * Um item por tag — pra ter vários, o editor empilha vários
 * `<rwaccordion>` em sequência (cada um abre/fecha independente via
 * resources/js/components.js). Sem `titulo`, cai num rótulo genérico em
 * vez de quebrar a renderização (fallback exigido pela Fase 4).
 */
class AccordionComponent {

	public static function render( ?string $input, array $args, Parser $parser, PPFrame $frame ): string {
		$content = $input === null ? '' : $parser->recursiveTagParse( $input, $frame );
		if ( trim( $content ) === '' ) {
			return '';
		}

		$title = trim( $args['titulo'] ?? $args['title'] ?? '' );
		if ( $title === '' ) {
			$title = wfMessage( 'religiowikicustomizer-accordion-defaulttitle' )->text();
		}

		return '<div class="rwc-accordion">'
			. '<button type="button" class="rwc-accordion-toggle" aria-expanded="false">'
			. Html::element( 'span', [], $title )
			. '<span class="rwc-accordion-chevron">▾</span>'
			. '</button>'
			. Html::rawElement( 'div', [ 'class' => 'rwc-accordion-body' ], $content )
			. '</div>';
	}
}
