<?php
/**
 * WordPress Native SEO Adapter.
 *
 * @package SeoCityPages\SEO\Adapters
 */

namespace SeoCityPages\SEO\Adapters;

use SeoCityPages\SEO\SEO_Adapter_Interface;
use SeoCityPages\SEO\Schema_Manager;
use SeoCityPages\SEO\SEO_Settings;
use SeoCityPages\URL\Request_Resolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Native_Adapter.
 */
class Native_Adapter implements SEO_Adapter_Interface {

	/**
	 * Reference to parent SEO Manager for resolving SEO payload.
	 *
	 * @var object|null
	 */
	protected $seo_manager;

	/**
	 * Constructor.
	 *
	 * @param object|null $seo_manager SEO Manager instance.
	 */
	public function __construct( $seo_manager = null ) {
		$this->seo_manager = $seo_manager;
	}

	/**
	 * Get adapter name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Native WordPress SEO';
	}

	/**
	 * Get adapter slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'native';
	}

	/**
	 * Always true as native fallback.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return true;
	}

	/**
	 * Register native WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Document Title.
		add_filter( 'pre_get_document_title', [ $this, 'filter_native_document_title' ], 15 );
		add_filter( 'document_title_parts', [ $this, 'filter_native_title_parts' ], 15 );

		// Robots Meta.
		add_filter( 'wp_robots', [ $this, 'filter_native_robots' ], 15 );

		// wp_head tags: meta description, canonical, open graph, twitter, schema.
		add_action( 'wp_head', [ $this, 'output_head_tags' ], 2 );
	}

	/**
	 * Get current city page.
	 *
	 * @return object|null
	 */
	protected function get_current_page(): ?object {
		return Request_Resolver::get_current_page();
	}

	/**
	 * Filter document title via pre_get_document_title.
	 *
	 * @param string $title Default title.
	 * @return string
	 */
	public function filter_native_document_title( string $title ): string {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $title;
		}

