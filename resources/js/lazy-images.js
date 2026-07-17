/*
 * Lazy loading de imagens (Fase 7) — só carregado quando habilitado em
 * Special:ReligiowikiCustomizer (aba Performance). Adiciona o atributo
 * nativo `loading="lazy"` do navegador nas imagens do conteúdo do artigo
 * que ainda não têm esse atributo — não reimplementa lazy loading em JS
 * (o navegador já faz isso sozinho a partir do atributo), só garante que
 * ele existe nas imagens que o MediaWiki core não marcou.
 */
( function () {
	'use strict';

	function mount() {
		document.querySelectorAll( '.mw-parser-output img:not([loading])' ).forEach( function ( img ) {
			img.loading = 'lazy';
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', mount );
	} else {
		mount();
	}
}() );
