<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Components;

use MediaWiki\MediaWikiServices;
use Title;

/**
 * Valida um link informado como PARÂMETRO de tag wiki (ex.: `link="..."` em
 * `<rwcard>`) antes de virar `href`. Diferente de wikitext nativo
 * (`[[...]]`/`[http://...]`), esses valores chegam como string crua de
 * atributo — não passam pelo parser de links do core, então não têm a
 * checagem de protocolo (`$wgUrlProtocols`) que o core já faz sozinho.
 *
 * Qualquer editor (grupo `editor`, não só admin) pode disparar isso via
 * wikitext — por isso a validação aqui é obrigatória, não uma sanitização
 * "básica" (decisão de segurança explícita da Fase 4).
 */
class LinkSanitizer {

	/**
	 * @return string|null URL segura pra usar em href, ou null se o valor
	 *   não for nem uma URL com protocolo permitido nem o título de uma
	 *   página existente — nesse caso, quem chamar deve simplesmente não
	 *   renderizar o link (fallback seguro, não uma exceção).
	 */
	public static function sanitize( string $value ): ?string {
		$value = trim( $value );
		if ( $value === '' ) {
			return null;
		}

		$protocols = MediaWikiServices::getInstance()->getMainConfig()->get( 'UrlProtocols' );
		$pattern = '/^(' . implode( '|', array_map(
			static function ( string $p ): string {
				return preg_quote( $p, '/' );
			},
			(array)$protocols
		) ) . ')/i';

		if ( preg_match( $pattern, $value ) ) {
			// Protocolo permitido pela própria config do wiki — o mesmo
			// allow-list que o wikitext nativo [http://...] usa. Ainda
			// assim passa por htmlspecialchars no ponto de saída (feito
			// pelo Html::element de quem chama), então aspas/ângulos não
			// escapam do atributo.
			return $value;
		}

		// Não tem protocolo reconhecido — trata como título de página
		// interna. Só retorna algo se a página existir de verdade (evita
		// gerar link vermelho a partir de um parâmetro mal digitado).
		$title = Title::newFromText( $value );
		if ( $title && $title->exists() ) {
			return $title->getLocalURL();
		}

		return null;
	}
}
