<?php


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Lenbox_Order_Table extends WP_List_Table {

	function __construct() {
		global $status, $page;

		parent::__construct(
			array(
				'singular' => __( 'order', 'lenbox-cbnx' ),     // singular name of the listed records
				'plural'   => __( 'orders', 'lenbox-cbnx' ),   // plural name of the listed records
				'ajax'     => false,        // does this table support ajax?

			)
		);

		add_action( 'admin_head', array( &$this, 'admin_header' ) );

	}

	function admin_header() {
		$page = ( isset( $_GET['page'] ) ) ? sanitize_text_field( $_GET['page'] ) : false;
		if ( 'my_list_test' != $page ) {
			return;
		};
	}

	function no_items() {
		_e( 'Aucun commande.' );
	}

	function column_default( $item, $column_name ) {
		switch ( $column_name ) {

			case '_id':
			case 'cbnx_mensualites':
			case 'firstName':
			case 'lastName':
			case 'montant à financer':
			case 'Statut':
			case 'Created Date':
				return property_exists( $item, $column_name ) ? $item->$column_name : '';
			default:
				return print_r( $item, true ); // Show the whole array for troubleshooting purposes
		}
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'Statut'       => array( 'Statut', false ),
			'Created Date' => array( 'Created Date', false ),
		);
		return $sortable_columns;
	}

	function get_columns() {
		$columns = array(
			'Created Date'        => __( 'Date', 'lenbox-cbnx' ),
			'_id'                 => __( 'Order ID', 'lenbox-cbnx' ),
			'cbnx_mensualites'    => __( 'Installment plan', 'lenbox-cbnx' ),
			'firstName'           => __( 'First Name', 'lenbox-cbnx' ),
			'lastName'            => __( 'Last Name', 'lenbox-cbnx' ),
			'montant à financer' => __( 'Amount to finance', 'lenbox-cbnx' ),
			'Statut'              => __( 'Status', 'lenbox-cbnx' ),
		);
		 return $columns;
	}

	function usort_reorder( $a, $b ) {
		// If no sort, default to title
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? sanitize_text_field( $_GET['orderby'] ) : '_id';
		// If no order, default to asc
		$order = ( ! empty( $_GET['order'] ) ) ? sanitize_text_field( $_GET['order'] ) : 'asc';

		// Determine sort order
		$str_contains = function( string $haystack, string $needle ): bool {
			return '' === $needle || false !== strpos( $haystack, $needle );
		};
		if ( $str_contains( $orderby, 'Date' ) ) {
			$a_time = strtotime( $a->$orderby );
			$b_time = strtotime( $b->$orderby );
			$result = ( $a_time < $b_time ) ? -1 : 1;
			$result = ( $a_time === $b_time ) ? 0 : $result;
		} else {
			$result = strcmp( $a->$orderby, $b->$orderby );
		}
		// Send final sort direction to usort
		return ( $order === 'asc' ) ? $result : -$result;
	}

	function column_orderid( $item ) {
		$req_page = sanitize_text_field( $_REQUEST['page'] );
		$actions  = array(
			'edit'   => sprintf( '<a href="?page=%s&action=%s&order=%s">Edit</a>', $req_page, 'edit', $item->_id ),
			'delete' => sprintf( '<a href="?page=%s&action=%s&order=%s">Delete</a>', $req_page, 'delete', $item->_id ),
		);

		return sprintf( '%1$s %2$s', $item->_id, $this->row_actions( $actions ) );
	}

	function get_results() {

		// Get settings based on ENV
		$cbnx    = WC()->payment_gateways->payment_gateways()['lenbox_floa_cbnx'];

		$limit = 'sort_field=Created Date&descending=true&cursor=' . ($this->get_pagenum() - 1) . '&limit=20&';
		$base_url  = 'https://app.finnocar.com/api/1.1/obj/demandepret?' . $limit . 'constraints=';
		$client_id = $cbnx->live_client_id;

		$constraints = array(
			array(
				'key'             => 'cbnx_ID',
				'constraint_type' => 'is_not_empty',
			),
			array(
				'key'             => 'agence',
				'constraint_type' => 'equals',
				'value'           => $client_id,
			),
		);
		$req_url     = $base_url . urlencode( json_encode( $constraints ) );
		$response    = wp_remote_get( $req_url );

		if ( is_wp_error( $response ) ) {
			esc_html_e( 'Error: Cannot load data from lenbox. Please check your configuration', 'lenbox-cbnx' );
			return;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! property_exists( $body, 'response' ) ) {
			esc_html_e( 'Error: Cannot load data from lenbox. Please check your configuration', 'lenbox-cbnx' );
			return;
		}
		if ( ! property_exists( $body->response, 'results' ) ) {
			esc_html_e( 'Error: Cannot load data from lenbox. Please check your configuration', 'lenbox-cbnx' );
			return;
		}
		$results     = $body->response->results;
		$total_count = ( (int) $body->response->remaining + (int) $body->response->cursor ) * (int) $body->response->count;
		if ( ! empty( $results ) ) {
			foreach ( $results as $item ) {
				$date_fieldname        = 'Created Date';
				$date_val              = strtotime( $item->$date_fieldname );
				$item->$date_fieldname = gmdate( 'd/m/Y H:i:s', $date_val );
			}

			usort( $results, array( &$this, 'usort_reorder' ) );
		}
		return array(
			'results'     => $results,
			'total_count' => $total_count,
		);
	}


	function prepare_items() {
		$results_array         = $this->get_results();
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$per_page              = 20;
		$current_page          = $this->get_pagenum();
		$results               = null;
		$total_items           = null;
		$this->items           = null;

		if ( isset( $results_array ) ) {
			$results     = $results_array['results'];
			$total_items = $results_array['total_count'];
			$this->items = $results;
		}

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,                  // WE have to calculate the total number of items
				'per_page'    => $per_page,                     // WE have to determine how many items to show on a page
			)
		);

	}

}

