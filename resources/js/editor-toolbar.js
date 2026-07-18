/*
 * Recursos para editores (Fase 7) — barra acima da área de edição de
 * wikitexto que (1) insere formatação básica e os componentes custom sem
 * decorar sintaxe e (2) EXPLICA o que cada comando faz (tooltip em cada
 * botão + um guia rápido "Ajuda de formatação"). Só aparece na edição por
 * wikitexto; manipula o textarea direto, sem depender da API interna do
 * WikiEditor (mais simples e estável entre versões).
 */
( function () {
	'use strict';

	// Formatação básica. Cada botão embrulha a seleção com `before`/`after`
	// e explica, no title (tooltip), exatamente o que faz — é o que faltava
	// (ex.: que "[[" serve pra criar link).
	var BASIC = [
		{ label: 'Negrito', title: 'Negrito — deixa o texto selecionado em negrito. Sintaxe: \'\'\'texto\'\'\' (três apóstrofos de cada lado).', before: "'''", after: "'''", sample: 'texto' },
		{ label: 'Itálico', title: 'Itálico — deixa o texto inclinado. Sintaxe: \'\'texto\'\' (dois apóstrofos de cada lado).', before: "''", after: "''", sample: 'texto' },
		{ label: 'Link', title: 'Link interno — liga a outra página do wiki. Selecione o nome da página e clique: vira [[Página]] (dois colchetes de cada lado).', before: '[[', after: ']]', sample: 'Nome da página' },
		{ label: 'Link externo', title: 'Link externo — aponta pra um site fora do wiki. Sintaxe: [https://site.com Texto do link].', before: '[https://', after: ' Texto do link]', sample: 'site.com' },
		{ label: 'Seção', title: 'Título de seção — cria uma divisão no artigo e entra no índice "Neste artigo". Sintaxe: == Seção == (dois iguais de cada lado).', before: '\n== ', after: ' ==\n', sample: 'Título da seção' },
		{ label: 'Subseção', title: 'Subtítulo — um nível abaixo da seção. Sintaxe: === Subseção === (três iguais de cada lado).', before: '\n=== ', after: ' ===\n', sample: 'Título' },
		{ label: '• Lista', title: 'Lista com marcadores — cada linha começando com * vira um item.', before: '\n* ', after: '', sample: 'item' },
		{ label: '1. Lista', title: 'Lista numerada — cada linha começando com # vira um item numerado.', before: '\n# ', after: '', sample: 'item' },
		{ label: 'Nota', title: 'Nota de rodapé / referência — a fonte aparece no fim do artigo. Sintaxe: <ref>fonte</ref>.', before: '<ref>', after: '</ref>', sample: 'fonte' },
		{ label: 'Imagem', title: 'Imagem com legenda — insere um arquivo já enviado. Sintaxe: [[Arquivo:foto.jpg|thumb|legenda]].', before: '[[Arquivo:', after: '|thumb|legenda]]', sample: 'foto.jpg' },
		{ label: 'Categoria', title: 'Categoria — coloca o artigo num grupo (ex.: Cristianismo). Não aparece no texto, só no rodapé. Sintaxe: [[Category:Nome]].', before: '\n[[Category:', after: ']]', sample: 'Nome da categoria' }
	];

	// Componentes custom da ReligioWiki (Fase 4/5) — agora com explicação.
	var COMPONENTS = [
		{ label: 'Card', title: 'Card — um cartão com título, texto, link e ícone.', text: '<rwcard titulo="" texto="" link="" icone="" />' },
		{ label: 'Alerta', title: 'Alerta — caixa de destaque (info/aviso/erro).', text: '<rwalert tipo="info">\n\n</rwalert>' },
		{ label: 'Accordion', title: 'Accordion — bloco que abre/fecha ao clicar no título.', text: '<rwaccordion titulo="">\n\n</rwaccordion>' },
		{ label: 'Abas', title: 'Abas — conteúdo em abas; separe cada aba com uma linha "----".', text: '<rwtabs>\nAba 1\n\n----\nAba 2\n\n</rwtabs>' },
		{ label: 'Citação', title: 'Citação — bloco de citação com autor e fonte.', text: '<rwquote autor="" fonte="">\n\n</rwquote>' },
		{ label: 'Callout', title: 'Callout — chamada em destaque com título.', text: '<rwcallout titulo="">\n\n</rwcallout>' },
		{ label: 'Grid', title: 'Grid — organiza blocos em colunas; separe cada coluna com "----".', text: '<rwgrid colunas="2">\n\n----\n\n</rwgrid>' },
		{ label: 'Linha do tempo', title: 'Linha do tempo — eventos em ordem cronológica.', text: '<rwtimeline>\nData\nDescrição do evento...\n\n</rwtimeline>' }
	];

	// Guia rápido: sintaxe -> o que faz (mesma informação dos tooltips, num só lugar).
	var CHEATS = [
		[ '[[Página]]', 'Link para outra página do wiki' ],
		[ '[[Página|texto]]', 'Link mostrando um texto diferente do nome da página' ],
		[ '[https://site.com texto]', 'Link para um site externo' ],
		[ "'''negrito'''", 'Texto em negrito' ],
		[ "''itálico''", 'Texto em itálico' ],
		[ '== Seção ==', 'Título de seção (entra no índice "Neste artigo")' ],
		[ '=== Subseção ===', 'Subtítulo, um nível abaixo' ],
		[ '* item', 'Lista com marcadores' ],
		[ '# item', 'Lista numerada' ],
		[ '<ref>fonte</ref>', 'Nota de rodapé / referência' ],
		[ '[[Arquivo:foto.jpg|thumb|legenda]]', 'Imagem com legenda' ],
		[ '[[Category:Nome]]', 'Coloca o artigo numa categoria' ]
	];

	function wrapSelection( textarea, before, after, sample ) {
		var start = textarea.selectionStart;
		var end = textarea.selectionEnd;
		var selected = textarea.value.substring( start, end );
		var inner = selected || sample || '';
		var text = before + inner + after;
		textarea.value = textarea.value.substring( 0, start ) + text + textarea.value.substring( end );
		textarea.focus();
		// Se não havia seleção, seleciona o "sample" pra facilitar sobrescrever.
		if ( !selected && inner ) {
			textarea.setSelectionRange( start + before.length, start + before.length + inner.length );
		} else {
			var pos = start + text.length;
			textarea.setSelectionRange( pos, pos );
		}
	}

	function makeButton( label, title, onClick ) {
		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.textContent = label;
		btn.title = title;
		btn.setAttribute( 'aria-label', title );
		btn.addEventListener( 'click', onClick );
		return btn;
	}

	function makeGroup( labelText ) {
		var group = document.createElement( 'div' );
		group.className = 'rwc-editor-group';
		var lbl = document.createElement( 'span' );
		lbl.className = 'rwc-editor-group-label';
		lbl.textContent = labelText;
		group.appendChild( lbl );
		return group;
	}

	function buildHelpPanel() {
		var panel = document.createElement( 'div' );
		panel.id = 'rwc-editor-help';
		panel.hidden = true;

		var intro = document.createElement( 'p' );
		intro.className = 'rwc-editor-help-intro';
		intro.textContent = 'Guia rápido de formatação — o que cada comando faz. Selecione um texto e clique num botão acima, ou digite a sintaxe à mão:';
		panel.appendChild( intro );

		var table = document.createElement( 'table' );
		table.className = 'rwc-editor-help-table';
		CHEATS.forEach( function ( row ) {
			var tr = document.createElement( 'tr' );
			var code = document.createElement( 'td' );
			var c = document.createElement( 'code' );
			c.textContent = row[ 0 ];
			code.appendChild( c );
			var desc = document.createElement( 'td' );
			desc.textContent = row[ 1 ];
			tr.appendChild( code );
			tr.appendChild( desc );
			table.appendChild( tr );
		} );
		panel.appendChild( table );
		return panel;
	}

	function mount() {
		var textarea = document.getElementById( 'wpTextbox1' );
		if ( !textarea ) {
			return;
		}

		var bar = document.createElement( 'div' );
		bar.id = 'rwc-editor-toolbar';

		var basicGroup = makeGroup( 'Formatação' );
		BASIC.forEach( function ( item ) {
			basicGroup.appendChild( makeButton( item.label, item.title, function () {
				wrapSelection( textarea, item.before, item.after, item.sample );
			} ) );
		} );
		bar.appendChild( basicGroup );

		var compGroup = makeGroup( 'Componentes' );
		COMPONENTS.forEach( function ( item ) {
			compGroup.appendChild( makeButton( item.label, item.title, function () {
				wrapSelection( textarea, item.text, '', '' );
			} ) );
		} );
		bar.appendChild( compGroup );

		var helpPanel = buildHelpPanel();
		var helpGroup = makeGroup( 'Ajuda' );
		var helpBtn = makeButton( '❓ Ajuda de formatação', 'Mostra/esconde um guia rápido do que cada comando faz.', function () {
			helpPanel.hidden = !helpPanel.hidden;
			helpBtn.setAttribute( 'aria-expanded', String( !helpPanel.hidden ) );
		} );
		helpBtn.className = 'rwc-editor-help-toggle';
		helpBtn.setAttribute( 'aria-expanded', 'false' );
		helpGroup.appendChild( helpBtn );
		bar.appendChild( helpGroup );

		textarea.parentNode.insertBefore( bar, textarea );
		textarea.parentNode.insertBefore( helpPanel, textarea );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', mount );
	} else {
		mount();
	}
}() );
