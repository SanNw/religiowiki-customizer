<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Theme;

use MediaWiki\Extension\ReligiowikiCustomizer\Services\CustomCodeStore;
use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\Module;

/**
 * Serve o JS personalizado (Fase 2) salvo via Special:ReligiowikiCustomizer.
 *
 * ATENÇÃO — declarado explicitamente aqui e na documentação (não implícito,
 * conforme decisão de arquitetura 3 da Fase 2): o conteúdo retornado por
 * getScript() é executado tal como foi digitado, para TODOS os visitantes
 * do site que carregarem qualquer página. Não há sanitização de conteúdo —
 * a única mitigação é controle de acesso: só quem tem `editinterface`
 * consegue chegar em Special:ReligiowikiCustomizer e salvar algo aqui. O
 * risco é equivalente ao que já existe hoje em MediaWiki:Common.js; esta
 * classe só centraliza a mesma capacidade dentro da extensão.
 */
class CustomJsResourceLoaderModule extends Module {

	/** @inheritDoc */
	public function getScript( Context $context ): string {
		return CustomCodeStore::newFromGlobalState()->getCustomJs();
	}

	/** @inheritDoc */
	public function getDefinitionSummary( Context $context ): array {
		$summary = parent::getDefinitionSummary( $context );
		$summary[] = [ 'customJs' => CustomCodeStore::newFromGlobalState()->getCustomJs() ];
		return $summary;
	}

	/** @inheritDoc */
	public function enableModuleContentVersion() {
		return true;
	}
}
