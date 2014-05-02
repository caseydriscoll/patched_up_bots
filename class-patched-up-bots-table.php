<?php

// http://www.paulund.co.uk/wordpress-tables-using-wp_list_table

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( plugin_dir_path( __FILE__ ) . 'class-wp-list-table.php' );
}

class Patched_Up_Bots_Table extends WP_List_Table {
	public $data = Array(); 
	private $type; // The type of table

	public function __construct() {
		parent::__construct();

		if( isset( $_GET['tab'] ) ) $this->type = $_GET['tab'];
		else $this->type = 'users';
	}

	/* Prepare $this->items for future display
	 *
	 * @author caseypatrickdriscoll
	 */
	public function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();

		switch ( $this->type ) {
			case 'users' : 
				$column = 'user_login'; 
				break;

			default : $column = 'user_login'; 
		}

		$data = $this->table_data();
		foreach( $data as $thing ) array_push( $this->data, $thing[$column] );
		usort( $data, array( &$this, 'sort_data' ) );
 
        $perPage = 10;
        $currentPage = $this->get_pagenum();
        $totalItems = count( $data );
 
        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );
 
        $data = array_slice( $data, ( ( $currentPage - 1 ) * $perPage ) , $perPage );
 
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $data;
	}

	public function get_usernames() {
		return json_encode( $this->data );
	}

	function table_data() {
		global $wpdb;

		switch ( $this->type ) {
			case 'users' : 
				$query = "SELECT * FROM $wpdb->users";
				break;
			default :
				$query = "SELECT * FROM $wpdb->posts";
		}

		return $wpdb->get_results( $query, ARRAY_A );
	}

	private function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'title';
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }

        $result = strcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;
    }

	public function get_columns() {
		switch ( $this->type ) {
		case 'users' : 
			$columns = array(
				'delete'		=> '',
				'user_login'	=> 'Username',
				'user_email'	=> 'Email',
				'display_name'	=> 'Name',
				'role'		=> 'Role',
			);
			break;
		default:
			$columns = array();
		}

        return $columns;
    }

	function column_default( $item, $column_name ) {
		switch ( $this->type ) {
		case 'users' :
			switch( $column_name ) {
				case 'delete':
				case 'user_login':
				case 'user_email':
				case 'display_name':
				case 'role':
					return $item[ $column_name ];
				default:
					return print_r( $item, true ); // TODO: For debugging. Go away?
			}
			break;
		default :
			return $item[ $column_name ];
		}

	}


}
