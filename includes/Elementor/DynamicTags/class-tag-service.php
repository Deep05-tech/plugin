<?php
/**
 * Elementor Dynamic Tag: Service Name.
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
 * Tag_Service Class.
 */
class Tag_Service extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * Get tag internal name identifier.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'seo_city_service';
	}

	/**
	 * Get tag human-readable title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Service Name', 'seo-city-pages' );
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
		$service = Page_Context::get_service();
		echo esc_html( $service );
	}
}
