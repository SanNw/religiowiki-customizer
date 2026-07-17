<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Api;

use MediaWiki\Extension\ReligiowikiCustomizer\Services\ThemeSettingsStore;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * PUT /religiowikicustomizer/v0/theme
 * Body: {"token": "...", "theme": {"primary": "#...", ...}}
 *
 * Gravação: sempre autenticada, restrita a `editinterface`, com token CSRF
 * — ver RequiresEditInterfaceTrait.
 */
class SaveThemeHandler extends SimpleHandler {
	use RequiresEditInterfaceTrait;

	public function run() {
		$body = $this->getValidatedBody();
		$user = $this->requireEditInterfaceWithToken( (string)( $body['token'] ?? '' ) );

		$values = is_array( $body['theme'] ?? null ) ? $body['theme'] : [];
		ThemeSettingsStore::newFromGlobalState()->saveTheme( $values, $user->getId() ?: null );

		return [ 'status' => 'ok' ];
	}

	/** @inheritDoc */
	public function getBodyValidator( $contentType ) {
		return new JsonBodyValidator( [
			'token' => [ ParamValidator::PARAM_TYPE => 'string', ParamValidator::PARAM_REQUIRED => true ],
			'theme' => [ ParamValidator::PARAM_TYPE => 'array', ParamValidator::PARAM_REQUIRED => true ],
		] );
	}
}
