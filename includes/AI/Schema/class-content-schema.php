<?php
/**
 * Structured Output Content Schema Definition.
 *
 * Defines versioned JSON Schema for OpenAI Structured Outputs API
 * and server-side structure validation.
 *
 * @package SeoCityPages\AI\Schema
 */

namespace SeoCityPages\AI\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content_Schema Class.
 */
class Content_Schema {

	public const SCHEMA_VERSION = '1.0';

	/**
	 * Get OpenAI-compatible Structured Output JSON Schema definition.
	 *
	 * @return array
	 */
	public static function get_openai_json_schema(): array {
		return [
			'name'   => 'seo_city_landing_page_content',
			'strict' => true,
			'schema' => [
				'type'                 => 'object',
				'properties'           => [
					'hero' => [
						'type'                 => 'object',
						'properties'           => [
							'title'       => [ 'type' => 'string', 'description' => 'Compelling hero H1 headline mentioning service and city naturally.' ],
							'description' => [ 'type' => 'string', 'description' => 'High-converting hero value proposition without fake statistics.' ],
							'cta_text'    => [ 'type' => 'string', 'description' => 'Action-oriented button label e.g. Get Free Proposal.' ],
						],
						'required'             => [ 'title', 'description', 'cta_text' ],
						'additionalProperties' => false,
					],
					'introduction' => [
						'type'                 => 'object',
						'properties'           => [
							'heading' => [ 'type' => 'string', 'description' => 'Strategic section heading for introduction.' ],
							'body'    => [ 'type' => 'string', 'description' => '2-3 sentences explaining market search challenges and practical client value.' ],
						],
						'required'             => [ 'heading', 'body' ],
						'additionalProperties' => false,
					],
					'service_overview' => [
						'type'                 => 'object',
						'properties'           => [
							'heading' => [ 'type' => 'string', 'description' => 'Comprehensive service breakdown heading.' ],
							'body'    => [ 'type' => 'string', 'description' => 'Detailed explanation of service deliverables.' ],
						],
						'required'             => [ 'heading', 'body' ],
						'additionalProperties' => false,
					],
					'benefits' => [
						'type'        => 'array',
						'description' => '3 to 5 core differentiators / business advantages.',
						'items'       => [
							'type'                 => 'object',
							'properties'           => [
								'title'       => [ 'type' => 'string', 'description' => 'Benefit title.' ],
								'description' => [ 'type' => 'string', 'description' => 'Concise explanation of practical benefit.' ],
							],
							'required'             => [ 'title', 'description' ],
							'additionalProperties' => false,
						],
					],
					'metro_stats' => [
						'type'        => 'array',
						'description' => '4 to 5 key market metrics for the metro area (e.g. Population, Business Count, Search Competition Index, Review Target, Rank Horizon).',
						'items'       => [
							'type'                 => 'object',
							'properties'           => [
								'value'       => [ 'type' => 'string', 'description' => 'Numeric or formatted value e.g. 2.8M+, 120k+, 84%, 4.8★, 90 Days.' ],
								'label'       => [ 'type' => 'string', 'description' => 'Short metric label e.g. Metro Population, Commercial Entities, Aggregator Density.' ],
								'description' => [ 'type' => 'string', 'description' => 'Context or benchmark explanation for this metric.' ],
							],
							'required'             => [ 'value', 'label', 'description' ],
							'additionalProperties' => false,
						],
					],
					'market_anatomy' => [
						'type'                 => 'object',
						'description'          => 'Market overview breaking down local geographic search nuances.',
						'properties'           => [
							'kicker'           => [ 'type' => 'string', 'description' => 'Short bold kicker quote e.g. Multiple markets in one city. Ranking here is an ecosystem of hyper-local intent.' ],
							'heading'          => [ 'type' => 'string', 'description' => 'Section headline explaining local search dynamics.' ],
							'lead_paragraph'   => [ 'type' => 'string', 'description' => 'High-level summary of search competition in the city.' ],
							'search_dynamics'  => [ 'type' => 'string', 'description' => 'Detailed analysis of why standard national SEO fails in this specific market.' ],
						],
						'required'             => [ 'kicker', 'heading', 'lead_paragraph', 'search_dynamics' ],
						'additionalProperties' => false,
					],
					'market_accordion' => [
						'type'        => 'array',
						'description' => '3 to 4 expandable strategy pillars addressing local intent, Map Pack, and entity authority.',
						'items'       => [
							'type'                 => 'object',
							'properties'           => [
								'title'       => [ 'type' => 'string', 'description' => 'Pillar title e.g. Submarket Micro-Targeting, Commercial Intent Mapping.' ],
								'subtitle'    => [ 'type' => 'string', 'description' => 'Short summary tag e.g. High CPC commercial search hubs.' ],
								'body'        => [ 'type' => 'string', 'description' => 'In-depth explanation of execution methodology for this pillar.' ],
							],
							'required'             => [ 'title', 'subtitle', 'body' ],
							'additionalProperties' => false,
						],
					],
					'submarkets' => [
						'type'        => 'array',
						'description' => '4 to 6 local districts / submarkets in the city with distinct search dynamics.',
						'items'       => [
							'type'                 => 'object',
							'properties'           => [
								'name'        => [ 'type' => 'string', 'description' => 'Submarket / neighborhood name e.g. Downtown Central District, Innovation Corridor.' ],
								'type_tag'    => [ 'type' => 'string', 'description' => 'Category tag e.g. Legal & Corporate Search, Retail & Foot Traffic, Tech Corridor.' ],
								'fact'        => [ 'type' => 'string', 'description' => 'Key localized search factor e.g. High CPC commercial queries with strong phone call intent.' ],
								'description' => [ 'type' => 'string', 'description' => 'Actionable explanation of how we optimize landing nodes and citations for this zone.' ],
							],
							'required'             => [ 'name', 'type_tag', 'fact', 'description' ],
							'additionalProperties' => false,
						],
					],
					'case_proof' => [
						'type'                 => 'object',
						'description'          => 'Client proof and performance benchmark highlight.',
						'properties'           => [
							'title'        => [ 'type' => 'string', 'description' => 'Case study / proof title e.g. Regional Growth Benchmark.' ],
							'tagline'      => [ 'type' => 'string', 'description' => 'Proven local performance headline.' ],
							'client_quote' => [ 'type' => 'string', 'description' => 'Compelling narrative or quote on ranking turnaround and inbound inquiries.' ],
							'highlights'   => [
								'type'        => 'array',
								'description' => '3 metric callout stats.',
								'items'       => [
									'type'                 => 'object',
									'properties'           => [
										'metric' => [ 'type' => 'string', 'description' => 'E.g. +310%, Top 3, #1 Rank.' ],
										'label'  => [ 'type' => 'string', 'description' => 'E.g. Inbound Organic Leads, Map Pack in Target Zip Codes, High-Intent Keywords.' ],
									],
									'required'             => [ 'metric', 'label' ],
									'additionalProperties' => false,
								],
							],
							'cta_text'     => [ 'type' => 'string', 'description' => 'CTA button label e.g. Explore Case Study Details →.' ],
						],
						'required'             => [ 'title', 'tagline', 'client_quote', 'highlights', 'cta_text' ],
						'additionalProperties' => false,
					],
					'signal_stack' => [
						'type'        => 'array',
						'description' => '4 ranking signal pillars explaining the local search authority formula.',
						'items'       => [
							'type'                 => 'object',
							'properties'           => [
								'label'       => [ 'type' => 'string', 'description' => 'Signal pillar name e.g. Content Depth & Topical Relevance.' ],
								'percentage'  => [ 'type' => 'integer', 'description' => 'Relative percentage weighting e.g. 35.' ],
								'description' => [ 'type' => 'string', 'description' => 'Why this ranking signal matters for local rank velocity.' ],
							],
							'required'             => [ 'label', 'percentage', 'description' ],
							'additionalProperties' => false,
						],
					],
					'essay' => [
						'type'                 => 'object',
						'description'          => 'Deep-dive editorial essay on market search mechanics with milestone roadmap.',
						'properties'           => [
							'heading'    => [ 'type' => 'string', 'description' => 'Editorial essay heading e.g. The Anatomy of Local Search Authority.' ],
							'body'       => [ 'type' => 'string', 'description' => 'Comprehensive 2-3 paragraph analysis of why holistic local search infrastructure wins.' ],
							'milestones' => [
								'type'        => 'array',
								'description' => '4 sequential milestone phases.',
								'items'       => [
									'type'                 => 'object',
									'properties'           => [
										'step'    => [ 'type' => 'string', 'description' => 'Phase identifier e.g. Phase 01.' ],
										'title'   => [ 'type' => 'string', 'description' => 'Phase title e.g. Technical Baseline & Geo-Entity Graph.' ],
										'summary' => [ 'type' => 'string', 'description' => 'Key deliverable summary.' ],
									],
									'required'             => [ 'step', 'title', 'summary' ],
									'additionalProperties' => false,
								],
							],
						],
						'required'             => [ 'heading', 'body', 'milestones' ],
						'additionalProperties' => false,
					],
					'process' => [
						'type'        => 'array',
						'description' => 'Exactly 4 sequential steps in delivery methodology.',
						'minItems'    => 4,
						'maxItems'    => 4,
						'items'       => [
							'type'                 => 'object',
							'properties'           => [
								'step_number' => [ 'type' => 'integer', 'description' => '1-indexed step number.' ],
								'title'       => [ 'type' => 'string', 'description' => 'Phase / Step title.' ],
								'description' => [ 'type' => 'string', 'description' => 'What happens during this phase.' ],
							],
							'required'             => [ 'step_number', 'title', 'description' ],
							'additionalProperties' => false,
						],
					],
					'local_relevance' => [
						'type'                 => 'object',
						'properties'           => [
							'heading' => [ 'type' => 'string', 'description' => 'Local geographic and market relevance heading.' ],
							'body'    => [ 'type' => 'string', 'description' => 'Why local businesses in the city benefit from localized execution, without fake landmarks or addresses.' ],
						],
						'required'             => [ 'heading', 'body' ],
						'additionalProperties' => false,
					],
					'faqs' => [
						'type'        => 'array',
						'description' => '3 to 5 common buyer questions and direct answers.',
						'items'       => [
							'type'                 => 'object',
							'properties'           => [
								'question' => [ 'type' => 'string', 'description' => 'Common buyer question.' ],
								'answer'   => [ 'type' => 'string', 'description' => 'Clear, honest, informative answer.' ],
							],
							'required'             => [ 'question', 'answer' ],
							'additionalProperties' => false,
						],
					],
					'final_cta' => [
						'type'                 => 'object',
						'properties'           => [
							'heading'     => [ 'type' => 'string', 'description' => 'Closing conversion headline.' ],
							'description' => [ 'type' => 'string', 'description' => 'Closing invitation to consultation or strategy audit.' ],
							'button_text' => [ 'type' => 'string', 'description' => 'CTA button label.' ],
						],
						'required'             => [ 'heading', 'description', 'button_text' ],
						'additionalProperties' => false,
					],
					'seo_metadata' => [
						'type'                 => 'object',
						'description'          => 'Targeted SEO metadata for this city page.',
						'properties'           => [
							'seo_title'        => [ 'type' => 'string', 'description' => 'Targeted SEO meta title (recommended 50-60 characters).' ],
							'meta_description' => [ 'type' => 'string', 'description' => 'Compelling meta description summarizing the service and city (recommended 140-160 characters).' ],
							'og_title'         => [ 'type' => 'string', 'description' => 'OpenGraph share title.' ],
							'og_description'   => [ 'type' => 'string', 'description' => 'OpenGraph share description.' ],
						],
						'required'             => [ 'seo_title', 'meta_description', 'og_title', 'og_description' ],
						'additionalProperties' => false,
					],
				],
				'required'             => [
					'hero',
					'introduction',
					'service_overview',
					'benefits',
					'metro_stats',
					'market_anatomy',
					'market_accordion',
					'submarkets',
					'case_proof',
					'signal_stack',
					'essay',
					'process',
					'local_relevance',
					'faqs',
					'final_cta',
					'seo_metadata',
				],
				'additionalProperties' => false,
			],
		];
	}
}
