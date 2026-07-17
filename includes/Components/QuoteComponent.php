<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Components;

use Html;
use Parser;
use PPFrame;

/**
 * `<rwquote autor="Nome" fonte="Obra">texto da citação, wikitext ok</rwquote>`
 */
class QuoteComponent {

	public static function render( ?string $input, array $args, Parser $parser, PPFrame $frame ): string {
		$content = $input === null ? '' : $parser->recursiveTagParse( $input, $frame );
		if ( trim( $content ) === '' ) {
			return '';
		}

		$author = trim( $args['autor'] ?? $args['author'] ?? '' );
		$source = trim( $args['fonte'] ?? $args['source'] ?? '' );

		$footer = '';
		if ( $author !== '' || $source !== '' ) {
			$footerText = trim( $author . ( $source !== '' ? ' — ' . $source : '' ) );
			$footer = Html::element( 'footer', [ 'class' => 'rwc-quote-footer' ], $footerText );
		}

		return '<blockquote class="rwc-quote">'
			. Html::rawElement( 'div', [ 'class' => 'rwc-quote-body' ], $content )
			. $footer
			. '</blockquote>';
	}
}
