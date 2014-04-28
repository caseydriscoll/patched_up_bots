<?php

class Patched_Up_Bots_Admin_Page {

	public static function render() {
		require_once( plugin_dir_path( __FILE__ ) . 'class-patched-up-users-table.php' );
		echo '<div class="wrap">';
		echo	'<h2>' . Patched_Up_Bots::PAGE_TITLE . '</h2>';

		$active_tab = isset( $_GET[ 'tab' ] ) ? esc_html( $_GET[ 'tab' ] ) : 'users';

		$tabs = array( 'timeline', 'users', 'posts', 'pages', 'comments', 'events', 'organizers', 'venues' );

		settings_errors();

		echo	'<h2 class="nav-tab-wrapper">';
		
		foreach ( $tabs as $tab ) {
			$activeClass = $active_tab == $tab ? 'nav-tab-active' : '';
			echo	'<a href="?page=' . Patched_Up_Bots::PAGE_SLUG . '&tab=' . $tab . '" class="nav-tab ' . $activeClass . '">' . ucfirst( $tab ) . '</a>';
		}

		// Convert json files into data
		$data = array();
		$datapath = plugin_dir_path( __FILE__ ) . 'data/';  
		foreach ( scandir( $datapath ) as $dir ) {
			if ( substr( $dir, 0, 1 ) == '.' ) continue;

			$jsonfile = $datapath . $dir . '/' . $dir . '.json';
			if ( file_exists( $jsonfile ) )
				$data[$dir] = json_decode( file_get_contents( $jsonfile ), true );
		}

		// Filter data into usable options
		$options = '<option>-- Choose a Library --</option>';
		foreach ( $data as $key => $library ) {
			if ( array_key_exists( $active_tab, $library ) )
				$options .= '<option value="' . $key . '">' . $library['title'] . '</option>';
		}

		$users_table = new Patched_Up_Users_Table();
		$users_table->prepare_items();

		echo	'</h2>';

		echo	'<h3>' . ucwords( $active_tab ) . '</h3>';

		echo	'<form method="POST">';

		echo		'<input type="hidden" name="generate" value="' . $active_tab . '">';

		echo		'Yo bots, please generate <input type="number" name="amount" value="0" style="width: 40px;"> ' . $active_tab . ' from the ' .
					'<select>' . $options . '</select>' . 
					' library.';

		echo		'<input type="button" class="button generate" value="Generate ' . ucwords( $active_tab ) . '" style="margin-left: 20px;">';

		$users_table->display();

		submit_button( 'Add ' . ucwords( $active_tab ) );

		echo	'</form>';

		echo '</div>'; 

		global $wp_roles;
		$roles = $wp_roles->get_names();

		$roleselect = '<select>';
		foreach ( $roles as $value => $role ) $roleselect .= '<option value="' . $value . '">' . $role . '</option>';
		$roleselect .= '<select>';?>

		<style>
			tr.new td { background-color: #ccffcc; }
		</style>
		<script>
			jQuery( document ).ready( function() {
				var row = '<tr class="new"><td class="user_login column-user_login"></td><td class="display_name column-display_name"></td><td class="role column-role"><?php echo $roleselect; ?></td></tr>';

				jQuery( '.generate' ).on( 'click', function() {
					numrows = jQuery( 'input[name=amount]' ).val();

					html = '';
					for ( i = 0; i < numrows; i++ )
						html += row;

						jQuery( 'table.tools_page_patched-up-bots' ).prepend( jQuery( html ) ); 
				} );

			} );

		</script>

<?
	}


}

?>
