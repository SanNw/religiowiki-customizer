<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\SpecialPages;

use MediaWiki\Extension\ReligiowikiCustomizer\Services\PageViewStore;
use MediaWiki\Extension\ReligiowikiCustomizer\Stats\ChartRenderer;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use SpecialPage;
use Status;

/**
 * Special:Artigos — painel de administração de artigos (Fase 9). Só admin
 * (direito `editinterface`, igual ao resto do ReligiowikiCustomizer).
 *
 * Três telas:
 *   Special:Artigos                → listagem, abas "Publicados" (namespace
 *                                    principal) e "Rascunhos" (namespace
 *                                    Rascunho), com contador de visitas e
 *                                    botões Editar / Publicar / Excluir por
 *                                    artigo, mais o gráfico de visitas do site.
 *   Special:Artigos/<Página>       → detalhe do artigo: contador total,
 *                                    gráfico de visitas ao longo do tempo e
 *                                    gráfico dos dias mais lidos.
 *
 * Ações (POST, com token CSRF):
 *   - publicar: move Rascunho:X → X (namespace principal), sem redirecionamento.
 *   - excluir:  exige digitar o nome exato do artigo para confirmar (validado
 *               no cliente e no servidor) antes de apagar.
 */
class SpecialArtigos extends SpecialPage {

	private const LIST_LIMIT = 500;
	private const SITE_CHART_DAYS = 30;

	private PageViewStore $viewStore;

	public function __construct() {
		parent::__construct( 'Artigos', 'editinterface' );
		$this->viewStore = PageViewStore::newFromGlobalState();
	}

	/** @inheritDoc */
	public function doesWrites() {
		return true;
	}

	/** @inheritDoc */
	protected function getGroupName() {
		return 'wiki';
	}

	/** @inheritDoc */
	public function getDescription() {
		return $this->msg( 'religiowikicustomizer-artigos-title' )->text();
	}

	/** @inheritDoc */
	public function execute( $subPage ) {
		$this->setHeaders();
		$this->checkPermissions();

		$out = $this->getOutput();
		$out->addModuleStyles( 'ext.religiowikiCustomizer.artigos' );
		$out->addModules( 'ext.religiowikiCustomizer.artigos' );

		$request = $this->getRequest();

		// ----- ações POST (publicar / excluir) -----
		if ( $request->wasPosted() ) {
			$this->handlePost();
			return;
		}

		// ----- confirmação de exclusão (GET) -----
		$deleteTarget = $request->getVal( 'delete' );
		if ( $deleteTarget !== null && $deleteTarget !== '' ) {
			$this->showDeleteConfirm( $deleteTarget );
			return;
		}

		// ----- detalhe de um artigo -----
		$subPage = is_string( $subPage ) ? trim( $subPage ) : '';
		if ( $subPage !== '' ) {
			$this->showDetail( $subPage );
			return;
		}

		// ----- listagem -----
		$this->showList();
	}

	// ================= Listagem =================

	private function showList(): void {
		$out = $this->getOutput();
		$tab = $this->getRequest()->getRawVal( 'tab', 'publicados' );
		if ( !in_array( $tab, [ 'publicados', 'rascunhos' ], true ) ) {
			$tab = 'publicados';
		}

		$done = $this->getRequest()->getVal( 'done' );
		if ( $done === 'publish' ) {
			$out->addHTML( $this->successBox( 'religiowikicustomizer-artigos-published' ) );
		} elseif ( $done === 'delete' ) {
			$out->addHTML( $this->successBox( 'religiowikicustomizer-artigos-deleted' ) );
		}

		// Gráfico de visitas do site inteiro.
		$site = $this->viewStore->getSiteWideByDay( self::SITE_CHART_DAYS );
		$sitePoints = [];
		foreach ( $site as $day => $total ) {
			$sitePoints[] = [ 'label' => $this->formatDay( $day ), 'value' => $total ];
		}
		$out->addHTML(
			Html::rawElement( 'div', [ 'class' => 'rw-artigos-sitechart' ],
				Html::element( 'h2', [], $this->msg( 'religiowikicustomizer-artigos-sitevisits' )->text() )
				. ChartRenderer::lineChart( $sitePoints,
					$this->msg( 'religiowikicustomizer-artigos-nodata' )->text() )
			)
		);

		$out->addHTML( $this->buildTabNav( $tab ) );

		if ( $tab === 'rascunhos' ) {
			$this->renderDraftList();
		} else {
			$this->renderPublishedList();
		}
	}

