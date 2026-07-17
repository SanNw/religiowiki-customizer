# Status da extensão — leia isto primeiro

Documento de orientação rápida pra quem (Claude ou humano) for continuar
este projeto sem ter acompanhado a conversa original. Não substitui o
README (instalação/uso) nem o prompt mestre das 8 fases — é só um mapa.

## O que está implementado

| Fase | Status | O que faz |
|---|---|---|
| 1. Fundação/tema | ✅ Feita | `Special:ReligiowikiCustomizer` (aba Aparência), tabela de config, geração de CSS via ResourceLoader |
| 2. Editor CSS/JS | ✅ Feita | Abas CSS/JS, preview local, log de auditoria |
| 3. Homepage Builder | ✅ Feita | Aba Homepage, 8 blocos (5 + Notícias/Livros/Estatísticas da Fase 4), substitui a Main Page com fallback |
| 4. Componentes | ✅ Feita | 8 parser tags (`<rwcard>`, `<rwalert>`, `<rwaccordion>`, `<rwtabs>`, `<rwquote>`, `<rwbadge>`, `<rwcallout>`, `<rwgrid>`) — ver `docs/COMPONENTS.md` |
| 5. Widgets semânticos | ✅ Feita | `<rwinfobox>`, `<rwbook>`, `<rwauthor>`, `<rwreligion>`, `<rwschool>`, `<rwtimeline>` — ver `docs/WIDGETS.md`. Citação reaproveita `<rwquote>`; Mapa documentado como pendência (não implementado) |
| 6. SEO | ✅ Feita | Meta description/OG/Twitter/canonical/robots/JSON-LD, `{{#rwseo:description\|...}}`, breadcrumbs, `maintenance/generateSitemap.php` |
| 7. Performance/skin | ✅ Feita | Lazy loading nativo, preload de fontes, classe `rwc-skin-*` no body, barra de inserção rápida no editor |
| 8. API REST/testes | ⬜ Não iniciada | Endpoints REST, export/import, PHPUnit |

## ⚠️ Nunca testado contra um MediaWiki real

Todo o código foi escrito e validado só com `php -l` (sintaxe) e validação
de JSON — **não há MediaWiki instalado no ambiente onde isso foi escrito**
pra rodar de verdade. Isso significa: é código correto pelas convenções
conhecidas da API do MediaWiki, mas pode ter um erro real (assinatura de
método errada, namespace de classe do core que mudou entre versões) que só
aparece no primeiro `update.php`/carregamento de página de verdade.

**Primeira coisa a fazer ao continuar**: rodar
`docker compose build --no-cache mediawiki && ./scripts/deploy-wiki-content.sh`
no religio-wiki e olhar o log/tela de erro do MediaWiki com atenção antes
de escrever mais código em cima.

## Pontos específicos de risco (por classe)

- `ThemeResourceLoaderModule`/`CustomCssResourceLoaderModule`/
  `CustomJsResourceLoaderModule` extendem `MediaWiki\ResourceLoader\Module`
  — esse é o namespace moderno (pós-reorganização do ResourceLoader); em
  versões mais antigas que 1.39 pode não existir. `requires.MediaWiki` no
  `extension.json` já declara `>= 1.39.0`.
- `ILoadBalancer::getConnectionRef()` é o método usado em todo lugar que
  toca banco (`ThemeSettingsStore`, `CustomCodeStore`,
  `HomepageConfigStore`) — se a versão do MediaWiki tiver deprecado isso em
  favor de outro método, é o primeiro lugar a olhar num erro de "Call to
  undefined method".
- `ManualLogEntry` é referenciado sem namespace (`use ManualLogEntry;`) —
  assume que o BC alias global ainda existe nessa versão do core.
- `HTMLForm::factory('ooui', ...)` e os tipos de campo (`text`, `check`,
  `int`, `textarea`) são todos tipos nativos estáveis — baixo risco.
- Campos de cor são texto validado por regex, **não** um
  `<input type=color>` nativo — funciona, mas não é a UX mais bonita
  possível; refinamento de UI válido pra quando o funcional estiver
  confirmado.
- Homepage Builder: reordenar blocos é por campo numérico "ordem", não
  drag-and-drop — decisão deliberada de simplicidade sobre UX.
- "Artigos em destaque" só suporta escolha manual de páginas nesta versão.
- `Components\LinkSanitizer` monta um regex a partir de `$wgUrlProtocols`
  em toda chamada (não cacheado) — funcionalmente correto, mas se algum
  componente for chamado em volume muito alto numa página só (centenas de
  `<rwcard link="...">`), vale revisar performance; não é um problema de
  segurança, só de eficiência potencial.
- Parser tags (Fase 4) ainda não têm nenhum teste manual de "o que acontece
  se um editor aninhar `<rwtabs>` dentro de `<rwtabs>`" ou outros casos de
  aninhamento — `recursiveTagParse` deveria lidar com isso corretamente
  (é o mecanismo padrão do MediaWiki pra tags aninhadas), mas não foi
  verificado ao vivo.
