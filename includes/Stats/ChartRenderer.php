<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Stats;

/**
 * Gera gráficos como SVG inline, sem nenhuma biblioteca JavaScript externa —
 * combina com a política do projeto (self-contained) e com a identidade
 * visual (as cores vêm das variáveis --rw-* via as classes CSS em
 * special-artigos.css, não hardcoded aqui).
 *
 * Todos os métodos recebem uma lista ordenada de pontos no formato
 * [ 'label' => string, 'value' => int ] e devolvem uma string HTML/SVG segura
 * (todo texto é escapado). O SVG usa viewBox + largura 100%, então escala
 * sozinho para a largura do container (responsivo).
 */
class ChartRenderer {

	private const WIDTH = 720;
	private const HEIGHT = 240;
	private const PAD_LEFT = 44;
	private const PAD_RIGHT = 14;
	private const PAD_TOP = 16;
	private const PAD_BOTTOM = 34;

	/**
	 * Gráfico de linha (evolução ao longo do tempo).
	 *
	 * @param array<int,array{label:string,value:int}> $points
	 * @param string $emptyMsg Mensagem exibida quando não há dados.
	 * @return string
	 */
	public static function lineChart( array $points, string $emptyMsg ): string {
		$points = self::normalize( $points );
		if ( $points === [] ) {
			return self::emptyState( $emptyMsg );
		}

		$max = self::maxValue( $points );
		$plotW = self::WIDTH - self::PAD_LEFT - self::PAD_RIGHT;
		$plotH = self::HEIGHT - self::PAD_TOP - self::PAD_BOTTOM;
		$count = count( $points );

		$coords = [];
		foreach ( $points as $i => $p ) {
			$x = $count === 1
				? self::PAD_LEFT + $plotW / 2
				: self::PAD_LEFT + $plotW * ( $i / ( $count - 1 ) );
			$y = self::PAD_TOP + $plotH * ( 1 - $p['value'] / $max );
			$coords[] = [ 'x' => $x, 'y' => $y, 'label' => $p['label'], 'value' => $p['value'] ];
		}

		$svg = self::openSvg();
		$svg .= self::gridAndYLabels( $max, $plotW, $plotH );

		// Área preenchida sob a linha.
		$areaPts = self::PAD_LEFT . ',' . ( self::PAD_TOP + $plotH );
		foreach ( $coords as $c ) {
			$areaPts .= ' ' . self::num( $c['x'] ) . ',' . self::num( $c['y'] );
		}
		$areaPts .= ' ' . self::num( self::PAD_LEFT + $plotW ) . ',' . ( self::PAD_TOP + $plotH );
		$svg .= '<polygon class="rw-chart-area" points="' . $areaPts . '" />';

		// A linha.
		$linePts = [];
		foreach ( $coords as $c ) {
			$linePts[] = self::num( $c['x'] ) . ',' . self::num( $c['y'] );
		}
		$svg .= '<polyline class="rw-chart-line" points="' . implode( ' ', $linePts ) . '" />';

		// Pontos + rótulos de X (rareados para não sobrepor).
		$step = (int)ceil( $count / 8 );
		foreach ( $coords as $i => $c ) {
			$svg .= '<circle class="rw-chart-point" cx="' . self::num( $c['x'] )
				. '" cy="' . self::num( $c['y'] ) . '" r="3"><title>'
				. self::esc( $c['label'] . ': ' . $c['value'] ) . '</title></circle>';
			if ( $i % $step === 0 || $i === $count - 1 ) {
				$svg .= '<text class="rw-chart-xlabel" x="' . self::num( $c['x'] )
					. '" y="' . ( self::HEIGHT - self::PAD_BOTTOM + 16 )
					. '" text-anchor="middle">' . self::esc( $c['label'] ) . '</text>';
			}
		}

		$svg .= '</svg>';
		return self::wrap( $svg );
	}

