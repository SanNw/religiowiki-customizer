/*
 * Special:Artigos (Fase 9) — confirmação de exclusão por digitação. O botão
 * "Excluir definitivamente" só habilita quando o texto digitado bate
 * exatamente com o nome completo do artigo (o servidor revalida isso também,
 * então mesmo com JS desligado a exclusão sem o nome certo é recusada). Um
 * confirm() final evita clique acidental.
 */
( function () {
	'use strict';

	function initDeleteForm() {
		var form = document.querySelector( '.rw-artigos-delete-form' );
		if ( !form ) {
			return;
		}
		var expected = form.getAttribute( 'data-expected' ) || '';
		var input = form.querySelector( '.rw-artigos-confirm-input' );
		var submit = form.querySelector( '.rw-artigos-delete-submit' );
		if ( !input || !submit ) {
			return;
		}

		function sync() {
			var match = input.value === expected;
			submit.disabled = !match;
		}

		input.addEventListener( 'input', sync );
		input.addEventListener( 'paste', function () {
			window.setTimeout( sync, 0 );
		} );
		sync();

		form.addEventListener( 'submit', function ( e ) {
			if ( input.value !== expected ) {
				e.preventDefault();
				return;
			}
			// eslint-disable-next-line no-alert
			if ( !window.confirm( mw.msg( 'religiowikicustomizer-artigos-delete-jsconfirm', expected ) ) ) {
				e.preventDefault();
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initDeleteForm );
	} else {
		initDeleteForm();
	}
}() );
