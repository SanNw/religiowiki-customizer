<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\SEO;

use Html;
use Title;

/**
 * Breadcrumbs baseados nas categorias do artigo (decisão de arquitetura 1
 * da Fase 6, item "Breadcrumbs"). Só considera a PRIMEIRA categoria do
 * artigo (não tenta resolver a árvore inteira de subcategorias — a
 * classificação do religio-wiki já tem uma hierarquia rasa o bastante,
 * ex.: Cristianismo > III. Monoteísmos Semíticos, então isso é suficiente
 * sem precisar de travessia recursiva de categoria).
 */
class BreadcrumbBuilder {

	/**
	 * @param Title $title
	 * @param string[] $categoryNames Nomes das categorias do artigo (sem o
	 *   prefixo "Category:"), na ordem em que aparecem no wikitext.
	 */
	public static function build( Title $title, array $categoryNames ): string {
		$items = [
			[ wfMessage( 'religiowikicustomizer-breadcrumb-home' )->text(), Title::newMainPage()->getLocalURL() ],
		];

		if ( $categoryNames !== [] ) {
			$catTitle = Title::makeTitleSafe( NS_CATEGORY, $categoryNames[0] );
			if ( $catTitle ) {
				$items[] = [ $catTitle->getText(), $catTitle->getLocalURL() ];
			}
		}

		$items[] = [ $title->getText(), null ];

		$html = '<nav class="rwc-breadcrumbs" aria-label="breadcrumb"><ol>';
		foreach ( $items as [ $label, $url ] ) {
			if ( $url !== null ) {
				$html .= '<li>' . Html::element( 'a', [ 'href' => $url ], $label ) . '</li>';
			} else {
				$html .= '<li aria-current="page">' . htmlspecialchars( $label, ENT_QUOTES ) . '</li>';
			}
		}
		$html .= '</ol></nav>';

		return $html;
	}
}
