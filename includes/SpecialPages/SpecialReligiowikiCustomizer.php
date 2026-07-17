<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\SpecialPages;

use HTMLForm;
use ManualLogEntry;
use MediaWiki\Extension\ReligiowikiCustomizer\Services\ConfigExporter;
use MediaWiki\Extension\ReligiowikiCustomizer\Services\CustomCodeStore;
use MediaWiki\Extension\ReligiowikiCustomizer\Services\HomepageConfigStore;
use MediaWiki\Extension\ReligiowikiCustomizer\Services\PerformanceSettingsStore;
use MediaWiki\Extension\ReligiowikiCustomizer\Services\SeoSettingsStore;
use MediaWiki\Extension\ReligiowikiCustomizer\Services\ThemeSettingsStore;
use SpecialPage;
use Status;

/**
 * Special:ReligiowikiCustomizer — sete abas:
 *   ?tab=aparencia    (Fase 1) — cores, tipografia, largura máxima.
 *   ?tab=css          (Fase 2) — CSS personalizado.
 *   ?tab=js           (Fase 2) — JS personalizado.
 *   ?tab=homepage     (Fase 3) — blocos da página principal.
 *   ?tab=seo          (Fase 6) — nome do site, descrição/imagem/Twitter padrão.
 *   ?tab=performance  (Fase 7) — lazy loading, preload de fontes.
 *   ?tab=exportimport (Fase 8) — exportar/importar toda a configuração.
 *
 * Cada aba é um HTMLForm independente (token CSRF próprio, automático).
 * Não usa mais FormSpecialPage (adequado só pra um formulário) porque agora
 * há sete, cada um com seu próprio submit — ver README da extensão.
 *
 * Permissão: restrita ao direito nativo `editinterface` (grupo sysop por
 * padrão) via SpecialPage::__construct(); SpecialPage::execute() chama
 * checkPermissions() antes de qualquer coisa renderizar.
 */
class SpecialReligiowikiCustomizer extends SpecialPage {

	private const TABS = [ 'aparencia', 'css', 'js', 'homepage', 'seo', 'performance', 'exportimport' ];

	private ThemeSettingsStore $themeStore;
	private CustomCodeStore $codeStore;
	private HomepageConfigStore $homepageStore;
	private SeoSettingsStore $seoStore;
	private PerformanceSettingsStore $performanceStore;