	private function renderPublishedList(): void {
		$rows = $this->fetchPages( NS_MAIN );
		if ( $rows === [] ) {
			$this->getOutput()->addHTML( $this->emptyBox( 'religiowikicustomizer-artigos-empty-published' ) );
			return;
		}
		$this->getOutput()->addHTML( $this->renderTable( $rows, false ) );
	}

	private function renderDraftList(): void {
		if ( !defined( 'NS_RASCUNHO' ) ) {
			$this->getOutput()->addHTML(
				$this->emptyBox( 'religiowikicustomizer-artigos-noraschunho-ns' )
			);
			return;
		}
		$rows = $this->fetchPages( NS_RASCUNHO );
		if ( $rows === [] ) {
			$this->getOutput()->addHTML( $this->emptyBox( 'religiowikicustomizer-artigos-empty-drafts' ) );
			return;
		}
		$this->getOutput()->addHTML( $this->renderTable( $rows, true ) );
	}

	/**
	 * @param int $ns
	 * @return array<int,Title>
	 */
	private function fetchPages( int $ns ): array {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$res = $dbr->select(
			'page',
			[ 'page_id', 'page_namespace', 'page_title' ],
			[ 'page_namespace' => $ns, 'page_is_redirect' => 0 ],
			__METHOD__,
			[ 'ORDER BY' => 'page_title ASC', 'LIMIT' => self::LIST_LIMIT ]
		);
		$titles = [];
		foreach ( $res as $row ) {
			$title = Title::makeTitle( (int)$row->page_namespace, $row->page_title );
			$title->resetArticleID( (int)$row->page_id );
			$titles[] = $title;
		}
		return $titles;
	}

	/**
	 * @param array<int,Title> $titles
	 * @param bool $isDraft Rascunhos ganham o botão "Publicar".
	 * @return string
	 */
	private function renderTable( array $titles, bool $isDraft ): string {
		$ids = [];
		foreach ( $titles as $t ) {
			$ids[] = $t->getArticleID();
		}
		$views = $this->viewStore->getTotalViewsForPages( $ids );

		$header = Html::rawElement( 'tr', [],
			Html::element( 'th', [], $this->msg( 'religiowikicustomizer-artigos-col-title' )->text() )
			. Html::element( 'th', [ 'class' => 'rw-artigos-col-views' ],
				$this->msg( 'religiowikicustomizer-artigos-col-views' )->text() )
			. Html::element( 'th', [ 'class' => 'rw-artigos-col-actions' ],
				$this->msg( 'religiowikicustomizer-artigos-col-actions' )->text() )
		);

		$body = '';
		foreach ( $titles as $title ) {
			$body .= $this->renderRow( $title, (int)( $views[ $title->getArticleID() ] ?? 0 ), $isDraft );
		}

		return Html::rawElement( 'table', [ 'class' => 'rw-artigos-table' ],
			Html::rawElement( 'thead', [], $header ) . Html::rawElement( 'tbody', [], $body )
		);
	}

	private function renderRow( Title $title, int $views, bool $isDraft ): string {
		$linkRenderer = $this->getLinkRenderer();

		// Nome do artigo → leva para a tela de detalhe (com gráficos).
		$nameCell = $linkRenderer->makeKnownLink(
			$this->getPageTitle( $title->getPrefixedText() ),
			$isDraft ? $title->getText() : $title->getPrefixedText()
		);

		$viewsCell = Html::element( 'span', [ 'class' => 'rw-artigos-views' ],
			$this->getLanguage()->formatNum( $views ) );

		// Ações.
		$actions = '';
		// Ver artigo.
		$actions .= Html::rawElement( 'a', [
			'class' => 'rw-artigos-act rw-artigos-act-view',
			'href' => $title->getLocalURL(),
			'title' => $this->msg( 'religiowikicustomizer-artigos-act-view' )->text(),
		], '👁' );
		// Editar.
		$actions .= Html::rawElement( 'a', [
			'class' => 'rw-artigos-act rw-artigos-act-edit',
			'href' => $title->getLocalURL( [ 'action' => 'edit' ] ),
			'title' => $this->msg( 'religiowikicustomizer-artigos-act-edit' )->text(),
		], '✏' );
		// Publicar (só rascunho).
		if ( $isDraft ) {
			$actions .= $this->publishForm( $title );
		}
		// Excluir → tela de confirmação.
		$actions .= Html::rawElement( 'a', [
			'class' => 'rw-artigos-act rw-artigos-act-delete',
			'href' => $this->getPageTitle()->getLocalURL( [ 'delete' => $title->getPrefixedText() ] ),
			'title' => $this->msg( 'religiowikicustomizer-artigos-act-delete' )->text(),
		], '🗑' );

		$actionsCell = Html::rawElement( 'div', [ 'class' => 'rw-artigos-actions' ], $actions );

		return Html::rawElement( 'tr', [],
			Html::rawElement( 'td', [ 'data-label' => $this->msg( 'religiowikicustomizer-artigos-col-title' )->text() ], $nameCell )
			. Html::rawElement( 'td', [ 'class' => 'rw-artigos-col-views', 'data-label' => $this->msg( 'religiowikicustomizer-artigos-col-views' )->text() ], $viewsCell )
			. Html::rawElement( 'td', [ 'class' => 'rw-artigos-col-actions' ], $actionsCell )
		);
	}

