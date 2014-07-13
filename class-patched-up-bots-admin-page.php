<?php

class Patched_Up_Bots_Admin_Page {

	public static function render() {
		require_once( plugin_dir_path( __FILE__ ) . 'class-patched-up-bots-table.php' );
		wp_enqueue_style( 'Patched_Up_Bots_Styles', plugins_url( 'style.css' , __FILE__ ) );

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

		echo	'<form method="POST" class="' . $active_tab . '">';

		echo		'<input type="hidden" name="generate" value="' . $active_tab . '">';

		echo		'<h3>Yo bots, please generate <input type="button" id="minus" class="button" value="–"><input type="text" min="1" name="amount" value="1"><input type="button" id="plus" class="button" value="+"> ';

		echo		'<select id="scripted" class="button"><option value="scripted">scripted</option><option value="random" selected>random</option></select> ' .	
			
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

			console.log( libraries );

			var totalItemsLeft = 0;

			// CPT plural readability (user/users)
			var cpt = ( '<?php echo $active_tab; ?>'.slice( -1 ) == 's' ) ?
				{ plural: '<?php echo $active_tab; ?>', single: '<?php echo substr( $active_tab, 0, -1 ); ?>' } :
				{ plural: '<?php echo $active_tab; ?>', single: '<?php echo $active_tab ?>' } ;

			function capitalize( word ) { return word.charAt( 0 ).toUpperCase() + word.slice( 1 ); }

			// Calculate the total number of cpts in every library (users in library)
			function calculateTotalItemsLeft() {
				totalItemsLeft = 0;
				Object.keys( libraries ).forEach( function( library ) {
					totalItemsLeft += Object.keys( libraries[library][cpt.plural] ).length;
				} );
			}

			function get_any_library() {
				var options = [];
				for( var library in libraries ) options.push( library );

				return options[Math.floor( Math.random() * options.length )];
			}

			jQuery( document ).ready( function() {
				for( var lib in libraries ) {
					takenData.forEach( function( data ) { if ( jQuery.inArray( data, libraries[lib] ) == -1 ) delete libraries[lib][cpt.plural][data];  } );
					if( Object.keys( libraries[lib][cpt.plural] ).length == 0 ) { 
						delete libraries[lib];
						jQuery( 'option[value="' + lib + '"]' ).remove();
					}
				}

				var library = get_any_library();
				var isScripted = jQuery( '#scripted' ).val() == 'scripted' ? true : false;

				// Initialize plurals 
				jQuery( '#cpt' ).text( cpt.single );
				jQuery( '.generate' ).val( 'Generate ' + capitalize( cpt.single ) );
				jQuery( 'input[name=submit]' ).val( 'Add ' + capitalize( cpt.single ) );

				// Iterate number generator
				jQuery( '#plus, #minus' ).on( 'click', function(e){
					num = parseInt( jQuery( 'input[name=amount]' ).val() );

					var data;
					calculateTotalItemsLeft();
					if( jQuery( 'select#library' ).val() == 'everything' )
						max = totalItemsLeft;
					else
						max = Object.keys( libraries[library][cpt.plural] ).length;

					if( jQuery( e.target ).attr('id') == 'minus' && num > 1 )
						if( e.shiftKey && num > 10 ) num -= 10;
						else if( e.altKey ) num = 1;
						else if( e.shiftKey ) num = 1;
						else num -= 1;
					else if( jQuery( e.target ).attr('id') == 'plus' )
						if( e.shiftKey ) num += 10;
						else if( e.altKey ) num = max;
						else num += 1;

					if( num > max ) num = max;

					jQuery( 'input[name=amount]' ).val( num );
					
					// Set plural tense
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

				// Event handler to change library on user selection
				jQuery( 'select#library' ).on( 'change', function() {
					if ( jQuery( 'select#library' ).val() == 'everything' ) return; 
					if ( jQuery( 'select#library' ).val() == 'anything' ) 
						library = get_any_library();
					else
						library = jQuery( 'select#library' ).val();
				} );

				// Change the '# items' label to reflect the cpt
				var total_added_rows;	// For the '# items' label above and below the table						
				total_added_rows = parseInt( jQuery( '.displaying-num' ).text().split( ' ' )[0] );
				if( total_added_rows == 1 ) jQuery( '.displaying-num' ).text( total_added_rows + ' ' + cpt.single );
				else jQuery( '.displaying-num' ).text( total_added_rows + ' ' + cpt.plural );

				// Row Generator
				jQuery( '.generate' ).on( 'click', function() {
					if ( jQuery( 'select#library' ).val() == '' ) return; // Should literally never happen now
					isScripted = jQuery( '#scripted' ).val() == 'scripted' ? true : false;

					/* 1) GENERATE DATA */
					// Was previously generating data in the midst of rendering rows for the 'everything' clause
					// Create an array of things first, then render that things array
					
					var data;			// The array of CPT objects to choose from
					var numrows;		// The number of rows to create, usually 1
					var selectedData;	// An array of cpt objects selected to render into rows

				   	numrows = parseInt( jQuery( 'input[name=amount]' ).val() );
					selectedData = Array();

					// If the selection is 'everything' you must select data from all libraries
					if ( jQuery( 'select#library' ).val() == 'everything' ) { 
						// If the number of requested rows is greater than everything left
						//		Just give everything that is left
						calculateTotalItemsLeft();
						numrows = numrows > totalItemsLeft ? totalItemsLeft : numrows;

						for( var i = 0; i < numrows; i++ ) {
							// Deleted as to prevent get_any_library from searching empty tree
							if( typeof data != 'undefined' && Object.keys( data ).length == 0 ) delete libraries[library];

							library = get_any_library(); 

							var thing;
							var count = 0;

							if ( isScripted ) { 
								data = libraries[library][cpt.plural];

								do {
									for ( var prop in data ) if ( Math.random() < 1/++count ) thing = prop;
									var isTaken = ( typeof thing !== 'undefined' && jQuery.inArray( thing, takenData ) == -1 ) ? false : true;
								} while ( isTaken );

								data[thing]['name'] = thing; // Save the username key to object
								data[thing]['library'] = library; // Save the library key to object
								selectedData.push( data[thing] );
								takenData.push( thing );

								// Trim list as data is taken
								delete data[thing];
							} else {
								// Generate a random object that looks like it is a scripted cpt.
								// It must have a template in the library 
								// For every item in the template, create an array with those items
								library = 'gameofthrones';
								data = libraries[library];
								thing =  Array();
								var template = data['template'][cpt.single].split( ', ' );
								for ( var param in template ) {
									items = data['random'][template[param]].split( ', ' );
									thing[template[param]] = items[Math.floor( Math.random() * items.length )];
								}
								thing['library'] = library;
								if ( cpt.single == 'user' )
									thing['name'] = thing['fname'] + thing['lname'];

								selectedData.push( thing );
							}
							console.log( selectedData );

						}
						
						calculateTotalItemsLeft();
						if ( totalItemsLeft == 0 ) { 
							this.disabled = true;
							jQuery( 'span#message' ).text( 'All options exhausted' ).addClass( 'danger' );
						}
					}
					// Otherwise, you are only selecting data from one library
					else {
						// If the number of requested rows is greater than what is left in the library
						//		Just give everything that is in the library
						data = libraries[library][cpt.plural];
						numrows = numrows > Object.keys( data ).length ? Object.keys( data ).length : numrows;

						for( var i = 0; i < numrows; i++ ) {
							var thing;
							var count = 0;

							do {
								for ( var prop in data ) if ( Math.random() < 1/++count ) thing = prop;
								var isTaken = ( typeof thing !== 'undefined' && jQuery.inArray( thing, takenData ) == -1 ) ? false : true;
							} while ( isTaken );

							data[thing]['name'] = thing; // Save the username key to object
							data[thing]['library'] = library; // Save the library key to object
							selectedData.push( data[thing] );
							takenData.push( thing );

							// Trim list as data is taken
							delete data[thing];
						}					

						if ( Object.keys( data ).length == 0 ) { 
							this.disabled = true;
							jQuery( 'span#message' ).text( 'All options exhausted' ).addClass( 'danger' );
						}
					}

					// Update the '# items' label
					total_added_rows += numrows;
					if( total_added_rows == 1 ) jQuery( '.displaying-num' ).text( total_added_rows + ' ' + cpt.single );
					else jQuery( '.displaying-num' ).text( total_added_rows + ' ' + cpt.plural );


					/* 2) RENDER DATA */
					var html = '';
					for ( i = 0; i < numrows; i++ ) {
						html += '<tr class="new">';
						html +=		'<td class="delete column-delete">';
						html +=			'<div class="dashicons dashicons-dismiss"></div>';
						html +=		'</td>';

						<?php 
						
						switch ( $active_tab ) { 
						case 'users' : 
							global $wp_roles;
							echo 'roles = ' . json_encode( $wp_roles->get_names() ) . ';'; ?>

							var user = selectedData[i];
							var nicename = capitalize( user.fname ) + " " + capitalize( user.lname );

							roleselect = '<select name="users[' + user.name + '][role]" class="widefat">';
							for ( role in roles ) {
								var selected = '';
								if ( role === user.role ) selected = 'selected';
								roleselect += '<option value="' + role + '" ' + selected + '>' + capitalize( roles[role] ) + '</option>';
							}
							roleselect += '<select>';

							user.photo = isScripted ? user.name : user.library; 

							html +=		'<td class="avatar column-avatar">';
							html +=			'<img src="<?php echo plugin_dir_url( __FILE__ ) . 'data/'; ?>' + user.library + '/img/' + user.photo + '.jpg" width="32" height="32" />'
							html +=		'</td>';
							html +=		'<td class="user_login column-user_login">';
							html +=			'<input name="users[' + user.name + '][user_login]" type="text" class="widefat" value="' + user.name + '" />';
							html +=		'</td>';
							html +=		'<td class="user_email column-user_email">';
							html +=			'<input name="users[' + user.name + '][user_email]" type="text" class="widefat" value="' + user.name + '@' + user.library + '.com" />';
							html +=		'</td>';
							html +=		'<td class="display_name column-display_name">';
							html +=			'<input name="users[' + user.name + '][display_name]" type="text" class="widefat" value="' + nicename + '">';
							html +=		'</td>';
							html +=		'<td class="role column-role">';
							html +=			roleselect;
							html +=		'</td>';
						<?php 
							break;
						case 'posts' : 
						case 'pages' : 

							$users = array();
							foreach ( get_users( $GLOBALS['blog_id'] ) as $user ) { // An array of all users
								$users[$user->user_login] = array( 'ID' => $user->ID, 'name' => $user->display_name );
							} 
							?>

							var users = <?php print_r( json_encode( $users, true ) ); ?>;
							
							console.log( 'libraries 1: ', libraries );
							var post = selectedData[i];
							var user = libraries[library]['users'][post.author];

							var statuses = <?php print_r( json_encode( get_post_statuses() ) ); ?>;

							statusselect = '<select name="posts[' + post.name + '][post_status]" class="widefat">';
							for ( status in statuses ) {
								var selected = '';
								if ( status === post.status ) selected = 'selected';
								statusselect += '<option value="' + status + '" ' + selected + '>' + capitalize( statuses[status] ) + '</option>';
							}
							statusselect += '</select>';

							authorselect = '<select name="posts[' + post.name + '][post_author]" class="widefat">';
							authorselect +=		'<option value="' + post.author + '" ' + selected + '>' + post.author + '</option>';
							for ( var u in users ) {
								var selected = '';
								if ( u === post.author ) selected = 'selected';
								authorselect += '<option value="' + users[u].ID + '" ' + selected + '>' + u + '</option>';
							}
							authorselect += '</select>';

							html +=		'<td class="post_title column-post_title">';
							html +=			'<input name="posts[' + post.name + '][post_title]" type="text" class="widefat" value="' + post.title + '" />';
							html +=			'<input name="posts[' + post.name + '][post_name]" type="text" class="widefat" value="' + post.name + '" />';
							html +=		'</td>';
							html +=		'<td class="post_author column-post_author">';
							html +=			authorselect;
							html +=			'<input name="posts[' + post.name + '][user][user_login]" type="hidden" value="' + post.author + '" />';
							html +=			'<input name="posts[' + post.name + '][user][user_email]" type="hidden" value="' + post.author + '@' + library + '.com" />';
							html +=			'<input name="posts[' + post.name + '][user][first_name]" type="hidden" value="' + user['fname'] + '" />';
							html +=			'<input name="posts[' + post.name + '][user][last_name]" type="hidden" value="' + user['lname'] + '" />';
							html +=			'<input name="posts[' + post.name + '][user][display_name]" type="hidden" value="' + user['fname'] + ' ' + user['lname'] + '" />';
							html +=			'<input name="posts[' + post.name + '][user][role]" type="hidden" value="' + user['role'] + '" />';
							html +=		'</td>';
							html +=		'<td class="post_content column-post_content">';
							html +=			'<textarea name="posts[' + post.name + '][post_content]" class="widefat">' + post.content + '</textarea>';
							html +=		'</td>';
							html +=		'<td class="post_date column-post_date">';
							html +=			'<input name="posts[' + post.name + '][post_date]" type="text" class="widefat" value="' + post.date + '" />';
							html +=		'</td>';
							html +=		'<td class="post_status column-post_status">';
							html +=			statusselect;
							html +=		'</td>';
<?php						break;
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
