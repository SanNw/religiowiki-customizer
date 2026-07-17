<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Theme;

use MediaWiki\Extension\ReligiowikiCustomizer\Services\ThemeSettingsStore;

/**
 * Converte a configuração de tema salva num bloco `:root { --rw-*: ...; }`.
 *
 * Também emite os aliases legados `--rw-bg`/`--rw-bg-elevated`/`--rw-link`
 * que mediawiki-config/common.css (no repositório religio-wiki) já lê —
 * assim common.css continua funcionando sem nenhuma alteração; a extensão
 * só passa a controlar de onde essas variáveis vêm, não o CSS que as usa.
 */
class ThemeCssGenerator {

	/**
	 * @param array<string,string> $theme
	 */
	public static function generate( array $theme ): string {
		$theme += ThemeSettingsStore::DEFAULTS;
		$v = static function ( string $key ) use ( $theme ): string {
			return self::sanitizeCssValue( (string)$theme[ $key ] );
		};

		$css = ":root {\n";
		$css .= "\t--rw-primary: {$v( 'primary' )};\n";
		$css .= "\t--rw-secondary: {$v( 'secondary' )};\n";
		$css .= "\t--rw-background: {$v( 'background' )};\n";
		$css .= "\t--rw-surface: {$v( 'surface' )};\n";
		$css .= "\t--rw-border: {$v( 'border' )};\n";
		$css .= "\t--rw-text: {$v( 'text' )};\n";
		$css .= "\t--rw-text-muted: {$v( 'textMuted' )};\n";
		$css .= "\t--rw-font-family: {$v( 'fontFamily' )};\n";
		$css .= "\t--rw-font-size-base: {$v( 'fontSizeBase' )};\n";
		$css .= "\t--rw-max-width: {$v( 'maxWidth' )};\n";
		$css .= "\n\t/* aliases: mantém mediawiki-config/common.css funcionando sem alteração */\n";
		$css .= "\t--rw-bg: var(--rw-background);\n";
		$css .= "\t--rw-bg-elevated: var(--rw-surface);\n";
		$css .= "\t--rw-link: var(--rw-primary);\n";
		$css .= "}\n";

		return $css;
	}

	/**
	 * Defesa em profundidade: mesmo os campos sendo validados (cores em
	 * formato hex, ver SpecialReligiowikiCustomizer) e restritos a
	 * `editinterface`, remove caracteres que poderiam escapar do valor da
	 * propriedade CSS e injetar regras arbitrárias.
	 */
	private static function sanitizeCssValue( string $value ): string {
		return str_replace( [ '{', '}', '<', '>', "\n", "\r" ], '', $value );
	}
}
