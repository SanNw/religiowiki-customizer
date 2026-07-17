<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Components;

/**
 * Divide o conteúdo de `<rwtabs>`/`<rwgrid>` em segmentos separados por uma
 * linha contendo só `----`. Cada segmento vira uma aba/coluna; a primeira
 * linha do segmento é o rótulo (aba) ou é ignorada (coluna, que não tem
 * rótulo). Compartilhado entre TabsComponent e GridComponent pra não
 * duplicar essa lógica de parsing.
 */
class SegmentSplitter {

	/**
	 * @return string[] Segmentos brutos (ainda não parseados como
	 *   wikitext) — sempre pelo menos 1 elemento, mesmo sem nenhum `----`
	 *   encontrado (todo o conteúdo vira o único segmento).
	 */
	public static function split( string $input ): array {
		$parts = preg_split( '/^----$/m', $input );
		return array_map( 'trim', $parts );
	}

	/**
	 * @param string $segment
	 * @return array{0:string,1:string} [rótulo, resto do conteúdo] — usado
	 *   pelas abas, onde a primeira linha é o título da aba.
	 */
	public static function splitLabel( string $segment ): array {
		$lines = explode( "\n", $segment, 2 );
		$label = trim( $lines[0] ?? '' );
		$rest = trim( $lines[1] ?? '' );
		return [ $label, $rest ];
	}
}
