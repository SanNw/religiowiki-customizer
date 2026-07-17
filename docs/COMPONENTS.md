# Biblioteca de componentes (Fase 4)

Parser tags disponíveis em qualquer página wiki, pra qualquer editor do
grupo `editor` (não exige `editinterface`). Todo parâmetro passa por
escape (`Html::element`); links passam por `Components\LinkSanitizer`
(mesma checagem de protocolo do wikitext nativo).

## `<rwcard>`

```wikitext
<rwcard titulo="Cristianismo" texto="Religião monoteísta abraâmica..." link="Cristianismo" icone="✝️" />
```
Parâmetros: `titulo`, `texto` (ou o conteúdo entre as tags, se não usar
`texto=`), `link` (título de página existente ou URL com protocolo
permitido), `icone`. Todos opcionais — sem nenhum, ainda renderiza uma
caixa vazia estilizada, não quebra a página.

## `<rwalert tipo="info|aviso|erro|sucesso">`

```wikitext
<rwalert tipo="aviso">
Este artigo carece de fontes adicionais.
</rwalert>
```
Conteúdo suporta wikitext completo (links, negrito etc.). `tipo` inválido
ou ausente cai em `info`.

## `<rwaccordion titulo="...">`

```wikitext
<rwaccordion titulo="Qual a diferença entre os três grupos?">
Ver [[Classificação das religiões]].
</rwaccordion>
```
Um item por tag — pra várias perguntas, empilhe vários `<rwaccordion>` em
sequência. Sem `titulo`, usa um rótulo genérico.

## `<rwtabs>`

```wikitext
<rwtabs>
Origem
Texto sobre a origem...
----
Ramos
Texto sobre os ramos...
</rwtabs>
```
Segmentos separados por uma linha só com `----`; a primeira linha de cada
segmento é o rótulo da aba. Sem nenhum `----`, vira uma aba só.

## `<rwquote autor="..." fonte="...">`

```wikitext
<rwquote autor="Santo Agostinho" fonte="Confissões">
Fizeste-nos para Ti, e o nosso coração está inquieto enquanto não repousa em Ti.
</rwquote>
```
`autor`/`fonte` opcionais — sem nenhum dos dois, mostra só a citação.

## `<rwbadge cor="cinza|verde|vermelho|azul|amarelo">`

```wikitext
<rwbadge cor="verde">Verificado</rwbadge>
```
Conteúdo é texto simples (sem wikitext) — é um rótulo curto, não um bloco.

## `<rwcallout titulo="...">`

```wikitext
<rwcallout titulo="Nota editorial">
A perspectiva ortodoxa é adotada por convenção do projeto — ver [[Religio Wiki:Sobre]].
</rwcallout>
```
Igual ao `<rwalert>`, mas sem tom semântico (sem ícone fixo) — destaque
neutro de propósito geral. `titulo` é opcional.

## `<rwgrid colunas="N">`

```wikitext
<rwgrid colunas="3">
Coluna 1...
----
Coluna 2...
----
Coluna 3...
</rwgrid>
```
Mesma convenção de separador `----` do `<rwtabs>`, sem rótulo — cada
segmento vira uma coluna lado a lado (`colunas` limitado a 1–6; se omitido,
usa o número de segmentos encontrados).

## Reaproveitamento pela Homepage Builder

`CardComponent::buildHtml()` é usado tanto pelo `<rwcard>` quanto pelos
blocos "Cards", "Notícias" e "Livros" da Homepage Builder (Fase 3) — o
mesmo HTML/CSS em todo lugar, sem duplicação.