	private function publishForm( Title $title ): string {
		return Html::rawElement( 'form', [
			'method' => 'post',
			'action' => $this->getPageTitle()->getLocalURL(),
			'class' => 'rw-artigos-publishform',
		],
			Html::hidden( 'rwaction', 'publish' )
			. Html::hidden( 'page', $title->getPrefixedText() )
			. Html::hidden( 'wpEditToken', $this->getContext()->getCsrfTokenSet()->getToken() )
			. Html::rawElement( 'button', [
				'type' => 'submit',
				'class' => 'rw-artigos-act rw-artigos-act-publish',
				'title' => $this->msg( 'religiowikicustomizer-artigos-act-publish' )->text(),
			], '⬆' )
		);
	}

	private function buildTabNav( string $active ): string {
		$page = $this->getPageTitle();
		$links = [];
		foreach ( [ 'publicados' => 'religiowikicustomizer-artigos-tab-published',
			'rascunhos' => 'religiowikicustomizer-artigos-tab-drafts' ] as $tab => $msg ) {
			$class = 'rw-artigos-tab' . ( $tab === $active ? ' rw-artigos-tab-active' : '' );
			$links[] = $this->getLinkRenderer()->makeLink(
				$page, $this->msg( $msg )->text(), [ 'class' => $class ], [ 'tab' => $tab ]
			);
		}
		return Html::rawElement( 'div', [ 'class' => 'rw-artigos-tabs' ], implode( '', $links ) );
	}

	// ================= Detalhe =================

	private function showDetail( string $subPage ): void {
		$out = $this->getOutput();
		$title = Title::newFromText( $subPage );
		if ( !$title || !$title->exists() ) {
			$out->addHTML( $this->emptyBox( 'religiowikicustomizer-artigos-notfound' ) );
			$out->addHTML( $this->backLink() );
			return;
		}

		$out->addHTML( $this->backLink() );

		$pageId = $title->getArticleID();
		$total = $this->viewStore->getTotalViews( $pageId );

		// Cabeçalho: nome + contador.
		$out->addHTML( Html::rawElement( 'div', [ 'class' => 'rw-artigos-detail-head' ],
			Html::rawElement( 'h2', [ 'class' => 'rw-artigos-detail-title' ],
				$this->getLinkRenderer()->makeKnownLink( $title, $title->getPrefixedText() ) )
			. Html::rawElement( 'div', [ 'class' => 'rw-artigos-counter' ],
				Html::element( 'span', [ 'class' => 'rw-artigos-counter-num' ],
					$this->getLanguage()->formatNum( $total ) )
				. Html::element( 'span', [ 'class' => 'rw-artigos-counter-label' ],
					$this->msg( 'religiowikicustomizer-artigos-totalviews' )->text() )
			)
		) );

		// Gráfico: visitas ao longo do tempo.
		$daily = $this->viewStore->getDailyViews( $pageId );
		$linePoints = [];
		foreach ( $daily as $day => $v ) {
			$linePoints[] = [ 'label' => $this->formatDay( $day ), 'value' => $v ];
		}
		$out->addHTML( Html::rawElement( 'section', [ 'class' => 'rw-artigos-chartblock' ],
			Html::element( 'h3', [], $this->msg( 'religiowikicustomizer-artigos-overtime' )->text() )
			. ChartRenderer::lineChart( $linePoints,
				$this->msg( 'religiowikicustomizer-artigos-nodata' )->text() )
		) );

		// Gráfico: dias mais lidos.
		$topDays = $this->viewStore->getTopDays( $pageId, 10 );
		$barPoints = [];
		foreach ( $topDays as $d ) {
			$barPoints[] = [ 'label' => $this->formatDay( $d['day'] ), 'value' => $d['views'] ];
		}
		$out->addHTML( Html::rawElement( 'section', [ 'class' => 'rw-artigos-chartblock' ],
			Html::element( 'h3', [], $this->msg( 'religiowikicustomizer-artigos-topdays' )->text() )
			. ChartRenderer::barChart( $barPoints,
				$this->msg( 'religiowikicustomizer-artigos-nodata' )->text() )
		) );
	}

