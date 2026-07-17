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
use MediaWiki\Extension\ReligiowikiCustomizer\SEO\BreadcrumbBuilder;
use MediaWiki\Extension\ReligiowikiCustomizer\SEO\SeoInjector;
use MediaWiki\Extension\ReligiowikiCustomizer\SEO\SeoParserFunction;
use MediaWiki\Extension\ReligiowikiCustomizer\Services\HomepageConfigStore;
use MediaWiki\Extension\ReligiowikiCustomizer\Widgets\AuthorWidget;
use MediaWiki\Extension\ReligiowikiCustomizer\Widgets\BookWidget;
use MediaWiki\Extension\ReligiowikiCustomizer\Widgets\InfoboxWidget;
use MediaWiki\Extension\ReligiowikiCustomizer\Widgets\ReligionWidget;
use MediaWiki\Extension\ReligiowikiCustomizer\Widgets\SchoolWidget;
use MediaWiki\Extension\ReligiowikiCustomizer\Widgets\TimelineWidget;
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
	 * componentes em toda página, e injeta as tags de SEO (Fase 6) no
	 * `<head>`. Ordem de carregamento do CSS de tema/personalizado é
	 * garantida por getDependencies() nas classes de módulo, não pela
	 * ordem abaixo.
	 *
	 * @inheritDoc
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$out->addModuleStyles( [
			'ext.religiowikiCustomizer.theme',
			'ext.religiowikiCustomizer.customCss',
			'ext.religiowikiCustomizer.components',
			'ext.religiowikiCustomizer.widgets',
			'ext.religiowikiCustomizer.seo',
		] );
		$out->addModules( [
			'ext.religiowikiCustomizer.customJs',
			'ext.religiowikiCustomizer.componentsJs',
		] );

		SeoInjector::inject( $out );
	}

	/**
	 * Homepage Builder (Fase 3): só troca o conteúdo da Main Page se houver
	 * pelo menos um bloco habilitado configurado — ver decisão de
	 * arquitetura 1 da Fase 3 (página wiki como fallback).
	 *
	 * Breadcrumbs (Fase 6): prependido em artigos normais (namespace
	 * principal, exceto a própria Main Page, que não precisa de trilha).
	 *
	 * @inheritDoc
	 */
	public function onOutputPageBeforeHTML( $out, &$text ): void {
		$title = $out->getTitle();
		if ( !$title ) {
			return;
		}

		if ( $title->isMainPage() ) {
			$blocks = HomepageConfigStore::newFromGlobalState()->getEnabledBlocksInOrder();
			if ( $blocks !== [] ) {
				$out->addModuleStyles( 'ext.religiowikiCustomizer.homepage' );
				$text = HomepageRenderer::render( $blocks );
			}
			return;
		}

		if ( $title->getNamespace() === NS_MAIN ) {
			// getCategories() retorna a lista de nomes de categoria do
			// artigo (não um mapa) — array_map só normaliza pra string,
			// sem assumir se o retorno é indexado ou associativo.
			$categories = array_values( array_map( 'strval', $out->getCategories() ) );
			$text = BreadcrumbBuilder::build( $title, $categories ) . $text;
		}
	}

	/**
	 * Registra os parser tags da biblioteca de componentes (Fase 4), dos
	 * widgets semânticos (Fase 5) e o parser function de SEO (Fase 6).
	 * Qualquer editor do grupo `editor` (não só admin) pode usar isso em
	 * wikitext — ver escape rigoroso em cada classe. `<rwquote>` (Fase 4)
	 * já cobre "Citação" da Fase 5 — não duplicado como uma segunda tag.
	 * "Mapa" não tem tag registrada nesta fase (ver docs/STATUS.md).
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

		$parser->setHook( 'rwinfobox', [ InfoboxWidget::class, 'render' ] );
		$parser->setHook( 'rwbook', [ BookWidget::class, 'render' ] );
		$parser->setHook( 'rwauthor', [ AuthorWidget::class, 'render' ] );
		$parser->setHook( 'rwreligion', [ ReligionWidget::class, 'render' ] );
		$parser->setHook( 'rwschool', [ SchoolWidget::class, 'render' ] );
		$parser->setHook( 'rwtimeline', [ TimelineWidget::class, 'render' ] );

		$parser->setFunctionHook( 'rwseo', [ SeoParserFunction::class, 'run' ] );
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
