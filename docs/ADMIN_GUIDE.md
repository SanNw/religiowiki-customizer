# Guia de administração

Para quem tem o direito `editinterface` (grupo `sysop` por padrão) e vai
usar `Special:ReligiowikiCustomizer` no dia a dia. Para sintaxe de
wikitext (parser tags), ver `docs/COMPONENTS.md` e `docs/WIDGETS.md` — este
guia é sobre o painel administrativo em si.

## Aba Aparência (`?tab=aparencia`)

Cores (formato hex, ex.: `#FBF3E1`), fonte (valor CSS `font-family` bruto),
tamanho base de fonte e largura máxima de conteúdo. Os campos de cor têm
**preview em tempo real** nesta própria página (muda ao digitar, antes de
salvar) — mas isso só afeta o que você vê aqui, não o site pros outros
visitantes até clicar Salvar. Botão "Restaurar padrão" apaga a configuração
salva — o site volta a usar os valores originais do tema.

## Abas CSS/JS personalizado (`?tab=css`, `?tab=js`)

Editor de texto simples (sem realce de sintaxe). Botão "Visualizar
alterações" aplica o conteúdo do textarea **só no seu navegador**, sem
salvar — teste à vontade antes de publicar. **O JavaScript salvo aqui roda
para TODOS os visitantes do site, em toda página, assim que salvo** — não
dê acesso a esta aba pra quem não seja totalmente confiável.

## Aba Homepage (`?tab=homepage`)

8 blocos possíveis (Hero, Cards, Destaque, Categorias, Pesquisa, Notícias,
Livros, Estatísticas) — cada um com uma caixa "Habilitar" e um campo
"Ordem" (número — menor aparece primeiro). Campos de conteúdo de
Cards/Notícias/Livros/Estatísticas são **JSON** (ver o texto de ajuda
abaixo de cada campo pro formato esperado). Com **nenhum bloco habilitado**,
a Página principal volta a ser a página wiki normal (editável como
qualquer outra) — não precisa "desligar" nada além de desabilitar todos os
blocos.

## Aba SEO (`?tab=seo`)

Configuração padrão (usada quando um artigo não define a própria via
`{{#rwseo:description|...}}` no wikitext). Nome do site aqui só afeta o
JSON-LD (dado estruturado pra buscadores) — não muda o nome exibido no
cabeçalho do wiki.

## Aba Performance (`?tab=performance`)

Lazy loading de imagens é um toggle simples. Fontes pra pré-carregar: uma
URL por linha — só URLs `http(s)://` são usadas, o resto é ignorado
silenciosamente.

## Aba Exportar/Importar (`?tab=exportimport`)

- **Exportar**: copie o conteúdo do primeiro textarea (JSON com toda a
  configuração — tema, CSS/JS, homepage) e guarde num arquivo próprio.
  Não existe botão de download, é copiar e colar.
- **Importar**: cole um JSON exportado (deste wiki ou de outro rodando a
  mesma versão da extensão) no segundo textarea, marque a caixa de
  confirmação e clique "Importar". Um alerta de confirmação do próprio
  navegador aparece antes de enviar — leia antes de aceitar, **isso
  substitui a configuração atual inteira**.
- Um backup automático da configuração de antes é salvo no banco (linha
  `backup_<timestamp>` na tabela `religiowiki_customizer_settings`) antes
  de qualquer importação — não é apagado, mas também não tem uma tela pra
  restaurá-lo com um clique nesta versão. Recuperação é manual via SQL:
  ```sql
  SELECT rwcs_value FROM religiowiki_customizer_settings
  WHERE rwcs_key LIKE 'backup_%' ORDER BY rwcs_id DESC LIMIT 1;
  ```
  Copie o JSON da coluna `rwcs_value` e cole de volta na aba Importar.

## Todas as alterações ficam registradas

`Special:Log/religiowikicustomizer` mostra quem mudou o quê e quando —
tema, CSS, JS, homepage, SEO, performance e importações, todos no mesmo
log.