	public function __construct() {
		parent::__construct( 'ReligiowikiCustomizer', 'editinterface' );
		$this->themeStore = ThemeSettingsStore::newFromGlobalState();
		$this->codeStore = CustomCodeStore::newFromGlobalState();
		$this->homepageStore = HomepageConfigStore::newFromGlobalState();
		$this->seoStore = SeoSettingsStore::newFromGlobalState();
		$this->performanceStore = PerformanceSettingsStore::newFromGlobalState();
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
			case 'homepage':
				$this->showHomepageForm();
				break;
			case 'seo':
				$this->showSeoForm();
				break;
			case 'performance':
				$this->showPerformanceForm();
				break;
			case 'exportimport':
				$this->showExportImportForm();
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
			'homepage' => 'religiowikicustomizer-tab-homepage',
			'seo' => 'religiowikicustomizer-tab-seo',
			'performance' => 'religiowikicustomizer-tab-performance',
			'exportimport' => 'religiowikicustomizer-tab-exportimport',
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
		if ( $result === true || ( $result instanceof Status && $result->isGood() ) ) {
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
		if ( $result === true || ( $result instanceof Status && $result->isGood() ) ) {
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
		if ( $result === true || ( $result instanceof Status && $result->isGood() ) ) {
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

	// ---------- Aba Homepage Builder (Fase 3) ----------

	/**
	 * Um HTMLForm só com todos os blocos (campos prefixados por tipo, ex.:
	 * 'hero_enabled', 'hero_title'). Reordenar é feito por um campo numérico
	 * "ordem" por bloco — sem drag-and-drop nesta versão: arrastar exigiria
	 * um layout OOUI/JS customizado por cima do HTMLForm, e o ganho de UX
	 * não compensa o risco de quebrar em alguma versão do MediaWiki; number
	 * input é 100% confiável e alcança o mesmo resultado funcional
	 * (reordenar), só com um passo a mais de clique. Fica como possível
	 * refinamento futuro, não como pendência bloqueante.
	 */
	private function showHomepageForm(): void {
		$config = $this->homepageStore->getConfig();
		$fields = [];

		foreach ( $config as $type => $block ) {
			$fields[ "{$type}_enabled" ] = [
				'type' => 'check',
				'label-message' => "religiowikicustomizer-homepage-{$type}-enabled",
				'default' => (bool)$block['enabled'],
			];
			$fields[ "{$type}_order" ] = [
				'type' => 'int',
				'label-message' => 'religiowikicustomizer-homepage-order',
				'default' => (int)$block['order'],
				'min' => 1,
			];

			switch ( $type ) {
				case 'hero':
					$fields['hero_title'] = [
						'type' => 'text',
						'label-message' => 'religiowikicustomizer-homepage-hero-title',
						'default' => $block['title'],
					];
					$fields['hero_subtitle'] = [
						'type' => 'text',
						'label-message' => 'religiowikicustomizer-homepage-hero-subtitle',
						'default' => $block['subtitle'],
					];
					$fields['hero_backgroundImage'] = [
						'type' => 'text',
						'label-message' => 'religiowikicustomizer-homepage-hero-bg',
						'default' => $block['backgroundImage'],
					];
					$fields['hero_ctaText'] = [
						'type' => 'text',
						'label-message' => 'religiowikicustomizer-homepage-hero-ctatext',
						'default' => $block['ctaText'],
					];
					$fields['hero_ctaLink'] = [
						'type' => 'text',
						'label-message' => 'religiowikicustomizer-homepage-hero-ctalink',
						'default' => $block['ctaLink'],
					];
					break;
				case 'cards':
					$fields['cards_itemsJson'] = [
						'type' => 'textarea',
						'rows' => 6,
						'label-message' => 'religiowikicustomizer-homepage-cards-items',
						'help-message' => 'religiowikicustomizer-homepage-cards-help',
						'default' => $block['itemsJson'],
					];
					break;
				case 'featured':
					$fields['featured_pagesJson'] = [
						'type' => 'textarea',
						'rows' => 3,
						'label-message' => 'religiowikicustomizer-homepage-featured-pages',
						'help-message' => 'religiowikicustomizer-homepage-featured-help',
						'default' => $block['pagesJson'],
					];
					break;
				case 'categories':
					$fields['categories_categoriesJson'] = [
						'type' => 'textarea',
						'rows' => 3,
						'label-message' => 'religiowikicustomizer-homepage-categories-items',
						'help-message' => 'religiowikicustomizer-homepage-categories-help',
						'default' => $block['categoriesJson'],
					];
					break;
				case 'noticias':
					$fields['noticias_itemsJson'] = [
						'type' => 'textarea',
						'rows' => 6,
						'label-message' => 'religiowikicustomizer-homepage-noticias-items',
						'help-message' => 'religiowikicustomizer-homepage-noticias-help',
						'default' => $block['itemsJson'],
					];
					break;
				case 'livros':
					$fields['livros_itemsJson'] = [
						'type' => 'textarea',
						'rows' => 6,
						'label-message' => 'religiowikicustomizer-homepage-livros-items',
						'help-message' => 'religiowikicustomizer-homepage-livros-help',
						'default' => $block['itemsJson'],
					];
					break;
				case 'estatisticas':
					$fields['estatisticas_itemsJson'] = [
						'type' => 'textarea',
						'rows' => 4,
						'label-message' => 'religiowikicustomizer-homepage-estatisticas-items',
						'help-message' => 'religiowikicustomizer-homepage-estatisticas-help',
						'default' => $block['itemsJson'],
					];
					break;
			}
		}

		$form = HTMLForm::factory( 'ooui', $fields, $this->getContext() );
		$form->setId( 'religiowikicustomizer-form-homepage' );
		$form->addHiddenField( 'tab', 'homepage' );
		$form->setSubmitTextMsg( 'religiowikicustomizer-save' );
		$form->setSubmitCallback( [ $this, 'onSubmitHomepage' ] );

		$result = $form->show();
		if ( $result === true || ( $result instanceof Status && $result->isGood() ) ) {
			$this->getOutput()->addWikiMsg( 'religiowikicustomizer-saved' );
		}
	}

	/**
	 * @param array $data Campos prefixados por tipo ('hero_title' etc.).
	 * @return Status
	 */
	public function onSubmitHomepage( array $data ) {
		$config = [];
		foreach ( HomepageConfigStore::BLOCK_TYPES as $type ) {
			$config[ $type ] = [
				'enabled' => !empty( $data[ "{$type}_enabled" ] ),
				'order' => (int)( $data[ "{$type}_order" ] ?? 0 ),
			];
			foreach ( $data as $key => $value ) {
				if ( strpos( $key, "{$type}_" ) === 0 ) {
					$field = substr( $key, strlen( "{$type}_" ) );
					if ( $field !== 'enabled' && $field !== 'order' ) {
						$config[ $type ][ $field ] = $value;
					}
				}
			}
		}

		$this->homepageStore->saveConfig( $config, $this->getUser()->getActorId() );
		$this->logChange( 'savehomepage' );
		return Status::newGood();
	}

	// ---------- Aba SEO (Fase 6) ----------

	private function showSeoForm(): void {
		$current = $this->seoStore->getSettings();

		$fields = [
			'siteNameOverride' => [
				'type' => 'text',
				'label-message' => 'religiowikicustomizer-seo-sitename',
				'default' => $current['siteNameOverride'],
			],
			'defaultDescription' => [
				'type' => 'textarea',
				'rows' => 3,
				'label-message' => 'religiowikicustomizer-seo-description',
				'default' => $current['defaultDescription'],
			],
			'defaultOgImage' => [
				'type' => 'text',
				'label-message' => 'religiowikicustomizer-seo-ogimage',
				'default' => $current['defaultOgImage'],
			],
			'twitterHandle' => [
				'type' => 'text',
				'label-message' => 'religiowikicustomizer-seo-twitter',
				'default' => $current['twitterHandle'],
			],
		];

		$form = HTMLForm::factory( 'ooui', $fields, $this->getContext() );
		$form->setId( 'religiowikicustomizer-form-seo' );
		$form->addHiddenField( 'tab', 'seo' );
		$form->setSubmitTextMsg( 'religiowikicustomizer-save' );
		$form->addPreText( $this->msg( 'religiowikicustomizer-seo-help' )->parseAsBlock() );
		$form->setSubmitCallback( [ $this, 'onSubmitSeo' ] );

		$result = $form->show();
		if ( $result === true || ( $result instanceof Status && $result->isGood() ) ) {
			$this->getOutput()->addWikiMsg( 'religiowikicustomizer-saved' );
		}
	}

	/**
	 * @param array $data
	 * @return Status
	 */
	public function onSubmitSeo( array $data ) {
		$this->seoStore->saveSettings( $data, $this->getUser()->getActorId() );
		$this->logChange( 'saveseo' );
		return Status::newGood();
	}

	// ---------- Aba Performance (Fase 7) ----------

	private function showPerformanceForm(): void {
		$current = $this->performanceStore->getSettings();

		$fields = [
			'lazyLoadImages' => [
				'type' => 'check',
				'label-message' => 'religiowikicustomizer-performance-lazyimages',
				'default' => $current['lazyLoadImages'],
			],
			'preloadFontsText' => [
				'type' => 'textarea',
				'rows' => 4,
				'label-message' => 'religiowikicustomizer-performance-preloadfonts',
				'help-message' => 'religiowikicustomizer-performance-preloadfonts-help',
				'default' => $current['preloadFontsText'],
			],
		];

		$form = HTMLForm::factory( 'ooui', $fields, $this->getContext() );
		$form->setId( 'religiowikicustomizer-form-performance' );
		$form->addHiddenField( 'tab', 'performance' );
		$form->setSubmitTextMsg( 'religiowikicustomizer-save' );
		$form->setSubmitCallback( [ $this, 'onSubmitPerformance' ] );

		$result = $form->show();
		if ( $result === true || ( $result instanceof Status && $result->isGood() ) ) {
			$this->getOutput()->addWikiMsg( 'religiowikicustomizer-saved' );
		}
	}

	/**
	 * @param array $data
	 * @return Status
	 */
	public function onSubmitPerformance( array $data ) {
		$this->performanceStore->saveSettings( $data, $this->getUser()->getActorId() );
		$this->logChange( 'saveperformance' );
		return Status::newGood();
	}

	// ---------- Aba Exportar/Importar (Fase 8) ----------

	private function showExportImportForm(): void {
		$exportJson = json_encode( ConfigExporter::exportAll(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

		$fields = [
			'exportJson' => [
				'type' => 'textarea',
				'rows' => 10,
				'label-message' => 'religiowikicustomizer-export-label',
				'default' => $exportJson,
				'readonly' => true,
				'cssclass' => 'religiowikicustomizer-code-editor',
			],
			'importJson' => [
				'type' => 'textarea',
				'rows' => 10,
				'label-message' => 'religiowikicustomizer-import-label',
				'default' => '',
				'cssclass' => 'religiowikicustomizer-code-editor',
			],
			'importConfirm' => [
				'type' => 'check',
				'label-message' => 'religiowikicustomizer-import-confirm',
			],
		];

		$form = HTMLForm::factory( 'ooui', $fields, $this->getContext() );
		$form->setId( 'religiowikicustomizer-form-exportimport' );
		$form->addHiddenField( 'tab', 'exportimport' );
		$form->setSubmitTextMsg( 'religiowikicustomizer-import-submit' );
		$form->addPreText( $this->msg( 'religiowikicustomizer-exportimport-help' )->parseAsBlock() );
		$form->setSubmitCallback( [ $this, 'onSubmitExportImport' ] );

		$result = $form->show();
		if ( $result === true || ( $result instanceof Status && $result->isGood() ) ) {
			$this->getOutput()->addWikiMsg( 'religiowikicustomizer-import-done' );
		}
	}

	/**
	 * @param array $data
	 * @return Status
	 */
	public function onSubmitExportImport( array $data ) {
		if ( empty( $data['importConfirm'] ) ) {
			return Status::newFatal( 'religiowikicustomizer-import-notconfirmed' );
		}

		$config = json_decode( (string)( $data['importJson'] ?? '' ), true );
		if ( !is_array( $config ) ) {
			return Status::newFatal( 'religiowikicustomizer-import-invalidjson' );
		}

		ConfigExporter::importAll( $config, $this->getUser()->getActorId() );
		$this->logChange( 'import' );
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