	// ================= Exclusão (confirmação) =================

	private function showDeleteConfirm( string $pageName ): void {
		$out = $this->getOutput();
		$title = Title::newFromText( $pageName );
		if ( !$title || !$title->exists() ) {
			$out->addHTML( $this->emptyBox( 'religiowikicustomizer-artigos-notfound' ) );
			$out->addHTML( $this->backLink() );
			return;
		}

		$out->setPageTitle( $this->msg( 'religiowikicustomizer-artigos-delete-heading',
			$title->getPrefixedText() )->text() );

		$exact = $title->getPrefixedText();
		$warning = Html::rawElement( 'div', [ 'class' => 'rw-artigos-delete-warning' ],
			$this->msg( 'religiowikicustomizer-artigos-delete-warning', $exact )->parseAsBlock()
		);

		$form = Html::rawElement( 'form', [
			'method' => 'post',
			'action' => $this->getPageTitle()->getLocalURL(),
			'class' => 'rw-artigos-delete-form',
			'data-expected' => $exact,
		],
			Html::hidden( 'rwaction', 'delete' )
			. Html::hidden( 'page', $exact )
			. Html::hidden( 'wpEditToken', $this->getContext()->getCsrfTokenSet()->getToken() )
			. Html::element( 'label', [ 'for' => 'rw-artigos-confirmname', 'class' => 'rw-artigos-confirm-label' ],
				$this->msg( 'religiowikicustomizer-artigos-delete-typeit', $exact )->text() )
			. Html::element( 'input', [
				'type' => 'text',
				'id' => 'rw-artigos-confirmname',
				'name' => 'confirmname',
				'class' => 'rw-artigos-confirm-input',
				'autocomplete' => 'off',
				'placeholder' => $exact,
			] )
			. Html::rawElement( 'div', [ 'class' => 'rw-artigos-delete-buttons' ],
				Html::element( 'button', [
					'type' => 'submit',
					'class' => 'rw-artigos-delete-submit',
					'disabled' => 'disabled',
				], $this->msg( 'religiowikicustomizer-artigos-delete-confirm' )->text() )
				. $this->getLinkRenderer()->makeLink(
					$this->getPageTitle(),
					$this->msg( 'religiowikicustomizer-artigos-cancel' )->text(),
					[ 'class' => 'rw-artigos-cancel' ]
				)
			)
		);

		$out->addHTML( $warning . $form );
	}

	// ================= Handlers POST =================

	private function handlePost(): void {
		$request = $this->getRequest();
		$out = $this->getOutput();

		if ( !$this->getContext()->getCsrfTokenSet()->matchToken( $request->getVal( 'wpEditToken' ) ) ) {
			$out->addHTML( $this->errorBox( 'sessionfailure' ) );
			$out->addHTML( $this->backLink() );
			return;
		}

		$action = $request->getVal( 'rwaction' );
		$title = Title::newFromText( (string)$request->getVal( 'page' ) );
		if ( !$title || !$title->exists() ) {
			$out->addHTML( $this->emptyBox( 'religiowikicustomizer-artigos-notfound' ) );
			$out->addHTML( $this->backLink() );
			return;
		}

		if ( $action === 'publish' ) {
			$this->doPublish( $title );
		} elseif ( $action === 'delete' ) {
			$this->doDelete( $title, (string)$request->getVal( 'confirmname' ) );
		} else {
			$out->addHTML( $this->backLink() );
		}
	}

