<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Hooks;

use MediaWiki\Extension\ReligiowikiCustomizer\Components\AccordionComponent;
use MediaWiki\Extension\ReligiowikiCustomizer\Components\AlertComponent;
use MediaWiki\Extension\ReligiowikiCustomizer\Components\BadgeComponent;
use MediaWiki\Extension\ReligiowikiCustomizer\Components\CalloutComponent;
use MediaWiki\Extension\ReligiowikiCustomizer\Components\CardComponent;
use MediaWiki\Extension\ReligiowikiCustomizer\Components\GridComponent;
use MediaWiki\Extension\ReligiowikiCustomizer\Components\QuoteComponent;
use MediaWiki\Extension\ReligiowikiCustomizer\Components\TabsComponent;
use MediaWiki\Extension\ReligiowikiCustomizer\Homepage\HomepageRenderer;
use MediaWiki\Extension\ReligiowikiCustomizer\Services\HomepageConfigStore;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\OutputPageBeforeHTMLHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class HookHandler implements
	BeforePageDisplayHook,
	LoadExtensionSchemaUpdatesHook,
	OutputPageBeforeHTMLHook,
	ParserFirstCallInitHook
{

	/**
	 * Adiciona os módulos de tema, CSS/JS personalizado e da biblioteca de
	 * componentes em toda página — os componentes (Fase 4) podem aparecer
	 * em qualquer artigo, não só na Main Page ou no painel admin. Ordem de
	 * carregamento do CSS de tema/personalizado é garantida por
	 * getDependencies() nas classes de módulo, não pela ordem abaixo.
	 *
	 * @inheritDoc
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$out->addModuleStyles( [
			'ext.religiowikiCustomizer.theme',
			'ext.religiowikiCustomizer.customCss',
			'ext.religiowikiCustomizer.components',
		] );
		$out->addModules( [
			'ext.religiowikiCustomizer.customJs',
			'ext.religiowikiCustomizer.componentsJs',
		] );
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
	 * Registra os parser tags da biblioteca de componentes (Fase 4).
	 * Qualquer editor do grupo `editor` (não só admin) pode usar isso em
	 * wikitext — ver escape rigoroso em cada classe de componente.
	 *
	 * @inheritDoc
	 */
	public function onParserFirstCallInit( $parser ): void {
		$parser->setHook( 'rwcard', [ CardComponent::class, 'render' ] );
		$parser->setHook( 'rwalert', [ AlertComponent::class, 'render' ] );
		$parser->setHook( 'rwaccordion', [ AccordionComponent::class, 'render' ] );
		$parser->setHook( 'rwtabs', [ TabsComponent::class, 'render' ] );
		$parser->setHook( 'rwquote', [ QuoteComponent::class, 'render' ] );
		$parser->setHook( 'rwbadge', [ BadgeComponent::class, 'render' ] );
		$parser->setHook( 'rwcallout', [ CalloutComponent::class, 'render' ] );
		$parser->setHook( 'rwgrid', [ GridComponent::class, 'render' ] );
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
