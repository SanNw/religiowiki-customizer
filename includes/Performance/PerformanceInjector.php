<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Performance;

use MediaWiki\Extension\ReligiowikiCustomizer\Services\PerformanceSettingsStore;
use OutputPage;

/**
 * Aplica a configuração de performance (Fase 7): `<link rel=preload>` pras
 * fontes configuradas, e enfileira o JS de lazy-loading só se habilitado
 * (não carrega o módulo à toa quando desligado).
 */
class PerformanceInjector {

	public static function inject( OutputPage $out ): void {
		$settings = PerformanceSettingsStore::newFromGlobalState()->getSettings();

		$fontUrls = array_filter( array_map( 'trim', explode( "\n", $settings['preloadFontsText'] ) ) );
		foreach ( $fontUrls as $i => $url ) {
			// Só protocolo http(s) — não é um parâmetro de wikitext exposto
			// a qualquer editor (fica atrás de editinterface), mas ainda
			// assim não vale a pena confiar cegamente no valor salvo.
			if ( !preg_match( '#^https?://#i', $url ) ) {
				continue;
			}
			$out->addHeadItem(
				"rwc-preload-font-{$i}",
				'<link rel="preload" as="font" crossorigin href="'
					. htmlspecialchars( $url, ENT_QUOTES ) . '">'
			);
		}

		if ( $settings['lazyLoadImages'] ) {
			$out->addModules( 'ext.religiowikiCustomizer.lazyImages' );
		}
	}
}
