<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Widgets;

use Parser;
use PPFrame;

/**
 * `<rwauthor nome="..." datas="..." tradicao="..." obras="..." imagem="..." />`
 */
class AuthorWidget {

	public static function render( ?string $input, array $args, Parser $parser, PPFrame $frame ): string {
		$title = trim( $args['nome'] ?? '' );

		$pairs = [
			[ wfMessage( 'religiowikicustomizer-author-datas' )->text(), trim( $args['datas'] ?? '' ) ],
			[ wfMessage( 'religiowikicustomizer-author-tradicao' )->text(), trim( $args['tradicao'] ?? '' ) ],
			[ wfMessage( 'religiowikicustomizer-author-obras' )->text(), trim( $args['obras'] ?? '' ) ],
		];

		return InfoboxBoxRenderer::render(
			$title,
			trim( $args['imagem'] ?? '' ) ?: null,
			$pairs,
			'rww-author',
			$parser,
			$frame
		);
	}
}
