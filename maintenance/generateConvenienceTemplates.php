<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

/**
 * Gera/atualiza os templates de conveniência que fazem artigos existentes
 * (escritos com `{{Infobox religião|...}}` etc., de antes da Fase 5)
 * continuarem funcionando sem edição — a lógica de verdade agora vive nos
 * widgets em PHP (parser tags), o template só repassa os parâmetros pra
 * tag correspondente. Decisão de arquitetura 1 da Fase 5.
 *
 * Idempotente — seguro rodar de novo a qualquer momento (não duplica
 * páginas, só atualiza se o conteúdo mudou).
 *
 * Uso: php maintenance/generateConvenienceTemplates.php
 */
class GenerateConvenienceTemplates extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription(
			'Cria/atualiza os templates de conveniência (Template:Infobox religião, ' .
			'Template:Livro, Template:Autor, Template:Religião, ' .
			'Template:Escola filosófica) que chamam os widgets da Fase 5.'
		);
		$this->requireExtension( 'ReligiowikiCustomizer' );
	}

	public function execute() {
		foreach ( $this->getTemplates() as $titleText => $wikitext ) {
			$this->createOrUpdate( $titleText, $wikitext );
		}
	}

	/**
	 * @return array<string,string> Título da página => wikitext.
	 */
	private function getTemplates(): array {
		return [
			'Template:Infobox religião' => <<<'WIKITEXT'
<rwinfobox
	nome="{{{nome|{{PAGENAME}}}}}"
	imagem="{{{imagem|}}}"
	grupo="{{{grupo|}}}"
	relacionadas="{{{relacionadas|}}}"
	texto_central="{{{texto_central|}}}"
	origem="{{{origem|}}}"
/><noinclude>
Uso:
{{Infobox religião
|nome=Cristianismo
|imagem=Exemplo.jpg
|grupo=III. Monoteísmos Semíticos
|relacionadas=[[Judaísmo]], [[Islã]]
|texto_central=Bíblia
|origem=Levante, século I
}}

(Gerado por maintenance/generateConvenienceTemplates.php — a lógica real
está em includes/Widgets/InfoboxWidget.php, este template só repassa os
parâmetros pra tag &lt;rwinfobox&gt;.)
</noinclude>
WIKITEXT,
			'Template:Livro' => <<<'WIKITEXT'
<rwbook
	titulo="{{{titulo|{{PAGENAME}}}}}"
	autor="{{{autor|}}}"
	ano="{{{ano|}}}"
	editora="{{{editora|}}}"
	capa="{{{capa|}}}"
	sinopse="{{{sinopse|}}}"
/><noinclude>
Uso: {{Livro|titulo=...|autor=...|ano=...|editora=...|capa=...|sinopse=...}}
</noinclude>
WIKITEXT,
			'Template:Autor' => <<<'WIKITEXT'
<rwauthor
	nome="{{{nome|{{PAGENAME}}}}}"
	datas="{{{datas|}}}"
	tradicao="{{{tradicao|}}}"
	obras="{{{obras|}}}"
	imagem="{{{imagem|}}}"
/><noinclude>
Uso: {{Autor|nome=...|datas=...|tradicao=...|obras=...|imagem=...}}
</noinclude>
WIKITEXT,
			'Template:Religião' => <<<'WIKITEXT'
<rwreligion
	nome="{{{nome|{{PAGENAME}}}}}"
	origem="{{{origem|}}}"
	periodo="{{{periodo|}}}"
	ramos="{{{ramos|}}}"
	imagem="{{{imagem|}}}"
/><noinclude>
Uso: {{Religião|nome=...|origem=...|periodo=...|ramos=...|imagem=...}}
</noinclude>
WIKITEXT,
			'Template:Escola filosófica' => <<<'WIKITEXT'
<rwschool
	nome="{{{nome|{{PAGENAME}}}}}"
	fundador="{{{fundador|}}}"
	periodo="{{{periodo|}}}"
	conceitos="{{{conceitos|}}}"
	imagem="{{{imagem|}}}"
/><noinclude>
Uso: {{Escola filosófica|nome=...|fundador=...|periodo=...|conceitos=...|imagem=...}}
</noinclude>
WIKITEXT,
		];
	}

	private function createOrUpdate( string $titleText, string $wikitext ): void {
		$title = Title::newFromText( $titleText );
		if ( !$title ) {
			$this->error( "Título inválido: $titleText" );
			return;
		}

		$services = MediaWikiServices::getInstance();
		$page = $services->getWikiPageFactory()->newFromTitle( $title );
		$existing = $page->getContent();
		if ( $existing && trim( $existing->serialize() ) === trim( $wikitext ) ) {
			$this->output( "Já atualizado: $titleText\n" );
			return;
		}

		$user = User::newSystemUser( 'Maintenance script', [ 'steal' => true ] );
		$updater = $page->newPageUpdater( $user );
		$updater->setContent( SlotRecord::MAIN, ContentHandler::makeContent( $wikitext, $title ) );
		$updater->saveRevision(
			CommentStoreComment::newUnsavedComment(
				'Gerado por maintenance/generateConvenienceTemplates.php (ReligiowikiCustomizer)'
			),
			EDIT_FORCE_BOT
		);

		$this->output( "Atualizado: $titleText\n" );
	}
}

$maintClass = GenerateConvenienceTemplates::class;
require_once RUN_MAINTENANCE_IF_MAIN;
