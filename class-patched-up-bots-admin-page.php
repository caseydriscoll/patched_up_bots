<?php

class Patched_Up_Bots_Admin_Page {

	public static function render() {
		require_once( plugin_dir_path( __FILE__ ) . 'class-patched-up-users-table.php' );
		echo '<div class="wrap">';
		echo	'<h2>' . Patched_Up_Bots::PAGE_TITLE . '</h2>';

		$active_tab = isset( $_GET[ 'tab' ] ) ? esc_html( $_GET[ 'tab' ] ) : 'users';

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
		$options = '<option value="anything">anything</option>';
		foreach ( $data as $key => $library ) {
			if ( array_key_exists( $active_tab, $library ) )
				$options .= '<option value="' . $key . '">' . $library['title'] . '</option>';
		}

		$users_table = new Patched_Up_Users_Table();
		$users_table->prepare_items();

		$taken_usernames = $users_table->get_usernames();

		echo	'</h2>';

		echo	'<form method="POST">';

		echo		'<input type="hidden" name="generate" value="' . $active_tab . '">';

		echo		'<h3>Yo bots, please generate <input type="button" id="minus" class="button" value="–"><input type="text" min="1" name="amount" value="1"><input type="button" id="plus" class="button" value="+"> <span id="cpt">' . substr( $active_tab, 0, -1 ) . '</span> from ' .
					'<select id="library" class="button">' . $options . '</select> , thanks!</h3>';

		echo		'<input type="button" class="button generate" value="Generate ' . ucwords( substr( $active_tab, 0, -1 ) ) . '">'; 

		echo 		'<span id="message"></span>';

		$users_table->display();

		submit_button( 'Add ' . ucwords( substr( $active_tab, 0, -1 ) ) );

		echo	'</form>';

		// echo '<pre>' . print_r( $data, true ) . '</pre>';
		// echo '<pre>' . $datajson . '</pre>';

		echo '</div>'; ?> 

		<style>
			tr.new td { background-color: #ccffcc; }
			input[name='amount'] { margin: 0px; padding: 4px 8px 3px; width: 40px; text-align: right; box-shadow: inset 0 1px 0 #fff,0 1px 0 rgba(0,0,0,.08); border-width: 1px 0; }

			#minus { border-top-right-radius: 0; border-bottom-right-radius: 0; }
			#plus  { border-top-left-radius:  0; border-bottom-left-radius:  0; }
			#minus, #plus { font-size: 18px; font-weight: bold; }
			#minus:focus, #plus:focus { outline: none; }

			#message { display: inline-block; margin-left: 20px; }
				.danger { color: red; }
		</style>
		<script>
			jQuery( document ).ready( function() {
				// CPT plural readability (user/users)
				var cpt = { plural: '<?php echo $active_tab; ?>', single: '<?php echo substr( $active_tab, 0, -1 ); ?>' };
	
				// Iterate number generator
				jQuery( '#plus, #minus' ).on( 'click', function(e){
					num = parseInt( jQuery( 'input[name=amount]' ).val() );
					if( jQuery( e.target ).attr('id') == 'minus' && num > 1 )
						jQuery( 'input[name=amount]' ).val( num - 1 );
					else if( jQuery( e.target ).attr('id') == 'plus' )
						jQuery( 'input[name=amount]' ).val( num + 1 );

					if( parseInt( jQuery( 'input[name=amount]' ).val() ) == 1 ) { 
						jQuery( '#cpt' ).text( cpt.single );
						jQuery( '.generate' ).val( 'Generate ' + cpt.single.charAt(0).toUpperCase() + cpt.single.slice(1));
						jQuery( 'input[name=submit]' ).val( 'Add ' + cpt.single.charAt(0).toUpperCase() + cpt.single.slice(1));
					} else {
						jQuery( '#cpt' ).text( cpt.plural );
						jQuery( '.generate' ).val( 'Generate ' + cpt.plural.charAt(0).toUpperCase() + cpt.plural.slice(1));
						jQuery( 'input[name=submit]' ).val( 'Add ' + cpt.plural.charAt(0).toUpperCase() + cpt.plural.slice(1));
					}
				} );

				// Data
				var library = [];
				library['name'] = get_any_library(); 
				load_library();
				jQuery( 'select#library' ).on( 'change', function() {
					if ( jQuery( 'select#library' ).val() == 'anything' ) 
						library['name'] = get_any_library();
					else
						library['name'] = jQuery( 'select#library' ).val();

					load_library();
				} );

				function load_library() {
					library['path'] = "<?php echo plugins_url( 'data', __FILE__ ) ?>/" + library['name'] + "/" + library['name'] + ".json";
					jQuery.getJSON( library['path'], function( data ) {
						library['data'] = data; 	
					} ); 
				}

				function get_any_library() {
					var options = [];
					jQuery( 'select#library option' ).each( function() {
						if( jQuery( this ).val() == 'anything' ) return;
						options.push( jQuery(this).val() );
					} );

					return options[Math.floor(Math.random() * options.length)];
				}

				// Row generator
				var	takenusers = <?php echo $taken_usernames; ?>;
				jQuery( '.generate' ).on( 'click', function() {
					if ( jQuery( 'select#library' ).val() == '' ) return;
					
					var users = library['data']['users'];
					var numrows = parseInt( jQuery( 'input[name=amount]' ).val() );

					// Trim list as users are taken
					takenusers.forEach( function( takenuser ) { delete users[takenuser] } );
					usersleft = Object.keys(users).length;
					numrows = usersleft < numrows ? usersleft : numrows;

					if ( numrows == 0 ) { 
						this.disabled = true;
						jQuery( 'span#message' ).text( 'All options exhausted' ).addClass( 'danger' );
					}

					var html = '';
					for ( i = 0; i < numrows; i++ ) {
						var user;
						var count = 0;
			
						do {
							for ( var prop in users ) if ( Math.random() < 1/++count ) user = prop;
							var isTaken = ( jQuery.inArray( user, takenusers ) == -1 ) ? false : true;
						} while ( isTaken );

						takenusers.push( user );

						var nicename = users[user]['First Name'] + " " + users[user]['Last Name'];

						<?php
						global $wp_roles;
						echo 'roles = ' . json_encode( $wp_roles->get_names() ); ?>

						roleselect = '<select name="users[' + user + '][role]>';
						for ( role in roles ) roleselect += '<option value="' + role + '">' + role.charAt(0).toUpperCase() + role.slice(1) + '</option>';
						roleselect += '<select>';

						html += '<tr class="new">';
						html +=		'<td class="user_login column-user_login">';
						html +=			'<input name="users[' + user + '][user_login]" type="text" class="widefat" value="' + user +'" />';
						html +=		'</td>';
						html +=		'<td class="user_email column-user_email">';
						html +=			'<input name="users[' + user + '][user_email]" type="text" class="widefat" value="' + user + '@' + library['name'] + '.com" />';
						html +=		'</td>';
						html +=		'<td class="display_name column-display_name">';
						html +=			'<input name="users[' + user + '][display_name]" type="text" class="widefat" value="' + nicename + '">';
						html +=		'</td>';
						html +=		'<td class="role column-role">';
						html +=			roleselect;
						html +=		'</td>';
						html +=	'</tr>';
					}

					jQuery( 'table.tools_page_patched-up-bots' ).prepend( jQuery( html ) ); 
				} );

			} );

		</script>

<?
	}


}

?>
