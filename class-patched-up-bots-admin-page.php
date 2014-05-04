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

		echo	'<form method="POST" class="' . $active_tab . '">';

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
			form.users tr td { padding: 8px 8px 0px !important; line-height: 24px !important; }

			tr.new td { border-bottom: 1px solid rgb(225, 225, 225); background-color: #ccffcc; }
			form.users tr.new td { padding: 8px 8px 0px !important; }
			.column-delete { width: 10px !important; }
				.column-delete .dashicons-dismiss { padding: 3px 0 0 0; }
				.column-delete .dashicons-dismiss:hover { color: #000; cursor: pointer; }
			.column-avatar { width: 30px !important; }
				.column-avatar img { margin-top: -3px !important; }
			.column-user_login { width: 300px !important; }
			.column-post_content { width: 500px !important; }
			input { margin-bottom: 8px !important; }
			input[name='amount'] { margin: 0px; padding: 4px 8px 3px; width: 40px; text-align: right; box-shadow: inset 0 1px 0 #fff,0 1px 0 rgba(0,0,0,.08); border-width: 1px 0; }
			textarea.widefat { width: 100% !important; min-height: 62px !important; }

			#minus { border-top-right-radius: 0; border-bottom-right-radius: 0; }
			#plus  { border-top-left-radius:  0; border-bottom-left-radius:  0; }
			#minus, #plus { font-size: 18px; font-weight: bold; }
			#minus:focus, #plus:focus { outline: none; }

			#message { display: inline-block; margin-left: 20px; }
				.danger { color: red; }
		</style>
		<script>
			// load all libraries
			var libraries = <?php echo $datajson; ?>;
			var takenData = <?php echo $taken_data; ?>; // At init, taken_data is a list of already used users, posts, pages, etc

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
				var library = get_any_library();

				for( var lib in libraries ) {
					takenData.forEach( function( data ) { if ( jQuery.inArray( data, libraries[lib] ) == -1 ) delete libraries[lib][cpt.plural][data];  } );
					if( Object.keys( libraries[lib][cpt.plural] ).length == 0 ) { 
						delete libraries[lib];
						jQuery( 'option[value="' + lib + '"]' ).remove();
					}
				}

				console.log( libraries );

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
							library = get_any_library(); 
							data = libraries[library][cpt.plural];

							var thing;
							var count = 0;

							do {
								for ( var prop in data ) if ( Math.random() < 1/++count ) thing = prop;
								var isTaken = ( typeof thing !== 'undefined' && jQuery.inArray( thing, takenData ) == -1 ) ? false : true;
							} while ( isTaken );

							data[thing]['name'] = thing; // Save the username key to object
							data[thing]['library'] = library; // Save the library key to object
							selectedData.push( data[thing] )
							takenData.push( thing );

							// Trim list as data is taken
							delete data[thing];

							if( Object.keys( data ).length == 0 ) delete libraries[library];
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
							selectedData.push( data[thing] )
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
							var nicename = user.fname + " " + user.lname;

							roleselect = '<select name="users[' + user.name + '][role]" class="widefat">';
							for ( role in roles ) {
								var selected = '';
								if ( role === user.role ) selected = 'selected';
								roleselect += '<option value="' + role + '" ' + selected + '>' + capitalize( roles[role] ) + '</option>';
							}
							roleselect += '<select>';

							html +=		'<td class="avatar column-avatar">';
							html +=			'<img src="<?php echo plugin_dir_url( __FILE__ ) . 'data/'; ?>' + user.library + '/img/' + user.name + '.jpg" width="32" height="32" />'
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
						case 'posts' : ?>

							var post = selectedData[i];

							var statuses = <?php print_r( json_encode( get_post_statuses() ) ); ?>;
							console.log( statuses );

							statusselect = '<select name="posts[' + post.name + '][post_status]" class="widefat">';
							for ( status in statuses ) {
								var selected = '';
								if ( status === post.status ) selected = 'selected';
								statusselect += '<option value="' + status + '" ' + selected + '>' + capitalize( statuses[status] ) + '</option>';
							}
							statusselect += '<select>';


							html +=		'<td class="post_title column-post_title">';
							html +=			'<input name="posts[' + post.name + '][post_title]" type="text" class="widefat" value="' + post.title + '" />';
							html +=			'<input name="posts[' + post.name + '][post_name]" type="text" class="widefat" value="' + post.name + '" />';
							html +=		'</td>';
							html +=		'<td class="post_author column-post_author">';
							html +=			'<input name="posts[' + post.name + '][post_author]" type="text" class="widefat" value="' + post.author + '" />';
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
