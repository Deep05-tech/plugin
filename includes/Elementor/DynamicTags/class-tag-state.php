<?php
/**
 * Elementor Dynamic Tag: State / Region Name.
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
 * Tag_State Class.
 */
class Tag_State extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * Get tag internal name identifier.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'seo_city_state';
	}

	/**
	 * Get tag human-readable title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'State / Region Name', 'seo-city-pages' );
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
		return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
	}

	/**
	 * Render dynamic value safely to output.
	 *
	 * @return void
	 */
	public function render(): void {
		$state = Page_Context::get_state();
		echo esc_html( $state );
	}
}