- `maintenance/generateConvenienceTemplates.php` (Fase 5) usa
  `WikiPage::newPageUpdater()` + `User::newSystemUser()` — API estável nas
  versões recentes, mas nunca rodada de verdade; se `requireExtension()`
  ou a criação do usuário de sistema falhar de um jeito inesperado, é o
  primeiro lugar a olhar. Rodar manualmente depois de aplicar a Fase 5:
  `docker compose exec mediawiki php extensions/ReligiowikiCustomizer/maintenance/generateConvenienceTemplates.php`.
- `InfoboxBoxRenderer` monta a imagem via `$parser->recursiveTagParse()`
  com sintaxe `[[Arquivo:...]]` — isso já corrigiu um bug real que eu
  mesmo cometi na primeira versão (embutir wikitext cru direto no HTML
  retornado não vira imagem sozinho, precisa passar pelo parser); vale
  conferir visualmente no primeiro teste que a imagem realmente aparece.
- `OutputPage::getCategories()` (usado em `HookHandler::onOutputPageBeforeHTML`
  pros breadcrumbs) — não tive 100% de certeza se o retorno é indexado ou
  associativo entre versões do core, então normalizei com
  `array_values(array_map('strval', ...))` em vez de assumir uma forma
  específica. Se os breadcrumbs vierem vazios num artigo que tem categoria
  de verdade, é o primeiro lugar a conferir.
- `OutputPage::addMeta()`/`addHeadItem()`/`setCanonicalUrl()`/`getProperty()`
  (`SeoInjector`) são métodos estáveis e antigos do core — baixo risco,
  mas nunca chamados nesta instalação especificamente.
- `Parser::setFunctionHook()` (`{{#rwseo:...}}`) — verificar se o parser
  function realmente aparece disponível em `Special:Version` → "Funções
  do analisador" depois do primeiro carregamento.
- `onEditPage__showEditForm_initial` (Fase 7) — nome de método mangled a
  partir do hook `"EditPage::showEditForm:initial"` (`::`→`__`, `:`→`_`,
  prefixo `on`). Registrado só via `extension.json` (não implementa uma
  interface tipada `...Hook`, ao contrário dos outros hooks desta classe)
  porque não confirmei o nome exato da interface PHP pra essa combinação
  específica — se o hook não disparar, é o primeiro lugar a conferir
  (grep por "showEditForm:initial" na versão do MediaWiki instalada).
- `OutputPage::addBodyClasses()` — método relativamente mais novo que
  `addModuleStyles()`; deveria existir em qualquer 1.39+, mas nunca
  chamado nesta instalação.

## Mapa de arquivos

```
extension.json                                  registro da extensão (hooks, módulos, special page)
composer.json                                    autoload PSR-4
includes/
  Hooks/HookHandler.php                          BeforePageDisplay, OutputPageBeforeHTML, LoadExtensionSchemaUpdates
  Services/
    ThemeSettingsStore.php                        tema (Fase 1)
    CustomCodeStore.php                           CSS/JS personalizado (Fase 2)
    HomepageConfigStore.php                        homepage (Fase 3)
  Theme/
    ThemeCssGenerator.php                          gera :root{--rw-*} a partir da config
    ThemeResourceLoaderModule.php                  serve o CSS de tema
    CustomCssResourceLoaderModule.php              serve o CSS personalizado
    CustomJsResourceLoaderModule.php               serve o JS personalizado
  Homepage/HomepageRenderer.php                    HTML dos blocos da homepage
  Components/                                      8 parser tags (Fase 4) + LinkSanitizer/SegmentSplitter compartilhados
  Widgets/                                          6 widgets semânticos (Fase 5) + InfoboxBoxRenderer compartilhado
  SEO/                                               SeoInjector, SeoParserFunction, BreadcrumbBuilder (Fase 6)
  Performance/PerformanceInjector.php               preload de fontes + lazy loading (Fase 7)
  SpecialPages/SpecialReligiowikiCustomizer.php    a página admin (6 abas)
sql/tables.json + sql/mysql/tables-generated.sql   a única tabela (chave-valor genérica)
resources/                                        CSS/JS servidos ao navegador
i18n/                                              en.json (fonte), qqq.json (docs), pt-br.json
maintenance/generateConvenienceTemplates.php      gera Template:Infobox religião etc. (Fase 5)
maintenance/generateSitemap.php                   gera sitemap.xml (Fase 6, agendável via cron)
docs/COMPONENTS.md                                sintaxe de uso de cada parser tag (pra editores, não só devs)
docs/WIDGETS.md                                   sintaxe de uso de cada widget semântico
```

Tudo fica em **uma tabela só** (`religiowiki_customizer_settings`,
chave-valor, uma linha por `rwcs_key`) — decisão da Fase 1, mantida em
todas as fases seguintes de propósito, pra nenhuma delas exigir migração
de schema.
