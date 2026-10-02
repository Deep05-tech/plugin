<?php
/**
 * Elementor Dynamic Tag: Page URL.
 *
 * @package SeoCityPages\Elementor\DynamicTags
 */

namespace SeoCityPages\Elementor\DynamicTags;

use SeoCityPages\Elementor\Page_Context;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Core\DynamicTags\Tag' ) ) {
	return;
}

/**
 * Tag_URL Class.
 */
class Tag_URL extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * Get tag internal name identifier.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'seo_city_url';
	}

	/**
	 * Get tag human-readable title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'SEO City Page URL', 'seo-city-pages' );
	}

	/**
	 * Get tag group identifier.
	 *
	 * @return string
	 */
	public function get_group(): string {
		return 'seo-city-pages';
	}

	/**
	 * Get tag categories.
	 *
	 * @return array
	 */
	public function get_categories(): array {
		$categories = [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
		if ( defined( '\Elementor\Modules\DynamicTags\Module::URL_CATEGORY' ) ) {
			$categories[] = \Elementor\Modules\DynamicTags\Module::URL_CATEGORY;
		}
		return $categories;
	}

	/**
	 * Render dynamic value safely to output.
	 *
	 * @return void
	 */
	public function render(): void {
		$url = Page_Context::get_url();
		echo esc_url( $url );
	}
}
