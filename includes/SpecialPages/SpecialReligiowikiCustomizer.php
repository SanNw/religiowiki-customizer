<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\SpecialPages;

use FormSpecialPage;
use HTMLForm;
use MediaWiki\Extension\ReligiowikiCustomizer\Services\ThemeSettingsStore;
use Status;

/**
 * Special:ReligiowikiCustomizer — aba "Aparência" da Fase 1: formulário pra
 * configurar cores, tipografia e largura máxima do tema.
 *
 * Restrita ao direito nativo `editinterface` (grupo sysop por padrão) via
 * o segundo parâmetro do construtor de FormSpecialPage — SpecialPage::execute()
 * chama checkPermissions() sozinho antes de renderizar, então não há como
 * chegar no formulário sem esse direito. CSRF é o token padrão do
 * HTMLForm/FormSpecialPage, também automático.
 */
class SpecialReligiowikiCustomizer extends FormSpecialPage {

	private ThemeSettingsStore $store;

	public function __construct() {
		parent::__construct( 'ReligiowikiCustomizer', 'editinterface' );
		$this->store = ThemeSettingsStore::newFromGlobalState();
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

	/**
	 * Campos como texto validado (não HTMLColorField — não existe como tipo
	 * nativo estável do HTMLForm no core; um widget de color-picker de
	 * verdade fica como refinamento de UI pra depois, sem bloquear a Fase 1
	 * funcionar corretamente).
	 *
	 * @inheritDoc
	 */
	protected function getFormFields() {
		$current = $this->store->getTheme();
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

		return $fields;
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

	/** @inheritDoc */
	protected function alterForm( HTMLForm $form ) {
		$form->setId( 'religiowikicustomizer-form' );
		$form->setSubmitTextMsg( 'religiowikicustomizer-save' );
		$form->addButton( [
			'name' => 'wpReligiowikiCustomizerReset',
			'value' => '1',
			'label-message' => 'religiowikicustomizer-reset',
			'flags' => [ 'destructive' ],
		] );
		$form->addHiddenField( 'title', $this->getPageTitle()->getPrefixedDBkey() );
	}

	/** @inheritDoc */
	public function onSubmit( array $data ) {
		if ( $this->getRequest()->getCheck( 'wpReligiowikiCustomizerReset' ) ) {
			$this->store->resetTheme();
			return Status::newGood( 'reset' );
		}

		$actorId = $this->getUser()->getActorId();
		$this->store->saveTheme( $data, $actorId );
		return Status::newGood( 'saved' );
	}

	/** @inheritDoc */
	public function onSuccess() {
		$this->getOutput()->addWikiMsg( 'religiowikicustomizer-saved' );
	}
}
