<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Widgets;

use Parser;
use PPFrame;

/**
 * `<rwinfobox nome="..." imagem="..." grupo="..." relacionadas="..." texto_central="..." origem="..." />`
 *
 * Equivalente ao antigo `Template:Infobox religião` (que usava
 * ParserFunctions puro) — mesmos nomes de parâmetro, pra que
 * `maintenance/generateConvenienceTemplates.php` possa gerar um template
 * de conveniência que só chama esta tag, sem quebrar artigos que já usam
 * `{{Infobox religião|...}}`.
 */
class InfoboxWidget {

	public static function render( ?string $input, array $args, Parser $parser, PPFrame $frame ): string {
		$title = trim( $args['nome'] ?? '' );
		if ( $title === '' ) {
			$title = $parser->getTitle() ? $parser->getTitle()->getText() : '';
		}

		$pairs = [
			[ wfMessage( 'religiowikicustomizer-infobox-grupo' )->text(), trim( $args['grupo'] ?? '' ) ],
			[ wfMessage( 'religiowikicustomizer-infobox-relacionadas' )->text(), trim( $args['relacionadas'] ?? '' ) ],
			[ wfMessage( 'religiowikicustomizer-infobox-textocentral' )->text(), trim( $args['texto_central'] ?? '' ) ],
			[ wfMessage( 'religiowikicustomizer-infobox-origem' )->text(), trim( $args['origem'] ?? '' ) ],
		];

		return InfoboxBoxRenderer::render(
			$title,
			trim( $args['imagem'] ?? '' ) ?: null,
			$pairs,
			'rww-religion-infobox',
			$parser,
			$frame
		);
	}
}
