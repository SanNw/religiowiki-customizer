# ReligiowikiCustomizer

Extensão MediaWiki para personalização administrativa da [Religio
Wiki](https://github.com/SanNw/religio-wiki): tema (Fase 1 — cores,
tipografia, largura), CSS/JS personalizado (Fase 2), homepage builder (Fase
3), componentes/widgets (Fases 4-5), SEO (Fase 6), performance/detecção de
skin (Fase 7) e API REST/exportação (Fase 8). Implementação em fases — ver
`docs/` conforme forem sendo concluídas.

## Status: as 8 fases planejadas concluídas

Guias completos: [`docs/ADMIN_GUIDE.md`](docs/ADMIN_GUIDE.md) (como usar
cada aba do painel) e [`docs/DEVELOPER_GUIDE.md`](docs/DEVELOPER_GUIDE.md)
(arquitetura, como estender). `docs/STATUS.md` lista riscos conhecidos e
pontos nunca testados contra um MediaWiki de verdade — leia antes de
assumir que algo "deveria simplesmente funcionar".

**Fase 1** — Fundação: estrutura da extensão, tabela de configuração,
geração de CSS a partir de tokens salvos no banco, e
`Special:ReligiowikiCustomizer` com um formulário para cores/tipografia/
largura máxima.

**Fase 2** — Editor de CSS/JS personalizado: duas abas novas em
`Special:ReligiowikiCustomizer` (`?tab=css` e `?tab=js`), cada uma com
textarea, botão "Salvar" e botão "Visualizar alterações" (aplica local, só
no navegador de quem está editando, sem afetar outros visitantes até
salvar de fato). Toda gravação (tema, CSS ou JS) fica registrada em
`Special:Log/religiowikicustomizer` com autor e timestamp.

**Fase 3** — Homepage Builder: aba `?tab=homepage` com 5 blocos (Hero
Banner, Cards, Artigos em destaque, Categorias, Pesquisa), cada um com
habilitar/desabilitar + campo de ordem numérico (sem drag-and-drop nesta
versão — ver nota no código). A Main Page é substituída pelo layout
configurado via hook `OutputPageBeforeHTML` **só se houver pelo menos um
bloco habilitado**; sem nenhum bloco habilitado (ou com a extensão
desativada), a página wiki normal continua sendo exibida e editável — é o
fallback pedido pela Fase 3. "Artigos em destaque" nesta versão só suporta
escolha manual de páginas (modo automático por mais visitadas não
implementado — precisaria de uma fonte de dados de pageviews que não está
garantida em toda instalação).

**Fase 4** — Biblioteca de componentes: 8 parser tags disponíveis em
qualquer página wiki (`<rwcard>`, `<rwalert>`, `<rwaccordion>`, `<rwtabs>`,
`<rwquote>`, `<rwbadge>`, `<rwcallout>`, `<rwgrid>`) — ver
[`docs/COMPONENTS.md`](docs/COMPONENTS.md) pra sintaxe de uso de cada um.
Modelo de ameaça diferente das
fases anteriores: qualquer editor do grupo `editor` pode usar essas tags,
não só admin — por isso todo parâmetro passa por escape rigoroso
(`Html::element`) e links por `Components\LinkSanitizer` (mesma checagem
de protocolo, `$wgUrlProtocols`, que o wikitext nativo já faz). A Homepage
Builder ganhou 3 blocos novos que dependiam disso (Notícias, Livros,
Estatísticas), e o bloco "Cards" existente foi refeito pra reaproveitar
`CardComponent::buildHtml` em vez de markup duplicado.

**Fase 5** — Widgets semânticos: `<rwinfobox>`, `<rwbook>`, `<rwauthor>`,
`<rwreligion>`, `<rwschool>`, `<rwtimeline>` — ver
[`docs/WIDGETS.md`](docs/WIDGETS.md). "Citação" reaproveita `<rwquote>` da
Fase 4 (não duplicado); "Mapa" fica documentado como pendência, não
implementado (dependeria de dados geográficos + lib externa, passivo de
manutenção maior que os demais). `maintenance/generateConvenienceTemplates.php`
gera templates de conveniência (`Template:Infobox religião` etc.) que só
chamam os widgets — mantém compatível qualquer artigo escrito antes da
Fase 5 com `{{Infobox religião|...}}`.

**Fase 6** — SEO: aba `?tab=seo` (nome do site, descrição padrão, imagem
OG padrão, Twitter handle). Meta Description, OpenGraph, Twitter Card,
canonical URL, robots (`noindex` fora do namespace principal) e JSON-LD
(`WebSite` na Main Page, `Article` nos artigos) injetados via
`BeforePageDisplay`. Meta description por página via
`{{#rwseo:description|texto}}` (page property, sem extração automática de
parágrafo — decisão explícita, custo/benefício não compensava). Breadcrumbs
baseados na primeira categoria do artigo, prependidos ao conteúdo em
qualquer página do namespace principal (exceto a Main Page).
`maintenance/generateSitemap.php` gera `sitemap.xml` (agendável via cron,
não sob demanda).

**Fase 7** — Performance, detecção de skin, recursos para editores: aba
`?tab=performance` (lazy loading nativo de imagens, preload de fontes via
`<link rel=preload>`) — sem sistema de build próprio, só recursos nativos
do navegador + o cache/minificação que o ResourceLoader já faz sozinho.
Detecção de skin: classe `rwc-skin-<nome>` no `<body>` via
`OutputPage::addBodyClasses()`, robustez básica (não quebrar em outra
skin), não paridade total — o religio-wiki está travado no Vector clássico
mesmo, isso é pra portabilidade da extensão em geral. Recursos para
editores: barra de botões acima da área de edição de wikitexto (só no
editor de código-fonte) que insere os componentes/widgets no cursor —
manipula o textarea diretamente, sem se acoplar à API interna do
WikiEditor.

