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
			case 'posts' :
				$column = 'post_type';

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
			case 'posts' :
				$query = "SELECT * FROM $wpdb->posts WHERE post_type = 'post'";
				break;
			default :
				$query = "SELECT * FROM $wpdb->users";

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
				'avatar'		=> '',
				'user_login'	=> 'Username',
				'user_email'	=> 'Email',
				'display_name'	=> 'Name',
				'role'			=> 'Role',
			);
			break;
		case 'posts' :
			$columns = array(
				'delete'		=> '',
				'post_title'	=> 'Title/Slug',
				'post_author'	=> 'Author',
				'post_content'	=> 'Content',
				'post_date'		=> 'Date',
				'post_status'	=> 'Status'
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
					break;
				case 'avatar':
					$hash = md5( strtolower( trim( $item['user_email'] ) ) );
					$uri = 'http://www.gravatar.com/avatar/' . $hash . '?d=404';
					$headers = @get_headers( $uri );
					if( !preg_match( '|200|', $headers[0] ) ) {
						$library = explode( '.', substr( strrchr( $item['user_email'], '@' ), 1 ) )[0];
						return '<img src="' . plugin_dir_url( __FILE__ ) . 'data/' . $library . '/img/' . $item['user_login'] . '.jpg' . '" width="32" height="32" />';
					} else {
						return get_avatar( $item['user_email'], '32' );
					}
					break;
				default:
					return print_r( $item, true ); // TODO: For debugging. Go away?
			}
			break;
		case 'posts' :
			switch( $column_name ) {
				case 'post_author' :
					return get_user_by( 'id', $item[ $column_name ] )->user_login;
					break;
				default :
					return $item[ $column_name ];
			}
		default :
			return $item[ $column_name ];
		}

	}


}
