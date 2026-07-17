<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Api;

use MediaWiki\Extension\ReligiowikiCustomizer\Services\ConfigExporter;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * PUT /religiowikicustomizer/v0/import
 * Body: {"token": "...", "config": {...}}
 *
 * Importação é equivalente, em risco, a gravar CSS/JS personalizado
 * diretamente (decisão de arquitetura 3 da Fase 8) — mesma exigência de
 * permissão e token que SaveThemeHandler, sem exceção. ConfigExporter faz
 * backup da configuração atual antes de aplicar.
 */
class ImportConfigHandler extends SimpleHandler {
	use RequiresEditInterfaceTrait;

	public function run() {
		$body = $this->getValidatedBody();
		$user = $this->requireEditInterfaceWithToken( (string)( $body['token'] ?? '' ) );

		$config = is_array( $body['config'] ?? null ) ? $body['config'] : [];
		ConfigExporter::importAll( $config, $user->getId() ?: null );

		return [ 'status' => 'ok' ];
	}

	/** @inheritDoc */
	public function getBodyValidator( $contentType ) {
		return new JsonBodyValidator( [
			'token' => [ ParamValidator::PARAM_TYPE => 'string', ParamValidator::PARAM_REQUIRED => true ],
			'config' => [ ParamValidator::PARAM_TYPE => 'array', ParamValidator::PARAM_REQUIRED => true ],
		] );
	}
}
