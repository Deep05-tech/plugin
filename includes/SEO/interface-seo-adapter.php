<?php
/**
 * SEO Adapter Interface.
 *
 * @package SeoCityPages\SEO
 */

namespace SeoCityPages\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface SEO_Adapter_Interface.
 */
interface SEO_Adapter_Interface {

	/**
	 * Get human-readable adapter name.
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Get adapter slug.
	 *
	 * @return string
	 */
	public function get_slug(): string;

	/**
	 * Check if the target SEO plugin is active.
	 *
	 * @return bool
	 */
	public function is_active(): bool;

	/**
	 * Register integration hooks into WordPress and target SEO plugin.
	 *
	 * @return void
	 */
	public function register_hooks(): void;

	/**
	 * Filter SEO Title.
	 *
	 * @param string      $title Default title.
	 * @param object|null $page  City page record.
	 * @return string
	 */
	public function filter_title( string $title, ?object $page ): string;

	/**
	 * Filter Meta Description.
	 *
	 * @param string      $description Default description.
	 * @param object|null $page        City page record.
	 * @return string
	 */
	public function filter_description( string $description, ?object $page ): string;

	/**
	 * Filter Canonical URL.
	 *
	 * @param string      $canonical Default canonical URL.
	 * @param object|null $page      City page record.
	 * @return string
	 */
	public function filter_canonical( string $canonical, ?object $page ): string;

	/**
	 * Filter Robots Meta Directives.
	 *
	 * @param array|string $robots Current robots directives.
	 * @param object|null  $page   City page record.
	 * @return array|string
	 */
	public function filter_robots( $robots, ?object $page );

	/**
	 * Filter OpenGraph Metadata.
	 *
	 * @param array       $og_data Current OpenGraph data.
	 * @param object|null $page    City page record.
	 * @return array
	 */
	public function filter_opengraph( array $og_data, ?object $page ): array;

	/**
	 * Filter Twitter / X Card Metadata.
	 *
	 * @param array       $twitter_data Current Twitter metadata.
	 * @param object|null $page         City page record.
	 * @return array
	 */
	public function filter_twitter( array $twitter_data, ?object $page ): array;

	/**
	 * Filter Schema Structured Data.
	 *
	 * @param array       $schema Current schema data.
	 * @param object|null $page   City page record.
	 * @return array
	 */
	public function filter_schema( array $schema, ?object $page ): array;
}