	/**
	 * Gráfico de barras (ex.: dias mais lidos).
	 *
	 * @param array<int,array{label:string,value:int}> $points
	 * @param string $emptyMsg
	 * @return string
	 */
	public static function barChart( array $points, string $emptyMsg ): string {
		$points = self::normalize( $points );
		if ( $points === [] ) {
			return self::emptyState( $emptyMsg );
		}

		$max = self::maxValue( $points );
		$plotW = self::WIDTH - self::PAD_LEFT - self::PAD_RIGHT;
		$plotH = self::HEIGHT - self::PAD_TOP - self::PAD_BOTTOM;
		$count = count( $points );

		$slot = $plotW / $count;
		$barW = min( 56, $slot * 0.6 );

		$svg = self::openSvg();
		$svg .= self::gridAndYLabels( $max, $plotW, $plotH );

		foreach ( $points as $i => $p ) {
			$cx = self::PAD_LEFT + $slot * ( $i + 0.5 );
			$h = $plotH * ( $p['value'] / $max );
			$x = $cx - $barW / 2;
			$y = self::PAD_TOP + ( $plotH - $h );
			$svg .= '<rect class="rw-chart-bar" x="' . self::num( $x ) . '" y="' . self::num( $y )
				. '" width="' . self::num( $barW ) . '" height="' . self::num( $h )
				. '" rx="2"><title>' . self::esc( $p['label'] . ': ' . $p['value'] )
				. '</title></rect>';
			$svg .= '<text class="rw-chart-barvalue" x="' . self::num( $cx ) . '" y="'
				. self::num( $y - 4 ) . '" text-anchor="middle">' . self::esc( (string)$p['value'] )
				. '</text>';
			$svg .= '<text class="rw-chart-xlabel" x="' . self::num( $cx ) . '" y="'
				. ( self::HEIGHT - self::PAD_BOTTOM + 16 ) . '" text-anchor="middle">'
				. self::esc( $p['label'] ) . '</text>';
		}

		$svg .= '</svg>';
		return self::wrap( $svg );
	}

	// ---------- helpers ----------

	private static function openSvg(): string {
		return '<svg class="rw-chart" viewBox="0 0 ' . self::WIDTH . ' ' . self::HEIGHT
			. '" preserveAspectRatio="xMidYMid meet" role="img" xmlns="http://www.w3.org/2000/svg">';
	}

	private static function wrap( string $svg ): string {
		return '<div class="rw-chart-wrap">' . $svg . '</div>';
	}

	private static function emptyState( string $msg ): string {
		return '<div class="rw-chart-empty">' . self::esc( $msg ) . '</div>';
	}

	/**
	 * Linhas de grade horizontais + rótulos do eixo Y (0, meio, máx).
	 */
	private static function gridAndYLabels( int $max, float $plotW, float $plotH ): string {
		$out = '';
		$levels = [ 0, 0.5, 1 ];
		foreach ( $levels as $frac ) {
			$y = self::PAD_TOP + $plotH * ( 1 - $frac );
			$out .= '<line class="rw-chart-grid" x1="' . self::PAD_LEFT . '" y1="' . self::num( $y )
				. '" x2="' . self::num( self::PAD_LEFT + $plotW ) . '" y2="' . self::num( $y ) . '" />';
			$val = (int)round( $max * $frac );
			$out .= '<text class="rw-chart-ylabel" x="' . ( self::PAD_LEFT - 6 ) . '" y="'
				. self::num( $y + 4 ) . '" text-anchor="end">' . self::esc( (string)$val ) . '</text>';
		}
		return $out;
	}

	/**
	 * @param array<int,array{label:string,value:int}> $points
	 * @return array<int,array{label:string,value:int}>
	 */
	private static function normalize( array $points ): array {
		$out = [];
		foreach ( $points as $p ) {
			if ( !is_array( $p ) ) {
				continue;
			}
			$out[] = [
				'label' => (string)( $p['label'] ?? '' ),
				'value' => (int)( $p['value'] ?? 0 ),
			];
		}
		// Sem dados úteis se a lista estiver vazia ou tudo for zero.
		$total = 0;
		foreach ( $out as $p ) {
			$total += $p['value'];
		}
		return $total > 0 ? $out : [];
	}

	/**
	 * @param array<int,array{label:string,value:int}> $points
	 * @return int Nunca menor que 1 (evita divisão por zero).
	 */
	private static function maxValue( array $points ): int {
		$max = 1;
		foreach ( $points as $p ) {
			if ( $p['value'] > $max ) {
				$max = $p['value'];
			}
		}
		return $max;
	}

	private static function num( float $n ): string {
		return rtrim( rtrim( number_format( $n, 2, '.', '' ), '0' ), '.' );
	}

	private static function esc( string $s ): string {
		return htmlspecialchars( $s, ENT_QUOTES );
	}
}
