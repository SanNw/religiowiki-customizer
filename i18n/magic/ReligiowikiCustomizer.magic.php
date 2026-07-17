<?php
/**
 * Palavras mágicas da ReligiowikiCustomizer.
 *
 * Faltava este arquivo -- Parser::setFunctionHook('rwseo', ...) (Fase 6,
 * HookHandler::onParserFirstCallInit) precisa que 'rwseo' esteja registrado
 * como magic word antes de qualquer parser function poder usá-lo; sem isso,
 * o primeiro Parser instanciado no processo (ex.: ao salvar
 * MediaWiki:Common.css via CssContentHandler::preSaveTransform) lança
 * UnexpectedValueException "invalid magic word 'rwseo'" e derruba a
 * requisição inteira -- não é um erro isolado da função em si, quebra
 * qualquer coisa que instancie o parser.
 *
 * @license GPL-2.0-or-later
 */

$magicWords = [];

// 0 = case-sensitive (o padrão dos outros parser functions do core, ex.
// {{#if:}}); {{#RWSEO:}} maiúsculo não funcionaria com isso, só {{#rwseo:}}.
$magicWords['en'] = [
	'rwseo' => [ 0, 'rwseo' ],
];
