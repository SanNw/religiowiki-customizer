<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Homepage;

use Html;
use MediaWiki\MediaWikiServices;
use Title;

/**
 * Renderiza os blocos habilitados da Homepage Builder como HTML.
 *
 * Todo campo de texto livre configurado pelo admin passa por Html::element
 * ou htmlspecialchars() antes de sair — são dados estruturados (não CSS/JS
 * livre como na Fase 2), então DEVEM ser escapados normalmente (decisão de
 * segurança explícita da Fase 3).
 */
class HomepageRenderer {

	/**
	 * @param array<int,array<string,mixed>> $blocks Blocos habilitados, em ordem.
	 */
	public static function render( array $blocks ): string {
		$html = '<div class="religiowikicustomizer-homepage">';
		foreach ( $blocks as $block ) {
			$html .= self::renderBlock( $block );
		}
		$html .= '</div>';
		return $html;
	}

	private static function renderBlock( array $block ): string {
		switch ( $block['type'] ?? '' ) {
			case 'hero':
				return self::renderHero( $block );
			case 'cards':
				return self::renderCards( $block );
			case 'featured':
				return self::renderFeatured( $block );
			case 'categories':
				return self::renderCategories( $block );
			case 'search':
				return self::renderSearch( $block );
			default:
				return '';
		}
	}

	private static function renderHero( array $block ): string {
		$style = '';
		if ( !empty( $block['backgroundImage'] ) ) {
			$style = ' style="background-image:url(' . htmlspecialchars( $block['backgroundImage'], ENT_QUOTES ) . ')"';
		}

		$cta = '';
		if ( !empty( $block['ctaText'] ) && !empty( $block['ctaLink'] ) ) {
			$cta = Html::element( 'a', [
				'class' => 'religiowikicustomizer-hero-cta',
				'href' => $block['ctaLink'],
			], $block['ctaText'] );
		}

		return '<div class="religiowikicustomizer-block religiowikicustomizer-hero"' . $style . '>'
			. Html::element( 'h1', [], $block['title'] ?? '' )
			. Html::element( 'p', [ 'class' => 'religiowikicustomizer-hero-subtitle' ], $block['subtitle'] ?? '' )
			. $cta
			. '</div>';
	}

	private static function renderCards( array $block ): string {
		$items = json_decode( (string)( $block['itemsJson'] ?? '[]' ), true );
		if ( !is_array( $items ) ) {
			return '';
		}

		$html = '<div class="religiowikicustomizer-block religiowikicustomizer-cards">';
		foreach ( $items as $item ) {
			if ( !is_array( $item ) ) {
				continue;
			}
			$html .= '<div class="religiowikicustomizer-card">';
			if ( !empty( $item['icon'] ) ) {
				$html .= Html::element( 'span', [ 'class' => 'religiowikicustomizer-card-icon' ], $item['icon'] );
			}
			$html .= Html::element( 'h3', [], $item['title'] ?? '' );
			$html .= Html::element( 'p', [], $item['text'] ?? '' );
			if ( !empty( $item['link'] ) ) {
				$html .= Html::element( 'a', [ 'href' => $item['link'] ], wfMessage( 'religiowikicustomizer-card-readmore' )->text() );
			}
			$html .= '</div>';
		}
		$html .= '</div>';
		return $html;
	}

	private static function renderFeatured( array $block ): string {
		$pages = json_decode( (string)( $block['pagesJson'] ?? '[]' ), true );
		if ( !is_array( $pages ) || $pages === [] ) {
			return '';
		}

		$html = '<div class="religiowikicustomizer-block religiowikicustomizer-featured">';
		foreach ( $pages as $pageName ) {
			if ( !is_string( $pageName ) ) {
				continue;
			}
			$title = Title::newFromText( $pageName );
			if ( !$title || !$title->exists() ) {
				continue;
			}
			$html .= '<div class="religiowikicustomizer-featured-item">'
				. Html::element( 'a', [ 'href' => $title->getLocalURL() ], $title->getPrefixedText() )
				. '</div>';
		}
		$html .= '</div>';
		return $html;
	}

	private static function renderCategories( array $block ): string {
		$categories = json_decode( (string)( $block['categoriesJson'] ?? '[]' ), true );
		if ( !is_array( $categories ) || $categories === [] ) {
			return '';
		}

		$html = '<div class="religiowikicustomizer-block religiowikicustomizer-categories"><ul>';
		foreach ( $categories as $catName ) {
			if ( !is_string( $catName ) ) {
				continue;
			}
			$title = Title::makeTitleSafe( NS_CATEGORY, $catName );
			if ( !$title ) {
				continue;
			}
			$html .= '<li>' . Html::element( 'a', [ 'href' => $title->getLocalURL() ], $title->getText() ) . '</li>';
		}
		$html .= '</ul></div>';
		return $html;
	}

	private static function renderSearch( array $block ): string {
		$searchTitle = MediaWikiServices::getInstance()->getSpecialPageFactory()
			->getTitleForAlias( 'Search' ) ?? Title::newFromText( 'Special:Search' );

		return '<div class="religiowikicustomizer-block religiowikicustomizer-search-block">'
			. '<form action="' . htmlspecialchars( $searchTitle->getLocalURL(), ENT_QUOTES ) . '" method="get">'
			. '<input type="text" name="search" placeholder="'
			. htmlspecialchars( wfMessage( 'religiowikicustomizer-search-placeholder' )->text(), ENT_QUOTES )
			. '">'
			. '<button type="submit">' . htmlspecialchars( wfMessage( 'searchbutton' )->text(), ENT_QUOTES ) . '</button>'
			. '</form></div>';
	}
}
