<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Theme;

use MediaWiki\Extension\ReligiowikiCustomizer\Services\CustomCodeStore;
use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\Module;

/**
 * Serve o CSS personalizado (Fase 2) salvo via Special:ReligiowikiCustomizer.
 *
 * Depende de ext.religiowikiCustomizer.theme — isso garante que este CSS
 * carrega DEPOIS do CSS de tema base, permitindo sobrescrita em cascata
 * (decisão de arquitetura 2 da Fase 2), independente da ordem em que os
 * hooks chamam addModuleStyles.
 */
class CustomCssResourceLoaderModule extends Module {

	/** @inheritDoc */
	public function getDependencies( ?Context $context = null ): array {
		return [ 'ext.religiowikiCustomizer.theme' ];
	}

	/** @inheritDoc */
	public function getStyles( Context $context ): array {
		$css = CustomCodeStore::newFromGlobalState()->getCustomCss();
		return [ '' => $css ];
	}

	/** @inheritDoc */
	public function getDefinitionSummary( Context $context ): array {
		$summary = parent::getDefinitionSummary( $context );
		$summary[] = [ 'customCss' => CustomCodeStore::newFromGlobalState()->getCustomCss() ];
		return $summary;
	}

	/** @inheritDoc */
	public function enableModuleContentVersion() {
		return true;
	}
}
