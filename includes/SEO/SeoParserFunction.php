<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\SEO;

use Parser;

/**
 * `{{#rwseo:description|Texto customizado de meta description para este artigo}}`
 *
 * Fonte da Meta Description por página (decisão de arquitetura 2 da
 * Fase 6): propriedade de página, setada por este parser function — se o
 * editor não usar, SeoInjector cai no fallback global configurado em
 * Special:ReligiowikiCustomizer (aba SEO). Não há extração automática do
 * primeiro parágrafo nesta versão — exigiria correr o parser duas vezes
 * (uma pra extrair texto simples, outra pro parse normal), custo que não
 * parece valer a pena frente a só declarar a descrição explicitamente.
 */
class SeoParserFunction {

	public static function run( Parser $parser, string $field = '', string $value = '' ): string {
		if ( trim( $field ) === 'description' && trim( $value ) !== '' ) {
			$parser->getOutput()->setPageProperty( SeoInjector::DESCRIPTION_PROPERTY, trim( $value ) );
		}
		return '';
	}
}
