<?php
/**
 * AI Generation Context Object.
 *
 * Encapsulates normalized page identity, business profile, brand voice,
 * and approved facts for safe prompt construction.
 *
 * @package SeoCityPages\AI
 */

namespace SeoCityPages\AI;

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Country_Repository;
use SeoCityPages\Database\State_Repository;
use SeoCityPages\Database\City_Repository;
use SeoCityPages\Database\Service_Repository;
use SeoCityPages\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI_Context Class.
 */
class AI_Context {

	private int $page_id;
	private string $country;
	private string $state;
	private string $city;
	private string $service;
	private string $primary_keyword;
	private string $target_keywords;
	private array $company_profile;
	private array $brand_voice;
	private array $approved_facts;
	private array $options;

	/**
	 * Constructor.
	 *
	 * @param int    $page_id         Page ID.
	 * @param string $country         Country name.
	 * @param string $state           State / Region name.
	 * @param string $city            City name.
	 * @param string $service         Service name.
	 * @param array  $company_profile Admin business profile attributes.
	 * @param array  $brand_voice     Brand voice and writing instructions.
	 * @param array  $approved_facts  Admin-approved facts.
	 * @param array  $options         Generation options (e.g. content_length, temperature).
	 * @param string $primary_keyword Target primary SEO keyword.
	 * @param string $target_keywords Secondary/LSI keywords list.
	 */
	public function __construct(
		int $page_id,
		string $country,
		string $state,
		string $city,
		string $service,
		$company_profile = [],
		$brand_voice = [],
		$approved_facts = [],
		$options = [],
		string $primary_keyword = '',
		string $target_keywords = ''
	) {
		$this->page_id         = $page_id;
		$this->country         = sanitize_text_field( $country );
		$this->state           = sanitize_text_field( $state );
		$this->city            = sanitize_text_field( $city );
		$this->service         = sanitize_text_field( $service );
		$this->company_profile = is_array( $company_profile ) ? $company_profile : [];
		$this->brand_voice     = is_array( $brand_voice ) ? $brand_voice : [];
		$this->approved_facts  = is_array( $approved_facts ) ? $approved_facts : [];
		$this->options         = is_array( $options ) ? $options : [];
		$this->primary_keyword = sanitize_text_field( $primary_keyword ?: ( $this->options['primary_keyword'] ?? '' ) );
		$this->target_keywords = sanitize_textarea_field( $target_keywords ?: ( $this->options['target_keywords'] ?? '' ) );
	}

