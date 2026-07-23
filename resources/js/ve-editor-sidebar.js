/*
 * Barra lateral sanfona pro VisualEditor (Fase 8) -- espelha os 4 grupos do
 * editor-toolbar.js (Formatação, Componentes, Ajuda, Inserir imagens), mas
 * dentro da lateral esquerda (#mw-panel), sticky durante o scroll, em vez da
 * barra horizontal do editor de código-fonte -- pedido do usuário depois que
 * o VisualEditor passou a ficar acessível via opt-in de preferência (ver
 * LocalSettings-snippet.php, rw-ve-optin).
 *
 * Só é montada quando o VisualEditor de fato ativa (mw.hook
 * 've.activationComplete') e é desmontada ao sair do modo de edição
 * (mw.hook 've.deactivationComplete') -- nunca aparece fora do modo visual.
 *
 * Inserção de conteúdo: em vez de reimplementar a conversão wikitexto -> DOM
 * do VisualEditor, reusa a MESMA peça pública que o próprio VE usa pra
 * colar wikitexto copiado (ve.ui.MWWikitextStringTransferHandler) e o mesmo
 * endpoint (action=visualeditor&paction=parsefragment, via
 * target.parseWikitextFragment) que templates/citações/assinaturas do
 * núcleo do VE já usam internamente -- não é uma gambiarra, é o caminho
 * oficial pra inserir wikitexto arbitrário dentro do modelo de dados do VE.
 */
