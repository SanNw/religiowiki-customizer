<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Widgets;

use Parser;
use PPFrame;

/**
 * `<rwschool nome="..." fundador="..." periodo="..." conceitos="..." imagem="..." />`
 */
class SchoolWidget {

	public static function render( ?string $input, array $args, Parser $parser, PPFrame $frame ): string {
		$title = trim( $args['nome'] ?? '' );

		$pairs = [
			[ wfMessage( 'religiowikicustomizer-school-fundador' )->text(), trim( $args['fundador'] ?? '' ) ],
			[ wfMessage( 'religiowikicustomizer-school-periodo' )->text(), trim( $args['periodo'] ?? '' ) ],
			[ wfMessage( 'religiowikicustomizer-school-conceitos' )->text(), trim( $args['conceitos'] ?? '' ) ],
		];

		return InfoboxBoxRenderer::render(
			$title,
			trim( $args['imagem'] ?? '' ) ?: null,
			$pairs,
			'rww-school',
			$parser,
			$frame
		);
	}
}
