<?php

class Patched_Up_Bots_Admin_Page {

	public static function render() {
		require_once( plugin_dir_path( __FILE__ ) . 'class-patched-up-bots-table.php' );
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

		echo	'<form method="POST">';

		echo		'<input type="hidden" name="generate" value="' . $active_tab . '">';

		echo		'<h3>Yo bots, please generate <input type="button" id="minus" class="button" value="–"><input type="text" min="1" name="amount" value="1"><input type="button" id="plus" class="button" value="+"> <span id="cpt"></span> from ' .
					'<select id="library" class="button">' . $options . '</select> , thanks!</h3>';

		echo		'<input type="button" class="button button-large generate" value="">'; 

		echo 		'<span id="message"></span>';

		$table->display();

		submit_button();

		echo	'</form>';

		echo '</div>'; ?> 

		<style>
			tr.new td { background-color: #ccffcc; }
			.column-delete { width: 10px !important; }
				.column-delete .dashicons-dismiss { padding: 3px 0 0 0; }
				.column-delete .dashicons-dismiss:hover { color: #000; cursor: pointer; }
			.column-user_login { width: 300px !important; }
			input[name='amount'] { margin: 0px; padding: 4px 8px 3px; width: 40px; text-align: right; box-shadow: inset 0 1px 0 #fff,0 1px 0 rgba(0,0,0,.08); border-width: 1px 0; }

			#minus { border-top-right-radius: 0; border-bottom-right-radius: 0; }
			#plus  { border-top-left-radius:  0; border-bottom-left-radius:  0; }
			#minus, #plus { font-size: 18px; font-weight: bold; }
			#minus:focus, #plus:focus { outline: none; }

			#message { display: inline-block; margin-left: 20px; }
				.danger { color: red; }
		</style>
		<script>
			function capitalize( word ) { return word.charAt( 0 ).toUpperCase() + word.slice( 1 ); }

			jQuery( document ).ready( function() {
				// load all libraries
				var libraries = <?php echo $datajson; ?>;
				var library = get_any_library();

				// CPT plural readability (user/users)
				var cpt = ( '<?php echo $active_tab; ?>'.slice( -1 ) == 's' ) ?
					{ plural: '<?php echo $active_tab; ?>', single: '<?php echo substr( $active_tab, 0, -1 ); ?>' } :
					{ plural: '<?php echo $active_tab; ?>', single: '<?php echo $active_tab ?>' } ;
	
				// Initialize plurals 
				jQuery( '#cpt' ).text( cpt.single );
				jQuery( '.generate' ).val( 'Generate ' + capitalize( cpt.single ) );
				jQuery( 'input[name=submit]' ).val( 'Add ' + capitalize( cpt.single ) );

				// Iterate number generator
				jQuery( '#plus, #minus' ).on( 'click', function(e){
					num = parseInt( jQuery( 'input[name=amount]' ).val() );
					if( jQuery( e.target ).attr('id') == 'minus' && num > 1 )
						jQuery( 'input[name=amount]' ).val( num - 1 );
					else if( jQuery( e.target ).attr('id') == 'plus' )
						jQuery( 'input[name=amount]' ).val( num + 1 );

					if( parseInt( jQuery( 'input[name=amount]' ).val() ) == 1 ) { 
						jQuery( '#cpt' ).text( cpt.single );
						jQuery( '.generate' ).val( 'Generate ' + capitalize( cpt.single ) );
						jQuery( 'input[name=submit]' ).val( 'Add ' + capitalize( cpt.single ) );
					} else {
						jQuery( '#cpt' ).text( cpt.plural );
						jQuery( '.generate' ).val( 'Generate ' + capitalize( cpt.plural ) ); 
						jQuery( 'input[name=submit]' ).val( 'Add ' + capitalize( cpt.plural ) );
					}
				} );

				jQuery( 'select#library' ).on( 'change', function() {
					if ( jQuery( 'select#library' ).val() == 'everything' ) return; 
					if ( jQuery( 'select#library' ).val() == 'anything' ) 
						library = get_any_library();
					else
						library = jQuery( 'select#library' ).val();
				} );

				function get_any_library() {
					var options = [];
					jQuery( 'select#library option' ).each( function() {
						if( jQuery( this ).val() == 'anything' || jQuery( this ).val() == 'everything' ) return;
						options.push( jQuery( this ).val() );
					} );

					return options[Math.floor( Math.random() * options.length )];
				}

				// Row generator
				var	taken_data = <?php echo $taken_data; ?>;
				jQuery( '.generate' ).on( 'click', function() {
					if ( jQuery( 'select#library' ).val() == '' ) return;
					
					var data = libraries[library]['<?php echo $active_tab; ?>'];
					var numrows = parseInt( jQuery( 'input[name=amount]' ).val() );

					// Trim list as data is taken
					taken_data.forEach( function( takendata ) { delete data[takendata] } );
					dataleft = Object.keys( data ).length;
					numrows = dataleft < numrows ? dataleft : numrows;

					if ( numrows == 0 ) { 
						this.disabled = true;
						jQuery( 'span#message' ).text( 'All options exhausted' ).addClass( 'danger' );
					}

					var html = '';
					for ( i = 0; i < numrows; i++ ) {
						if ( jQuery( 'select#library' ).val() == 'everything' ) { 
							library = get_any_library(); 
							data = libraries[library]['<?php echo $active_tab; ?>'];
						}

						var thing;
						var count = 0;
			
						do {
							for ( var prop in data ) if ( Math.random() < 1/++count ) thing = prop;
							var isTaken = ( jQuery.inArray( thing, taken_data) == -1 ) ? false : true;
						} while ( isTaken );

						taken_data.push( thing );

						html += '<tr class="new">';
						html +=		'<td class="delete column-delete">';
						html +=			'<div class="dashicons dashicons-dismiss"></div>';
						html +=		'</td>';

						<?php 
						
						switch ( $active_tab ) { 
						case 'users' : 
							global $wp_roles;
							echo 'roles = ' . json_encode( $wp_roles->get_names() ) . ';'; ?>

							var user = thing;
							var nicename = data[user]['fname'] + " " + data[user]['lname'];

							roleselect = '<select name="users[' + user + '][role]">';
							for ( role in roles ) roleselect += '<option value="' + role + '">' + capitalize( roles[role] ) + '</option>';
							roleselect += '<select>';

							html +=		'<td class="user_login column-user_login">';
							html +=			'<input name="users[' + user + '][user_login]" type="text" class="widefat" value="' + user +'" />';
							html +=		'</td>';
							html +=		'<td class="user_email column-user_email">';
							html +=			'<input name="users[' + user + '][user_email]" type="text" class="widefat" value="' + user + '@' + library + '.com" />';
							html +=		'</td>';
							html +=		'<td class="display_name column-display_name">';
							html +=			'<input name="users[' + user + '][display_name]" type="text" class="widefat" value="' + nicename + '">';
							html +=		'</td>';
							html +=		'<td class="role column-role">';
							html +=			roleselect;
							html +=		'</td>';
						<?php 
							break;
						default: ?>
							html +=		'';

						<?php } ?>	
						html +=	'</tr>';
					}

					jQuery( 'table.tools_page_patched-up-bots' ).prepend( jQuery( html ) ); 
					jQuery( '.dashicons-dismiss' ).on( 'click', function() { jQuery( this ).parent().parent().remove(); } );
				} );

			} );

		</script>

<?
	}


}

?>
