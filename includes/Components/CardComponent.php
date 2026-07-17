<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Components;

use Html;
use Parser;
use PPFrame;

/**
 * `<rwcard titulo="..." texto="..." link="..." icone="📖" />`
 *
 * buildHtml() é reaproveitado pelo bloco "Cards" (e Notícias/Livros) da
 * Homepage Builder (Fase 3) — evita duplicar o markup do card em dois
 * lugares (decisão de integração explícita da Fase 4).
 */
class CardComponent {

	/**
	 * Entrada do parser tag `<rwcard>`.
	 *
	 * @param string|null $input
	 * @param array $args
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return string
	 */
	public static function render( ?string $input, array $args, Parser $parser, PPFrame $frame ): string {
		return self::buildHtml( [
			'title' => $args['titulo'] ?? $args['title'] ?? '',
			'text' => $args['texto'] ?? $args['text'] ?? trim( (string)$input ),
			'link' => $args['link'] ?? '',
			'icon' => $args['icone'] ?? $args['icon'] ?? '',
		] );
	}

	/**
	 * @param array{title?:string,text?:string,link?:string,icon?:string} $item
	 *   Todo campo é opcional — um card sem nenhum conteúdo ainda assim
	 *   renderiza (div vazia estilizada), nunca quebra a página.
	 */
	public static function buildHtml( array $item ): string {
		$html = '<div class="rwc-card">';

		if ( !empty( $item['icon'] ) ) {
			$html .= Html::element( 'span', [ 'class' => 'rwc-card-icon' ], (string)$item['icon'] );
		}
		if ( !empty( $item['title'] ) ) {
			$html .= Html::element( 'h3', [ 'class' => 'rwc-card-title' ], (string)$item['title'] );
		}
		if ( !empty( $item['text'] ) ) {
			$html .= Html::element( 'p', [ 'class' => 'rwc-card-text' ], (string)$item['text'] );
		}
		if ( !empty( $item['link'] ) ) {
			$url = LinkSanitizer::sanitize( (string)$item['link'] );
			if ( $url !== null ) {
				$html .= Html::element( 'a', [ 'class' => 'rwc-card-link', 'href' => $url ],
					wfMessage( 'religiowikicustomizer-card-readmore' )->text() );
			}
		}

		$html .= '</div>';
		return $html;
	}
}
