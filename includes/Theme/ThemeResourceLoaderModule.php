<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Theme;

use MediaWiki\Extension\ReligiowikiCustomizer\Services\ThemeSettingsStore;
use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\Module;

/**
 * Serve o tema atual (salvo em religiowiki_customizer_settings, ou DEFAULTS
 * se nada foi salvo ainda) como um módulo ResourceLoader de estilos.
 *
 * Versionamento: getDefinitionSummary() inclui os valores do tema em si, e
 * o ResourceLoader usa esse resumo pra calcular o hash de versão do módulo
 * — qualquer alteração salva via Special:ReligiowikiCustomizer muda o hash
 * automaticamente, então o navegador busca o CSS novo sozinho, sem precisar
 * de purga de cache manual (decisão de arquitetura 4 da Fase 2 do prompt
 * mestre, resolvida aqui na base já pensando nisso).
 */
class ThemeResourceLoaderModule extends Module {

	/** @inheritDoc */
	public function getStyles( Context $context ): array {
		$theme = ThemeSettingsStore::newFromGlobalState()->getTheme();
		return [ '' => ThemeCssGenerator::generate( $theme ) ];
	}

	/** @inheritDoc */
	public function getDefinitionSummary( Context $context ): array {
		$summary = parent::getDefinitionSummary( $context );
		$summary[] = [
			'theme' => ThemeSettingsStore::newFromGlobalState()->getTheme(),
		];
		return $summary;
	}

	/** @inheritDoc */
	public function enableModuleContentVersion() {
		return true;
	}

	/**
	 * Module::getType() tem padrão LOAD_GENERAL -- sem sobrescrever isso,
	 * este módulo (adicionado via addModuleStyles em HookHandler) nunca
	 * entrava de fato no <link rel=stylesheet> combinado da página real
	 * (confirmado ao vivo: presente e correto quando pedido isoladamente
	 * via load.php, mas ausente do HTML de qualquer página normal do
	 * wiki). Na prática, isso significava que a cor configurada no painel
	 * admin nunca chegava no site -- só o valor de fallback de cada
	 * var(--rw-*, fallback) no CSS, que por acaso bate com o tema padrão.
	 *
	 * @inheritDoc
	 */
	public function getType() {
		return self::LOAD_STYLES;
	}
}
