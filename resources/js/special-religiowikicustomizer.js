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

	// As três funções abaixo usam delegação de evento (listener no
	// document/form, não no elemento final) de propósito: o OOUI infusiona
	// (hidrata) o HTML estático do HTMLForm de forma assíncrona, depois do
	// DOMContentLoaded -- o <input>/<textarea> real (dentro do <div>
	// wrapper que carrega o id customizado; o wrapper em si recebe o id, o
	// controle nativo dentro dele ganha um id autogerado tipo "ooui-php-N")
	// muitas vezes ainda não existe no instante em que mount() roda. Buscar
	// o elemento de verdade só no momento do clique/input (em vez de uma
	// vez só no bind) evita essa corrida por completo.

	function wireThemeLivePreview() {
		var form = document.getElementById( 'religiowikicustomizer-form-theme' );
		if ( !form ) {
			return;
		}
		form.addEventListener( 'input', function ( e ) {
			var input = e.target;
			if ( input.tagName !== 'INPUT' ) {
				return;
			}
			var wrapper = input.closest( '[id^="mw-input-wp"]' );
			if ( !wrapper ) {
				return;
			}
			var key = wrapper.id.slice( 'mw-input-wp'.length );
			if ( !COLOR_TO_VAR[ key ] ) {
				return;
			}
			document.documentElement.style.setProperty( COLOR_TO_VAR[ key ], input.value );
		} );
	}

	function wireCssPreview() {
		document.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '#religiowikicustomizer-preview-css-btn' );
			if ( !btn ) {
				return;
			}
			var wrapper = document.getElementById( 'religiowikicustomizer-customcss-textarea' );
			var textarea = wrapper ? wrapper.querySelector( 'textarea' ) : null;
			if ( !textarea ) {
				return;
			}
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
		document.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '#religiowikicustomizer-preview-js-btn' );
			if ( !btn ) {
				return;
			}
			var wrapper = document.getElementById( 'religiowikicustomizer-customjs-textarea' );
			var textarea = wrapper ? wrapper.querySelector( 'textarea' ) : null;
			if ( !textarea ) {
				return;
			}
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

	function wireImportConfirm() {
		var form = document.getElementById( 'religiowikicustomizer-form-exportimport' );
		if ( !form ) {
			return;
		}
		form.addEventListener( 'submit', function ( e ) {
			// "Modal de aviso" antes de importar (Fase 8) — confirm()
			// nativo, sem dependência nova só pra isso.
			if ( !window.confirm(
				'Isso vai SUBSTITUIR toda a configuração atual (tema, CSS/JS, homepage). ' +
				'Um backup automático é feito antes, mas confirme que é isso que você quer.'
			) ) {
				e.preventDefault();
			}
		} );
	}

	function mount() {
		wireThemeLivePreview();
		wireCssPreview();
		wireImportConfirm();
		wireJsPreview();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', mount );
	} else {
		mount();
	}
}() );
