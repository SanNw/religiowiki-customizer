<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Hooks;

use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class HookHandler implements BeforePageDisplayHook, LoadExtensionSchemaUpdatesHook {

	/**
	 * Adiciona o módulo de tema em toda página — é assim que as variáveis
	 * CSS geradas a partir da configuração salva chegam ao navegador, sem
	 * tocar em MediaWiki:Common.css.
	 *
	 * @inheritDoc
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$out->addModuleStyles( 'ext.religiowikiCustomizer.theme' );
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
