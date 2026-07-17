<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\SpecialPages;

use HTMLForm;
use ManualLogEntry;
use MediaWiki\Extension\ReligiowikiCustomizer\Services\CustomCodeStore;
use MediaWiki\Extension\ReligiowikiCustomizer\Services\ThemeSettingsStore;
use SpecialPage;
use Status;

/**
 * Special:ReligiowikiCustomizer — três abas:
 *   ?tab=aparencia (Fase 1) — cores, tipografia, largura máxima.
 *   ?tab=css       (Fase 2) — CSS personalizado.
 *   ?tab=js        (Fase 2) — JS personalizado.
 *
 * Cada aba é um HTMLForm independente (token CSRF próprio, automático).
 * Não usa mais FormSpecialPage (adequado só pra um formulário) porque agora
 * há três, cada um com seu próprio submit — ver README da extensão.
 *
 * Permissão: restrita ao direito nativo `editinterface` (grupo sysop por
 * padrão) via SpecialPage::__construct(); SpecialPage::execute() chama
 * checkPermissions() antes de qualquer coisa renderizar.
 */
class SpecialReligiowikiCustomizer extends SpecialPage {

	private const TABS = [ 'aparencia', 'css', 'js' ];

	private ThemeSettingsStore $themeStore;
	private CustomCodeStore $codeStore;

	public function __construct() {
		parent::__construct( 'ReligiowikiCustomizer', 'editinterface' );
		$this->themeStore = ThemeSettingsStore::newFromGlobalState();
		$this->codeStore = CustomCodeStore::newFromGlobalState();
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
		return $this->msg( 'religiowikicustomizer-title' )->text();
	}

	/** @inheritDoc */
	public function execute( $subPage ) {
		$this->setHeaders();
		$this->checkPermissions();

		$out = $this->getOutput();
		$out->addModuleStyles( 'ext.religiowikiCustomizer.special' );
		$out->addModules( 'ext.religiowikiCustomizer.special' );

		$tab = $this->getRequest()->getRawVal( 'tab', 'aparencia' );
		if ( !in_array( $tab, self::TABS, true ) ) {
			$tab = 'aparencia';
		}

		$out->addHTML( $this->buildTabNav( $tab ) );

		switch ( $tab ) {
			case 'css':
				$this->showCssForm();
				break;
			case 'js':
				$this->showJsForm();
				break;
			default:
				$this->showThemeForm();
		}
	}

	private function buildTabNav( string $activeTab ): string {
		$pageTitle = $this->getPageTitle();
		$links = [];
		$labels = [
			'aparencia' => 'religiowikicustomizer-tab-aparencia',
			'css' => 'religiowikicustomizer-tab-css',
			'js' => 'religiowikicustomizer-tab-js',
		];
		foreach ( $labels as $tab => $msgKey ) {
			$class = $tab === $activeTab ? 'religiowikicustomizer-tab religiowikicustomizer-tab-active'
				: 'religiowikicustomizer-tab';
			$links[] = $this->getLinkRenderer()->makeLink(
				$pageTitle,
				$this->msg( $msgKey )->text(),
				[ 'class' => $class ],
				[ 'tab' => $tab ]
			);
		}
		return '<div class="religiowikicustomizer-tabs">' . implode( '', $links ) . '</div>';
	}

	// ---------- Aba Aparência (Fase 1) ----------

	private function showThemeForm(): void {
		$current = $this->themeStore->getTheme();
		$colorFields = [
			'primary' => 'religiowikicustomizer-field-primary',
			'secondary' => 'religiowikicustomizer-field-secondary',
			'background' => 'religiowikicustomizer-field-background',
			'surface' => 'religiowikicustomizer-field-surface',
			'border' => 'religiowikicustomizer-field-border',
			'text' => 'religiowikicustomizer-field-text',
			'textMuted' => 'religiowikicustomizer-field-textmuted',
		];

		$fields = [];
		foreach ( $colorFields as $key => $msgKey ) {
			$fields[ $key ] = [
				'type' => 'text',
				'label-message' => $msgKey,
				'default' => $current[ $key ],
				'validation-callback' => [ self::class, 'validateColor' ],
				'help-message' => 'religiowikicustomizer-help-colorformat',
				'cssclass' => 'religiowikicustomizer-color-input',
			];
		}
		$fields['fontFamily'] = [
			'type' => 'text',
			'label-message' => 'religiowikicustomizer-field-fontfamily',
			'default' => $current['fontFamily'],
		];
		$fields['fontSizeBase'] = [
			'type' => 'text',
			'label-message' => 'religiowikicustomizer-field-fontsizebase',
			'default' => $current['fontSizeBase'],
		];
		$fields['maxWidth'] = [
			'type' => 'text',
			'label-message' => 'religiowikicustomizer-field-maxwidth',
			'default' => $current['maxWidth'],
		];

		$form = HTMLForm::factory( 'ooui', $fields, $this->getContext() );
		$form->setId( 'religiowikicustomizer-form-theme' );
		$form->addHiddenField( 'tab', 'aparencia' );
		$form->setSubmitTextMsg( 'religiowikicustomizer-save' );
		$form->addButton( [
			'name' => 'wpReligiowikiCustomizerReset',
			'value' => '1',
			'label-message' => 'religiowikicustomizer-reset',
			'flags' => [ 'destructive' ],
		] );
		$form->setSubmitCallback( [ $this, 'onSubmitTheme' ] );

		$result = $form->show();
		if ( $result === true ) {
			$this->getOutput()->addWikiMsg( 'religiowikicustomizer-saved' );
		}
	}

