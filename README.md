# ReligiowikiCustomizer

Extensão MediaWiki para personalização administrativa da [Religio
Wiki](https://github.com/SanNw/religio-wiki): tema (Fase 1 — cores,
tipografia, largura), CSS/JS personalizado (Fase 2), homepage builder (Fase
3), componentes/widgets (Fases 4-5), SEO (Fase 6), performance/detecção de
skin (Fase 7) e API REST/exportação (Fase 8). Implementação em fases — ver
`docs/` conforme forem sendo concluídas.

## Status: Fase 1 concluída

Fundação: estrutura da extensão, tabela de configuração, geração de CSS a
partir de tokens salvos no banco, e `Special:ReligiowikiCustomizer` com um
formulário para cores/tipografia/largura máxima.

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
