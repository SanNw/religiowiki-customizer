<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Api;

use MediaWiki\Rest\HttpException;
use User;

/**
 * Checagem de permissão compartilhada pelos handlers REST de escrita
 * (SaveThemeHandler, ImportConfigHandler) e de leitura restrita
 * (ExportConfigHandler) — gravação SEMPRE exige `editinterface` e token
 * CSRF, sem exceção (decisão de arquitetura 2 da Fase 8, não negociável).
 *
 * Reaproveitado em vez de duplicar a mesma checagem em cada Handler —
 * requer que a classe que usa esta trait extenda
 * `MediaWiki\Rest\Handler` (pra ter `getAuthority()`/`getSession()`
 * disponíveis).
 */
trait RequiresEditInterfaceTrait {

	/**
	 * @return User O usuário autenticado, se passou na checagem.
	 * @throws HttpException 403 se não tiver o direito.
	 */
	private function requireEditInterface(): User {
		$authority = $this->getAuthority();
		if ( !$authority->isAllowed( 'editinterface' ) ) {
			throw new HttpException(
				'Permissão negada — esta ação requer o direito "editinterface".',
				403
			);
		}
		return $this->getSession()->getUser();
	}

	/**
	 * @param string $token Token CSRF (mesmo obtido via
	 *   action=query&meta=tokens&type=csrf).
	 * @return User
	 * @throws HttpException 403 se a permissão ou o token forem inválidos.
	 */
	private function requireEditInterfaceWithToken( string $token ): User {
		$user = $this->requireEditInterface();
		if ( !$user->matchEditToken( $token ) ) {
			throw new HttpException( 'Token CSRF inválido ou ausente.', 403 );
		}
		return $user;
	}
}
