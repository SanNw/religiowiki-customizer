<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Hooks;

use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class HookHandler implements BeforePageDisplayHook, LoadExtensionSchemaUpdatesHook {

	/**
	 * Adiciona os módulos de tema e de CSS/JS personalizado em toda página
	 * — é assim que a configuração salva chega ao navegador, sem tocar em
	 * MediaWiki:Common.css/Common.js. Ordem de carregamento do CSS é
	 * garantida por getDependencies() em CustomCssResourceLoaderModule
	 * (personalizado sempre depois do tema base), não pela ordem das
	 * chamadas abaixo.
	 *
	 * @inheritDoc
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$out->addModuleStyles( [
			'ext.religiowikiCustomizer.theme',
			'ext.religiowikiCustomizer.customCss',
		] );
		$out->addModules( 'ext.religiowikiCustomizer.customJs' );
	}

	/**
	 * Registra a tabela religiowiki_customizer_settings em `update.php`.
	 *
	 * @inheritDoc
	 */
	public function onLoadExtensionSchemaUpdates( $updater ): void {
		$dir = dirname( __DIR__, 2 ) . '/sql';
		$updater->addExtensionTable(
			'religiowiki_customizer_settings',
			$dir . '/mysql/tables-generated.sql'
		);
	}
}
