<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Tests\Unit\Theme;

use MediaWiki\Extension\ReligiowikiCustomizer\Theme\ThemeCssGenerator;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\ReligiowikiCustomizer\Theme\ThemeCssGenerator
 *
 * Teste de unidade puro — ThemeCssGenerator::generate() não toca banco nem
 * serviços do MediaWiki, só monta uma string a partir de um array, então
 * roda sem bootstrap completo (MediaWikiUnitTestCase, não
 * MediaWikiIntegrationTestCase).
 *
 * Prioriza o que a Fase 8 pede: módulos de maior risco (aqui, a
 * sanitização de valores antes de virarem CSS), não 100% de cobertura.
 */
class ThemeCssGeneratorTest extends MediaWikiUnitTestCase {

	public function testGeneratesAllExpectedCustomProperties(): void {
		$css = ThemeCssGenerator::generate( [
			'primary' => '#111111',
			'secondary' => '#222222',
			'background' => '#333333',
			'surface' => '#444444',
			'border' => '#555555',
			'text' => '#666666',
			'textMuted' => '#777777',
			'fontFamily' => 'Arial, sans-serif',
			'fontSizeBase' => '18px',
			'maxWidth' => '1300px',
		] );

		$this->assertStringContainsString( '--rw-primary: #111111;', $css );
		$this->assertStringContainsString( '--rw-secondary: #222222;', $css );
		$this->assertStringContainsString( '--rw-background: #333333;', $css );
		$this->assertStringContainsString( '--rw-surface: #444444;', $css );
		$this->assertStringContainsString( '--rw-border: #555555;', $css );
		$this->assertStringContainsString( '--rw-text: #666666;', $css );
		$this->assertStringContainsString( '--rw-text-muted: #777777;', $css );
		$this->assertStringContainsString( '--rw-font-family: Arial, sans-serif;', $css );
		$this->assertStringContainsString( '--rw-font-size-base: 18px;', $css );
		$this->assertStringContainsString( '--rw-max-width: 1300px;', $css );
	}

	public function testEmitsLegacyAliasesForCommonCssCompatibility(): void {
		$css = ThemeCssGenerator::generate( [ 'primary' => '#92400E' ] );

		// mediawiki-config/common.css (repositório religio-wiki) lê essas
		// três variáveis — se algum dia sumirem daqui, common.css quebra
		// silenciosamente sem que nada nesta extensão avise.
		$this->assertStringContainsString( '--rw-bg: var(--rw-background);', $css );
		$this->assertStringContainsString( '--rw-bg-elevated: var(--rw-surface);', $css );
		$this->assertStringContainsString( '--rw-link: var(--rw-primary);', $css );
	}

	/**
	 * --rw-background/--rw-surface/--rw-primary são valores únicos (sem
	 * isso, ficavam presos na cor clara em qualquer tema, já que — ao
	 * contrário de --rw-bg/--rw-bg-elevated/--rw-link — não tinham
	 * variante escura/personalizada em lugar nenhum).
	 */
	public function testEmitsDarkAndCustomThemeOverridesForNewCanonicalNames(): void {
		$css = ThemeCssGenerator::generate( [] );

		$this->assertStringContainsString( ':root[data-theme="dark"]', $css );
		$this->assertStringContainsString( ':root[data-theme="custom"]', $css );

		$darkBlock = substr( $css, strpos( $css, ':root[data-theme="dark"]' ) );
		$this->assertStringContainsString( '--rw-primary: #E0A868;', $darkBlock );
		$this->assertStringContainsString( '--rw-background: #1B1712;', $darkBlock );
		$this->assertStringContainsString( '--rw-surface: #24201A;', $darkBlock );

		$customBlock = substr( $css, strpos( $css, ':root[data-theme="custom"]' ) );
		$this->assertStringContainsString( '--rw-primary: var(--rw-custom-link, #92400E);', $customBlock );
		$this->assertStringContainsString( '--rw-background: var(--rw-custom-bg, #FBF3E1);', $customBlock );
		$this->assertStringContainsString( '--rw-surface: var(--rw-custom-bg-elevated, #FFFDF7);', $customBlock );
	}

	public function testMissingKeysFallBackToDefaultsInsteadOfBreaking(): void {
		$css = ThemeCssGenerator::generate( [] );

		$this->assertStringContainsString( '--rw-background: #FBF3E1;', $css );
		$this->assertStringContainsString( '--rw-text: #241C15;', $css );
	}

	/**
	 * Defesa em profundidade (ver ThemeCssGenerator::sanitizeCssValue): um
	 * valor com `{`/`}` não deve conseguir fechar a regra `:root{...}` e
	 * injetar uma regra CSS arbitrária depois dela.
	 */
	public function testStripsCharactersThatCouldBreakOutOfTheCssRule(): void {
		$css = ThemeCssGenerator::generate( [
			'primary' => '#111} body { display:none} :root{--x:1',
		] );

		// Só a estrutura legítima (":root {...}", ":root[data-theme=dark]
		// {...}" e ":root[data-theme=custom] {...}") deve ter chaves — se
		// um valor injetado sobrevivesse à sanitização, esse count subiria
		// além dos 3 pares esperados.
		$this->assertSame( 3, substr_count( $css, '{' ) );
		$this->assertSame( 3, substr_count( $css, '}' ) );
	}
}
