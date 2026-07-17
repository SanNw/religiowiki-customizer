<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Widgets;

use Parser;
use PPFrame;

/**
 * `<rwbook titulo="..." autor="..." ano="..." editora="..." capa="..." sinopse="..." />`
 */
class BookWidget {

	public static function render( ?string $input, array $args, Parser $parser, PPFrame $frame ): string {
		$title = trim( $args['titulo'] ?? '' );

		$pairs = [
			[ wfMessage( 'religiowikicustomizer-book-autor' )->text(), trim( $args['autor'] ?? '' ) ],
			[ wfMessage( 'religiowikicustomizer-book-ano' )->text(), trim( $args['ano'] ?? '' ) ],
			[ wfMessage( 'religiowikicustomizer-book-editora' )->text(), trim( $args['editora'] ?? '' ) ],
			[ wfMessage( 'religiowikicustomizer-book-sinopse' )->text(), trim( $args['sinopse'] ?? '' ) ],
		];

		return InfoboxBoxRenderer::render(
			$title,
			trim( $args['capa'] ?? '' ) ?: null,
			$pairs,
			'rww-book',
			$parser,
			$frame
		);
	}
}
