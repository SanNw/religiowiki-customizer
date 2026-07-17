( function () {
	'use strict';

	// MediaWiki:Common.js já injeta a marca (.rw-brand) no cabeçalho, mas o
	// núcleo do MediaWiki não carrega o módulo `site` (Common.css/Common.js)
	// em Special:Preferências -- é assim em qualquer instalação, não uma
	// falha nossa: a página de preferências evita depender do CSS/JS do
	// site pra continuar utilizável mesmo se eles estiverem quebrados.
	// Este módulo próprio da extensão roda ali também (confirmado ao vivo),
	// então replica só o essencial da marca aqui. A checagem abaixo faz
	// isso ser inofensivo nas páginas normais, onde Common.js já rodou.
	function mount() {
		var head = document.getElementById( 'mw-head' );
		if ( !head || document.querySelector( '.rw-brand' ) ) {
			return;
		}

		var link = document.createElement( 'a' );
		link.className = 'rw-brand';
		link.href = ( typeof mw !== 'undefined' && mw.util ) ? mw.util.getUrl( '' ) : '/';

		var mark = document.createElement( 'span' );
		mark.className = 'rw-brand-mark';
		mark.textContent = 'R';
		mark.setAttribute( 'aria-hidden', 'true' );
		link.appendChild( mark );

		var label = document.createElement( 'span' );
		label.textContent = ( typeof mw !== 'undefined' && mw.config ) ? mw.config.get( 'wgSiteName' ) : 'Religio Wiki';
		link.appendChild( label );

		head.insertBefore( link, head.firstChild );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', mount );
	} else {
		mount();
	}
}() );
