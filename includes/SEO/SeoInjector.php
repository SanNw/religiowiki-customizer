<?php

namespace MediaWiki\Extension\ReligiowikiCustomizer\SEO;

use MediaWiki\Extension\ReligiowikiCustomizer\Services\SeoSettingsStore;
use OutputPage;

/**
 * Injeta as tags de SEO no `<head>` — Meta Description, OpenGraph, Twitter
 * Card, canonical, robots e JSON-LD. Chamado a partir de
 * HookHandler::onBeforePageDisplay (decisão de arquitetura 1 da Fase 6:
 * BeforePageDisplay pras tags estáticas/de config; a Meta Description por
 * página já chega pronta via OutputPage::getProperty(), populada durante o
 * parse pelo parser function `{{#rwseo:description|...}}` —
 * ver SeoParserFunction).
 */
class SeoInjector {

	public const DESCRIPTION_PROPERTY = 'rwc-meta-description';

	public static function inject( OutputPage $out ): void {
		$settings = SeoSettingsStore::newFromGlobalState()->getSettings();
		$title = $out->getTitle();
		if ( !$title ) {
			return;
		}

		$description = (string)( $out->getProperty( self::DESCRIPTION_PROPERTY ) ?: $settings['defaultDescription'] );

		$out->addMeta( 'description', $description );
		$out->setCanonicalUrl( $title->getFullURL() );

		// OpenGraph/Twitter usam o atributo `property`, não `name` —
		// addMeta() só gera `name=`, então esses vão via addHeadItem com
		// HTML já escapado manualmente.
		$ogTags = [
			'og:title' => $title->getPrefixedText(),
			'og:description' => $description,
			'og:type' => 'article',
			'og:url' => $title->getFullURL(),
		];
		if ( $settings['defaultOgImage'] !== '' ) {
			$ogTags['og:image'] = $settings['defaultOgImage'];
		}
		foreach ( $ogTags as $property => $content ) {
			$out->addHeadItem(
				'rwc-og-' . $property,
				'<meta property="' . htmlspecialchars( $property, ENT_QUOTES )
					. '" content="' . htmlspecialchars( $content, ENT_QUOTES ) . '">'
			);
		}

		$out->addHeadItem( 'rwc-twitter-card',
			'<meta name="twitter:card" content="summary">' );
		if ( $settings['twitterHandle'] !== '' ) {
			$handle = ltrim( $settings['twitterHandle'], '@' );
			$out->addHeadItem( 'rwc-twitter-site',
				'<meta name="twitter:site" content="@' . htmlspecialchars( $handle, ENT_QUOTES ) . '">' );
		}

		// Robots: noindex fora do namespace principal (páginas especiais,
		// discussão, categoria etc.) — regra declarada explicitamente
		// (decisão de arquitetura 2 da Fase 6), não uma lista arbitrária.
		if ( $title->getNamespace() !== NS_MAIN ) {
			$out->addMeta( 'robots', 'noindex,follow' );
		}

		self::injectJsonLd( $out, $title, $description );
	}

	private static function injectJsonLd( OutputPage $out, \Title $title, string $description ): void {
		if ( $title->isMainPage() ) {
			$settings = SeoSettingsStore::newFromGlobalState()->getSettings();
			$siteName = $settings['siteNameOverride'] !== ''
				? $settings['siteNameOverride']
				: $out->getConfig()->get( 'Sitename' );
			$json = [
				'@context' => 'https://schema.org',
				'@type' => 'WebSite',
				'name' => $siteName,
				'url' => $title->getFullURL(),
			];
		} elseif ( $title->getNamespace() === NS_MAIN ) {
			$json = [
				'@context' => 'https://schema.org',
				'@type' => 'Article',
				'headline' => $title->getPrefixedText(),
				'description' => $description,
				'url' => $title->getFullURL(),
			];
		} else {
			return;
		}

		$out->addHeadItem(
			'rwc-jsonld',
			'<script type="application/ld+json">'
				. json_encode( $json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
				. '</script>'
		);
	}
}
