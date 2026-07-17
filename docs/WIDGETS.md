# Widgets semânticos (Fase 5)

Parser tags específicos do domínio da Religio Wiki. Mesma regra de escape
rigoroso da Fase 4 (qualquer editor pode usar, não só admin).

## `<rwinfobox>` — Infobox religião

```wikitext
<rwinfobox nome="Cristianismo" imagem="Exemplo.jpg" grupo="III. Monoteísmos Semíticos"
	relacionadas="[[Judaísmo]], [[Islã]]" texto_central="Bíblia" origem="Levante, século I" />
```
Documenta a classificação (I/II/III) do artigo. Também existe
`{{Infobox religião|...}}` como template de conveniência — mesmos nomes de
parâmetro, gerado por `maintenance/generateConvenienceTemplates.php` — pra
não quebrar artigos escritos antes da Fase 5.

## `<rwbook>` — Livro

```wikitext
<rwbook titulo="Confissões" autor="Santo Agostinho" ano="397" editora="—" capa="Confissoes.jpg"
	sinopse="Autobiografia espiritual..." />
```

## `<rwauthor>` — Autor

```wikitext
<rwauthor nome="Santo Agostinho" datas="354–430" tradicao="Cristianismo (Patrística)"
	obras="Confissões, A Cidade de Deus" />
```

## `<rwreligion>` — Religião

```wikitext
<rwreligion nome="Cristianismo" origem="Levante, século I" periodo="Do século I até hoje"
	ramos="Catolicismo, Ortodoxia, Protestantismo" />
```
Diferente do `<rwinfobox>` (que documenta a classificação do artigo) —
este é pra artigos sobre a própria tradição (história, período, ramos).

## `<rwschool>` — Escola Filosófica

```wikitext
<rwschool nome="Patrística" fundador="—" periodo="Séculos II–VIII"
	conceitos="Fé e razão, Trindade, graça" />
```

## `<rwtimeline>` — Linha do Tempo

```wikitext
<rwtimeline>
313
Edito de Milão — cristianismo passa a ser tolerado no Império Romano.
----
380
Edito de Tessalônica — cristianismo se torna religião oficial do Império.
</rwtimeline>
```
Mesma convenção `----` de `<rwtabs>`/`<rwgrid>` (Fase 4) — primeira linha
do segmento é a data.

## Citação

Não existe um `<rwquote-widget>` separado — `<rwquote>` (Fase 4, ver
`docs/COMPONENTS.md`) já cobre isso; teria sido duplicação.

## Mapa — não implementado

Deixado como pendência documentada (não um stub de código): dependeria de
dados geográficos das tradições e possivelmente uma biblioteca externa
(Leaflet) — passivo de manutenção maior que os outros widgets. Retome só
se/quando fizer sentido pro projeto.
