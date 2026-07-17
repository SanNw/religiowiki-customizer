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
			// #mw-input-wp<key> é o <div> wrapper do OOUI, não o <input>
			// real (mesmo problema do wireCssPreview/wireJsPreview) -- busca
			// o <input> de verdade dentro do wrapper.
			var wrapper = form.querySelector( '#mw-input-wp' + key );
			var input = wrapper ? wrapper.querySelector( 'input' ) : null;
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
		// No modo de exibição "ooui" do HTMLForm, o 'id' do campo vai pro
		// <div> wrapper do OOUI, não pro <textarea> real (que recebe um id
		// autogerado tipo "ooui-php-N") -- por isso busca o <textarea> real
		// dentro do wrapper, em vez de getElementById direto.
		var wrapper = document.getElementById( 'religiowikicustomizer-customcss-textarea' );
		var textarea = wrapper ? wrapper.querySelector( 'textarea' ) : null;
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
		// Mesmo problema do wireCssPreview: o wrapper OOUI fica com o id, o
		// <textarea> real tem um id autogerado -- busca dentro do wrapper.
		var wrapper = document.getElementById( 'religiowikicustomizer-customjs-textarea' );
		var textarea = wrapper ? wrapper.querySelector( 'textarea' ) : null;
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
