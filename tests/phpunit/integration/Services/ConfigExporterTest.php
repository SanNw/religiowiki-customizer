<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Tests\Integration\Services;

use MediaWiki\Extension\ReligiowikiCustomizer\Services\ConfigExporter;
use MediaWiki\Extension\ReligiowikiCustomizer\Services\ThemeSettingsStore;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\ReligiowikiCustomizer\Services\ConfigExporter
 * @group Database
 *
 * Teste de integração — toca a tabela religiowiki_customizer_settings de
 * verdade. NUNCA EXECUTADO neste ambiente (sem MediaWiki instalado pra
 * rodar contra) — ver docs/STATUS.md. Precisa que a extensão esteja
 * carregada e a tabela já criada (update.php) no ambiente de teste real.
 */
class ConfigExporterTest extends MediaWikiIntegrationTestCase {

	public function testImportAllCreatesABackupRowBeforeOverwriting(): void {
		$store = ThemeSettingsStore::newFromGlobalState();
		$store->saveTheme( [ 'primary' => '#ORIGINAL' ], null );

		$db = $this->getDb();
		$countBefore = (int)$db->selectField(
			'religiowiki_customizer_settings', 'COUNT(*)', [], __METHOD__
		);

		ConfigExporter::importAll( [ 'theme' => [ 'primary' => '#NEW0000' ] ], null );

		$countAfter = (int)$db->selectField(
			'religiowiki_customizer_settings', 'COUNT(*)', [], __METHOD__
		);

		// Pelo menos uma linha nova (o backup) deve ter aparecido.
		$this->assertGreaterThan( $countBefore, $countAfter );
		$this->assertSame( '#NEW0000', $store->getTheme()['primary'] );
	}

	public function testExportAllRoundTripsThroughImportAll(): void {
		$store = ThemeSettingsStore::newFromGlobalState();
		$store->saveTheme( [ 'primary' => '#ABCDEF' ], null );

		$exported = ConfigExporter::exportAll();
		$this->assertSame( '#ABCDEF', $exported['theme']['primary'] );

		$store->saveTheme( [ 'primary' => '#000000' ], null );
		ConfigExporter::importAll( $exported, null );

		$this->assertSame( '#ABCDEF', $store->getTheme()['primary'] );
	}
}
