/*
 * JS de Special:ReligiowikiCustomizer — só roda nessa página (carregado via
 * ext.religiowikiCustomizer.special, não em toda página do wiki).
 *
 * Três funções:
 *   1. Preview em tempo real da aba Aparência: recalcula as CSS custom
 *      properties no <html> a cada tecla, sem reload nem gravar nada — o
 *      admin vê o efeito na própria página do formulário.
 *   2. Botão "Visualizar" da aba CSS: injeta o textarea num <style> só
 *      nesta aba de navegador, sem persistir.
 *   3. Botão "Visualizar" da aba JS: executa o textarea uma vez, só nesta
 *      aba de navegador, sem persistir — é a mesma execução que ele teria
 *      se salvo (ver aviso na página), só que local e não repetida a cada
 *      carregamento.
 */
( function () {
	'use strict';

	var COLOR_TO_VAR = {
		primary: '--rw-primary',
		secondary: '--rw-secondary',
		background: '--rw-background',
		surface: '--rw-surface',
		border: '--rw-border',
		text: '--rw-text',
		textMuted: '--rw-text-muted'
	};

	function wireThemeLivePreview() {
		var form = document.getElementById( 'religiowikicustomizer-form-theme' );
		if ( !form ) {
			return;
		}
		Object.keys( COLOR_TO_VAR ).forEach( function ( key ) {
			var input = form.querySelector( '#mw-input-wp' + key );
			if ( !input ) {
				return;
			}
			input.addEventListener( 'input', function () {
				document.documentElement.style.setProperty( COLOR_TO_VAR[ key ], input.value );
			} );
		} );
	}

	function wireCssPreview() {
		var btn = document.getElementById( 'religiowikicustomizer-preview-css-btn' );
		var textarea = document.getElementById( 'religiowikicustomizer-customcss-textarea' );
		if ( !btn || !textarea ) {
			return;
		}
		btn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			var style = document.getElementById( 'religiowikicustomizer-preview-style' );
			if ( !style ) {
				style = document.createElement( 'style' );
				style.id = 'religiowikicustomizer-preview-style';
				document.head.appendChild( style );
			}
			style.textContent = textarea.value;
		} );
	}

	function wireJsPreview() {
		var btn = document.getElementById( 'religiowikicustomizer-preview-js-btn' );
		var textarea = document.getElementById( 'religiowikicustomizer-customjs-textarea' );
		if ( !btn || !textarea ) {
			return;
		}
		btn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			// Executa uma vez, só no navegador de quem clicou — mesmo
			// código que rodaria pra todo mundo se fosse salvo (ver aviso
			// na página). Não usa eval() direto: um <script> injetado roda
			// no escopo global normal da página, igual ao módulo real.
			var script = document.createElement( 'script' );
			script.textContent = textarea.value;
			document.body.appendChild( script );
			document.body.removeChild( script );
		} );
	}

	function mount() {
		wireThemeLivePreview();
		wireCssPreview();
		wireJsPreview();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', mount );
	} else {
		mount();
	}
}() );
