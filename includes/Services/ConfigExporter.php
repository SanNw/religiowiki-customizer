<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Services;

use MediaWiki\MediaWikiServices;

/**
 * Exportação/importação de toda a configuração das Fases 1–3 (tema,
 * CSS/JS personalizado, homepage) — Fase 8.
 *
 * Importar é equivalente, em risco, a gravar CSS/JS personalizado
 * diretamente (decisão de arquitetura 3 da Fase 8): quem chama
 * `importAll()` precisa já ter passado pela mesma checagem de permissão
 * (`editinterface`) que a Fase 2 exige — este serviço não verifica
 * permissão sozinho, quem chama (Special page ou handler REST) é
 * responsável por isso, igual às outras Stores desta extensão.
 */
class ConfigExporter {

	/**
	 * @return array{theme:array,customCss:string,customJs:string,homepage:array,exportedAt:string}
	 */
	public static function exportAll(): array {
		return [
			'theme' => ThemeSettingsStore::newFromGlobalState()->getTheme(),
			'customCss' => CustomCodeStore::newFromGlobalState()->getCustomCss(),
			'customJs' => CustomCodeStore::newFromGlobalState()->getCustomJs(),
			'homepage' => HomepageConfigStore::newFromGlobalState()->getConfig(),
			'exportedAt' => wfTimestampNow(),
		];
	}

	/**
	 * Faz backup da configuração ATUAL (antes de sobrescrever) e então
	 * aplica $data. Backup fica versionado numa linha própria da mesma
	 * tabela (chave `backup_<timestamp>`), não apenas sobrescrito — pode
	 * ser recuperado manualmente via SQL se necessário
	 * (`SELECT rwcs_value FROM religiowiki_customizer_settings WHERE
	 * rwcs_key LIKE 'backup_%' ORDER BY rwcs_id DESC`).
	 *
	 * @param array $data Mesmo formato de exportAll().
	 * @param int|null $actorId
	 */
	public static function importAll( array $data, ?int $actorId ): void {
		self::backupCurrent( $actorId );

		if ( isset( $data['theme'] ) && is_array( $data['theme'] ) ) {
			ThemeSettingsStore::newFromGlobalState()->saveTheme( $data['theme'], $actorId );
		}
		if ( isset( $data['customCss'] ) ) {
			CustomCodeStore::newFromGlobalState()->saveCustomCss( (string)$data['customCss'], $actorId );
		}
		if ( isset( $data['customJs'] ) ) {
			CustomCodeStore::newFromGlobalState()->saveCustomJs( (string)$data['customJs'], $actorId );
		}
		if ( isset( $data['homepage'] ) && is_array( $data['homepage'] ) ) {
			HomepageConfigStore::newFromGlobalState()->saveConfig( $data['homepage'], $actorId );
		}
	}

	private static function backupCurrent( ?int $actorId ): void {
		$snapshot = self::exportAll();
		$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbw = $lb->getConnection( DB_PRIMARY );
		$dbw->insert(
			'religiowiki_customizer_settings',
			[
				'rwcs_key' => 'backup_' . wfTimestampNow(),
				'rwcs_value' => json_encode( $snapshot ),
				'rwcs_updated' => $dbw->timestamp(),
				'rwcs_updated_by_actor' => $actorId,
			],
			__METHOD__
		);
	}
}