		$seo = $this->seo_manager->resolve_page_seo( $page );
		return ! empty( $seo['seo_title'] ) ? $seo['seo_title'] : $title;
	}

	/**
	 * Filter title parts array.
	 *
	 * @param array $title_parts Parts.
	 * @return array
	 */
	public function filter_native_title_parts( array $title_parts ): array {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $title_parts;
		}

		$seo = $this->seo_manager->resolve_page_seo( $page );
		if ( ! empty( $seo['seo_title'] ) ) {
			return [ 'title' => $seo['seo_title'] ];
		}

		return $title_parts;
	}

	/**
	 * Filter native wp_robots array.
	 *
	 * @param array $robots Robots flags.
	 * @return array
	 */
	public function filter_native_robots( array $robots ): array {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $robots;
		}

		$seo = $this->seo_manager->resolve_page_seo( $page );
		$is_indexable = ( 'index' === $seo['robots_index'] );
		$is_followable = ( 'follow' === $seo['robots_follow'] );

		$robots['noindex']  = ! $is_indexable;
		$robots['nofollow'] = ! $is_followable;

		if ( $is_indexable ) {
			unset( $robots['noindex'] );
		}
		if ( $is_followable ) {
			unset( $robots['nofollow'] );
		}

		return $robots;
	}

	/**
	 * Output meta tags and JSON-LD schema into wp_head.
	 *
	 * @return void
	 */
	public function output_head_tags(): void {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return;
		}

		$seo = $this->seo_manager->resolve_page_seo( $page );

		echo "\n<!-- SEO City Pages Metadata -->\n";

		// Meta Description.
		if ( ! empty( $seo['meta_description'] ) ) {
			printf( "<meta name=\"description\" content=\"%s\" />\n", esc_attr( $seo['meta_description'] ) );
		}

		// Canonical URL.
		if ( ! empty( $seo['canonical_url'] ) ) {
			printf( "<link rel=\"canonical\" href=\"%s\" />\n", esc_url( $seo['canonical_url'] ) );
		}

		// Open Graph.
		if ( ! empty( $seo['og_title'] ) ) {
			printf( "<meta property=\"og:title\" content=\"%s\" />\n", esc_attr( $seo['og_title'] ) );
		}
		if ( ! empty( $seo['og_description'] ) ) {
			printf( "<meta property=\"og:description\" content=\"%s\" />\n", esc_attr( $seo['og_description'] ) );
		}
		if ( ! empty( $seo['canonical_url'] ) ) {
			printf( "<meta property=\"og:url\" content=\"%s\" />\n", esc_url( $seo['canonical_url'] ) );
		}
		printf( "<meta property=\"og:type\" content=\"website\" />\n" );
		if ( ! empty( $seo['og_image'] ) ) {
			printf( "<meta property=\"og:image\" content=\"%s\" />\n", esc_url( $seo['og_image'] ) );
		}

		// Twitter Card.
		$twitter_card = SEO_Settings::get( 'default_twitter_card', 'summary_large_image' );
		printf( "<meta name=\"twitter:card\" content=\"%s\" />\n", esc_attr( $twitter_card ) );

		if ( ! empty( $seo['twitter_title'] ) ) {
			printf( "<meta name=\"twitter:title\" content=\"%s\" />\n", esc_attr( $seo['twitter_title'] ) );
		}
		if ( ! empty( $seo['twitter_description'] ) ) {
			printf( "<meta name=\"twitter:description\" content=\"%s\" />\n", esc_attr( $seo['twitter_description'] ) );
		}
		if ( ! empty( $seo['twitter_image'] ) ) {
			printf( "<meta name=\"twitter:image\" content=\"%s\" />\n", esc_url( $seo['twitter_image'] ) );
		}

		echo "<!-- End SEO City Pages Metadata -->\n";

		// Schema JSON-LD.
		$schema_html = Schema_Manager::render_json_ld( $page, $seo );
		if ( ! empty( $schema_html ) ) {
			echo $schema_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public function filter_title( string $title, ?object $page ): string {
		if ( ! $page || ! $this->seo_manager ) {
			return $title;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		return $seo['seo_title'] ?: $title;
	}

	public function filter_description( string $description, ?object $page ): string {
		if ( ! $page || ! $this->seo_manager ) {
			return $description;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		return $seo['meta_description'] ?: $description;
	}

	public function filter_canonical( string $canonical, ?object $page ): string {
		if ( ! $page || ! $this->seo_manager ) {
			return $canonical;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		return $seo['canonical_url'] ?: $canonical;
	}

	public function filter_robots( $robots, ?object $page ) {
		if ( ! $page || ! $this->seo_manager ) {
			return $robots;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		$is_indexable = ( 'index' === $seo['robots_index'] );
		$is_followable = ( 'follow' === $seo['robots_follow'] );

		if ( is_array( $robots ) ) {
			if ( $is_indexable ) {
				unset( $robots['noindex'] );
			} else {
				$robots['noindex'] = true;
			}

			if ( $is_followable ) {
				unset( $robots['nofollow'] );
			} else {
				$robots['nofollow'] = true;
			}
			return $robots;
		}

		return sprintf( '%s, %s', $is_indexable ? 'index' : 'noindex', $is_followable ? 'follow' : 'nofollow' );
	}

	public function filter_opengraph( array $og_data, ?object $page ): array {
		if ( ! $page || ! $this->seo_manager ) {
			return $og_data;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		return array_merge( $og_data, [
			'title'       => $seo['og_title'],
			'description' => $seo['og_description'],
			'image'       => $seo['og_image'],
			'url'         => $seo['canonical_url'],
		] );
	}

	public function filter_twitter( array $twitter_data, ?object $page ): array {
		if ( ! $page || ! $this->seo_manager ) {
			return $twitter_data;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		return array_merge( $twitter_data, [
			'title'       => $seo['twitter_title'],
			'description' => $seo['twitter_description'],
			'image'       => $seo['twitter_image'],
		] );
	}

	public function filter_schema( array $schema, ?object $page ): array {
		if ( ! $page || ! $this->seo_manager ) {
			return $schema;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		return Schema_Manager::generate_schema( $page, $seo );
	}
}
