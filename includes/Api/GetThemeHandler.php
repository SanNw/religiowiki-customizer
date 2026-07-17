<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Api;

use MediaWiki\Extension\ReligiowikiCustomizer\Services\ThemeSettingsStore;
use MediaWiki\Rest\SimpleHandler;

/**
 * GET /religiowikicustomizer/v0/theme
 *
 * Leitura pública, sem autenticação (decisão de arquitetura 2 da Fase 8):
 * os valores retornados aqui já são publicamente visíveis pra qualquer
 * visitante via o CSS entregue em toda página
 * (ext.religiowikiCustomizer.theme) — não há nada sensível pra proteger
 * na leitura, só na escrita (ver SaveThemeHandler).
 */
class GetThemeHandler extends SimpleHandler {

	public function run() {
		return ThemeSettingsStore::newFromGlobalState()->getTheme();
	}
}
