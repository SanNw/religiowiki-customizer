<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Widgets;

use Parser;
use PPFrame;

/**
 * `<rwreligion nome="..." origem="..." periodo="..." ramos="..." imagem="..." />`
 *
 * Distinto de `<rwinfobox>` (Infobox religião, que documenta a
 * classificação I/II/III do artigo) — este widget é pra artigos sobre a
 * própria tradição religiosa em si (origem histórica, período, ramos).
 */
class ReligionWidget {

	public static function render( ?string $input, array $args, Parser $parser, PPFrame $frame ): string {
		$title = trim( $args['nome'] ?? '' );

		$pairs = [
			[ wfMessage( 'religiowikicustomizer-religion-origem' )->text(), trim( $args['origem'] ?? '' ) ],
			[ wfMessage( 'religiowikicustomizer-religion-periodo' )->text(), trim( $args['periodo'] ?? '' ) ],
			[ wfMessage( 'religiowikicustomizer-religion-ramos' )->text(), trim( $args['ramos'] ?? '' ) ],
		];

		return InfoboxBoxRenderer::render(
			$title,
			trim( $args['imagem'] ?? '' ) ?: null,
			$pairs,
			'rww-religion',
			$parser,
			$frame
		);
	}
}
