<?php

// http://www.paulund.co.uk/wordpress-tables-using-wp_list_table

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( plugin_dir_path( __FILE__ ) . 'class-wp-list-table.php' );
}

class Patched_Up_Users_Table extends WP_List_Table {
	public $usernames = Array(); 

	public function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		$data = $this->table_data();
		foreach( $data as $user ) array_push( $this->usernames, $user['user_login'] );
		usort( $data, array( &$this, 'sort_data' ) );
 
        $perPage = 10;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);
 
        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );
 
        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);
 
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = $data;
	}

	public function get_usernames() {
		return json_encode( $this->usernames );
	}

	function table_data() {
		global $wpdb;

        $query = "SELECT * FROM $wpdb->users";
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
        $columns = array(
            'user_login'	=> 'Username',
            'user_email'	=> 'Email',
            'display_name'	=> 'Name',
            'role'		=> 'Role',
        );

        return $columns;
    }

	public function get_sortable_columns() {
        return array('user_login' => array('user_login', false));
    }

	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'user_login':
			case 'user_email':
			case 'display_name':
			case 'role':
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}


}
