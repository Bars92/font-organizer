<?php
defined( 'ABSPATH' ) or die( 'Jog on!' );

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class ElementsTable extends WP_List_Table {

	private $custom_elements;

	/** Class constructor */
	public function __construct() {
		parent::__construct( array(
				'singular' => 'custom_element', //singular name of the listed records
				'plural'   => 'custom_elements', //plural name of the listed records
				'ajax'     => false, //does this table support ajax?
			) );

		/**
		 * Optional. You can handle your bulk actions however you see fit. In this
		 * case, we'll handle them within our package just to keep things clean.
		 */
		$this->process_bulk_action();
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
		case 'id':
		case 'name':
		case 'custom_elements':
		case 'important':
			return $item->$column_name;
		default:
			return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
			/*$2%s*/ $item->id              //The value of the checkbox should be the record's id
		);
	}

	/**
	 * Render the custom_elements editable text
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_custom_elements( $item ) {
		return sprintf(
			'<input type="text" name="custom_elements" value="%1$s" style="background:transparent;box-shadow:none;border:0;width:100%%;direction:ltr;" />',
			/*$2%s*/ $item->custom_elements
		);
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
		'cb'        => '<input type="checkbox" />',
		'id'    => __( 'Id', 'font-organizer' ),
		'custom_elements'   => __( 'Custom Elements', 'font-organizer' ),
		'important'   => __( 'Important', 'font-organizer' ),
		);

		return $columns;
	}

	function column_important( $item ) {
		//Return the title contents
		return sprintf( '<input type="checkbox" name="important" %1$s /> <span style="color:%2$s">%3$s</span>', 
						checked($item->important, true, false),
						$item->important ? 'darkgreen' : 'darkred',
			/*$1%s*/ 	$item->important ? __('Yes', 'font-organizer') : __('No', 'font-organizer') 
		);
	}

	function column_id( $item ) {

		//Build row actions
		$actions = array(
			'delete'    => sprintf( '<a href="?page=%s&action=%s&manage_font_id=%s&custom_element=%s#step6">%s</a>', $_REQUEST['page'], 'delete', $item->font_id, $item->id, __('Delete', 'font-organizer')), 
		);

		//Return the title contents
		return sprintf( '%1$s %2$s',
			/*$1%s*/ $item->id,
			/*$2%s*/ $this->row_actions( $actions )
		);
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'id' => array( 'id', true ),
			'important' => array( 'important', false ),
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
		'bulk-delete' => __('Delete', 'font-organizer'),
		);

		return $actions;
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items_by_font($custom_elements, $font_id) {
		$this->custom_elements = $custom_elements;

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();


		/**
		 * REQUIRED. Finally, we build an array to be used by the class for column
		 * headers. The $this->_column_headers property takes an array which contains
		 * 3 other arrays. One for all columns, one for hidden columns, and one
		 * for sortable columns.
		 */
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Get the font given custom elements only!
		$data = array();
		foreach ($this->custom_elements as $custom_element) {
			if($custom_element->font_id == $font_id)
				$data[] = $custom_element;
		}

		$per_page     = $this->get_items_per_page( 'custom_elements_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = count( $data );

		$this->set_pagination_args( array(
				'total_items' => $total_items,                  //WE have to calculate the total number of items
				'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
				'total_pages' => ceil( $total_items/$per_page )   //WE have to calculate the total number of pages
			) );

		/**
		 * This checks for sorting input and sorts the data in our array accordingly.
		 *
		 * In a real-world situation involving a database, you would probably want
		 * to handle sorting by passing the 'orderby' and 'order' values directly
		 * to a custom query. The returned data will be pre-sorted, and this array
		 * sorting technique would be unnecessary.
		 */
		function usort_reorder_custom_elements( $a, $b ) {
			$orderby = ( !empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'id'; //If no sort, default to date
			$order = ( !empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'desc'; //If no order, default to desc
			$result = strcmp( $a->$orderby, $b->$orderby ); //Determine sort order
			return ( $order==='asc' ) ? $result : -$result; //Send final sort direction to usort
		}

		usort( $data, 'usort_reorder_custom_elements' );

		/**
		 * The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to
		 */
		$data = array_slice( $data, ( ( $current_page-1 )*$per_page ), $per_page );
		$this->items = $data;
	}

	public function no_items() {
		_e( 'No custom elements found.', 'font-organizer' );
	}

    private function delete_from_database($id){
        global $wpdb;
        $table_name = $wpdb->prefix . FO_ELEMENTS_DATABASE;

        $wpdb->delete( $table_name, array( 'id' => $id ) );
    }

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {
			$this->delete_from_database( absint( $_GET['custom_element'] ) );
		}

		// If the delete bulk action is triggered
		if ( ( isset( $_GET['action'] ) && $_GET['action'] == 'bulk-delete' )
			|| ( isset( $_GET['action2'] ) && $_GET['action2'] == 'bulk-delete' )
		) {
			$delete_ids = esc_sql( $_GET['custom_element'] );

			if(empty($delete_ids))
				return;

			// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $id ) {
				$this->delete_from_database( absint( $id ) );
			}
		}
	}
}
?>
