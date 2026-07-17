<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Widgets;

use Html;
use MediaWiki\Extension\ReligiowikiCustomizer\Components\LinkSanitizer;
use Parser;
use PPFrame;
use Title;

/**
 * Estrutura visual compartilhada por todos os widgets de infobox (Infobox
 * religião, Livro, Autor, Religião, Escola Filosófica) — título + imagem
 * opcional + lista de pares chave-valor. Evita reimplementar a mesma caixa
 * 5 vezes (decisão de reuso da Fase 5).
 */
class InfoboxBoxRenderer {

	/**
	 * @param string $title
	 * @param string|null $image Nome de um arquivo já enviado ao wiki (sem
	 *   o prefixo "Arquivo:"), ou null/vazio pra não mostrar imagem.
	 * @param array<int,array{0:string,1:string}> $pairs Lista de [rótulo, valor].
	 *   Pares com valor vazio são mostrados como "—", nunca omitidos (mantém
	 *   a caixa com layout consistente independente do quanto foi
	 *   preenchido — fallback exigido pela Fase 5).
	 * @param string $cssClass Classe adicional pro tipo de widget (ex.:
	 *   'rww-book'), além da genérica 'rww-infobox'.
	 * @param Parser $parser Necessário pra renderizar a imagem de verdade —
	 *   `[[Arquivo:...]]` só vira `<img>` se passar pelo parser
	 *   (recursiveTagParse), não é algo que dê pra montar como string HTML
	 *   crua e devolver direto do tag hook.
	 * @param PPFrame $frame
	 */
	public static function render(
		string $title,
		?string $image,
		array $pairs,
		string $cssClass,
		Parser $parser,
		PPFrame $frame
	): string {
		$html = '<div class="rww-infobox ' . htmlspecialchars( $cssClass, ENT_QUOTES ) . '">';
		$html .= Html::element( 'div', [ 'class' => 'rww-infobox-title' ], $title );

		if ( $image ) {
			$fileTitle = Title::makeTitleSafe( NS_FILE, $image );
			if ( $fileTitle && $fileTitle->exists() ) {
				$wikitext = '[[' . $fileTitle->getPrefixedText() . '|frameless|center|upright=1.3]]';
				$html .= '<div class="rww-infobox-image">'
					. $parser->recursiveTagParse( $wikitext, $frame )
					. '</div>';
			}
		}

		$html .= '<dl class="rww-infobox-pairs">';
		foreach ( $pairs as [ $label, $value ] ) {
			$html .= Html::element( 'dt', [], $label );
			$html .= Html::element( 'dd', [], $value !== '' ? $value : '—' );
		}
		$html .= '</dl></div>';

		return $html;
	}

	/**
	 * Constrói o `href` de um valor que pode ser um link (ex.: obra
	 * relacionada), validado via LinkSanitizer — usado pelos widgets que
	 * quiserem linkar um valor em vez de só exibir texto.
	 */
	public static function safeLink( string $value ): ?string {
		return LinkSanitizer::sanitize( $value );
	}
}