	/**
	 * Factory method to build AI_Context directly from a City Page ID.
	 *
	 * @param int   $page_id Page ID.
	 * @param array $options Additional runtime options.
	 * @return self|null
	 */
	public static function from_page_id( int $page_id, array $options = [] ): ?self {
		$page = City_Page_Repository::get( $page_id );
		if ( ! $page ) {
			return null;
		}

		$settings = get_option( Settings::OPTION_NAME, [] );

		$company_profile = [
			'company_name'        => sanitize_text_field( $settings['ai_company_name'] ?? get_bloginfo( 'name' ) ),
			'description'         => sanitize_textarea_field( $settings['ai_company_description'] ?? '' ),
			'core_services'       => sanitize_text_field( $settings['ai_core_services'] ?? '' ),
			'target_audience'     => sanitize_text_field( $settings['ai_target_audience'] ?? 'Business owners, founders, and marketing decision makers' ),
			'brand_positioning'   => sanitize_text_field( $settings['ai_brand_positioning'] ?? 'Results-driven, transparent, and authoritative' ),
			'differentiators'     => sanitize_textarea_field( $settings['ai_differentiators'] ?? '' ),
		];

		$brand_voice = [
			'tone'                 => sanitize_text_field( $settings['ai_tone'] ?? 'Professional, direct, clear, and compelling' ),
			'style'                => sanitize_text_field( $settings['ai_style'] ?? 'Modern agency, concise paragraphs, practical' ),
			'prohibited_phrases'   => sanitize_textarea_field( $settings['ai_prohibited_phrases'] ?? 'cheap SEO, guarantee #1 rankings, best in the world' ),
			'preferred_terminology'=> sanitize_textarea_field( $settings['ai_preferred_terminology'] ?? '' ),
			'content_length'       => sanitize_text_field( $settings['ai_content_length'] ?? 'medium' ),
		];

		$approved_facts = [];
		if ( ! empty( $page->city_description ) ) {
			$approved_facts[] = sanitize_textarea_field( $page->city_description );
		}
		if ( ! empty( $page->service_description ) ) {
			$approved_facts[] = sanitize_textarea_field( $page->service_description );
		}

		$primary_keyword = ! empty( $page->primary_keyword ) ? sanitize_text_field( $page->primary_keyword ) : '';
		$target_keywords = ! empty( $page->target_keywords ) ? sanitize_textarea_field( $page->target_keywords ) : '';

		// If no page-specific keywords set, query the uploaded Global Keyword Bank.
		if ( empty( $primary_keyword ) && empty( $target_keywords ) && class_exists( '\\SeoCityPages\\Database\\Keyword_Repository' ) ) {
			$bank_kws = \SeoCityPages\Database\Keyword_Repository::get_relevant_for_service(
				$page->service_name ?? '',
				$page->city_name ?? '',
				10
			);
			if ( ! empty( $bank_kws ) ) {
				$primary_keyword = array_shift( $bank_kws );
				$target_keywords = implode( ', ', $bank_kws );
			}
		}

		return new self(
			$page_id,
			$page->country_name ?? '',
			$page->state_name ?? '',
			$page->city_name ?? '',
			$page->service_name ?? '',
			$company_profile,
			$brand_voice,
			$approved_facts,
			$options,
			$primary_keyword,
			$target_keywords
		);
	}

	public function get_page_id(): int {
		return $this->page_id;
	}

	public function get_country(): string {
		return $this->country;
	}

	public function get_state(): string {
		return $this->state;
	}

	public function get_city(): string {
		return $this->city;
	}

	public function get_service(): string {
		return $this->service;
	}

	public function get_primary_keyword(): string {
		return $this->primary_keyword;
	}

	public function get_target_keywords(): string {
		return $this->target_keywords;
	}

	/**
	 * Get normalized array list of target keywords.
	 *
	 * @return string[]
	 */
	public function get_keywords_list(): array {
		$list = [];
		if ( ! empty( $this->primary_keyword ) ) {
			$list[] = $this->primary_keyword;
		}
		if ( ! empty( $this->target_keywords ) ) {
			$split = preg_split( '/[\r\n,]+/', $this->target_keywords );
			foreach ( $split as $kw ) {
				$clean = trim( $kw );
				if ( ! empty( $clean ) && ! in_array( $clean, $list, true ) ) {
					$list[] = $clean;
				}
			}
		}
		return $list;
	}

	public function get_company_profile(): array {
		return $this->company_profile;
	}

	public function get_brand_voice(): array {
		return $this->brand_voice;
	}

	public function get_approved_facts(): array {
		return $this->approved_facts;
	}

	public function get_options(): array {
		return $this->options;
	}

	public function get_option( string $key, $default = null ) {
		return $this->options[ $key ] ?? $default;
	}

	public function to_array(): array {
		return [
			'page_id'          => $this->page_id,
			'country'          => $this->country,
			'state'            => $this->state,
			'city'             => $this->city,
			'service'          => $this->service,
			'primary_keyword'  => $this->primary_keyword,
			'target_keywords'  => $this->target_keywords,
			'keywords_list'    => $this->get_keywords_list(),
			'company_profile'  => $this->company_profile,
			'brand_voice'      => $this->brand_voice,
			'approved_facts'   => $this->approved_facts,
			'options'          => $this->options,
		];
	}
}
