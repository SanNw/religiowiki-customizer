<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Tests\Integration\Components;

use MediaWiki\Extension\ReligiowikiCustomizer\Components\LinkSanitizer;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\ReligiowikiCustomizer\Components\LinkSanitizer
 * @group Database
 *
 * Teste de INTEGRAÇÃO (não unit): sanitize() usa MediaWikiServices (config
 * $wgUrlProtocols) e Title::newFromText()/exists() (que toca o banco pra
 * checar existência de página), então precisa do bootstrap completo do
 * MediaWiki — não roda com só `phpunit`, precisa de
 * `tests/phpunit/phpunit.php` do core. NUNCA EXECUTADO neste ambiente
 * (nenhuma instalação MediaWiki disponível pra rodar contra) — ver
 * docs/STATUS.md.
 */
class LinkSanitizerTest extends MediaWikiIntegrationTestCase {

	public function testAllowsUrlWithPermittedProtocol(): void {
		$this->assertSame( 'https://exemplo.org/', LinkSanitizer::sanitize( 'https://exemplo.org/' ) );
	}

	public function testRejectsDisallowedProtocol(): void {
		// javascript: não está em $wgUrlProtocols por padrão — não deve
		// virar href de jeito nenhum, nem tentando resolver como página.
		$this->assertNull( LinkSanitizer::sanitize( 'javascript:alert(1)' ) );
	}

	public function testEmptyValueReturnsNull(): void {
		$this->assertNull( LinkSanitizer::sanitize( '' ) );
		$this->assertNull( LinkSanitizer::sanitize( '   ' ) );
	}

	public function testNonExistentPageTitleReturnsNull(): void {
		$this->assertNull( LinkSanitizer::sanitize( 'Esta Página Certamente Não Existe Aqui XYZ123' ) );
	}

	public function testExistingPageTitleReturnsLocalUrl(): void {
		$this->insertPage( 'Página De Teste Do LinkSanitizer' );
		$url = LinkSanitizer::sanitize( 'Página De Teste Do LinkSanitizer' );
		$this->assertNotNull( $url );
		$this->assertStringContainsString( 'P%C3%A1gina', (string)$url );
	}
}
