/*
 * JS da biblioteca de componentes (Fase 4) — carregado em toda página
 * (pode haver <rwaccordion>/<rwtabs> em qualquer artigo). Só interação de
 * mostrar/esconder — nenhum dado é lido do servidor aqui.
 */
( function () {
	'use strict';

	function wireAccordions() {
		document.querySelectorAll( '.rwc-accordion-toggle' ).forEach( function ( btn ) {
			if ( btn.dataset.rwcWired ) {
				return;
			}
			btn.dataset.rwcWired = '1';
			var body = btn.parentNode.querySelector( '.rwc-accordion-body' );
			btn.addEventListener( 'click', function () {
				var isOpen = body.classList.toggle( 'rwc-open' );
				btn.setAttribute( 'aria-expanded', String( isOpen ) );
			} );
		} );
	}

	function wireTabs() {
		document.querySelectorAll( '.rwc-tabs' ).forEach( function ( tabs ) {
			if ( tabs.dataset.rwcWired ) {
				return;
			}
			tabs.dataset.rwcWired = '1';

			var buttons = tabs.querySelectorAll( '.rwc-tabs-tab' );
			var panels = tabs.querySelectorAll( '.rwc-tabs-panel' );

			buttons.forEach( function ( btn ) {
				btn.addEventListener( 'click', function () {
					var index = btn.getAttribute( 'data-rwc-tab-index' );
					buttons.forEach( function ( b ) {
						var active = b === btn;
						b.classList.toggle( 'rwc-tabs-tab-active', active );
						b.setAttribute( 'aria-selected', String( active ) );
					} );
					panels.forEach( function ( p ) {
						p.classList.toggle(
							'rwc-tabs-panel-active',
							p.getAttribute( 'data-rwc-panel-index' ) === index
						);
					} );
				} );
			} );
		} );
	}

	function mount() {
		wireAccordions();
		wireTabs();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', mount );
	} else {
		mount();
	}
}() );
