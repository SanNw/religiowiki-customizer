<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Api;

use MediaWiki\Extension\ReligiowikiCustomizer\Services\ConfigExporter;
use MediaWiki\Rest\SimpleHandler;

/**
 * GET /religiowikicustomizer/v0/export
 *
 * Ao contrário de GetThemeHandler, esta leitura EXIGE `editinterface` —
 * o export inclui CSS/JS personalizado por completo, que pode conter
 * informação que o admin não necessariamente quer expor a qualquer
 * visitante anônimo (mesmo já sendo executado no navegador de todo mundo
 * via ext.religiowikiCustomizer.customJs, ter o código-fonte completo
 * disponível num endpoint de export dedicado é uma superfície diferente
 * o bastante pra justificar a restrição).
 */
class ExportConfigHandler extends SimpleHandler {
	use RequiresEditInterfaceTrait;

	public function run() {
		$this->requireEditInterface();
		return ConfigExporter::exportAll();
	}
}
