<?php
/**
 * Rank Math SEO Plugin Adapter.
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
 * Class Rank_Math_Adapter.
 */
class Rank_Math_Adapter implements SEO_Adapter_Interface {

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
		return 'Rank Math SEO';
	}

	public function get_slug(): string {
		return 'rank_math';
	}

	public function is_active(): bool {
		return SEO_Environment_Detector::is_rank_math_active();
	}

	public function register_hooks(): void {
		add_filter( 'rank_math/frontend/title', [ $this, 'filter_rank_math_title' ], 10, 1 );
		add_filter( 'rank_math/frontend/description', [ $this, 'filter_rank_math_description' ], 10, 1 );
		add_filter( 'rank_math/frontend/canonical', [ $this, 'filter_rank_math_canonical' ], 10, 1 );
		add_filter( 'rank_math/frontend/robots', [ $this, 'filter_rank_math_robots' ], 10, 1 );

		// OpenGraph.
		add_filter( 'rank_math/opengraph/facebook/title', [ $this, 'filter_rank_math_og_title' ], 10, 1 );
		add_filter( 'rank_math/opengraph/facebook/description', [ $this, 'filter_rank_math_og_desc' ], 10, 1 );
		add_filter( 'rank_math/opengraph/facebook/image', [ $this, 'filter_rank_math_og_image' ], 10, 1 );
		add_filter( 'rank_math/opengraph/facebook/url', [ $this, 'filter_rank_math_og_url' ], 10, 1 );

		// Twitter.
		add_filter( 'rank_math/opengraph/twitter/title', [ $this, 'filter_rank_math_twitter_title' ], 10, 1 );
		add_filter( 'rank_math/opengraph/twitter/description', [ $this, 'filter_rank_math_twitter_desc' ], 10, 1 );
		add_filter( 'rank_math/opengraph/twitter/image', [ $this, 'filter_rank_math_twitter_image' ], 10, 1 );

		// Schema.
		add_filter( 'rank_math/json_ld', [ $this, 'filter_rank_math_schema' ], 10, 2 );
	}

	protected function get_current_page(): ?object {
		return Request_Resolver::get_current_page();
	}

	public function filter_rank_math_title( string $title ): string {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $title;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		return ! empty( $seo['seo_title'] ) ? $seo['seo_title'] : $title;
	}

	public function filter_rank_math_description( string $desc ): string {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $desc;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		return ! empty( $seo['meta_description'] ) ? $seo['meta_description'] : $desc;
	}

	public function filter_rank_math_canonical( string $canonical ): string {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $canonical;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		return ! empty( $seo['canonical_url'] ) ? $seo['canonical_url'] : $canonical;
	}

	public function filter_rank_math_robots( array $robots ): array {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $robots;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		$is_indexable = ( 'index' === $seo['robots_index'] );
		$is_followable = ( 'follow' === $seo['robots_follow'] );

		$robots['index']  = $is_indexable ? 'index' : 'noindex';
		$robots['follow'] = $is_followable ? 'follow' : 'nofollow';

		return $robots;
	}

	public function filter_rank_math_og_title( string $title ): string {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $title;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		return ! empty( $seo['og_title'] ) ? $seo['og_title'] : $title;
	}

	public function filter_rank_math_og_desc( string $desc ): string {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $desc;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		return ! empty( $seo['og_description'] ) ? $seo['og_description'] : $desc;
	}

	public function filter_rank_math_og_image( string $image ): string {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $image;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		return ! empty( $seo['og_image'] ) ? $seo['og_image'] : $image;
	}

	public function filter_rank_math_og_url( string $url ): string {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $url;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		return ! empty( $seo['canonical_url'] ) ? $seo['canonical_url'] : $url;
	}

	public function filter_rank_math_twitter_title( string $title ): string {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $title;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		return ! empty( $seo['twitter_title'] ) ? $seo['twitter_title'] : $title;
	}

	public function filter_rank_math_twitter_desc( string $desc ): string {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $desc;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		return ! empty( $seo['twitter_description'] ) ? $seo['twitter_description'] : $desc;
	}

	public function filter_rank_math_twitter_image( string $image ): string {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $image;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		return ! empty( $seo['twitter_image'] ) ? $seo['twitter_image'] : $image;
	}

	public function filter_rank_math_schema( array $data, $jsonld ): array {
		$page = $this->get_current_page();
		if ( ! $page || ! $this->seo_manager ) {
			return $data;
		}
		$seo = $this->seo_manager->resolve_page_seo( $page );
		$custom_schema = Schema_Manager::generate_schema( $page, $seo );
		if ( ! empty( $custom_schema ) ) {
			$data['seoCityPageSchema'] = $custom_schema;
		}
		return $data;
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
			$robots['index']  = $is_indexable ? 'index' : 'noindex';
			$robots['follow'] = $is_followable ? 'follow' : 'nofollow';
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
