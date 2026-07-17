<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Components;

use Html;
use Parser;
use PPFrame;

/**
 * `<rwcallout titulo="Nota">conteúdo em wikitext</rwcallout>`
 *
 * Diferente de `<rwalert>`: não tem um "tipo" semântico (info/aviso/erro/
 * sucesso) nem ícone fixo — é um destaque neutro de propósito geral, com
 * título opcional.
 */
class CalloutComponent {

	public static function render( ?string $input, array $args, Parser $parser, PPFrame $frame ): string {
		$content = $input === null ? '' : $parser->recursiveTagParse( $input, $frame );
		if ( trim( $content ) === '' ) {
			return '';
		}

		$title = trim( $args['titulo'] ?? $args['title'] ?? '' );
		$titleHtml = $title !== '' ? Html::element( 'div', [ 'class' => 'rwc-callout-title' ], $title ) : '';

		return '<div class="rwc-callout">'
			. $titleHtml
			. Html::rawElement( 'div', [ 'class' => 'rwc-callout-body' ], $content )
			. '</div>';
	}
}