**Fase 8** — API REST, exportação/importação e testes: 4 endpoints REST
(`GET`/`PUT /religiowikicustomizer/v0/theme`, `GET
/religiowikicustomizer/v0/export`, `PUT /religiowikicustomizer/v0/import`)
usando o framework REST do core (`MediaWiki\Rest\SimpleHandler`), não a
API legada. Leitura de tema é **pública** (já é visível no CSS de toda
página); leitura de export e toda escrita exigem `editinterface` **e**
token CSRF, sem exceção. Aba `?tab=exportimport`: exporta toda a
configuração (tema, CSS/JS, homepage) como JSON, importa com confirmação
explícita (checkbox + `confirm()` no navegador) e backup automático
versionado antes de qualquer sobrescrita. Testes PHPUnit priorizando os
módulos de maior risco: geração de CSS (unit, `tests/phpunit/unit/`) e
sanitização de link/export-import (integração, `tests/phpunit/integration/`
— **nunca executados** neste ambiente, sem MediaWiki instalado pra rodar
contra; ver `docs/STATUS.md`).

### Como funciona

- Configuração fica em `religiowiki_customizer_settings` (uma linha por
  grupo de configuração — hoje só `theme`; Fases seguintes acrescentam
  linhas novas, sem migração de schema).
- A cada carregamento de página, o módulo ResourceLoader
  `ext.religiowikiCustomizer.theme` gera `:root { --rw-*: ...; }` a partir
  da configuração salva (ou dos valores padrão, se nada foi salvo ainda —
  os mesmos já usados em `mediawiki-config/common.css` do religio-wiki, pra
  uma instalação nova renderizar idêntica ao site atual).
- **`mediawiki-config/common.css` do religio-wiki não precisa de nenhuma
  alteração**: a extensão também emite os aliases legados `--rw-bg`,
  `--rw-bg-elevated` e `--rw-link` apontando pros tokens novos, então o CSS
  já escrito continua funcionando sem mudança nenhuma — só passa a receber
  os valores de uma fonte configurável em vez de hardcoded.
- Cache: o hash de versão do módulo é derivado da própria configuração
  salva (`getDefinitionSummary`), então qualquer alteração feita no painel
  invalida o cache do navegador sozinha — não precisa de `purgeCache`
  manual.
- CSS/JS personalizado (Fase 2) usa a mesma tabela (`custom_css`/
  `custom_js`) e o mesmo mecanismo de módulo dinâmico. O CSS personalizado
  declara `ext.religiowikiCustomizer.theme` como dependência, garantindo
  que carrega **depois** do tema base (cascata previsível, sobrescreve o
  que precisar). O JS personalizado é executado **sem sanitização de
  conteúdo** — a mitigação é só controle de acesso (`editinterface`), risco
  equivalente ao que `MediaWiki:Common.js` já representa hoje; isso é
  intencional e está documentado no código e na própria página do
  formulário, não é um descuido.

### Instalação

1. Clone este repositório em `extensions/ReligiowikiCustomizer/` da sua
   instalação MediaWiki (no religio-wiki isso já é automático via
   `Dockerfile`).
2. Adicione ao final do `LocalSettings.php`:
   ```php
   wfLoadExtension( 'ReligiowikiCustomizer' );
   ```
3. Rode `php maintenance/update.php` — cria a tabela
   `religiowiki_customizer_settings`.
4. Acesse `Special:ReligiowikiCustomizer` logado como um usuário com o
   direito `editinterface` (grupo `sysop` por padrão).

### Atualização (guia de atualização)

Depois de puxar uma versão nova do repositório (`git pull` na pasta da
extensão, ou rebuild da imagem Docker no caso do religio-wiki):

1. Rode `php maintenance/update.php` de novo — cria/ajusta tabelas se a
   versão nova precisar (idempotente, seguro rodar mesmo sem mudança de
   schema).
2. Se a versão nova mexeu em templates de conveniência (Fase 5), rode
   também `php maintenance/generateConvenienceTemplates.php`.
3. Confira `Special:Version` pra garantir que a extensão carregou sem
   erro fatal antes de considerar a atualização concluída.

Nenhuma migração de dado é necessária entre fases — a tabela
`religiowiki_customizer_settings` é a mesma desde a Fase 1, cada fase só
acrescenta uma chave nova.

### Testes

```bash
php tests/phpunit/phpunit.php extensions/ReligiowikiCustomizer/tests/phpunit/
```
(rodar de dentro da raiz de uma instalação MediaWiki real — não funciona
como `phpunit` isolado, os testes de integração precisam do bootstrap
completo do core). Ver `docs/STATUS.md` pra saber quais testes são unit
(rodam rápido, sem banco) e quais são integração (mais lentos, tocam
banco de verdade).

### Permissão

Restrita ao direito nativo `editinterface` — não é criado nenhum grupo ou
direito novo; qualquer conta no grupo `sysop` (ou explicitamente concedida
`editinterface`) já acessa. Os parser tags de componentes/widgets (Fases
4-5) são a exceção deliberada: qualquer conta no grupo `editor` pode
usá-los em wikitext, não só `sysop` — ver "Modelo de ameaça" em
`docs/COMPONENTS.md`.

### Requisitos

MediaWiki >= 1.39 (testado de olho na 1.43, versão rodando no religio-wiki).

## Licença

GPL-2.0-or-later — ver `LICENSE`.
