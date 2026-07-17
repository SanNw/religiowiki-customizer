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

		// --rw-background/--rw-surface/--rw-primary são valores únicos,
		// configurados pelo admin, sem variante por tema -- diferente de
		// --rw-bg/--rw-bg-elevated/--rw-link (nomes antigos, mesma coisa via
		// alias acima), que já têm dark mode próprio hardcoded em
		// mediawiki-config/common.css. Sem isso, qualquer CSS que use os
		// nomes novos diretamente (ex.: Homepage Builder) ficava preso na
		// cor clara em qualquer tema. Usa a mesma paleta escura já
		// estabelecida em common.css pros nomes antigos -- não inventa cor
		// nova, só estende a mesma pros nomes novos. --rw-text/--rw-text-
		// muted/--rw-border usam o mesmo nome nos dois sistemas, então já
		// funcionam via common.css sem precisar repetir aqui.
		$css .= "\n:root[data-theme=\"dark\"] {\n";
		$css .= "\t--rw-primary: #E0A868;\n";
		$css .= "\t--rw-background: #1B1712;\n";
		$css .= "\t--rw-surface: #24201A;\n";
		$css .= "}\n";

		// Mesma lacuna no tema "personalizado" (leitor escolhe fundo/texto/
		// link via o pop-up de tema em common.js) -- espelha exatamente os
		// mesmos --rw-custom-* e fallbacks que common.css já usa pros nomes
		// antigos, só estendendo pros nomes novos.
		$css .= "\n:root[data-theme=\"custom\"] {\n";
		$css .= "\t--rw-primary: var(--rw-custom-link, #92400E);\n";
		$css .= "\t--rw-background: var(--rw-custom-bg, #FBF3E1);\n";
		$css .= "\t--rw-surface: var(--rw-custom-bg-elevated, #FFFDF7);\n";
		$css .= "}\n";

		$css .= self::chromeCss();

		return $css;
	}

	/**
	 * Marca (.rw-brand) e ocultação do logo nativo, sempre presentes -- não
	 * depende de mediawiki-config/common.css. Este módulo (ext.religiowiki
	 * Customizer.theme) é o único carregado em TODAS as páginas, incluindo
	 * Special:Preferências, onde o núcleo do MediaWiki não carrega o módulo
	 * `site` (Common.css/Common.js) por design -- ver resources/js/chrome.js
	 * pra quem injeta o elemento .rw-brand nesse caso. Sem isso, Preferências
	 * mostrava o placeholder cru de $wgLogo (nunca configurado) por baixo do
	 * #p-logo, que o common.css normalmente esconde.
	 */
	private static function chromeCss(): string {
		return <<<CSS

#p-logo,
.mw-wiki-logo {
	display: none;
}
.rw-brand {
	display: flex;
	align-items: baseline;
	gap: 8px;
	font-weight: 700;
	font-size: 1.05rem;
	color: var(--rw-text, #241C15);
	text-decoration: none;
	white-space: nowrap;
}
.rw-brand:hover {
	text-decoration: none;
}
.rw-brand .rw-brand-mark {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 22px;
	height: 22px;
	border: 1.5px solid var(--rw-text, #241C15);
	border-radius: 50%;
	font-size: 0.75rem;
	font-weight: 700;
	flex-shrink: 0;
}

CSS;
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