	/**
	 * @param array $data
	 * @return Status
	 */
	public function onSubmitTheme( array $data ) {
		$actorId = $this->getUser()->getActorId();

		if ( $this->getRequest()->getCheck( 'wpReligiowikiCustomizerReset' ) ) {
			$this->themeStore->resetTheme();
			$this->logChange( 'resettheme' );
			return Status::newGood();
		}

		$this->themeStore->saveTheme( $data, $actorId );
		$this->logChange( 'savetheme' );
		return Status::newGood();
	}

	/**
	 * @param mixed $value
	 * @return bool|string
	 */
	public static function validateColor( $value ) {
		if ( is_string( $value ) && preg_match( '/^#[0-9a-fA-F]{3,8}$/', $value ) ) {
			return true;
		}
		return wfMessage( 'religiowikicustomizer-error-invalidcolor' )->text();
	}

	// ---------- Aba CSS personalizado (Fase 2) ----------

	private function showCssForm(): void {
		$fields = [
			'customCss' => [
				'type' => 'textarea',
				'label-message' => 'religiowikicustomizer-field-customcss',
				'default' => $this->codeStore->getCustomCss(),
				'rows' => 20,
				'cssclass' => 'religiowikicustomizer-code-editor',
				'id' => 'religiowikicustomizer-customcss-textarea',
			],
		];

		$form = HTMLForm::factory( 'ooui', $fields, $this->getContext() );
		$form->setId( 'religiowikicustomizer-form-css' );
		$form->addHiddenField( 'tab', 'css' );
		$form->setSubmitTextMsg( 'religiowikicustomizer-save' );
		$form->addButton( [
			'name' => 'religiowikicustomizer-preview-css',
			'value' => '1',
			'label-message' => 'religiowikicustomizer-preview',
			'id' => 'religiowikicustomizer-preview-css-btn',
		] );
		$form->addPreText( $this->msg( 'religiowikicustomizer-css-help' )->parseAsBlock() );
		$form->setSubmitCallback( [ $this, 'onSubmitCss' ] );

		$result = $form->show();
		if ( $result === true ) {
			$this->getOutput()->addWikiMsg( 'religiowikicustomizer-saved' );
		}
	}

	/**
	 * @param array $data
	 * @return Status
	 */
	public function onSubmitCss( array $data ) {
		$this->codeStore->saveCustomCss( (string)$data['customCss'], $this->getUser()->getActorId() );
		$this->logChange( 'savecss' );
		return Status::newGood();
	}

	// ---------- Aba JS personalizado (Fase 2) ----------

	private function showJsForm(): void {
		$fields = [
			'customJs' => [
				'type' => 'textarea',
				'label-message' => 'religiowikicustomizer-field-customjs',
				'default' => $this->codeStore->getCustomJs(),
				'rows' => 20,
				'cssclass' => 'religiowikicustomizer-code-editor',
				'id' => 'religiowikicustomizer-customjs-textarea',
			],
		];

		$form = HTMLForm::factory( 'ooui', $fields, $this->getContext() );
		$form->setId( 'religiowikicustomizer-form-js' );
		$form->addHiddenField( 'tab', 'js' );
		$form->setSubmitTextMsg( 'religiowikicustomizer-save' );
		$form->addButton( [
			'name' => 'religiowikicustomizer-preview-js',
			'value' => '1',
			'label-message' => 'religiowikicustomizer-preview',
			'id' => 'religiowikicustomizer-preview-js-btn',
		] );
		// Aviso obrigatório — declarado explicitamente pra quem for usar a
		// página, não só nos comentários do código (decisão de arquitetura
		// 3 da Fase 2).
		$form->addPreText(
			'<div class="religiowikicustomizer-js-warning">' .
			$this->msg( 'religiowikicustomizer-js-warning' )->parse() .
			'</div>'
		);
		$form->setSubmitCallback( [ $this, 'onSubmitJs' ] );

		$result = $form->show();
		if ( $result === true ) {
			$this->getOutput()->addWikiMsg( 'religiowikicustomizer-saved' );
		}
	}

	/**
	 * @param array $data
	 * @return Status
	 */
	public function onSubmitJs( array $data ) {
		$this->codeStore->saveCustomJs( (string)$data['customJs'], $this->getUser()->getActorId() );
		$this->logChange( 'savejs' );
		return Status::newGood();
	}

	/**
	 * Registra a alteração no log de administração nativo do MediaWiki
	 * (Special:Log/religiowikicustomizer) — autor e timestamp ficam
	 * gravados automaticamente pela própria tabela `logging` do core.
	 */
	private function logChange( string $action ): void {
		$logEntry = new ManualLogEntry( 'religiowikicustomizer', $action );
		$logEntry->setPerformer( $this->getUser() );
		$logEntry->setTarget( $this->getPageTitle() );
		$logId = $logEntry->insert();
		$logEntry->publish( $logId );
	}
}
