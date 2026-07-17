<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Components;

use Html;
use Parser;
use PPFrame;

/**
 * `<rwbadge cor="verde">Texto curto</rwbadge>`
 *
 * Conteúdo é texto simples (sem wikitext) — um badge é um rótulo curto, não
 * um bloco de conteúdo; `Html::element` já escapa tudo.
 */
class BadgeComponent {

	private const VALID_COLORS = [ 'cinza', 'verde', 'vermelho', 'azul', 'amarelo' ];

	public static function render( ?string $input, array $args, Parser $parser, PPFrame $frame ): string {
		$text = trim( (string)$input );
		if ( $text === '' ) {
			return '';
		}

		$color = strtolower( trim( $args['cor'] ?? $args['color'] ?? 'cinza' ) );
		if ( !in_array( $color, self::VALID_COLORS, true ) ) {
			$color = 'cinza';
		}

		return Html::element( 'span', [
			'class' => "rwc-badge rwc-badge-{$color}",
		], $text );
	}
}
