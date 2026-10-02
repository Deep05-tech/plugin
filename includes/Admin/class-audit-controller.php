<?php
/**
 * Append-Only Audit Log Admin Controller.
 *
 * @package SeoCityPages\Admin
 */

namespace SeoCityPages\Admin;

use SeoCityPages\Database\Audit_Log_Repository;
use SeoCityPages\Security\Security;
use SeoCityPages\Security\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Audit Controller Class.
 */
class Audit_Controller {

	public const NONCE_ACTION = 'seo_city_pages_audit_action';

	/**
	 * Render the Audit Log viewer screen.
	 *
	 * @return void
	 */
	public function render(): void {
		Security::enforce_capability( Capabilities::VIEW_AUDIT_LOGS );

		$page     = ! empty( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
		$per_page = 50;
		$offset   = ( $page - 1 ) * $per_page;

		$filters = [];
		if ( ! empty( $_GET['event_type'] ) ) {
			$filters['event_type'] = sanitize_key( $_GET['event_type'] );
		}
		if ( ! empty( $_GET['entity_type'] ) ) {
			$filters['entity_type'] = sanitize_key( $_GET['entity_type'] );
		}
		if ( ! empty( $_GET['user_id'] ) ) {
			$filters['user_id'] = (int) $_GET['user_id'];
		}
		if ( ! empty( $_GET['search'] ) ) {
			$filters['search'] = sanitize_text_field( wp_unslash( $_GET['search'] ) );
		}
		if ( ! empty( $_GET['date_from'] ) ) {
			$filters['date_from'] = sanitize_text_field( wp_unslash( $_GET['date_from'] ) );
		}
		if ( ! empty( $_GET['date_to'] ) ) {
			$filters['date_to'] = sanitize_text_field( wp_unslash( $_GET['date_to'] ) );
		}

		$total_logs = Audit_Log_Repository::count_logs( $filters );
		$logs       = Audit_Log_Repository::get_logs( $filters, $per_page, $offset );
		$total_pages = (int) ceil( $total_logs / $per_page );

		$template = SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/audit-log.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}
}
