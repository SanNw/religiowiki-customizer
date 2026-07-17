<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Components;

use Html;
use Parser;
use PPFrame;

/**
 * ```
 * <rwtabs>
 * Aba 1
 * Conteúdo da aba 1, em wikitext...
 * ----
 * Aba 2
 * Conteúdo da aba 2...
 * </rwtabs>
 * ```
 * Cada segmento separado por uma linha `----`; a primeira linha do
 * segmento é o rótulo da aba (ver SegmentSplitter, compartilhado com
 * GridComponent). Sem nenhum `----`, todo o conteúdo vira uma aba só —
 * não quebra a página por faltar o separador.
 */
class TabsComponent {

	public static function render( ?string $input, array $args, Parser $parser, PPFrame $frame ): string {
		if ( $input === null || trim( $input ) === '' ) {
			return '';
		}

		$segments = SegmentSplitter::split( $input );
		$tabs = [];
		foreach ( $segments as $i => $segment ) {
			if ( trim( $segment ) === '' ) {
				continue;
			}
			[ $label, $rest ] = SegmentSplitter::splitLabel( $segment );
			if ( $label === '' ) {
				$label = wfMessage( 'religiowikicustomizer-tabs-defaultlabel', $i + 1 )->text();
			}
			$tabs[] = [
				'label' => $label,
				'html' => $parser->recursiveTagParse( $rest, $frame ),
			];
		}

		if ( $tabs === [] ) {
			return '';
		}

		$nav = '<div class="rwc-tabs-nav" role="tablist">';
		$panels = '';
		foreach ( $tabs as $i => $tab ) {
			$active = $i === 0;
			$nav .= Html::element( 'button', [
				'type' => 'button',
				'class' => 'rwc-tabs-tab' . ( $active ? ' rwc-tabs-tab-active' : '' ),
				'role' => 'tab',
				'aria-selected' => $active ? 'true' : 'false',
				'data-rwc-tab-index' => (string)$i,
			], $tab['label'] );

			$panels .= Html::rawElement( 'div', [
				'class' => 'rwc-tabs-panel' . ( $active ? ' rwc-tabs-panel-active' : '' ),
				'data-rwc-panel-index' => (string)$i,
			], $tab['html'] );
		}
		$nav .= '</div>';

		return '<div class="rwc-tabs">' . $nav . $panels . '</div>';
	}
}
