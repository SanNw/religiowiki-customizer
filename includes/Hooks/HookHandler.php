<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Hooks;

use MediaWiki\Extension\ReligiowikiCustomizer\Homepage\HomepageRenderer;
use MediaWiki\Extension\ReligiowikiCustomizer\Services\HomepageConfigStore;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\OutputPageBeforeHTMLHook;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class HookHandler implements BeforePageDisplayHook, LoadExtensionSchemaUpdatesHook, OutputPageBeforeHTMLHook {

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
	 * Homepage Builder (Fase 3): só troca o conteúdo da Main Page se houver
	 * pelo menos um bloco habilitado configurado. Se a extensão for
	 * desativada, este hook nunca roda; se estiver ativa mas sem nenhum
	 * bloco habilitado, $text permanece o HTML normal da página wiki — a
	 * página em si continua existindo e editável como sempre (decisão de
	 * arquitetura 1 da Fase 3: página wiki como fallback).
	 *
	 * @inheritDoc
	 */
	public function onOutputPageBeforeHTML( $out, &$text ): void {
		$title = $out->getTitle();
		if ( !$title || !$title->isMainPage() ) {
			return;
		}

		$blocks = HomepageConfigStore::newFromGlobalState()->getEnabledBlocksInOrder();
		if ( $blocks === [] ) {
			return;
		}

		$out->addModuleStyles( 'ext.religiowikiCustomizer.homepage' );
		$text = HomepageRenderer::render( $blocks );
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
