<?php

class Patched_Up_Bots_Admin_Page {

	public static function render() {
		require_once( plugin_dir_path( __FILE__ ) . 'class-patched-up-bots-table.php' );
		wp_enqueue_style( 'Patched_Up_Bots_Styles', plugins_url( 'style.css' , __FILE__ ) );
		wp_enqueue_script( 'Patched_Up_Bots_Scripts', plugins_url( 'script.js' , __FILE__ ) );

		$active_tab = isset( $_GET[ 'tab' ] ) ? esc_html( $_GET[ 'tab' ] ) : 'users';

		echo '<div class="wrap">';
		echo	'<form method="POST" class="' . $active_tab . '">';
		echo		'<h2>' . Patched_Up_Bots::PAGE_TITLE . get_submit_button( 'Add ' . $active_tab, array( 'primary', 'top-submit' ) , 'submit', false ) . '</h2>';

		$tabs = array( 'timeline', 'users' );

		foreach( get_post_types( '', 'objects' ) as $posttype ) {
			if( $posttype->name == 'revision' || $posttype->name == 'nav_menu_item' ) continue;
			array_push( $tabs, strtolower( $posttype->labels->name ) );
			if( $posttype->name == 'post' ) array_push( $tabs, 'comments' );
		}

		settings_errors();

		echo	'<h2 class="nav-tab-wrapper">';
		
		foreach ( $tabs as $tab ) {
			$activeClass = $active_tab == $tab ? 'nav-tab-active' : '';
			echo	'<a href="?page=' . Patched_Up_Bots::PAGE_SLUG . '&tab=' . $tab . '" class="nav-tab ' . $activeClass . '">' . ucfirst( $tab ) . '</a>';
		}

		echo	'</h2>';

		// Convert json files into data
		$data = array();
		$datapath = plugin_dir_path( __FILE__ ) . 'data/';  
		$datajson = '';
		foreach ( scandir( $datapath ) as $dir ) {
			if ( substr( $dir, 0, 1 ) == '.' ) continue;

			$jsonfile = $datapath . $dir . '/' . $dir . '.json';
			if ( file_exists( $jsonfile ) ) {
				$datajson = file_get_contents( $jsonfile );
				$data[$dir] = json_decode( $datajson, true );
			}
		}
		$datajson = json_encode( $data );

		// Filter data into usable options
		$options =  '<option value="everything">everything</option>';
		$options .= '<option value="anything">anything</option>';
		foreach ( $data as $key => $library ) {
			if ( array_key_exists( $active_tab, $library ) )
				$options .= '<option value="' . $key . '">' . $library['title'] . '</option>';
		}

		$table = new Patched_Up_Bots_Table();
		$table->prepare_items();

		$taken_data = $table->get_usernames();


		echo		'<input type="hidden" name="generate" value="' . $active_tab . '">';

		echo		'<h3>Yo bots, please generate <input type="button" id="minus" class="button" value="–"><input type="text" min="1" name="amount" value="1"><input type="button" id="plus" class="button" value="+"> ';

		echo		'<select id="scripted" class="button"><option value="scripted" selected>scripted</option><option value="random">random</option></select> ' .	
			
					'<span id="cpt"></span> from ' .

					'<select id="library" class="button">' . $options . '</select> , thanks!</h3>';

		echo		'<input type="button" class="button button-large generate" value="">'; 

		echo 		'<span id="message"></span>';

		$table->display();

		submit_button();

		echo	'</form>';

		echo '</div>'; ?> 

		<script>
			// load all libraries
			var libraries = <?php echo $datajson; ?>;
			var takenData = <?php echo $taken_data; ?>; // At init, taken_data is a list of already used users, posts, pages, etc
			var active_tab = '<?php echo $active_tab; ?>';

			<?php
				global $wp_roles;
				echo 'roles = ' . json_encode( $wp_roles->get_names() ) . ';';
				echo 'datafile = "' . plugin_dir_url( __FILE__ ) . 'data/";';
				$users = array();
				foreach ( get_users( $GLOBALS['blog_id'] ) as $user ) { // an array of all users
					$users[$user->user_login] = array( 'id' => $user->ID, 'name' => $user->display_name );
				} 
				echo 'users = ' . print_r( json_encode( $users, true ), true );
			?>
				
			var statuses = <?php print_r( json_encode( get_post_statuses() ) ); ?>;

			var totalItemsLeft = 0;

			// CPT plural readability (user/users)
			var cpt = ( '<?php echo $active_tab; ?>'.slice( -1 ) == 's' ) ?
				{ plural: '<?php echo $active_tab; ?>', single: '<?php echo substr( $active_tab, 0, -1 ); ?>' } :
				{ plural: '<?php echo $active_tab; ?>', single: '<?php echo $active_tab ?>' } ;

		</script>

<?
	}


}

?>
