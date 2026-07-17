<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Tests\Unit\Components;

use MediaWiki\Extension\ReligiowikiCustomizer\Components\SegmentSplitter;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\ReligiowikiCustomizer\Components\SegmentSplitter
 *
 * Compartilhado por <rwtabs>/<rwgrid> (Fase 4) e <rwtimeline> (Fase 5) —
 * qualquer editor usa isso, então o fallback "sem nenhum ---- encontrado"
 * precisa realmente não quebrar nada.
 */
class SegmentSplitterTest extends MediaWikiUnitTestCase {

	public function testSplitsOnFourDashesLine(): void {
		$segments = SegmentSplitter::split( "Um\n----\nDois\n----\nTrês" );
		$this->assertSame( [ 'Um', 'Dois', 'Três' ], $segments );
	}

	public function testNoSeparatorFoundYieldsSingleSegment(): void {
		$segments = SegmentSplitter::split( 'Só um bloco, sem separador nenhum.' );
		$this->assertSame( [ 'Só um bloco, sem separador nenhum.' ], $segments );
	}

	public function testDashesNotAloneOnTheirOwnLineAreNotTreatedAsSeparator(): void {
		// "----" no meio de uma frase não deve dividir o conteúdo.
		$segments = SegmentSplitter::split( "Texto com ---- traços no meio, não é separador." );
		$this->assertCount( 1, $segments );
	}

	public function testSplitLabelSeparatesFirstLineFromRest(): void {
		[ $label, $rest ] = SegmentSplitter::splitLabel( "Título da aba\nResto do conteúdo\nsegunda linha" );
		$this->assertSame( 'Título da aba', $label );
		$this->assertSame( "Resto do conteúdo\nsegunda linha", $rest );
	}

	public function testSplitLabelWithNoContentAfterFirstLine(): void {
		[ $label, $rest ] = SegmentSplitter::splitLabel( 'Só um título' );
		$this->assertSame( 'Só um título', $label );
		$this->assertSame( '', $rest );
	}
}
