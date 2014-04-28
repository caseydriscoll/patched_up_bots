<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( plugin_dir_path( __FILE__ ) . 'class-wp-list-table.php' );
}

class Patched_Up_Users_Table extends WP_List_Table {
	public function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		$data = $this->table_data();
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

	function table_data() {
		global $wpdb;

        $query = "SELECT * FROM $wpdb->users";
		return $wpdb->get_results( $query, ARRAY_A );
	}

	public function get_columns() {
        $columns = array(
            'user_login'	=> 'Username',
            'display_name'	=> 'Name',
            'role'		=> 'Role',
        );

        return $columns;
    }

	public function get_sortable_columns() {
        return array('display_namem' => array('display_name', false));
    }

	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'user_login':
			case 'display_name':
			case 'role':
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}


}