	private function doPublish( Title $title ): void {
		$out = $this->getOutput();

		if ( !defined( 'NS_RASCUNHO' ) || $title->getNamespace() !== NS_RASCUNHO ) {
			$out->addHTML( $this->errorBoxText(
				$this->msg( 'religiowikicustomizer-artigos-publish-notdraft' )->text() ) );
			$out->addHTML( $this->backLink() );
			return;
		}

		$target = Title::makeTitleSafe( NS_MAIN, $title->getText() );
		if ( !$target ) {
			$out->addHTML( $this->errorBoxText(
				$this->msg( 'religiowikicustomizer-artigos-publish-badtarget' )->text() ) );
			$out->addHTML( $this->backLink() );
			return;
		}
		if ( $target->exists() ) {
			$out->addHTML( $this->errorBoxText(
				$this->msg( 'religiowikicustomizer-artigos-publish-exists', $target->getPrefixedText() )->text() ) );
			$out->addHTML( $this->backLink() );
			return;
		}

		$services = MediaWikiServices::getInstance();
		$movePage = $services->getMovePageFactory()->newMovePage( $title, $target );
		$reason = $this->msg( 'religiowikicustomizer-artigos-publish-reason' )->inContentLanguage()->text();
		$status = $movePage->move( $this->getUser(), $reason, false );

		if ( !$status->isOK() ) {
			$out->addHTML( $this->errorBoxText(
				$this->msg( 'religiowikicustomizer-artigos-publish-failed' )->text()
				. ' ' . Status::wrap( $status )->getMessage()->text() ) );
			$out->addHTML( $this->backLink() );
			return;
		}

		$out->redirect( $this->getPageTitle()->getLocalURL( [ 'tab' => 'rascunhos', 'done' => 'publish' ] ) );
	}

	private function doDelete( Title $title, string $confirmName ): void {
		$out = $this->getOutput();

		// Confirmação por digitação (também validada no cliente): o nome
		// digitado precisa bater exatamente com o nome completo do artigo.
		if ( $confirmName !== $title->getPrefixedText() ) {
			$out->addHTML( $this->errorBoxText(
				$this->msg( 'religiowikicustomizer-artigos-delete-mismatch' )->text() ) );
			$this->showDeleteConfirm( $title->getPrefixedText() );
			return;
		}

		$services = MediaWikiServices::getInstance();
		$wikiPage = $services->getWikiPageFactory()->newFromTitle( $title );
		$deletePage = $services->getDeletePageFactory()->newDeletePage( $wikiPage, $this->getAuthority() );
		$reason = $this->msg( 'religiowikicustomizer-artigos-delete-reason' )->inContentLanguage()->text();
		$status = $deletePage->deleteIfAllowed( $reason );

		if ( !$status->isOK() ) {
			$out->addHTML( $this->errorBoxText(
				$this->msg( 'religiowikicustomizer-artigos-delete-failed' )->text()
				. ' ' . Status::wrap( $status )->getMessage()->text() ) );
			$out->addHTML( $this->backLink() );
			return;
		}

		$out->redirect( $this->getPageTitle()->getLocalURL( [ 'done' => 'delete' ] ) );
	}

	// ================= helpers de UI =================

	private function successBox( string $msgKey ): string {
		return Html::rawElement( 'div', [ 'class' => 'rw-artigos-msg rw-artigos-msg-success' ],
			$this->msg( $msgKey )->parseAsBlock() );
	}

	private function emptyBox( string $msgKey ): string {
		return Html::rawElement( 'div', [ 'class' => 'rw-artigos-msg rw-artigos-msg-empty' ],
			$this->msg( $msgKey )->parseAsBlock() );
	}

	private function errorBox( string $msgKey ): string {
		return Html::rawElement( 'div', [ 'class' => 'rw-artigos-msg rw-artigos-msg-error' ],
			$this->msg( $msgKey )->parseAsBlock() );
	}

	private function errorBoxText( string $text ): string {
		return Html::element( 'div', [ 'class' => 'rw-artigos-msg rw-artigos-msg-error' ], $text );
	}

	private function backLink(): string {
		return Html::rawElement( 'p', [ 'class' => 'rw-artigos-back' ],
			$this->getLinkRenderer()->makeLink(
				$this->getPageTitle(),
				$this->msg( 'religiowikicustomizer-artigos-back' )->text()
			)
		);
	}

	/** AAAAMMDD (int) → "DD/MM". */
	private function formatDay( int $day ): string {
		$s = str_pad( (string)$day, 8, '0', STR_PAD_LEFT );
		return substr( $s, 6, 2 ) . '/' . substr( $s, 4, 2 );
	}
}
