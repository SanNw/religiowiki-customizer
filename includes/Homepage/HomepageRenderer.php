<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\Homepage;

use Html;
use MediaWiki\Extension\ReligiowikiCustomizer\Components\CardComponent;
use MediaWiki\Extension\ReligiowikiCustomizer\Components\LinkSanitizer;
use MediaWiki\MediaWikiServices;
use Title;

/**
 * Renderiza os blocos habilitados da Homepage Builder como HTML.
 *
 * Todo campo de texto livre configurado pelo admin passa por Html::element
 * ou htmlspecialchars() antes de sair — são dados estruturados (não CSS/JS
 * livre como na Fase 2), então DEVEM ser escapados normalmente (decisão de
 * segurança explícita da Fase 3). Links passam por
 * Components\LinkSanitizer — mesma checagem de protocolo que a Fase 4
 * exige dos parser tags, aplicada aqui também por consistência/defesa em
 * profundidade, embora a Homepage só seja editável por quem já tem
 * `editinterface`.
 *
 * Cards/Notícias/Livros reaproveitam CardComponent::buildHtml (Fase 4) em
 * vez de duplicar o markup do card — integração pedida explicitamente pelo
 * prompt mestre da Fase 4.
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
				return self::renderCardGrid( $block, 'items', static function ( array $item ): array {
					return $item;
				} );
			case 'featured':
				return self::renderFeatured( $block );
			case 'categories':
				return self::renderCategories( $block );
			case 'search':
				return self::renderSearch( $block );
			case 'noticias':
				return self::renderCardGrid( $block, 'items', static function ( array $item ): array {
					$date = trim( (string)( $item['date'] ?? '' ) );
					$text = trim( (string)( $item['text'] ?? '' ) );
					return [
						'title' => $item['title'] ?? '',
						'text' => $date !== '' ? "{$date} — {$text}" : $text,
						'link' => $item['link'] ?? '',
						'icon' => $item['icon'] ?? '📰',
					];
				} );
			case 'livros':
				return self::renderCardGrid( $block, 'items', static function ( array $item ): array {
					return [
						'title' => $item['title'] ?? '',
						'text' => $item['author'] ?? '',
						'link' => $item['link'] ?? '',
						'icon' => $item['icon'] ?? '📖',
					];
				} );
			case 'estatisticas':
				return self::renderStats( $block );
			default:
				return '';
		}
	}

	private static function renderHero( array $block ): string {
		$style = '';
		if ( !empty( $block['backgroundImage'] ) ) {
			$bg = LinkSanitizer::sanitize( (string)$block['backgroundImage'] );
			if ( $bg !== null ) {
				$style = ' style="background-image:url(' . htmlspecialchars( $bg, ENT_QUOTES ) . ')"';
			}
		}

		$cta = '';
		if ( !empty( $block['ctaText'] ) && !empty( $block['ctaLink'] ) ) {
			$url = LinkSanitizer::sanitize( (string)$block['ctaLink'] );
			if ( $url !== null ) {
				$cta = Html::element( 'a', [
					'class' => 'religiowikicustomizer-hero-cta',
					'href' => $url,
				], $block['ctaText'] );
			}
		}

		return '<div class="religiowikicustomizer-block religiowikicustomizer-hero"' . $style . '>'
			. Html::element( 'h1', [], $block['title'] ?? '' )
			. Html::element( 'p', [ 'class' => 'religiowikicustomizer-hero-subtitle' ], $block['subtitle'] ?? '' )
			. $cta
			. '</div>';
	}

	/**
	 * Bloco genérico de "grid de cards" — usado por Cards, Notícias e
	 * Livros, cada um só mudando como o item bruto do JSON vira o array
	 * {title,text,link,icon} que CardComponent::buildHtml espera.
	 *
	 * @param array $block
	 * @param string $unused Mantido só por clareza de assinatura.
	 * @param callable $mapItem
	 */
	private static function renderCardGrid( array $block, string $unused, callable $mapItem ): string {
		$items = json_decode( (string)( $block['itemsJson'] ?? '[]' ), true );
		if ( !is_array( $items ) || $items === [] ) {
			return '';
		}

		$html = '<div class="religiowikicustomizer-block religiowikicustomizer-cards">';
		foreach ( $items as $item ) {
			if ( !is_array( $item ) ) {
				continue;
			}
			$html .= CardComponent::buildHtml( $mapItem( $item ) );
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

	private static function renderStats( array $block ): string {
		$items = json_decode( (string)( $block['itemsJson'] ?? '[]' ), true );
		if ( !is_array( $items ) || $items === [] ) {
			return '';
		}

		$html = '<div class="religiowikicustomizer-block religiowikicustomizer-stats">';
		foreach ( $items as $item ) {
			if ( !is_array( $item ) ) {
				continue;
			}
			$html .= '<div class="religiowikicustomizer-stat">'
				. Html::element( 'span', [ 'class' => 'religiowikicustomizer-stat-value' ], (string)( $item['value'] ?? '' ) )
				. Html::element( 'span', [ 'class' => 'religiowikicustomizer-stat-label' ], (string)( $item['label'] ?? '' ) )
				. '</div>';
		}
		$html .= '</div>';
		return $html;
	}
}
