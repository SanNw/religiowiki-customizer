# Status da extensão — leia isto primeiro

Documento de orientação rápida pra quem (Claude ou humano) for continuar
este projeto sem ter acompanhado a conversa original. Não substitui o
README (instalação/uso) nem o prompt mestre das 8 fases — é só um mapa.

## O que está implementado

| Fase | Status | O que faz |
|---|---|---|
| 1. Fundação/tema | ✅ Feita | `Special:ReligiowikiCustomizer` (aba Aparência), tabela de config, geração de CSS via ResourceLoader |
| 2. Editor CSS/JS | ✅ Feita | Abas CSS/JS, preview local, log de auditoria |
| 3. Homepage Builder | ✅ Feita | Aba Homepage, 5 blocos, substitui a Main Page com fallback |
| 4. Componentes | ⬜ Não iniciada | `<rwcard>`, `<rwaccordion>` etc. |
| 5. Widgets semânticos | ⬜ Não iniciada | Infobox, Livro, Autor, Religião... |
| 6. SEO | ⬜ Não iniciada | Meta tags, sitemap, JSON-LD |
| 7. Performance/skin | ⬜ Não iniciada | Lazy loading, detecção de skin |
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
  SpecialPages/SpecialReligiowikiCustomizer.php    a página admin (4 abas)
sql/tables.json + sql/mysql/tables-generated.sql   a única tabela (chave-valor genérica)
resources/                                        CSS/JS servidos ao navegador
i18n/                                              en.json (fonte), qqq.json (docs), pt-br.json
```

Tudo fica em **uma tabela só** (`religiowiki_customizer_settings`,
chave-valor, uma linha por `rwcs_key`) — decisão da Fase 1, mantida em
todas as fases seguintes de propósito, pra nenhuma delas exigir migração
de schema.
