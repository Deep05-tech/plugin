<?php
/**
 * All in One SEO Plugin Adapter.
 *
 * @package SeoCityPages\SEO\Adapters
 */

namespace SeoCityPages\SEO\Adapters;

use SeoCityPages\SEO\SEO_Adapter_Interface;
use SeoCityPages\SEO\SEO_Environment_Detector;
use SeoCityPages\SEO\Schema_Manager;
use SeoCityPages\URL\Request_Resolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AIOSEO_Adapter.
 */
class AIOSEO_Adapter implements SEO_Adapter_Interface {

	/**
	 * SEO Manager.
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

	public function get_name(): string {
		return 'All in One SEO';
	}

	public function get_slug(): string {
		return 'aioseo';
	}

	public function is_active(): bool {
		return SEO_Environment_Detector::is_aioseo_active();
	}

	public function register_hooks(): void {
		add_filter( 'aioseo_title', [ $this, 'filter_aioseo_title' ], 10, 1 );
		add_filter( 'aioseo_description', [ $this, 'filter_aioseo_description' ], 10, 1 );
		add_filter( 'aioseo_canonical_url', [ $this, 'filter_aioseo_canonical' ], 10, 1 );
		add_filter( 'aioseo_robots_meta', [ $this, 'filter_aioseo_robots' ], 10, 1 );

		// OpenGraph & Twitter.
		add_filter( 'aioseo_facebook_tags', [ $this, 'filter_aioseo_facebook' ], 10, 1 );
		add_filter( 'aioseo_twitter_tags', [ $this, 'filter_aioseo_twitter' ], 10, 1 );

		// Schema.
		add_filter( 'aioseo_schema_output', [ $this, 'filter_aioseo_schema' ], 10, 1 );
	}

	protected function get_current_page(): ?object {
		return Request_Resolver::get_current_page();
	}

	public function filter_aioseo_title( string $title ): string {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $title;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		return ! empty( $seo['seo_title'] ) ? $seo['seo_title'] : $title;
	}

	public function filter_aioseo_description( string $desc ): string {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $desc;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		return ! empty( $seo['meta_description'] ) ? $seo['meta_description'] : $desc;
	}

	public function filter_aioseo_canonical( string $canonical ): string {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $canonical;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		return ! empty( $seo['canonical_url'] ) ? $seo['canonical_url'] : $canonical;
	}

	public function filter_aioseo_robots( array $robots ): array {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $robots;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		$is_indexable = ( 'index' === $seo['robots_index'] );
		$is_followable = ( 'follow' === $seo['robots_follow'] );

		$robots['noindex']  = ! $is_indexable;
		$robots['nofollow'] = ! $is_followable;

		return $robots;
	}

	public function filter_aioseo_facebook( array $tags ): array {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $tags;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		if ( ! empty( $seo['og_title'] ) ) {
			$tags['og:title'] = $seo['og_title'];
		}
		if ( ! empty( $seo['og_description'] ) ) {
			$tags['og:description'] = $seo['og_description'];
		}
		if ( ! empty( $seo['canonical_url'] ) ) {
			$tags['og:url'] = $seo['canonical_url'];
		}
		if ( ! empty( $seo['og_image'] ) ) {
			$tags['og:image'] = $seo['og_image'];
		}
		return $tags;
	}

	public function filter_aioseo_twitter( array $tags ): array {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $tags;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		if ( ! empty( $seo['twitter_title'] ) ) {
			$tags['twitter:title'] = $seo['twitter_title'];
		}
		if ( ! empty( $seo['twitter_description'] ) ) {
			$tags['twitter:description'] = $seo['twitter_description'];
		}
		if ( ! empty( $seo['twitter_image'] ) ) {
			$tags['twitter:image'] = $seo['twitter_image'];
		}
		return $tags;
	}

	public function filter_aioseo_schema( array $schema ): array {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $schema;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		$custom_schema = Schema_Manager::generate_schema( $page, $seo );
		if ( ! empty( $custom_schema ) ) {
			$schema[] = $custom_schema;
		}
		return $schema;
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
			$robots['noindex']  = ! $is_indexable;
			$robots['nofollow'] = ! $is_followable;
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