( function () {
	'use strict';

	// Mesmos dados de editor-toolbar.js (Formatação/Componentes), duplicados
	// aqui de propósito -- os dois módulos rodam em superfícies de edição
	// diferentes (textarea vs. VE) e não compensa acoplar os dois só pra
	// compartilhar um array pequeno.
	var BASIC = [
		{ label: 'Negrito', title: 'Negrito — deixa o texto selecionado em negrito.', before: "'''", after: "'''", sample: 'texto' },
		{ label: 'Itálico', title: 'Itálico — deixa o texto inclinado.', before: "''", after: "''", sample: 'texto' },
		{ label: 'Link', title: 'Link interno — liga a outra página do wiki.', before: '[[', after: ']]', sample: 'Nome da página' },
		{ label: 'Link externo', title: 'Link externo — aponta pra um site fora do wiki.', before: '[https://', after: ' Texto do link]', sample: 'site.com' },
		{ label: 'Seção', title: 'Título de seção — cria uma divisão no artigo.', before: '\n== ', after: ' ==\n', sample: 'Título da seção' },
		{ label: 'Subseção', title: 'Subtítulo — um nível abaixo da seção.', before: '\n=== ', after: ' ===\n', sample: 'Título' },
		{ label: '• Lista', title: 'Lista com marcadores.', before: '\n* ', after: '', sample: 'item' },
		{ label: '1. Lista', title: 'Lista numerada.', before: '\n# ', after: '', sample: 'item' },
		{ label: 'Nota', title: 'Nota de rodapé / referência — a fonte aparece no fim do artigo.', before: '<ref>', after: '</ref>', sample: 'fonte' },
		{ label: 'Imagem', title: 'Imagem com legenda — insere um arquivo já enviado (pelo nome).', before: '[[Arquivo:', after: '|thumb|legenda]]', sample: 'foto.jpg' },
		{ label: 'Categoria', title: 'Categoria — coloca o artigo num grupo.', before: '\n[[Category:', after: ']]', sample: 'Nome da categoria' }
	];

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

	var CHEATS = [
		[ '[[Página]]', 'Link para outra página do wiki' ],
		[ '[[Página|texto]]', 'Link mostrando um texto diferente do nome da página' ],
		[ '[https://site.com texto]', 'Link para um site externo' ],
		[ "'''negrito'''", 'Texto em negrito' ],
		[ "''itálico''", 'Texto em itálico' ],
		[ '== Seção ==', 'Título de seção' ],
		[ '* item', 'Lista com marcadores' ],
		[ '# item', 'Lista numerada' ],
		[ '<ref>fonte</ref>', 'Nota de rodapé / referência' ],
		[ '[[Category:Nome]]', 'Coloca o artigo numa categoria' ],
		[ 'Componentes (Card, Alerta...)', 'Use o grupo "Componentes" abaixo — cada um tem parâmetros próprios' ]
	];

	var sidebarEl = null;

	/* Insere um trecho de WIKITEXTO no ponto atual do cursor/seleção do VE,
	 * substituindo a seleção se houver uma. Usa o mesmo par de peças
	 * públicas que ve.ui.MWWikitextStringTransferHandler usa pra colar
	 * wikitexto (ver esse arquivo em VisualEditor/modules/ve-mw/ui/
	 * datatransferhandlers/) -- parseWikitextFragment faz a chamada de API
	 * (action=visualeditor&paction=parsefragment) e
	 * createDocumentFromParsoidHtml converte o HTML resultante num
	 * ve.dm.Document pronto pra inserir.
	 */
	function insertWikitext( wikitext, button ) {
		var target = ve.init.target;
		if ( !target || !target.getSurface ) {
			return;
		}
		var surfaceModel = target.getSurface().getModel();
		var fragment = surfaceModel.getFragment();
		var doc = surfaceModel.getDocument();

		if ( button ) {
			button.disabled = true;
		}

		target.parseWikitextFragment( wikitext, false, doc ).then( function ( response ) {
			if ( ve.getProp( response, 'visualeditor', 'result' ) !== 'success' ) {
				return;
			}
			var newDoc = ve.ui.MWWikitextStringTransferHandler.static.createDocumentFromParsoidHtml(
				response.visualeditor.content,
				doc
			);
			if ( !newDoc.data.hasContent() ) {
				return;
			}
			fragment.insertDocument( newDoc ).collapseToEnd().select();
		} ).always( function () {
			if ( button ) {
				button.disabled = false;
			}
		} );
	}

	/* Envolve o texto SELECIONADO na superfície do VE com before/after (ex.:
	 * negrito, itálico, link) -- equivalente ao wrapSelection() do editor de
	 * código-fonte, só que operando na seleção do VE em vez do textarea. */
	function wrapSelection( before, after, sample, button ) {
		var target = ve.init.target;
		var fragment = target.getSurface().getModel().getFragment();
		var selected = fragment.getText();
		var inner = selected || sample || '';
		insertWikitext( before + inner + after, button );
	}

	function makeButton( label, title, onClick ) {
		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'rwc-ve-btn';
		btn.textContent = label;
		btn.title = title;
		btn.setAttribute( 'aria-label', title );
		btn.addEventListener( 'click', onClick );
		return btn;
	}

	/* Grupo sanfona: título clicável (toggle) + corpo que expande/recolhe.
	 * Confinado à coluna da lateral esquerda -- ao contrário de um flyout/
	 * popup, o corpo expandido empurra o resto da lateral pra baixo dentro
	 * da MESMA coluna estreita, então nunca cobre o texto sendo editado
	 * (que fica numa coluna totalmente separada). */
	function makeGroup( label, buildBody ) {
		var group = document.createElement( 'div' );
		group.className = 'rwc-ve-group';

		var toggle = document.createElement( 'button' );
		toggle.type = 'button';
		toggle.className = 'rwc-ve-group-toggle';
		toggle.setAttribute( 'aria-expanded', 'false' );

		var labelSpan = document.createElement( 'span' );
		labelSpan.textContent = label;
		var chevron = document.createElement( 'span' );
		chevron.className = 'rwc-ve-group-chevron';
		chevron.textContent = '▾';
		chevron.setAttribute( 'aria-hidden', 'true' );
		toggle.appendChild( labelSpan );
		toggle.appendChild( chevron );

		var body = document.createElement( 'div' );
		body.className = 'rwc-ve-group-body';
		body.hidden = true;
		var built = false;

		toggle.addEventListener( 'click', function () {
			var expanded = toggle.getAttribute( 'aria-expanded' ) === 'true';
			if ( !built ) {
				buildBody( body );
				built = true;
			}
			toggle.setAttribute( 'aria-expanded', String( !expanded ) );
			body.hidden = expanded;
		} );

		group.appendChild( toggle );
		group.appendChild( body );
		return group;
	}

	function buildFormatacaoGroup( body ) {
		BASIC.forEach( function ( item ) {
			var btn = makeButton( item.label, item.title, function () {
				wrapSelection( item.before, item.after, item.sample, btn );
			} );
			body.appendChild( btn );
		} );
	}

	function buildComponentesGroup( body ) {
		COMPONENTS.forEach( function ( item ) {
			var btn = makeButton( item.label, item.title, function () {
				insertWikitext( item.text, btn );
			} );
			body.appendChild( btn );
		} );
	}

	function buildAjudaGroup( body ) {
		CHEATS.forEach( function ( row ) {
			var item = document.createElement( 'div' );
			item.className = 'rwc-ve-help-item';
			var code = document.createElement( 'code' );
			code.textContent = row[ 0 ];
			var span = document.createElement( 'span' );
			span.textContent = row[ 1 ];
			item.appendChild( code );
			item.appendChild( span );
			body.appendChild( item );
		} );
	}

	function buildImagensGroup( body ) {
		var btn = makeButton(
			'🖼️ Inserir imagens e mídia',
			'Abre o diálogo nativo do editor visual pra enviar uma imagem nova ou buscar entre as já enviadas.',
			function () {
				var target = ve.init.target;
				if ( target && target.getSurface ) {
					target.getSurface().executeCommand( 'media' );
				}
			}
		);
		body.appendChild( btn );
	}

	function buildSidebar() {
		var el = document.createElement( 'div' );
		el.id = 'rwc-ve-sidebar';
		el.appendChild( makeGroup( 'Formatação', buildFormatacaoGroup ) );
		el.appendChild( makeGroup( 'Componentes', buildComponentesGroup ) );
		el.appendChild( makeGroup( 'Ajuda de formatação', buildAjudaGroup ) );
		el.appendChild( makeGroup( 'Inserir imagens', buildImagensGroup ) );
		return el;
	}

	function mountSidebar() {
		if ( sidebarEl ) {
			return;
		}
		// A lateral esquerda tem uma sequência de ".portal" (Navegação,
		// Categorias, Ferramentas...) dentro de #mw-panel. "Ferramentas" é
		// o portlet nativo TOOLBOX do MediaWiki -- nesta skin o id sai como
		// "p-TOOLBOX" (maiúsculo, é a CHAVE crua do array de content_navigation,
		// não a abreviação "tb" de skins mais antigas tipo Vector -- confirmado
		// ao vivo inspecionando o DOM). Insere logo depois dele, ou no fim da
		// lateral se por algum motivo ele não existir.
		var panel = document.getElementById( 'mw-panel' );
		if ( !panel ) {
			return;
		}
		sidebarEl = buildSidebar();
		var toolsPortal = document.getElementById( 'p-TOOLBOX' );
		if ( toolsPortal && toolsPortal.parentNode === panel ) {
			toolsPortal.insertAdjacentElement( 'afterend', sidebarEl );
		} else {
			panel.appendChild( sidebarEl );
		}
	}

	function unmountSidebar() {
		if ( sidebarEl && sidebarEl.parentNode ) {
			sidebarEl.parentNode.removeChild( sidebarEl );
		}
		sidebarEl = null;
	}

	mw.hook( 've.activationComplete' ).add( mountSidebar );
	mw.hook( 've.deactivationComplete' ).add( unmountSidebar );
}() );
