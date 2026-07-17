# ReligiowikiCustomizer

Extensão MediaWiki para personalização administrativa da [Religio
Wiki](https://github.com/SanNw/religio-wiki): tema (Fase 1 — cores,
tipografia, largura), CSS/JS personalizado (Fase 2), homepage builder (Fase
3), componentes/widgets (Fases 4-5), SEO (Fase 6), performance/detecção de
skin (Fase 7) e API REST/exportação (Fase 8). Implementação em fases — ver
`docs/` conforme forem sendo concluídas.

## Status: Fases 1 e 2 concluídas

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

### Permissão

Restrita ao direito nativo `editinterface` — não é criado nenhum grupo ou
direito novo; qualquer conta no grupo `sysop` (ou explicitamente concedida
`editinterface`) já acessa.

### Requisitos

MediaWiki >= 1.39 (testado de olho na 1.43, versão rodando no religio-wiki).

## Licença

GPL-2.0-or-later — ver `LICENSE`.
