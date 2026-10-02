<?php
/**
 * US Counties & States Data Provider.
 *
 * @package SeoCityPages\Data
 */

namespace SeoCityPages\Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * County Dataset Class.
 */
class County_Dataset {

	/**
	 * List of all 50 US States + DC.
	 *
	 * @var array<string, string>
	 */
	public const US_STATES = [
		'AL' => 'Alabama',
		'AK' => 'Alaska',
		'AZ' => 'Arizona',
		'AR' => 'Arkansas',
		'CA' => 'California',
		'CO' => 'Colorado',
		'CT' => 'Connecticut',
		'DE' => 'Delaware',
		'DC' => 'District of Columbia',
		'FL' => 'Florida',
		'GA' => 'Georgia',
		'HI' => 'Hawaii',
		'ID' => 'Idaho',
		'IL' => 'Illinois',
		'IN' => 'Indiana',
		'IA' => 'Iowa',
		'KS' => 'Kansas',
		'KY' => 'Kentucky',
		'LA' => 'Louisiana',
		'ME' => 'Maine',
		'MD' => 'Maryland',
		'MA' => 'Massachusetts',
		'MI' => 'Michigan',
		'MN' => 'Minnesota',
		'MS' => 'Mississippi',
		'MO' => 'Missouri',
		'MT' => 'Montana',
		'NE' => 'Nebraska',
		'NV' => 'Nevada',
		'NH' => 'New Hampshire',
		'NJ' => 'New Jersey',
		'NM' => 'New Mexico',
		'NY' => 'New York',
		'NC' => 'North Carolina',
		'ND' => 'North Dakota',
		'OH' => 'Ohio',
		'OK' => 'Oklahoma',
		'OR' => 'Oregon',
		'PA' => 'Pennsylvania',
		'RI' => 'Rhode Island',
		'SC' => 'South Carolina',
		'SD' => 'South Dakota',
		'TN' => 'Tennessee',
		'TX' => 'Texas',
		'UT' => 'Utah',
		'VT' => 'Vermont',
		'VA' => 'Virginia',
		'WA' => 'Washington',
		'WV' => 'West Virginia',
		'WI' => 'Wisconsin',
		'WY' => 'Wyoming',
	];

	/**
	 * Get all US States.
	 *
	 * @return array<string, string> State code => State name.
	 */
	public static function get_states(): array {
		return self::US_STATES;
	}

	/**
	 * Get State name from Code.
	 *
	 * @param string $code 2-letter state code.
	 * @return string State name or code if not found.
	 */
	public static function get_state_name( string $code ): string {
		$code = strtoupper( trim( $code ) );
		return self::US_STATES[ $code ] ?? $code;
	}

	/**
	 * Get complete list of US Counties mapped by State Code.
	 *
	 * @return array<string, string[]> Array of state code => list of county names.
	 */
	public static function get_all_counties_by_state(): array {
		static $counties_cache = null;
		if ( null !== $counties_cache ) {
			return $counties_cache;
		}

		$file = __DIR__ . '/counties-data.php';
		if ( file_exists( $file ) ) {
			$counties_cache = require $file;
		} else {
			$counties_cache = self::get_fallback_counties();
		}

		return $counties_cache;
	}

	/**
	 * Get counties for specific state(s).
	 *
	 * @param string|string[] $states Single state code/name or array of state codes.
	 * @return array<int, array{state_code: string, state_name: string, county_name: string, county_slug: string}>
	 */
	public static function get_counties( $states = [] ): array {
		$all = self::get_all_counties_by_state();
		$results = [];

		$filter_states = [];
		if ( ! empty( $states ) ) {
			if ( is_string( $states ) ) {
				$filter_states = array_map( 'strtoupper', array_map( 'trim', explode( ',', $states ) ) );
			} elseif ( is_array( $states ) ) {
				$filter_states = array_map( 'strtoupper', array_map( 'trim', $states ) );
			}
		}

		foreach ( $all as $state_code => $counties ) {
			$state_code_upper = strtoupper( $state_code );
			$state_name = self::get_state_name( $state_code_upper );

			if ( ! empty( $filter_states ) && ! in_array( $state_code_upper, $filter_states, true ) && ! in_array( strtoupper( $state_name ), $filter_states, true ) ) {
				continue;
			}

			foreach ( $counties as $county ) {
				$county_clean = trim( $county );
				$slug = function_exists( 'sanitize_title' )
					? sanitize_title( $county_clean )
					: strtolower( trim( preg_replace( '/[^a-zA-Z0-9]+/', '-', $county_clean ), '-' ) );
				$results[] = [
					'state_code'  => $state_code_upper,
					'state_name'  => $state_name,
					'county_name' => $county_clean,
					'county_slug' => $slug,
				];
			}
		}

		return $results;
	}

	/**
	 * Count total counties for given state filter.
	 *
	 * @param string|string[] $states Optional state filter.
	 * @return int Total counties count.
	 */
	public static function count_counties( $states = [] ): int {
		return count( self::get_counties( $states ) );
	}

	/**
	 * Fallback counties list for top states if data file is absent.
	 *
	 * @return array<string, string[]>
	 */
	private static function get_fallback_counties(): array {
		return [
			'CA' => [ 'Los Angeles County', 'San Diego County', 'Orange County', 'Riverside County', 'San Bernardino County', 'Santa Clara County', 'Alameda County', 'Sacramento County', 'Contra Costa County', 'Fresno County', 'Kern County', 'San Francisco County', 'Ventura County', 'San Mateo County', 'San Joaquin County' ],
			'TX' => [ 'Harris County', 'Dallas County', 'Tarrant County', 'Bexar County', 'Travis County', 'Collin County', 'Denton County', 'Hidalgo County', 'El Paso County', 'Fort Bend County', 'Montgomery County', 'Williamson County', 'Cameron County', 'Nueces County', 'Brazoria County' ],
			'FL' => [ 'Miami-Dade County', 'Broward County', 'Palm Beach County', 'Hillsborough County', 'Orange County', 'Pinellas County', 'Duval County', 'Lee County', 'Polk County', 'Brevard County', 'Volusia County', 'Pasco County', 'Seminole County', 'Sarasota County', 'Manatee County' ],
			'NY' => [ 'Kings County (Brooklyn)', 'Queens County', 'New York County (Manhattan)', 'Suffolk County', 'Bronx County', 'Nassau County', 'Westchester County', 'Erie County', 'Monroe County', 'Richmond County (Staten Island)', 'Onondaga County', 'Orange County', 'Rockland County', 'Albany County', 'Dutchess County' ],
		];
	}
}
