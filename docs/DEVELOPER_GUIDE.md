# Guia do desenvolvedor

Como a extensão é organizada e como estender cada parte. Para o status
fase-a-fase e riscos conhecidos, ver `docs/STATUS.md` primeiro.

## Princípio central: uma tabela, várias chaves

`religiowiki_customizer_settings` (`sql/tables.json`) é a única tabela de
toda a extensão — chave-valor genérica (`rwcs_key` único, `rwcs_value`
JSON). Cada "grupo de configuração" (tema, CSS/JS, homepage, SEO,
performance) é uma linha. **Não crie uma tabela nova pra uma feature
nova** — adicione uma chave nova. Isso foi decidido na Fase 1
especificamente pra Fases seguintes não precisarem de migração de schema,
e se manteve verdade nas 8 fases.

Padrão pra uma Store nova (ver `ThemeSettingsStore`/`CustomCodeStore`/
`HomepageConfigStore`/`SeoSettingsStore`/`PerformanceSettingsStore` como
exemplos, todos praticamente idênticos):

```php
class MinhaFeatureStore {
	private const SETTINGS_KEY = 'minha_feature';
	public const DEFAULTS = [ 'campo' => 'valor padrão' ];

	public function __construct( ILoadBalancer $loadBalancer ) { ... }
	public static function newFromGlobalState(): self { ... }
	public function getSettings(): array { /* selectRow + json_decode + merge com DEFAULTS */ }
	public function saveSettings( array $values, ?int $actorId ): void { /* upsert + json_encode */ }
}
```

## Como o CSS/JS chega ao navegador

Um módulo ResourceLoader por "coisa que gera CSS/JS dinamicamente"
(`ThemeResourceLoaderModule`, `CustomCssResourceLoaderModule`,
`CustomJsResourceLoaderModule`) — cada um extends
`MediaWiki\ResourceLoader\Module`, implementa `getStyles()`/`getScript()`
lendo a Store correspondente, e `getDefinitionSummary()` incluindo os
valores atuais (é isso que invalida o cache do navegador sozinho quando a
config muda — não precisa de `purgeCache` manual). Registrado em
`extension.json` → `ResourceModules` com `"class"` em vez de
`"styles"`/`"scripts"` fixos.

Módulos "estáticos" (CSS/JS que não dependem de config salva, ex.:
`ext.religiowikiCustomizer.components`) são registrados normalmente com
`"styles"`/`"scripts"` apontando pros arquivos em `resources/`.

Tudo é carregado em toda página via `HookHandler::onBeforePageDisplay()` —
é o único lugar que decide o que entra em cada página; ao adicionar um
módulo novo, é lá que ele precisa ser enfileirado (`addModuleStyles()`/
`addModules()`).

## Como adicionar um parser tag/parser function novo

1. Classe em `includes/Components/` (uso geral) ou `includes/Widgets/`
   (específico do domínio religioso) com um método estático
   `render( ?string $input, array $args, Parser $parser, PPFrame $frame ): string`.
2. **Escape rigoroso obrigatório** — qualquer editor do grupo `editor`
   pode disparar isso, não só admin. `Html::element()`/`Html::rawElement()`
   pra saída, `Components\LinkSanitizer::sanitize()` pra qualquer valor
   que vire `href`.
3. Se o corpo da tag deve suportar wikitext (links, negrito etc.), use
   `$parser->recursiveTagParse( $input, $frame )` — **nunca** embuta
   wikitext cru (`[[...]]`, `{{...}}`) dentro do HTML retornado esperando
   que "funcione sozinho": o retorno de uma tag hook não é reprocessado
   como wikitext (isso já foi um bug real nesta extensão, ver
   `InfoboxBoxRenderer` e `docs/STATUS.md`).
4. Registre em `HookHandler::onParserFirstCallInit()` via
   `$parser->setHook( 'nometag', [ MinhaClasse::class, 'render' ] )` (ou
   `setFunctionHook()` pra `{{#funcao:...}}`).
5. Fallback obrigatório: parâmetro faltando não pode quebrar a página —
   sempre um `if ( vazio ) return '';` ou um valor padrão razoável, nunca
   uma exceção não tratada.

## Como adicionar um bloco novo na Homepage Builder

1. Acrescente o tipo em `HomepageConfigStore::BLOCK_TYPES` e um item em
   `DEFAULTS` (com `type`, `enabled`, `order` + campos específicos).
2. Adicione um `case` em `HomepageRenderer::renderBlock()`.
3. Se o bloco for uma lista de itens tipo card, reaproveite
   `CardComponent::buildHtml()` em vez de escrever markup novo (ver como
   Notícias/Livros fazem isso).
4. Adicione os campos do formulário em
   `SpecialReligiowikiCustomizer::showHomepageForm()` (switch por tipo) —
   o mecanismo de habilitar/ordem já é genérico, só precisa dos campos
   específicos do bloco novo.

## Segurança — regras que não mudam entre fases

- Gravação (qualquer Store) sempre exige `editinterface` — verificado na
  Special:Page ou no Handler REST, nunca "confiado" vindo de outro lugar.
- Handlers REST de escrita exigem token CSRF explícito
  (`RequiresEditInterfaceTrait::requireEditInterfaceWithToken()`), além da
  permissão.
- CSS/JS personalizado (Fase 2) e componentes/widgets em wikitext (Fases
  4-5) têm modelos de ameaça DIFERENTES — o primeiro é admin-only e não
  sanitiza conteúdo (mitigação = controle de acesso); o segundo é
  qualquer `editor` e exige escape rigoroso de todo parâmetro. Não
  confunda os dois ao adicionar algo novo.
- Nunca adicione `$wgHiddenPrefs` a nenhuma instrução de LocalSettings —
  foi removido do MediaWiki core e derruba o site com `DomainException`
  (aconteceu de verdade no religio-wiki, ver o histórico de commits de lá).

## Testes

`tests/phpunit/unit/` — sem dependência de banco/MediaWikiServices,
extendem `MediaWikiUnitTestCase`. `tests/phpunit/integration/` — tocam
banco de verdade, extendem `MediaWikiIntegrationTestCase`, marcados
`@group Database`. Rodar com
`php tests/phpunit/phpunit.php extensions/ReligiowikiCustomizer/tests/phpunit/`
de dentro de uma instalação MediaWiki real.
