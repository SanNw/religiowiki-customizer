/*
 * Recursos para editores (Fase 7) — botões de inserção rápida acima da
 * área de edição de wikitexto, pra inserir os componentes (Fase 4) e
 * templates de conveniência (Fase 5) sem decorar a sintaxe. Só aparece na
 * edição por wikitexto (não no VisualEditor); manipula o textarea
 * diretamente, sem depender da API interna do WikiEditor (mais simples e
 * estável entre versões do que se acoplar ao módulo de toolbar dele).
 */
( function () {
	'use strict';

	var SNIPPETS = [
		{ label: 'Card', text: '<rwcard titulo="" texto="" link="" icone="" />' },
		{ label: 'Alerta', text: '<rwalert tipo="info">\n\n</rwalert>' },
		{ label: 'Accordion', text: '<rwaccordion titulo="">\n\n</rwaccordion>' },
		{ label: 'Tabs', text: '<rwtabs>\nAba 1\n\n----\nAba 2\n\n</rwtabs>' },
		{ label: 'Citação', text: '<rwquote autor="" fonte="">\n\n</rwquote>' },
		{ label: 'Callout', text: '<rwcallout titulo="">\n\n</rwcallout>' },
		{ label: 'Grid', text: '<rwgrid colunas="2">\n\n----\n\n</rwgrid>' },
		{ label: 'Linha do tempo', text: '<rwtimeline>\nData\nDescrição do evento...\n\n</rwtimeline>' }
	];

	function insertAtCursor( textarea, text ) {
		var start = textarea.selectionStart;
		var end = textarea.selectionEnd;
		var before = textarea.value.substring( 0, start );
		var after = textarea.value.substring( end );
		textarea.value = before + text + after;
		textarea.focus();
		var pos = start + text.length;
		textarea.setSelectionRange( pos, pos );
	}

	function mount() {
		var textarea = document.getElementById( 'wpTextbox1' );
		if ( !textarea ) {
			return;
		}

		var bar = document.createElement( 'div' );
		bar.id = 'rwc-editor-toolbar';

		SNIPPETS.forEach( function ( snippet ) {
			var btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.textContent = snippet.label;
			btn.addEventListener( 'click', function () {
				insertAtCursor( textarea, snippet.text );
			} );
			bar.appendChild( btn );
		} );

		textarea.parentNode.insertBefore( bar, textarea );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', mount );
	} else {
		mount();
	}
}() );
