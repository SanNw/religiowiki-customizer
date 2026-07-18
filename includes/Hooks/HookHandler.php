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
use MediaWiki\Extension\ReligiowikiCustomizer\Performance\PerformanceInjector;
use MediaWiki\Extension\ReligiowikiCustomizer\SEO\BreadcrumbBuilder;
use MediaWiki\Extension\ReligiowikiCustomizer\SEO\SeoInjector;
use MediaWiki\Extension\ReligiowikiCustomizer\SEO\SeoParserFunction;
use MediaWiki\Extension\ReligiowikiCustomizer\Services\HomepageConfigStore;
use MediaWiki\Extension\ReligiowikiCustomizer\Services\PageViewStore;
use MediaWiki\Extension\ReligiowikiCustomizer\Widgets\AuthorWidget;
use MediaWiki\Extension\ReligiowikiCustomizer\Widgets\BookWidget;
use MediaWiki\Extension\ReligiowikiCustomizer\Widgets\InfoboxWidget;
use MediaWiki\Extension\ReligiowikiCustomizer\Widgets\ReligionWidget;
use MediaWiki\Extension\ReligiowikiCustomizer\Widgets\SchoolWidget;
use MediaWiki\Extension\ReligiowikiCustomizer\Widgets\TimelineWidget;
use MediaWiki\Deferred\DeferredUpdates;
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
	 * componentes em toda página, injeta as tags de SEO (Fase 6) e a
	 * config de performance (Fase 7) no `<head>`, e marca o body com a
	 * skin ativa (`rwc-skin-<nome>`) — detecção de skin da Fase 7: robustez
	 * básica de CSS condicional, não paridade total entre skins (o
	 * religio-wiki está travado no Vector clássico via $wgSkipSkins, mas a
	 * extensão em si deve funcionar em outras instalações). Ordem de
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
			'ext.religiowikiCustomizer.widgets',
			'ext.religiowikiCustomizer.seo',
		] );
		$out->addModules( [
			'ext.religiowikiCustomizer.customJs',
			'ext.religiowikiCustomizer.componentsJs',
			'ext.religiowikiCustomizer.chrome',
		] );

		$out->addBodyClasses( 'rwc-skin-' . $skin->getSkinName() );

		SeoInjector::inject( $out );
		PerformanceInjector::inject( $out );

		$this->maybeRecordView( $out );
	}

	/**
	 * Contabiliza uma visualização (Fase 9) quando a página sendo exibida é um
	 * artigo publicado (namespace principal) ou um rascunho (namespace
	 * "Rascunho", se definido no LocalSettings) que EXISTE, e a requisição é
	 * uma leitura normal (action=view, método GET). A gravação vai para um
	 * DeferredUpdate para não atrasar a resposta nem escrever no meio da
	 * renderização.
	 *
	 * @param \OutputPage $out
	 */
	private function maybeRecordView( $out ): void {
		$title = $out->getTitle();
		if ( !$title || !$title->exists() ) {
			return;
		}

		$ns = $title->getNamespace();
		$isDraft = defined( 'NS_RASCUNHO' ) && $ns === NS_RASCUNHO;
		if ( $ns !== NS_MAIN && !$isDraft ) {
			return;
		}

		$request = $out->getRequest();
		if ( $request->wasPosted() ) {
			return;
		}
		// 'view' é o default; só contamos leituras, não history/edit/raw etc.
		if ( $request->getVal( 'action', 'view' ) !== 'view' ) {
			return;
		}

		$pageId = $title->getArticleID();
		if ( $pageId <= 0 ) {
			return;
		}

		DeferredUpdates::addCallableUpdate( static function () use ( $pageId ): void {
			PageViewStore::newFromGlobalState()->recordView( $pageId );
		} );
	}

	/**
	 * Recursos para editores (Fase 7): botões de inserção rápida dos
	 * componentes/widgets acima da área de edição de wikitexto. Manipula o
	 * textarea diretamente via JS em vez de se acoplar à API interna do
	 * WikiEditor — mais simples e estável entre versões.
	 */
	public function onEditPage__showEditForm_initial( $editPage, $out ): void {
		$out->addModules( 'ext.religiowikiCustomizer.editorToolbar' );
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
	 * Registra as tabelas da extensão em `update.php`:
	 *  - religiowiki_customizer_settings (Fase 1+): storage chave-valor.
	 *  - rwc_page_views (Fase 9): contagem diária de visualizações por página,
	 *    consumida pelo dashboard Special:Artigos.
	 *
	 * @inheritDoc
	 */
	public function onLoadExtensionSchemaUpdates( $updater ): void {
		$dir = dirname( __DIR__, 2 ) . '/sql';
		$updater->addExtensionTable(
			'religiowiki_customizer_settings',
			$dir . '/mysql/tables-generated.sql'
		);
		// rwc_page_views usa um patch SÓ com ela — não tables-generated.sql (que
		// tem as duas tabelas). Numa instalação onde a settings já existe, rodar
		// o arquivo com as duas tentaria recriá-la e abortava com "Error 1050:
		// Table already exists". Ver comentário no próprio patch.
		$updater->addExtensionTable(
			'rwc_page_views',
			$dir . '/mysql/patch-rwc_page_views.sql'
		);
	}
}
